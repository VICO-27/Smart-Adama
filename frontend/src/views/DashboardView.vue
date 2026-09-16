<script setup lang="ts">
import AppFooter from '@/components/layout/AppFooter.vue'
import {
  ref,
  computed,
  onMounted,
} from 'vue'

import { RouterLink } from 'vue-router'

import AppShell from '@/components/layout/AppShell.vue'

import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import { useChatStore } from '@/stores/chat'

import { useI18n } from 'vue-i18n'


/* ============================================================
   STORES
============================================================ */

const auth = useAuthStore()
const progress = useProgressStore()
const chatStore = useChatStore()

const { t } = useI18n()


/* ============================================================
   DASHBOARD
============================================================ */

const fallbackDashboard: App.Dashboard = {
  completion_pct: 0,
  total_chapters: 13,
  completed_chapters: 0,
  quizzes_passed: 0,
  average_quiz_score: null,
  current_streak: 0,
  total_chat_sessions: 0,
  earned_badge_count: 0,
  current_chapter: null,
  current_position: null,
  chapter_progress: [],
}

const d = computed(() => progress.dashboard || fallbackDashboard)


const firstName = computed(() => {
  const name = auth.user?.name?.trim()

  return (
    name?.split(/\s+/)[0] ||
    'Learner'
  )
})


/* ============================================================
   GREETING
============================================================ */

const hour = new Date().getHours()


const timeGreeting = computed(() => {
  if (hour < 5) {
    return (
      t('dashboard.gn') ||
      'Good night'
    )
  }

  if (hour < 12) {
    return (
      t('dashboard.gm') ||
      'Good morning'
    )
  }

  if (hour < 17) {
    return (
      t('dashboard.ga') ||
      'Good afternoon'
    )
  }

  if (hour < 21) {
    return (
      t('dashboard.ge') ||
      'Good evening'
    )
  }

  return (
    t('dashboard.gn') ||
    'Good night'
  )
})


/* ============================================================
   PROGRESS
============================================================ */

const totalChapters = computed(() => {
  return d.value?.total_chapters || 13
})


const completedChapters = computed(() => {
  return d.value?.completed_chapters || 0
})


const completionPct = computed(() => {
  return Math.min(
    100,
    Math.max(
      0,
      d.value?.completion_pct || 0,
    ),
  )
})


const hasChapters = computed(() => {
  return totalChapters.value > 0
})


const isBookComplete = computed(() => {
  return (
    hasChapters.value &&
    completedChapters.value >=
      totalChapters.value
  )
})


const continueChapter = computed(() => {
  return d.value?.current_chapter || null
})


const recommendedChapter = computed(() => {
  if (!d.value?.chapter_progress || d.value.chapter_progress.length === 0) return 1

  const firstUnfinishedIndex = d.value.chapter_progress.findIndex(c => c.status !== 'COMPLETED')
  if (firstUnfinishedIndex !== -1) {
    return firstUnfinishedIndex + 1
  }

  return 1
})


const continueTitle = computed(() => {
  if (!hasChapters.value) {
    return t('dashboard.book_ready') || 'Ready to start'
  }

  if (isBookComplete.value) {
    return t('dashboard.book_done') || 'Course complete'
  }

  if (continueChapter.value) {
    return continueChapter.value.title || `Chapter`
  }

  return t('dashboard.start_learning')
})


const continueSubtext = computed(() => {
  if (!hasChapters.value) {
    return t('dashboard.sub_ready') || 'Ready to start.'
  }

  if (isBookComplete.value) {
    return t('dashboard.sub_done') || 'All content completed.'
  }

  const cp = d.value?.chapter_progress?.find(p => p.chapter_id === continueChapter.value?.id)

  if (cp) {
    const readingStatus = cp.reading_progress >= 100 
      ? t('dashboard.reading_complete') 
      : t('dashboard.reading_progress', { pct: cp.reading_progress })
    
    const quizScore = cp.best_quiz_score_pct !== null 
      ? `Quiz: ${cp.best_quiz_score_pct}%` 
      : t('dashboard.no_quiz_attempts')
      
    return `${readingStatus} • ${quizScore}`
  }

  return t('dashboard.resume_reading')
})


/* ============================================================
   METRICS
============================================================ */

const currentStreak = computed(() => {
  return (
    d.value?.current_streak ||
    0
  )
})


const averageQuizScore = computed(() => {
  return (
    d.value?.average_quiz_score ??
    null
  )
})


const quizzesPassed = computed(() => {
  return (
    d.value?.quizzes_passed ||
    0
  )
})


const badgesEarned = computed(() => {
  return (
    d.value?.earned_badge_count ||
    0
  )
})


/* ============================================================
   CHAPTER JOURNEY
============================================================ */

const journey = computed(() => {
  if (!totalChapters.value) {
    return []
  }

  const cpList = d.value?.chapter_progress || []

  return Array.from(
    {
      length: totalChapters.value,
    },
    (_, index) => {
      const chapterNum = index + 1
      const cp = cpList[index]

      let state = 'upcoming'
      if (cp) {
        if (cp.status === 'COMPLETED') state = 'completed'
        else if (cp.status === 'IN_PROGRESS') state = 'in_progress'
      }

      // Also indicate if this is the currently recommended or continued chapter
      const isCurrent = cp && continueChapter.value && cp.chapter_id === continueChapter.value.id

      return {
        chapter: chapterNum,
        state: isCurrent ? 'current' : state,
        actual_state: state
      }
    },
  )
})


/* ============================================================
   RECENT AI CONVERSATIONS
============================================================ */

function formatChatTime(
  value?: string,
): string {
  if (!value) {
    return 'Recently'
  }

  const date = new Date(value)

  const hours = Math.floor(
    (
      Date.now() -
      date.getTime()
    ) /
      (1000 * 60 * 60),
  )

  if (hours < 1) {
    return 'Just now'
  }

  if (hours < 24) {
    return `${hours}h ago`
  }

  if (hours < 48) {
    return 'Yesterday'
  }

  return date.toLocaleDateString(
    'en-US',
    {
      month: 'short',
      day: 'numeric',
    },
  )
}


const recentChats = computed(() => {
  const sessions =
    chatStore.sessions || []

  return sessions
    .slice(0, 3)
    .map((session: any) => ({
      id: session.id,

      title:
        session.title ||
        'Smart Adama AI session',

      time:
        formatChatTime(
          session.updated_at ||
          session.created_at,
        ),
    }))
})


/* ============================================================
   DAILY CHALLENGE
============================================================ */

const dailyChallenge = ref({
  question:
    'Which core pillar focuses on supporting startups and local digital economic growth?',

  options: [
    'e-Governance',
    'Enterprise',
    'Innovation',
  ],

  correctIndex: 1,

  selected:
    null as number | null,

  answered: false,
})


function answerChallenge(
  index: number,
) {
  if (
    dailyChallenge.value.answered
  ) {
    return
  }

  dailyChallenge.value.selected =
    index

  dailyChallenge.value.answered =
    true
}


/* ============================================================
   PLATFORM UPDATES
============================================================ */

const updates = [
  {
    id: 1,
    date: 'Today',
    title:
      'Chapter 4: Food Security is now fully available.',
    new: true,
  },

  {
    id: 2,
    date: 'Aug 1',
    title:
      'Smart Adama Ecosystem Beta officially launched.',
    new: false,
  },

  {
    id: 3,
    date: 'Aug 1',
    title:
      'Multilingual learning support is now available.',
    new: false,
  },
]


