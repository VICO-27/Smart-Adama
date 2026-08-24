<script setup lang="ts">
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

const d = computed(() => progress.dashboard)


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
  return d.value?.total_chapters || 0
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


const nextChapter = computed(() => {
  if (!hasChapters.value) {
    return 1
  }

  return Math.min(
    completedChapters.value + 1,
    totalChapters.value,
  )
})


const continueTitle = computed(() => {
  if (!hasChapters.value) {
    return (
      t('dashboard.book_ready') ||
      'Ready to start'
    )
  }

  if (isBookComplete.value) {
    return (
      t('dashboard.book_done') ||
      'Course complete'
    )
  }

  return (
    t('dashboard.chap_of', {
      next: nextChapter.value,
      total: totalChapters.value,
    }) ||
    `Chapter ${nextChapter.value} of ${totalChapters.value}`
  )
})


const continueSubtext = computed(() => {
  if (!hasChapters.value) {
    return (
      t('dashboard.sub_ready') ||
      'Your learning journey awaits.'
    )
  }

  if (isBookComplete.value) {
    return (
      t('dashboard.sub_done') ||
      'You have completed the available learning content.'
    )
  }

  return 'Your progress is saved automatically. Continue where you left off.'
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

  return Array.from(
    {
      length:
        totalChapters.value,
    },
    (_, index) => {
      const chapter = index + 1

      if (
        chapter <=
        completedChapters.value
      ) {
        return {
          chapter,
          state: 'completed',
        }
      }

      if (
        chapter ===
        nextChapter.value
      ) {
        return {
          chapter,
          state: 'current',
        }
      }

      return {
        chapter,
        state: 'upcoming',
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
      'Complete your first chapter.',
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
      'Score 100% on any quiz.',
    earned: false,
    progress: null,
  },

  {
    id: '4',
    name: 'On a Roll',
    description:
      'Maintain a 3-day learning streak.',
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
      'Maintain a 7-day learning streak.',
    earned: false,
    progress: {
      current: 0,
      required: 7,
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

onMounted(() => {
  progress.loadAll()
  chatStore.loadSessions(1)
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
              Welcome back,
              <span>
                {{ firstName }}
              </span>
            </h1>

            <p>
              Your Smart Adama learning journey,
              all in one place.
            </p>

          </div>

          <div class="status">
            <a href="https://ethiocoders.et/" target="_blank" class="hover:underline text-brand-500 dark:text-brand-300">5 Million Ethiopian Coders</a>
            <span class="mx-2 opacity-40">•</span>
            <a href="https://portal.adamacity.gov.et/" target="_blank" class="hover:underline text-brand-500 dark:text-brand-300">Smart Adama City</a>
          </div>

        </header>

        <!-- ========================================================
             LOADING STATE
        ========================================================= -->
        <template v-if="!d">
          <div class="loading-journey mt-8"></div>
          <div class="loading-line"></div>
          <div class="loading-grid">
            <div
              v-for="n in 4"
              :key="n"
              class="loading-block"
            ></div>
          </div>
        </template>

        <!-- ====================================================
             LEARNING JOURNEY
        ===================================================== -->
        <template v-else>

        <section class="journey-section">

          <div class="journey-header">

            <div>

              <span class="section-label">
                Continue your journey
              </span>

              <h2>
                {{ continueTitle }}
              </h2>

              <p>
                {{ continueSubtext }}
              </p>

            </div>


            <div class="journey-completion">

              <strong>
                {{ completionPct }}%
              </strong>

              <span>
                complete
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
                      item.state ===
                      'current'
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

            <div class="journey-stat">

              <strong>
                {{ completedChapters }}
              </strong>

              <span>
                of
                {{ totalChapters }}
                chapters
              </span>

            </div>


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
                  overall progress
                </span>

                <span
                  v-if="!isBookComplete"
                >
                  Next:
                  Chapter
                  {{ nextChapter }}
                </span>

                <span
                  v-else
                >
                  Course complete
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
                    ? 'Review learning'
                    : 'Continue reading'
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

        <section class="momentum-section">

          <div class="section-heading">

            <span class="section-label">
              Your momentum
            </span>

            <h2>
              Keep moving forward
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
                  day streak
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
                  average quiz score
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
                  quizzes passed
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
                  badges unlocked
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

                <span class="section-label">
                  Activity
                </span>

                <h2>
                  Recent AI conversations
                </h2>

              </div>


              <RouterLink
                to="/study"
                class="quiet-link"
              >

                View all

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
                No conversations yet
              </strong>

              <span>
                Ask something inside Study Mode
                to start your history.
              </span>

            </div>

          </article>


          <!-- AI -->

          <article class="ai-panel">

            <div class="ai-background"></div>

            <div class="ai-content">

              <span class="ai-label">
                Smart Adama AI
              </span>

              <h2>
                Learn with context.
              </h2>

              <p>
                Your AI tutor lives inside the
                learning experience. Ask for an
                explanation, summary, example, or
                clarification whenever you need one.
              </p>

              <RouterLink
                to="/study"
                class="ai-button"
              >

                Open AI tutor

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

            <span class="section-label">
              Milestones
            </span>

            <h2>
              Your learning achievements
            </h2>

            <p>
              Complete chapters, build consistency,
              and unlock recognition along the way.
            </p>

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
                {{ badge.name }}
              </h3>

              <p>
                {{ badge.description }}
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
                    Progress
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
                Unlocked
              </div>


              <div
                v-else
                class="milestone-locked"
              >
                Keep learning
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
                  Practice
                </span>

                <h2>
                  Daily challenge
                </h2>

              </div>

              <span class="xp-label">
                +10 XP
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
                  Platform
                </span>

                <h2>
                  Smart Adama updates
                </h2>

              </div>

              <span class="latest">
                Latest
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
                      New
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


        <!-- ====================================================
             GOVERNMENT-STYLE FOOTER
        ===================================================== -->

        <footer class="city-footer">

          <!-- Main -->

          <div class="city-footer-main">

            <div class="city-footer-brand">

              <div class="city-footer-logo">

                <img
                  src="/logo.png"
                  alt="Smart Adama"
                />

              </div>


              <div>

                <strong>
                  Smart Adama
                </strong>

                <span>
                  Smart City Learning Platform
                </span>

              </div>

            </div>


            <p class="city-footer-description">
              A digital learning platform for understanding
              Adama's smart city vision, initiatives, services,
              and civic development.
            </p>


            <!-- Platform -->

            <div class="city-footer-column">

              <h3>
                Platform
              </h3>

              <RouterLink to="/dashboard">
                Dashboard
              </RouterLink>

              <RouterLink to="/study">
                Study
              </RouterLink>

              <RouterLink to="/game">
                Game
              </RouterLink>

              <RouterLink to="/profile">
                Profile
              </RouterLink>

            </div>


            <!-- Resources -->

            <div class="city-footer-column">

              <h3>
                Resources
              </h3>

              <a
                href="/books/SA-Book.pdf"
                target="_blank"
                rel="noopener noreferrer"
              >
                Smart Adama Book
              </a>

              <RouterLink to="/study">
                Learning Center
              </RouterLink>

              <RouterLink to="/game">
                Challenges
              </RouterLink>

              <RouterLink to="/profile">
                Account Settings
              </RouterLink>

            </div>


            <!-- Languages -->

            <div class="city-footer-column">

              <h3>
                Languages
              </h3>

              <button type="button">
                English
              </button>

              <button type="button">
                Afaan Oromoo
              </button>

              <button type="button">
                አማርኛ
              </button>

            </div>

          </div>


          <!-- Government identity -->

          <div class="city-government-bar">

            <div class="city-government-inner">

              <div class="government-identity">

                <span class="government-line"></span>

                <span>
                  Smart Adama City
                </span>

                <span class="government-separator">
                  /
                </span>

                <span>
                  Digital Learning Platform
                </span>

              </div>


              <div class="government-links">

                <span>
                  Public Service & Innovation
                </span>

                <span>
                  Learning · Technology · Community
                </span>

              </div>

            </div>

          </div>


          <!-- Copyright -->

          <div class="city-footer-bottom">

            <span>
              ©
              {{
                new Date()
                  .getFullYear()
              }}
              Smart Adama City
            </span>

            <span>
              Built for learning and civic knowledge
            </span>

          </div>

        </footer>

        </template>

      </div>

    </main>

  </AppShell>
</template>


<style scoped>

/* ============================================================
   PAGE
============================================================ */

.dashboard-page {
  min-height: 100vh;

  background:
    var(--sa-page-bg);

  color:
    var(--sa-text);

  transition:
    background-color 0.3s ease,
    color 0.3s ease;
}


.dashboard-container {
  width:
    min(100%, 1180px);

  margin:
    0 auto;

  padding:
    5rem 1rem 0;

  position:
    relative;

  z-index:
    10;
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
  display:
    block;

  color:
    var(--sa-text-muted);

  font-size:
    0.5rem;

  font-weight:
    800;

  letter-spacing:
    0.14em;

  text-transform:
    uppercase;
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

  font-size:
    0.68rem;

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

  font-size:
    0.48rem;

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
    var(--sa-surface);

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

  font-size:
    0.61rem;

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

  font-size:
    0.45rem;

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

  font-size:
    0.42rem;

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
    auto
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

  font-size:
    0.48rem;
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

  font-size:
    0.43rem;
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

  font-size:
    0.56rem;

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

  font-size:
    0.57rem;
}


.momentum-strip {
  display:
    grid;

  grid-template-columns:
    1fr
    auto
    1fr
    auto
    1fr
    auto
    1fr;

  align-items:
    center;

  padding:
    13px 0;

  border-top:
    1px solid
    var(--sa-border);

  border-bottom:
    1px solid
    var(--sa-border);
}


.momentum-item {
  display:
    flex;

  align-items:
    center;

  gap:
    10px;

  min-width:
    0;

  padding:
    0 16px;
}


.momentum-item:first-child {
  padding-left:
    0;
}


.momentum-item:last-child {
  padding-right:
    0;
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

  font-size:
    0.44rem;

  white-space:
    nowrap;
}


.momentum-divider {
  width:
    1px;

  height:
    31px;

  background:
    var(--sa-border);
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

  font-size:
    0.95rem;

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

  font-size:
    0.48rem;

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

  font-size:
    0.57rem;

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

  font-size:
    0.44rem;
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

  font-size:
    0.6rem;

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

  font-size:
    0.48rem;

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

  font-size:
    0.5rem;

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

  font-size:
    0.58rem;

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

  font-size:
    0.56rem;

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

  overflow-x:
    auto;

  gap:
    15px;

  padding-bottom:
    12px;
}


.milestone {
  flex:
    0 0 auto;

  position:
    relative;

  width:
    140px;

  min-height:
    150px;

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

  font-size:
    0.66rem;

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

  font-size:
    0.45rem;

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

  font-size:
    0.4rem;
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

  font-size:
    0.4rem;

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

  font-size:
    0.44rem;

  font-weight:
    800;
}


.challenge-question {
  margin:
    6px 0 12px;

  color:
    var(--sa-text);

  font-size:
    0.7rem;

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
    var(--sa-surface);

  color:
    var(--sa-text-secondary);

  font-size:
    0.55rem;

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

  font-size:
    0.45rem;

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

  font-size:
    0.5rem;

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

  font-size:
    0.45rem;

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

  font-size:
    0.4rem;

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

  font-size:
    0.43rem;

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

  font-size:
    0.55rem;

  line-height:
    1.55;
}


/* ============================================================
   SMART ADAMA CITY FOOTER
============================================================ */

.city-footer {
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

  background:
    #243A5A;

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

  font-size:
    0.82rem;

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

  font-size:
    0.43rem;

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

  font-size:
    0.5rem;

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

  font-size:
    0.45rem;

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

  font-size:
    0.48rem;

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

  font-size:
    0.45rem;

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

  font-size:
    0.42rem;

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

  font-size:
    0.4rem;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1050px) {

  /* .milestone-grid flex handles responsiveness */


  .city-footer-main {
    grid-template-columns:
      1.5fr
      1.5fr
      1fr;
  }

}


@media (max-width: 980px) {

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


@media (max-width: 760px) {

  .dashboard-container {
    padding:
      4.6rem
      0.75rem
      0;
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
    grid-template-columns:
      1fr
      1fr;
  }


  .momentum-divider {
    display:
      none;
  }


  .momentum-item,
  .momentum-item:first-child,
  .momentum-item:last-child {
    padding:
      9px 8px;

    border-bottom:
      1px solid
      var(--sa-border);
  }


  /* .milestone-grid flex handles responsiveness */


  .city-footer-main {
    grid-template-columns:
      1fr 1fr;

    padding:
      26px 18px;
  }


  .city-footer-brand {
    grid-column:
      1 / -1;
  }


  .city-footer-description {
    max-width:
      none;
  }


  .city-government-inner {
    align-items:
      flex-start;

    flex-direction:
      column;

    padding:
      12px 18px;
  }


  .government-links {
    flex-wrap:
      wrap;
  }


  .city-footer-bottom {
    align-items:
      flex-start;

    flex-direction:
      column;

    padding:
      11px 18px;
  }

}


@media (max-width: 520px) {

  .dashboard-header h1 {
    font-size:
      1.8rem;
  }


  .journey-section,
  .workspace-panel,
  .ai-panel,
  .lower-panel {
    border-radius:
      15px;
  }


  .momentum-strip {
    grid-template-columns:
      1fr;
  }


  .momentum-item {
    border-bottom:
      1px solid
      var(--sa-border);
  }


  .momentum-item:last-child {
    border-bottom:
      0;
  }


  .chapter-nodes {
    justify-content:
      flex-start;

    min-width:
      max-content;
  }


  .timeline-track {
    display:
      none;
  }


  /* .milestone-grid flex handles responsiveness */


  .loading-grid {
    grid-template-columns:
      1fr;
  }


  .footer-links {
    flex-wrap:
      wrap;
  }

}


@media (max-width: 430px) {

  .city-footer-main {
    grid-template-columns:
      1fr;
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

</style>