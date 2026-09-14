import { defineStore } from 'pinia'
import { ref } from 'vue'
import { progressApi } from '@/api/progress'

export const useProgressStore = defineStore('progress', () => {
  const CACHED_DASHBOARD_KEY = 'smart_adama_cached_dashboard'
  const CACHED_BADGES_KEY = 'smart_adama_cached_badges'
  const CACHED_STREAK_KEY = 'smart_adama_cached_streak'

  let initialDashboard: App.Dashboard | null = null
  try {
    const raw = localStorage.getItem(CACHED_DASHBOARD_KEY)
    if (raw) initialDashboard = JSON.parse(raw)
  } catch (e) {}

  let initialBadges: App.Badge[] = []
  try {
    const raw = localStorage.getItem(CACHED_BADGES_KEY)
    if (raw) initialBadges = JSON.parse(raw)
  } catch (e) {}

  let initialStreak: App.Streak | null = null
  try {
    const raw = localStorage.getItem(CACHED_STREAK_KEY)
    if (raw) initialStreak = JSON.parse(raw)
  } catch (e) {}

  const dashboard = ref<App.Dashboard | null>(initialDashboard)
  const summary = ref<App.ProgressSummary | null>(null)
  const badges = ref<App.Badge[]>(initialBadges)
  const streak = ref<App.Streak | null>(initialStreak)
  const loading = ref(false)

  // Newly earned badges during this session (for toast/animation trigger)
  const newlyEarnedBadges = ref<App.Badge[]>([])

  async function loadDashboard() {
    loading.value = !dashboard.value
    try {
      const { data } = await progressApi.getDashboard()
      if (data?.dashboard) {
        dashboard.value = data.dashboard
        try {
          localStorage.setItem(CACHED_DASHBOARD_KEY, JSON.stringify(data.dashboard))
        } catch (e) {}
      }
    } catch (err) {
      console.error('Failed to load dashboard progress:', err)
    } finally {
      loading.value = false
    }
  }

  async function loadSummary() {
    try {
      const { data } = await progressApi.getSummary()
      if (data?.progress) {
        summary.value = data.progress
      }
    } catch (err) {
      console.error('Failed to load progress summary:', err)
    }
  }

  async function loadBadges() {
    try {
      const { data } = await progressApi.getBadges()
      const previous = new Set(badges.value.filter((b) => b.earned).map((b) => b.id))
      badges.value = data.badges || []
      try {
        localStorage.setItem(CACHED_BADGES_KEY, JSON.stringify(badges.value))
      } catch (e) {}

      // Detect newly earned since last load (for Req 11.2 celebratory animation)
      newlyEarnedBadges.value = (data.badges || []).filter(
        (b) => b.earned && b.awarded_at && !previous.has(b.id),
      )
    } catch (err) {
      console.error('Failed to load badges:', err)
    }
  }

  async function loadStreak() {
    try {
      const { data } = await progressApi.getStreak()
      if (data?.streak) {
        streak.value = data.streak
        try {
          localStorage.setItem(CACHED_STREAK_KEY, JSON.stringify(data.streak))
        } catch (e) {}
      }
    } catch (err) {
      console.error('Failed to load streak:', err)
    }
  }

  async function loadAll() {
    await Promise.allSettled([loadDashboard(), loadBadges(), loadStreak()])
  }

  function clearNewlyEarned() {
    newlyEarnedBadges.value = []
  }

  function chapterProgress(chapterId: string) {
    if (summary.value?.chapterProgress) {
      return summary.value.chapterProgress.find(p => p.chapter_id === chapterId) || null
    }
    if (dashboard.value?.chapter_progress) {
      return dashboard.value.chapter_progress.find(p => p.chapter_id === chapterId) || null
    }
    return null
  }

  return {
    dashboard,
    summary,
    badges,
    streak,
    loading,
    newlyEarnedBadges,
    loadDashboard,
    loadSummary,
    loadBadges,
    loadStreak,
    loadAll,
    clearNewlyEarned,
    chapterProgress,
  }
})