/* ============================================================
   BADGES
============================================================ */

const fallbackBadges = [
  {
    id: '1',
    name: 'First Step',
    description:
      'Complete 1 chapter.',
    earned: false,
    progress: {
      current: 0,
      required: 1,
    },
  },

  {
    id: '2',
    name: 'Committed Learner',
    description:
      'Complete 5 chapters.',
    earned: false,
    progress: {
      current: 0,
      required: 5,
    },
  },

  {
    id: '3',
    name: 'Perfectionist',
    description:
      '100% on a quiz.',
    earned: false,
    progress: null,
  },

  {
    id: '4',
    name: 'On a Roll',
    description:
      '3-day streak.',
    earned: false,
    progress: {
      current: 0,
      required: 3,
    },
  },

  {
    id: '5',
    name: 'Week Warrior',
    description:
      '7-day streak.',
    earned: false,
    progress: {
      current: 0,
      required: 7,
    },
  },

  {
    id: '6',
    name: 'Unstoppable',
    description:
      '30-day streak.',
    earned: false,
    progress: {
      current: 0,
      required: 30,
    },
  },

  {
    id: '7',
    name: 'Smart Adama Master',
    description:
      'Complete the book.',
    earned: false,
    progress: {
      current: 0,
      required: 13,
    },
  },

  {
    id: '8',
    name: 'Quiz Ace',
    description:
      'Pass 10 quizzes.',
    earned: false,
    progress: {
      current: 0,
      required: 10,
    },
  },
]


const badges = computed(() => {
  return (
    progress.badges?.length
      ? progress.badges
      : fallbackBadges
  )
})


function badgeType(
  name: string,
) {
  const value = name.toLowerCase()

  if (
    value.includes('step')
  ) {
    return 'book'
  }

  if (
    value.includes('learner')
  ) {
    return 'user'
  }

  if (
    value.includes('perfect')
  ) {
    return 'check'
  }

  if (
    value.includes('roll') ||
    value.includes('week')
  ) {
    return 'flame'
  }

  return 'bolt'
}


/* ============================================================
   LIFECYCLE
============================================================ */

import { useTour } from '@/composables/useTour'
import { homeTour } from '@/composables/tourRegistry'

onMounted(() => {
  progress.loadAll().catch((e) => console.error('Dashboard progress load error:', e))
  chatStore.loadSessions(1).catch((e) => console.error('Dashboard chat sessions load error:', e))
  
  const { registerTour, startTour } = useTour()
  registerTour(homeTour)
  setTimeout(() => startTour('home'), 800) // slight delay for visual stability
})
</script>


<template>

  <AppShell>

    <!-- ========================================================
         DASHBOARD
    ========================================================= -->

    <main class="dashboard-page">

      <!-- DIAGONAL VIDEO BACKGROUND (Loads instantly for ambient effect) -->
      <div class="global-bg-video">
        <video
          autoplay
          loop
          muted
          playsinline
          preload="none"
          poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
          src="/videos/smart-adama-book.mp4"
        ></video>
        <div class="global-bg-overlay-fade"></div>
      </div>

      <div class="dashboard-container">

        <!-- ====================================================
             HEADER (Immediately painted for LCP)
        ===================================================== -->

        <header class="dashboard-header">

          <div>

            <span class="eyebrow">
              {{ timeGreeting }}
            </span>

            <h1>
              {{ $t('dash.welcome') }}
              <span>
                {{ firstName }}
              </span>
            </h1>

            <p>
              {{ $t('dash.journey') }}
            </p>

          </div>

          <div class="status flex items-center bg-[#395886] text-white px-5 py-2.5 rounded-full shadow-lg text-sm font-bold tracking-wide mt-4 md:mt-0">
            <a href="https://ethiocoders.et/" target="_blank" class="hover:text-[#d4af37] transition-colors">{{ $t('dash.coders') }}</a>
            <span class="mx-3 opacity-60">•</span>
            <a href="https://portal.adamacity.gov.et/" target="_blank" class="hover:text-[#d4af37] transition-colors">{{ $t('dash.city') }}</a>
          </div>

        </header>

        <!-- PROGRESSIVE LOADING PROGRESS BAR -->
        <div v-if="progress.loading" class="dashboard-loading-bar" aria-hidden="true"></div>

        <section class="journey-section">

          <div class="journey-header">

            <div>

              <h2>
                {{ continueTitle }}
              </h2>

            </div>


            <div class="journey-completion">

              <strong>
                {{ completionPct }}%
              </strong>

              <span>
                {{ $t('dashboard.complete') }}
              </span>

            </div>

          </div>


          <!-- Chapter timeline -->

          <div class="chapter-timeline">

            <div class="timeline-track">

              <div
                class="timeline-progress"
                :style="{
                  width:
                    `${completionPct}%`,
                }"
              ></div>

            </div>


            <div class="chapter-nodes">

              <div
                v-for="
                  item in journey
                "
                :key="item.chapter"
                class="chapter-node"
                :class="item.state"
              >

                <span class="chapter-circle">

                  <svg
                    v-if="
                      item.state ===
                      'completed'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                  <span
                    v-else-if="
                      item.state === 'current' || item.actual_state === 'in_progress'
                    "
                    class="current-dot"
                  ></span>

                </span>


                <span class="chapter-number">
                  {{
                    String(
                      item.chapter,
                    ).padStart(2, '0')
                  }}
                </span>

              </div>

            </div>

          </div>


          <!-- Journey footer -->

          <div class="journey-footer">




            <div class="journey-progress">

              <div
                class="journey-progress-track"
              >

                <span
                  :style="{
                    width:
                      `${completionPct}%`,
                  }"
                ></span>

              </div>

              <div class="journey-progress-meta">

                <span>
                  {{ completionPct }}%
                  {{ $t('dashboard.overall_progress') }}
                </span>

                <span
                  v-if="!isBookComplete"
                >
                  {{ $t('dashboard.next_chapter') }}
                  {{ recommendedChapter }}
                </span>

                <span
                  v-else
                >
                  {{ $t('dash.course_complete') }}
                </span>

              </div>

            </div>


            <RouterLink
              to="/study"
              class="journey-button"
            >

              <span>
                {{
                  isBookComplete
                    ? $t('dashboard.review_learning')
                    : $t('dashboard.continue_reading')
                }}
              </span>

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  d="M5 12h14"
                  stroke-linecap="round"
                />

                <path
                  d="m13 6 6 6-6 6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>

            </RouterLink>

          </div>

        </section>


        <!-- ====================================================
             MOMENTUM
        ===================================================== -->

        <section class="momentum-section" data-tour="momentum">

          <div class="section-heading">

            <h2>
              {{ $t('dash.stats') }}
            </h2>

          </div>


          <div class="momentum-strip">


            <!-- Streak -->

            <div class="momentum-item">

              <div class="momentum-icon">

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M13.5 2.5c.4 3.5-1.4 5.5-3.3 7.1C8.7 10.9 8 12.2 8 14a4 4 0 0 0 4 4c1.6 0 2.9-.9 3.6-2.2.4 1.2.2 3-1.4 4.7 3.5-.9 5.8-3.6 5.8-7.2 0-4.2-2.7-8-6.5-10.8Z"
                  />
                </svg>

              </div>

              <div>

                <strong>
                  {{ currentStreak }}
                </strong>

                <span>
                  {{ $t('dash.day_streak') }}
                </span>

              </div>

            </div>


            <div class="momentum-divider"></div>


            <!-- Score -->

            <div class="momentum-item">

              <div class="momentum-icon">

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M7 4h10a2 2 0 0 1 2 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 0 1 2-2Z"
                  />

                  <path
                    d="M9 8h6M9 12h4"
                  />
                </svg>

              </div>

              <div>

                <strong>
                  {{
                    averageQuizScore !==
                    null
                      ? `${averageQuizScore}%`
                      : '—'
                  }}
                </strong>

                <span>
                  {{ $t('dash.avg_quiz') }}
                </span>

              </div>

            </div>


            <div class="momentum-divider"></div>


            <!-- Quizzes -->

            <div class="momentum-item">

              <div class="momentum-icon">

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M5 4h14v16H5V4Z"
                  />

                  <path
                    d="M9 8h6M9 12h6M9 16h4"
                  />
                </svg>

              </div>

              <div>

                <strong>
                  {{ quizzesPassed }}
                </strong>

                <span>
                  {{ $t('dash.quizzes_passed') }}
                </span>

              </div>

            </div>


            <div class="momentum-divider"></div>


            <!-- Badges -->

            <div class="momentum-item">

              <div class="momentum-icon">

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <circle
                    cx="12"
                    cy="8"
                    r="4"
                  />

                  <path
                    d="m9 12-2 8 5-3 5 3-2-8"
                  />
                </svg>

              </div>

              <div>

                <strong>
                  {{ badgesEarned }}
                </strong>

                <span>
                  {{ $t('dash.badges') }}
                </span>

              </div>

            </div>

          </div>

        </section>


        <!-- ====================================================
             WORKSPACE
        ===================================================== -->

        <section class="workspace">


          <!-- Recent conversations -->

          <article
            class="workspace-panel"
          >

            <div class="panel-header">

              <div>

                <h2>
                  {{ $t('dash.recent_chats') }}
                </h2>

              </div>


              <RouterLink
                to="/study"
                class="quiet-link"
              >

                {{ $t('dash.view_all') }}

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path
                    d="M5 12h14"
                    stroke-linecap="round"
                  />

                  <path
                    d="m13 6 6 6-6 6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>

              </RouterLink>

            </div>


            <div
              v-if="
                recentChats.length
              "
              class="activity-list"
            >

              <RouterLink
                v-for="
                  chat in recentChats
                "
                :key="chat.id"
                to="/study"
                class="activity-row"
              >

                <div class="activity-icon">

                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <path
                      d="M5 5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-6l-4 4v-4H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"
                    />

                    <path
                      d="M8 9h8M8 12h5"
                    />
                  </svg>

                </div>


                <div class="activity-copy">

                  <strong>
                    {{ chat.title }}
                  </strong>

                  <span>
                    {{ chat.time }}
                  </span>

                </div>


                <svg
                  class="activity-arrow"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M5 12h14"
                    stroke-linecap="round"
                  />

                  <path
                    d="m13 6 6 6-6 6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>

              </RouterLink>

            </div>


            <div
              v-else
              class="empty-activity"
            >

              <div class="empty-icon">

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M5 5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-6l-4 4v-4H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"
                  />
                </svg>

              </div>

              <strong>
                {{ $t('dash.no_convos') }}
              </strong>

              <span>
                Ask something inside Study Mode

              </span>

            </div>

          </article>


          <!-- AI -->

          <article class="ai-panel">

            <div class="ai-background"></div>

            <div class="ai-content">

              <h2>
                {{ $t('dash.ai_tutor') }}
              </h2>

              <RouterLink
                to="/study"
                class="ai-button"
              >

                {{ $t('dash.open_tutor') }}

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    d="M5 12h14"
                    stroke-linecap="round"
                  />

                  <path
                    d="m13 6 6 6-6 6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>

              </RouterLink>

            </div>


            <div
              class="ai-visual"
              aria-hidden="true"
            >

              <span
                class="ai-orbit large"
              ></span>

              <span
                class="ai-orbit small"
              ></span>

              <span class="ai-core">

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="3"
                  />

                  <circle
                    cx="5.5"
                    cy="7.5"
                    r="1.1"
                  />

                  <circle
                    cx="18.5"
                    cy="7.5"
                    r="1.1"
                  />

                  <circle
                    cx="5.5"
                    cy="16.5"
                    r="1.1"
                  />

                  <circle
                    cx="18.5"
                    cy="16.5"
                    r="1.1"
                  />

                  <path
                    d="m9.5 10-3-1.6M14.5 10l3-1.6M9.5 14l-3 1.6M14.5 14l3 1.6"
                  />
                </svg>

              </span>

            </div>

          </article>

        </section>


        <!-- ====================================================
             MILESTONES
        ===================================================== -->

        <section
          class="milestones-section"
        >

          <div class="section-heading">

            <h2>
              {{ $t('dash.achievements') }}
            </h2>

          </div>


          <div class="milestone-grid">

            <article
              v-for="
                badge in badges
              "
              :key="badge.id"
              class="milestone"
              :class="{
                earned:
                  badge.earned,
              }"
            >

              <div class="milestone-top">

                <div class="milestone-icon">

                  <!-- Book -->

                  <svg
                    v-if="
                      badgeType(
                        badge.name,
                      ) === 'book'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <path
                      d="M5 4h14v16H5V4Z"
                    />

                    <path
                      d="M9 8h6M9 12h6M9 16h4"
                    />
                  </svg>


                  <!-- User -->

                  <svg
                    v-else-if="
                      badgeType(
                        badge.name,
                      ) === 'user'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <circle
                      cx="12"
                      cy="8"
                      r="3.5"
                    />

                    <path
                      d="M5 20a7 7 0 0 1 14 0"
                    />
                  </svg>


                  <!-- Check -->

                  <svg
                    v-else-if="
                      badgeType(
                        badge.name,
                      ) === 'check'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <circle
                      cx="12"
                      cy="12"
                      r="8.5"
                    />

                    <path
                      d="m8 12 2.5 2.5L16 9"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>


                  <!-- Flame -->

                  <svg
                    v-else-if="
                      badgeType(
                        badge.name,
                      ) === 'flame'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <path
                      d="M13.5 2.5c.4 3.5-1.4 5.5-3.3 7.1C8.7 10.9 8 12.2 8 14a4 4 0 0 0 4 4c1.6 0 2.9-.9 3.6-2.2.4 1.2.2 3-1.4 4.7 3.5-.9 5.8-3.6 5.8-7.2 0-4.2-2.7-8-6.5-10.8Z"
                    />
                  </svg>


                  <!-- Bolt -->

                  <svg
                    v-else
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <path
                      d="M13 3 4 14h7l-1 7 9-11h-7l1-7Z"
                      stroke-linejoin="round"
                    />
                  </svg>

                </div>


                <span
                  class="milestone-state"
                  :class="{
                    earned:
                      badge.earned,
                  }"
                >

                  <svg
                    v-if="
                      badge.earned
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                  <svg
                    v-else
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <rect
                      x="5"
                      y="10"
                      width="14"
                      height="10"
                      rx="2"
                    />

                    <path
                      d="M8 10V7a4 4 0 0 1 8 0v3"
                    />
                  </svg>

                </span>

              </div>


              <h3>
                {{ $te(`badges.${badge.code}.name`) ? $t(`badges.${badge.code}.name`) : badge.name }}
              </h3>

              <p>
                {{ $te(`badges.${badge.code}.desc`) ? $t(`badges.${badge.code}.desc`) : badge.description }}
              </p>


              <div
                v-if="
                  !badge.earned &&
                  badge.progress
                "
                class="milestone-progress"
              >

                <div
                  class="milestone-progress-meta"
                >

                  <span>
                    {{ $t('dash.progress') }}
                  </span>

                  <strong>
                    {{
                      badge.progress.current
                    }}
                    /
                    {{
                      badge.progress.required
                    }}
                  </strong>

                </div>


                <div
                  class="milestone-progress-track"
                >

                  <span
                    :style="{
                      width:
                        `${Math.min(
                          100,
                          (
                            badge.progress.current /
                            badge.progress.required
                          ) *
                            100,
                        )}%`,
                    }"
                  ></span>

                </div>

              </div>


              <div
                v-else-if="
                  badge.earned
                "
                class="milestone-complete"
              >
                {{ $t('dash.unlocked') }}
              </div>


              <div
                v-else
                class="milestone-locked"
              >
                {{ $t('dash.keep_learning') }}
              </div>

            </article>

          </div>

        </section>


        <!-- ====================================================
             PRACTICE + UPDATES
        ===================================================== -->

        <section class="lower-grid">


          <!-- Daily challenge -->

          <article class="lower-panel">

            <div class="panel-header">

              <div>

                <span class="section-label">
                  {{ $t('dash.practice') }}
                </span>

                <h2>
                  {{ $t('dash.daily') }}
                </h2>

              </div>

              <span class="xp-label">
                {{ $t('dash.xp') }}
              </span>

            </div>


            <p class="challenge-question">
              {{ dailyChallenge.question }}
            </p>


            <div class="challenge-options">

              <button
                v-for="
                  (
                    option,
                    index
                  ) in
                    dailyChallenge.options
                "
                :key="index"
                type="button"
                class="challenge-option"
                :disabled="
                  dailyChallenge.answered
                "
                :class="{
                  correct:
                    dailyChallenge.answered &&
                    index ===
                      dailyChallenge.correctIndex,

                  incorrect:
                    dailyChallenge.answered &&
                    index ===
                      dailyChallenge.selected &&
                    index !==
                      dailyChallenge.correctIndex,

                  muted:
                    dailyChallenge.answered &&
                    index !==
                      dailyChallenge.selected &&
                    index !==
                      dailyChallenge.correctIndex,
                }"
                @click="
                  answerChallenge(index)
                "
              >

                <span class="option-letter">
                  {{
                    String.fromCharCode(
                      65 + index,
                    )
                  }}
                </span>

                <span>
                  {{ option }}
                </span>


                <svg
                  v-if="
                    dailyChallenge.answered &&
                    index ===
                      dailyChallenge.correctIndex
                  "
                  class="option-result"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    d="m5 12 4 4L19 6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>


                <svg
                  v-else-if="
                    dailyChallenge.answered &&
                    index ===
                      dailyChallenge.selected
                  "
                  class="option-result"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    d="M6 6l12 12M18 6 6 18"
                    stroke-linecap="round"
                  />
                </svg>

              </button>

            </div>


            <div
              v-if="
                dailyChallenge.answered
              "
              class="challenge-feedback"
              :class="{
                success:
                  dailyChallenge.selected ===
                  dailyChallenge.correctIndex,

                error:
                  dailyChallenge.selected !==
                  dailyChallenge.correctIndex,
              }"
            >
              {{
                dailyChallenge.selected ===
                dailyChallenge.correctIndex
                  ? 'Correct. Nice work.'
                  : 'Not quite. Keep learning and come back tomorrow.'
              }}
            </div>

          </article>


          <!-- Updates -->

          <article class="lower-panel">

            <div class="panel-header">

              <div>

                <span class="section-label">
                  {{ $t('dash.platform') }}
                </span>

                <h2>
                  {{ $t('dash.updates') }}
                </h2>

              </div>

              <span class="latest">
                {{ $t('dash.latest') }}
              </span>

            </div>


            <div class="updates">

              <div
                v-for="
                  update in updates
                "
                :key="update.id"
                class="update"
              >

                <span
                  class="update-index"
                  :class="{
                    active:
                      update.new,
                  }"
                >
                  {{ update.id }}
                </span>


                <div>

                  <div class="update-meta">

                    <span>
                      {{ update.date }}
                    </span>

                    <span
                      v-if="
                        update.new
                      "
                      class="new"
                    >
                      {{ $t('dash.new') }}
                    </span>

                  </div>

                  <p>
                    {{ update.title }}
                  </p>

                </div>

              </div>

            </div>

          </article>

        </section>

      </div>

      <!-- ====================================================
           GOVERNMENT-STYLE FOOTER
      ===================================================== -->
      <AppFooter />

    </main>

  </AppShell>
</template>


<style scoped>

/* ============================================================
   PAGE
============================================================ */

.dashboard-page {
  min-height: 100dvh;

  background:
    var(--sa-page-bg);

  color:
    var(--sa-text);

  transition:
    background-color 0.3s ease,
    color 0.3s ease;
}


.dashboard-container {
  width: 100%;
  max-width: 1800px;
  margin: 0 auto;
  padding: 5rem 3rem 5rem;
  position: relative;
  z-index: 10;
}


/* ============================================================
   LOADING
============================================================ */

.loading-title,
.loading-journey,
.loading-line,
.loading-block {
  background:
    var(--sa-surface-soft);

  border:
    1px solid
    var(--sa-border);

  animation:
    loadingPulse 1.5s ease-in-out infinite;
}


.loading-title {
  width:
    320px;

  height:
    48px;

  border-radius:
    10px;
}


.loading-journey {
  height:
    280px;

  margin-top:
    24px;

  border-radius:
    22px;
}


.loading-line {
  height:
    80px;

  margin-top:
    18px;

  border-radius:
    14px;
}


.loading-grid {
  display:
    grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap:
    12px;

  margin-top:
    18px;
}


.loading-block {
  height:
    120px;

  border-radius:
    16px;
}


@keyframes loadingPulse {
  0%,
  100% {
    opacity:
      0.55;
  }

  50% {
    opacity:
      0.9;
  }
}


/* ============================================================
   HEADER
============================================================ */

.dashboard-header {
  position: relative;
  z-index: 1;
  display:
    flex;

  align-items:
    flex-end;

  justify-content:
    space-between;

  gap:
    24px;

  margin-bottom:
    20px;
}


.eyebrow,
.section-label {
  display: inline-block;
  color: var(--sa-text-secondary);
  background: var(--sa-surface-muted);
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 6px 14px;
  border-radius: 9999px;
  border: 1px solid var(--sa-border);
  margin-bottom: 8px;
}


.dashboard-header h1 {
  margin-top:
    5px;

  color:
    var(--sa-text);

  font-size:
    clamp(
      1.9rem,
      4vw,
      2.7rem
    );

  line-height:
    1.05;

  font-weight:
    800;

  letter-spacing:
    -0.045em;
}


.dashboard-header h1 span {
  color:
    #395886;
}


html.dark
.dashboard-header h1 span {
  color:
    #8AAEE0;
}


.dashboard-header p {
  max-width:
    640px;

  margin-top:
    7px;

  color:
    var(--sa-text-muted);

  font-size: 1.09rem;

  line-height:
    1.6;
}


.status {
  display:
    inline-flex;

  align-items:
    center;

  flex-shrink:
    0;

  padding:
    8px 11px;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    999px;

  background:
    var(--sa-surface);

  color:
    var(--sa-text-muted);

  font-size: 0.77rem;

  font-weight:
    700;
}


/* ============================================================
   JOURNEY
============================================================ */

.journey-section {
  margin-top:
    40px;

  padding:
    22px;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    20px;

  background:
    #ffffff;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);

  box-shadow:
    var(--sa-card-shadow);
}


.journey-header {
  display:
    flex;

  justify-content:
    space-between;

  gap:
    24px;
}


.journey-header h2 {
  margin-top:
    4px;

  color:
    var(--sa-text);

  font-size:
    clamp(
      1.35rem,
      3vw,
      1.95rem
    );

  line-height:
    1.1;

  font-weight:
    800;

  letter-spacing:
    -0.04em;
}


.journey-header p {
  max-width:
    650px;

  margin-top:
    7px;

  color:
    var(--sa-text-muted);

  font-size: 0.98rem;

  line-height:
    1.55;
}


.journey-completion {
  flex-shrink:
    0;

  text-align:
    right;
}


.journey-completion strong {
  display:
    block;

  color:
    #395886;

  font-size:
    2.2rem;

  line-height:
    0.9;

  font-weight:
    800;

  letter-spacing:
    -0.06em;
}


html.dark
.journey-completion strong {
  color:
    #8AAEE0;
}


.journey-completion span {
  display:
    block;

  margin-top:
    7px;

  color:
    var(--sa-text-muted);

  font-size: 0.72rem;

  font-weight:
    700;

  letter-spacing:
    0.09em;

  text-transform:
    uppercase;
}


/* ============================================================
   CHAPTER TIMELINE
============================================================ */

.chapter-timeline {
  position:
    relative;

  margin-top:
    31px;

  overflow-x:
    auto;

  scrollbar-width:
    none;
}


.chapter-timeline::-webkit-scrollbar {
  display:
    none;
}


.timeline-track {
  position:
    absolute;

  left:
    11px;

  right:
    11px;

  top:
    10px;

  height:
    2px;

  background:
    var(--sa-track);
}


.timeline-progress {
  height:
    100%;

  background:
    linear-gradient(
      90deg,
      #395886,
      #638ECB
    );

  transition:
    width
    0.8s
    var(--ease-out);
}


.chapter-nodes {
  position:
    relative;

  display:
    flex;

  justify-content:
    space-between;

  gap:
    14px;

  min-width:
    420px;
}


.chapter-node {
  display:
    flex;

  flex-direction:
    column;

  align-items:
    center;

  min-width:
    23px;
}


.chapter-circle {
  position:
    relative;

  z-index:
    2;

  width:
    22px;

  height:
    22px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border:
    2px solid
    var(--sa-track);

  border-radius:
    50%;

  background:
    var(--sa-surface);

  color:
    white;
}


.chapter-node.completed
.chapter-circle {
  background:
    #638ECB;

  border-color:
    #638ECB;
}


.chapter-node.current
.chapter-circle {
  border-color:
    #395886;

  box-shadow:
    0 0 0 4px
    rgba(
      99,
      142,
      203,
      0.12
    );
}


.chapter-node.current
.current-dot {
  width:
    7px;

  height:
    7px;

  border-radius:
    50%;

  background:
    #395886;
}


.chapter-node.in_progress
.chapter-circle {
  border-color:
    #638ECB;
  background:
    rgba(99, 142, 203, 0.2);
}

.chapter-node.in_progress
.current-dot {
  width:
    7px;

  height:
    7px;

  border-radius:
    50%;

  background:
    #638ECB;
}


html.dark
.chapter-node.current
.current-dot {
  background:
    #B1C9EF;
}


.chapter-circle svg {
  width:
    11px;

  height:
    11px;
}


.chapter-number {
  margin-top:
    7px;

  color:
    var(--sa-text-faint);

  font-size: 0.67rem;

  font-weight:
    800;
}


.chapter-node.current
.chapter-number {
  color:
    var(--sa-text);
}


/* ============================================================
   JOURNEY FOOTER
============================================================ */

.journey-footer {
  display:
    grid;

  grid-template-columns:
    1fr
    auto;

  align-items:
    end;

  gap:
    20px;

  margin-top:
    23px;

  padding-top:
    17px;

  border-top:
    1px solid
    var(--sa-border);
}


.journey-stat {
  display:
    flex;

  align-items:
    baseline;

  gap:
    5px;

  white-space:
    nowrap;
}


.journey-stat strong {
  color:
    var(--sa-text);

  font-size:
    1.2rem;

  font-weight:
    800;
}


.journey-stat span {
  color:
    var(--sa-text-muted);

  font-size: 0.77rem;
}


.journey-progress {
  min-width:
    0;
}


.journey-progress-track {
  height:
    5px;

  overflow:
    hidden;

  border-radius:
    999px;

  background:
    var(--sa-track);
}


.journey-progress-track span {
  display:
    block;

  height:
    100%;

  border-radius:
    inherit;

  background:
    linear-gradient(
      90deg,
      #395886,
      #638ECB
    );
}


.journey-progress-meta {
  display:
    flex;

  justify-content:
    space-between;

  gap:
    12px;

  margin-top:
    5px;

  color:
    var(--sa-text-faint);

  font-size: 0.69rem;
}


.journey-button {
  display:
    inline-flex;

  align-items:
    center;

  gap:
    6px;

  min-height:
    36px;

  padding:
    8px 12px;

  border:
    0;

  border-radius:
    9px;

  background:
    #10B981;

  color:
    white;

  font-size: 0.90rem;

  font-weight:
    800;

  text-decoration:
    none;

  white-space:
    nowrap;

  transition:
    background
    0.18s ease,
    transform
    0.18s ease;
}


.journey-button:hover {
  background:
    #059669;

  transform:
    translateY(-1px);
}


.journey-button svg {
  width:
    12px;

  height:
    12px;
}


/* ============================================================
   MOMENTUM
============================================================ */

.momentum-section {
  margin-top:
    30px;
}


.section-heading {
  margin-bottom:
    10px;
}


.section-heading h2 {
  margin-top:
    4px;

  color:
    var(--sa-text);

  font-size:
    1.08rem;

  font-weight:
    800;

  letter-spacing:
    -0.025em;
}


.section-heading p {
  margin-top:
    5px;

  color:
    var(--sa-text-muted);

  font-size: 0.91rem;
}


.momentum-strip {
  display:
    grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap:
    20px;

  margin-top:
    15px;
}


.momentum-item {
  display:
    flex;

  flex-direction:
    column;

  align-items:
    flex-start;

  gap:
    10px;

  min-width:
    0;

  padding:
    20px;

  background:
    var(--sa-surface);

  border:
    1px solid var(--sa-border);

  border-radius:
    16px;

  box-shadow:
    var(--sa-card-shadow);
}


.momentum-icon {
  width:
    31px;

  height:
    31px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  flex-shrink:
    0;

  border-radius:
    8px;

  color:
    #638ECB;

  background:
    var(--sa-bg);
}


.momentum-icon svg {
  width:
    15px;

  height:
    15px;
}


.momentum-item strong {
  display:
    block;

  color:
    var(--sa-text);

  font-size:
    1.15rem;

  line-height:
    1;

  font-weight:
    800;

  letter-spacing:
    -0.035em;
}


.momentum-item span {
  display:
    block;

  margin-top:
    4px;

  color:
    var(--sa-text-muted);

  font-size: 0.70rem;

  white-space:
    nowrap;
}


.momentum-divider {
  display:
    none;
}


/* ============================================================
   WORKSPACE
============================================================ */

.workspace {
  display:
    grid;

  grid-template-columns:
    1fr
    1fr;

  gap:
    24px;

  margin-top:
    50px;
}


.workspace-panel,
.ai-panel {
  min-height:
    280px;

  border-radius:
    18px;

  overflow:
    hidden;
}


.workspace-panel {
  padding:
    18px;

  border:
    1px solid
    var(--sa-border);

  background:
    var(--sa-surface);

  box-shadow:
    var(--sa-card-shadow);
}


.panel-header {
  display:
    flex;

  justify-content:
    space-between;

  gap:
    14px;

  margin-bottom:
    12px;
}


.panel-header h2 {
  margin-top:
    4px;

  color:
    var(--sa-text);

  font-size: 1.52rem;

  font-weight:
    800;

  letter-spacing:
    -0.02em;
}


.quiet-link {
  display:
    inline-flex;

  align-items:
    center;

  gap:
    4px;

  color:
    #638ECB;

  font-size: 0.77rem;

  font-weight:
    800;

  text-decoration:
    none;
}


.quiet-link svg {
  width:
    10px;

  height:
    10px;
}


.activity-list {
  display:
    flex;

  flex-direction:
    column;

  gap:
    3px;
}


.activity-row {
  display:
    flex;

  align-items:
    center;

  gap:
    10px;

  padding:
    9px;

  border-radius:
    8px;

  text-decoration:
    none;

  transition:
    background
    0.18s ease;
}


.activity-row:hover {
  background:
    var(--sa-surface-soft);
}


.activity-icon {
  width:
    31px;

  height:
    31px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  flex-shrink:
    0;

  border-radius:
    8px;

  color:
    #638ECB;

  background:
    var(--sa-bg);
}


.activity-icon svg {
  width:
    14px;

  height:
    14px;
}


.activity-copy {
  min-width:
    0;

  flex:
    1;
}


.activity-copy strong {
  display:
    block;

  overflow:
    hidden;

  color:
    var(--sa-text);

  font-size: 0.91rem;

  font-weight:
    700;

  text-overflow:
    ellipsis;

  white-space:
    nowrap;
}


.activity-copy span {
  display:
    block;

  margin-top:
    3px;

  color:
    var(--sa-text-faint);

  font-size: 0.70rem;
}


.activity-arrow {
  width:
    11px;

  height:
    11px;

  color:
    var(--sa-text-faint);
}


.empty-activity {
  min-height:
    185px;

  display:
    flex;

  flex-direction:
    column;

  align-items:
    center;

  justify-content:
    center;

  text-align:
    center;
}


.empty-icon {
  width:
    40px;

  height:
    40px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border-radius:
    10px;

  color:
    #638ECB;

  background:
    var(--sa-bg);
}


.empty-icon svg {
  width:
    17px;

  height:
    17px;
}


.empty-activity strong {
  margin-top:
    9px;

  color:
    var(--sa-text);

  font-size: 0.96rem;

  font-weight:
    800;
}


.empty-activity span {
  max-width:
    200px;

  margin-top:
    4px;

  color:
    var(--sa-text-muted);

  font-size: 0.77rem;

  line-height:
    1.5;
}


/* ============================================================
   AI
============================================================ */

.ai-panel {
  position:
    relative;

  overflow:
    hidden;

  display:
    flex;

  align-items:
    center;

  padding:
    23px;

  background:
    #395886;

  color:
    white;
}


.ai-background {
  position:
    absolute;

  inset:
    0;

  pointer-events:
    none;

  background:
    linear-gradient(
      115deg,
      transparent 0%,
      rgba(255,255,255,0.04) 38%,
      transparent 72%
    );
}


.ai-content {
  position:
    relative;

  z-index:
    3;

  max-width:
    500px;
}


.ai-label {
  display:
    inline-block;

  color:
    #B1C9EF;

  font-size: 0.80rem;

  font-weight:
    800;

  letter-spacing:
    0.14em;

  text-transform:
    uppercase;
}


.ai-content h2 {
  margin-top:
    7px;

  color:
    white;

  font-size:
    clamp(
      1.45rem,
      3vw,
      1.95rem
    );

  line-height:
    1.05;

  font-weight:
    800;

  letter-spacing:
    -0.04em;
}


.ai-content p {
  max-width:
    470px;

  margin-top:
    8px;

  color:
    #D5DEEF;

  font-size: 0.93rem;

  line-height:
    1.65;
}


.ai-button {
  display:
    inline-flex;

  align-items:
    center;

  gap:
    6px;

  margin-top:
    14px;

  padding:
    9px 12px;

  border-radius:
    8px;

  background:
    white;

  color:
    #395886;

  font-size: 0.90rem;

  font-weight:
    800;

  text-decoration:
    none;

  transition:
    transform
    0.18s ease;
}


.ai-button:hover {
  transform:
    translateY(-1px);
}


.ai-button svg {
  width:
    12px;

  height:
    12px;
}


/* AI visual */

.ai-visual {
  position:
    absolute;

  right:
    26px;

  top:
    50%;

  width:
    145px;

  height:
    145px;

  transform:
    translateY(-50%);
}


.ai-orbit {
  position:
    absolute;

  left:
    50%;

  top:
    50%;

  border:
    1px solid
    rgba(
      213,
      222,
      239,
      0.24
    );

  border-radius:
    50%;

  transform:
    translate(
      -50%,
      -50%
    );
}


.ai-orbit.large {
  width:
    130px;

  height:
    130px;
}


.ai-orbit.small {
  width:
    88px;

  height:
    88px;

  border-color:
    rgba(
      177,
      201,
      239,
      0.3
    );
}


.ai-core {
  position:
    absolute;

  left:
    50%;

  top:
    50%;

  width:
    54px;

  height:
    54px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.18
    );

  border-radius:
    14px;

  background:
    rgba(
      255,
      255,
      255,
      0.08
    );

  color:
    #D5DEEF;

  transform:
    translate(
      -50%,
      -50%
    );
}


.ai-core svg {
  width:
    23px;

  height:
    23px;
}


/* ============================================================
   MILESTONES
============================================================ */

.milestones-section {
  margin-top:
    50px;
}


.milestone-grid {
  display:
    flex;

  flex-wrap:
    nowrap;

  overflow-x:
    auto;

  gap:
    20px;

  padding-bottom:
    20px;
}


.milestone {
  flex:
    0 0 auto;

  position:
    relative;

  width:
    260px;

  min-height:
    160px;

  display:
    flex;

  flex-direction:
    column;

  padding:
    13px;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    14px;

  background:
    var(--sa-surface);

  box-shadow:
    var(--sa-card-shadow);

  opacity:
    0.72;

  cursor:
    default;

  transition:
    transform
    0.22s ease,
    opacity
    0.22s ease,
    border-color
    0.22s ease,
    box-shadow
    0.22s ease;
}


.milestone:hover {
  opacity:
    1;

  transform:
    translateY(-4px);

  border-color:
    #8AAEE0;

  box-shadow:
    var(--sa-card-shadow-hover);
}


.milestone.earned {
  opacity:
    1;

  border-color:
    rgba(
      99,
      142,
      203,
      0.40
    );

  background:
    linear-gradient(
      180deg,
      var(--sa-surface),
      rgba(
        99,
        142,
        203,
        0.035
      )
    );
}


.milestone-top {
  display:
    flex;

  align-items:
    flex-start;

  justify-content:
    space-between;

  gap:
    8px;
}


.milestone-icon {
  width:
    36px;

  height:
    36px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    9px;

  color:
    #8AAEE0;

  background:
    var(--sa-bg);

  transition:
    transform
    0.22s ease,
    background
    0.22s ease,
    color
    0.22s ease;
}


.milestone:hover
.milestone-icon {
  transform:
    translateY(-1px)
    rotate(-3deg);
}


.milestone.earned
.milestone-icon {
  color:
    #395886;

  background:
    rgba(
      99,
      142,
      203,
      0.09
    );

  border-color:
    rgba(
      99,
      142,
      203,
      0.22
    );
}


html.dark
.milestone.earned
.milestone-icon {
  color:
    #B1C9EF;
}


.milestone-icon svg {
  width:
    17px;

  height:
    17px;
}


.milestone-state {
  color:
    var(--sa-text-faint);
}


.milestone-state.earned {
  color:
    #638ECB;
}


.milestone-state svg {
  width:
    11px;

  height:
    11px;
}


.milestone h3 {
  margin-top:
    9px;

  color:
    var(--sa-text);

  font-size: 1.06rem;

  line-height:
    1.2;

  font-weight:
    800;
}


.milestone p {
  margin-top:
    4px;

  min-height:
    36px;

  color:
    var(--sa-text-muted);

  font-size: 0.72rem;

  line-height:
    1.45;
}


.milestone-progress {
  margin-top:
    auto;
}


.milestone-progress-meta {
  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  color:
    var(--sa-text-faint);

  font-size: 0.64rem;
}


.milestone-progress-meta strong {
  color:
    var(--sa-text);
}


.milestone-progress-track {
  height:
    4px;

  margin-top:
    5px;

  overflow:
    hidden;

  border-radius:
    999px;

  background:
    var(--sa-track);
}


.milestone-progress-track span {
  display:
    block;

  height:
    100%;

  border-radius:
    inherit;

  background:
    linear-gradient(
      90deg,
      #8AAEE0,
      #638ECB
    );

  transition:
    width
    0.5s ease;
}


.milestone-complete,
.milestone-locked {
  margin-top:
    auto;

  padding-top:
    8px;

  font-size: 0.64rem;

  font-weight:
    800;

  letter-spacing:
    0.07em;

  text-transform:
    uppercase;
}


.milestone-complete {
  color:
    #638ECB;
}


.milestone-locked {
  color:
    var(--sa-text-faint);
}


/* ============================================================
   LOWER GRID
============================================================ */

.lower-grid {
  display:
    grid;

  grid-template-columns:
    1fr
    1fr;

  gap:
    24px;

  margin-top:
    50px;
}


.lower-panel {
  min-height:
    270px;

  padding:
    18px;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    18px;

  background:
    var(--sa-surface);

  box-shadow:
    var(--sa-card-shadow);
}


.xp-label {
  display:
    inline-flex;

  align-items:
    center;

  padding:
    5px 8px;

  border:
    1px solid
    rgba(
      99,
      142,
      203,
      0.18
    );

  border-radius:
    999px;

  background:
    rgba(
      99,
      142,
      203,
      0.06
    );

  color:
    #638ECB;

  font-size: 0.70rem;

  font-weight:
    800;
}


.challenge-question {
  margin:
    6px 0 12px;

  color:
    var(--sa-text);

  font-size: 1.12rem;

  line-height:
    1.6;

  font-weight:
    700;
}


.challenge-options {
  display:
    flex;

  flex-direction:
    column;

  gap:
    5px;
}


.challenge-option {
  display:
    flex;

  align-items:
    center;

  gap:
    8px;

  width:
    100%;

  min-height:
    38px;

  padding:
    7px 8px;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    9px;

  background:
    var(--sa-surface-muted);

  color:
    var(--sa-text-secondary);

  font-size: 0.88rem;

  font-weight:
    700;

  text-align:
    left;

  cursor:
    pointer;

  transition:
    background
    0.18s ease,
    border-color
    0.18s ease,
    transform
    0.18s ease;
}


.challenge-option:not(:disabled):hover {
  background:
    var(--sa-surface-soft);

  border-color:
    #8AAEE0;

  transform:
    translateX(2px);
}


.challenge-option.correct {
  background:
    rgba(
      16,
      185,
      129,
      0.07
    );

  border-color:
    rgba(
      16,
      185,
      129,
      0.28
    );

  color:
    #059669;
}


.challenge-option.incorrect {
  background:
    rgba(
      239,
      68,
      68,
      0.06
    );

  border-color:
    rgba(
      239,
      68,
      68,
      0.24
    );

  color:
    #DC2626;
}


.challenge-option.muted {
  opacity:
    0.42;
}


.option-letter {
  width:
    23px;

  height:
    23px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  flex-shrink:
    0;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    6px;

  font-size: 0.72rem;

  font-weight:
    800;

  color:
    var(--sa-text-muted);
}


.option-result {
  width:
    12px;

  height:
    12px;

  margin-left:
    auto;
}


.challenge-feedback {
  margin-top:
    10px;

  padding:
    8px 10px;

  border-radius:
    8px;

  font-size: 0.80rem;

  font-weight:
    700;
}


.challenge-feedback.success {
  color:
    #059669;

  background:
    rgba(
      16,
      185,
      129,
      0.07
    );
}


.challenge-feedback.error {
  color:
    #DC2626;

  background:
    rgba(
      239,
      68,
      68,
      0.07
    );
}


/* ============================================================
   UPDATES
============================================================ */

.latest {
  color:
    #638ECB;

  font-size: 0.72rem;

  font-weight:
    800;

  text-transform:
    uppercase;

  letter-spacing:
    0.08em;
}


.updates {
  display:
    flex;

  flex-direction:
    column;

  gap:
    17px;
}


.update {
  position:
    relative;

  display:
    flex;

  align-items:
    flex-start;

  gap:
    10px;
}


.update-index {
  width:
    20px;

  height:
    20px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  flex-shrink:
    0;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    6px;

  color:
    var(--sa-text-faint);

  background:
    var(--sa-surface-soft);

  font-size: 0.64rem;

  font-weight:
    800;
}


.update-index.active {
  color:
    #395886;

  border-color:
    rgba(
      99,
      142,
      203,
      0.28
    );

  background:
    rgba(
      99,
      142,
      203,
      0.08
    );
}


html.dark
.update-index.active {
  color:
    #B1C9EF;
}


.update-meta {
  display:
    flex;

  gap:
    6px;

  color:
    var(--sa-text-faint);

  font-size: 0.69rem;

  font-weight:
    800;

  text-transform:
    uppercase;

  letter-spacing:
    0.07em;
}


.update .new {
  color:
    #638ECB;
}


.update p {
  margin-top:
    4px;

  color:
    var(--sa-text-secondary);

  font-size: 0.88rem;

  line-height:
    1.55;
}


/* ============================================================
   SMART ADAMA CITY FOOTER
============================================================ */

.city-footer {
  position:
    relative;
  z-index:
    10;
  margin-top:
    46px;

  overflow:
    hidden;

  border-top:
    1px solid
    rgba(
      255,
      255,
      255,
      0.08
    );

  background-color:
    #243A5A !important;
  opacity:
    1 !important;

  color:
    #F0F3FA;
}


/* Main */

.city-footer-main {
  display:
    grid;

  grid-template-columns:
    1.65fr
    1.7fr
    0.85fr
    0.9fr
    0.8fr;

  gap:
    28px;

  padding:
    34px 30px
    30px;
}


.city-footer-brand {
  display:
    flex;

  align-items:
    flex-start;

  gap:
    10px;
}


.city-footer-logo {
  width:
    38px;

  height:
    38px;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  padding:
    4px;

  border-radius:
    9px;

  background:
    white;
}


.city-footer-logo img {
  width:
    100%;

  height:
    100%;

  object-fit:
    contain;
}


.city-footer-brand strong {
  display:
    block;

  color:
    white;

  font-size: 1.31rem;

  font-weight:
    800;

  letter-spacing:
    -0.02em;
}


.city-footer-brand span {
  display:
    block;

  margin-top:
    3px;

  color:
    #B1C9EF;

  font-size: 0.69rem;

  font-weight:
    700;

  letter-spacing:
    0.07em;

  text-transform:
    uppercase;
}


.city-footer-description {
  max-width:
    280px;

  color:
    #D5DEEF;

  font-size: 0.80rem;

  line-height:
    1.7;
}


.city-footer-column {
  display:
    flex;

  flex-direction:
    column;

  gap:
    7px;
}


.city-footer-column h3 {
  margin-bottom:
    2px;

  color:
    #B1C9EF;

  font-size: 0.72rem;

  font-weight:
    800;

  letter-spacing:
    0.12em;

  text-transform:
    uppercase;
}


.city-footer-column a,
.city-footer-column button {
  width:
    fit-content;

  padding:
    0;

  border:
    0;

  background:
    transparent;

  color:
    #D5DEEF;

  font-family:
    inherit;

  font-size: 0.77rem;

  font-weight:
    600;

  text-decoration:
    none;

  text-align:
    left;

  cursor:
    pointer;

  transition:
    color
    0.18s ease;
}


.city-footer-column a:hover,
.city-footer-column button:hover {
  color:
    white;
}


/* Government strip */

.city-government-bar {
  border-top:
    1px solid
    rgba(
      255,
      255,
      255,
      0.09
    );

  border-bottom:
    1px solid
    rgba(
      255,
      255,
      255,
      0.09
    );

  background:
    #1F334F;
}


.city-government-inner {
  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    16px;

  padding:
    12px 30px;
}


.government-identity {
  display:
    flex;

  align-items:
    center;

  gap:
    7px;

  color:
    #D5DEEF;

  font-size: 0.72rem;

  font-weight:
    700;
}


.government-line {
  width:
    4px;

  height:
    16px;

  border-radius:
    2px;

  background:
    #638ECB;
}


.government-separator {
  color:
    #638ECB;
}


.government-links {
  display:
    flex;

  align-items:
    center;

  gap:
    15px;

  color:
    #8AAEE0;

  font-size: 0.67rem;

  font-weight:
    600;
}


/* Bottom */

.city-footer-bottom {
  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    16px;

  padding:
    11px 30px;

  background:
    #182A43;

  color:
    #8AAEE0;

  font-size: 0.64rem;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1024px) {

  /* .milestone-grid flex handles responsiveness */


  .city-footer-main {
    grid-template-columns:
      1.5fr
      1.5fr
      1fr;
  }

}


@media (max-width: 768px) {

  .dashboard-container {
    padding:
      4.8rem
      1.25rem
      1.5rem;
  }

  .dashboard-header {
    align-items:
      flex-start;

    flex-direction:
      column;
  }


  .workspace,
  .lower-grid {
    grid-template-columns:
      1fr;
  }


  .ai-visual {
    display:
      none;
  }


  .loading-grid {
    grid-template-columns:
      repeat(
        2,
        1fr
      );
  }

}


@media (max-width: 640px) {

  .dashboard-container {
    padding:
      4.6rem
      0.75rem
      1.25rem;
  }


  .journey-header {
    flex-direction:
      column;
  }


  .journey-completion {
    text-align:
      left;
  }


  .journey-footer {
    grid-template-columns:
      1fr;
  }


  .journey-button {
    justify-content:
      center;
  }


  .momentum-strip {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
  }

  .momentum-divider {
    display: none;
  }

  .momentum-item,
  .momentum-item:first-child,
  .momentum-item:last-child {
    padding: 10px 6px;
    align-items: center;
    text-align: center;
  }

  /* .milestone-grid flex handles responsiveness */

  .city-footer-main {
    grid-template-columns: 1.3fr 0.9fr 0.9fr 0.9fr;
    gap: 0.75rem;
    padding: 20px 12px;
  }

  .city-footer-brand {
    grid-column: auto;
  }

  .city-footer-description {
    max-width: none;
  }

  .city-government-inner {
    align-items: center;
    flex-direction: row;
    justify-content: space-between;
    padding: 12px 18px;
  }

  .government-links {
    flex-wrap: wrap;
  }

  .city-footer-bottom {
    align-items: center;
    flex-direction: row;
    justify-content: space-between;
    padding: 11px 18px;
  }

}

@media (max-width: 480px) {

  .dashboard-header h1 {
    font-size: 1.8rem;
  }

  .journey-section,
  .workspace-panel,
  .ai-panel,
  .lower-panel {
    border-radius: 15px;
  }

  .momentum-strip {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 5px;
  }

  .momentum-item {
    padding: 8px 4px;
    align-items: center;
    text-align: center;
    border-bottom: 0;
    border-radius: 10px;
    gap: 3px;
  }

  .momentum-icon {
    width: 22px;
    height: 22px;
  }

  .momentum-icon svg {
    width: 13px;
    height: 13px;
  }

  .momentum-item strong {
    font-size: 0.85rem;
    line-height: 1.1;
  }

  .momentum-item span {
    font-size: 0.5rem;
    line-height: 1.1;
    display: block;
  }

  .momentum-item:last-child {
    border-bottom: 0;
  }

  .chapter-nodes {
    justify-content: flex-start;
    min-width: max-content;
  }

  .timeline-track {
    display: none;
  }

  /* .milestone-grid flex handles responsiveness */

  .loading-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 5px;
  }


  .footer-links {
    flex-wrap:
      wrap;
  }

}


@media (max-width: 380px) {

  .city-footer-main {
    grid-template-columns: 1.2fr 0.9fr 0.9fr 0.9fr;
    gap: 0.45rem;
  }

}


/* ============================================================
   REDUCED MOTION
============================================================ */

@media (prefers-reduced-motion: reduce) {

  *,
  *::before,
  *::after {
    animation-duration:
      0.01ms !important;

    animation-iteration-count:
      1 !important;

    transition-duration:
      0.01ms !important;
  }
}

html.dark .journey-section,
html.dark .lower-panel,
html.dark .dashboard-card {
  background: #1e293b !important;
}
html.dark .journey-button {
  background: #8AAEE0 !important;
  color: #0f172a !important;
}
</style>
