<script setup lang="ts">
import {
  ref,
  computed,
  onMounted,
  onUnmounted,
  shallowRef,
} from 'vue'

import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import { useI18n } from 'vue-i18n'

import AppShell from '@/components/layout/AppShell.vue'
import SaCard from '@/components/ui/SaCard.vue'
import apiClient from '@/api/client'


/* ============================================================
   TYPES
============================================================ */

type ThemePreference = 'light' | 'dark' | 'system'

type NoticeType = 'success' | 'error' | 'info' | ''

interface Notice {
  text: string
  type: NoticeType
}


/* ============================================================
   STORES
============================================================ */

const router = useRouter()
const auth = useAuthStore()
const progress = useProgressStore()
const { locale } = useI18n()


/* ============================================================
   PROFILE FORM
============================================================ */

const formData = ref({
  name: auth.user?.name || '',
  notify_badges:
    auth.user?.notify_badges ?? true,
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})


/* ============================================================
   UI STATE
============================================================ */

const fileInputRef =
  ref<HTMLInputElement | null>(null)

const profileMessage =
  ref<Notice>({
    text: '',
    type: '',
  })

const securityMessage =
  ref<Notice>({
    text: '',
    type: '',
  })

const appearanceMessage =
  ref<Notice>({
    text: '',
    type: '',
  })

const isSavingSecurity =
  ref(false)

const isUploadingAvatar =
  ref(false)

const isDeletingAccount =
  ref(false)

const showDeleteConfirmation =
  ref(false)


/* ============================================================
   THEME
============================================================ */

const themePreference =
  ref<ThemePreference>('system')

const resolvedDarkMode =
  ref(false)

let systemMediaQuery:
  MediaQueryList | null = null


function getSystemTheme(): boolean {
  return window.matchMedia(
    '(prefers-color-scheme: dark)',
  ).matches
}


function resolveTheme(
  theme: ThemePreference,
): boolean {
  if (theme === 'dark') {
    return true
  }

  if (theme === 'light') {
    return false
  }

  return getSystemTheme()
}


function applyTheme(
  theme: ThemePreference,
) {
  const isDark =
    resolveTheme(theme)

  resolvedDarkMode.value =
    isDark

  const root =
    document.documentElement

  const body =
    document.body

  const app =
    document.getElementById('app')

  root.classList.toggle(
    'dark',
    isDark,
  )

  root.dataset.theme =
    isDark
      ? 'dark'
      : 'light'

  root.style.colorScheme =
    isDark
      ? 'dark'
      : 'light'

  body.classList.toggle(
    'dark',
    isDark,
  )

  body.dataset.theme =
    isDark
      ? 'dark'
      : 'light'

  if (app) {
    app.classList.toggle(
      'dark',
      isDark,
    )

    app.dataset.theme =
      isDark
        ? 'dark'
        : 'light'
  }

  localStorage.setItem(
    'sa_theme',
    theme,
  )

  localStorage.setItem(
    'theme',
    theme,
  )
}


function initializeTheme() {
  const savedTheme =
    localStorage.getItem(
      'sa_theme',
    )

  const legacyTheme =
    localStorage.getItem(
      'theme',
    )

  if (
    savedTheme === 'light' ||
    savedTheme === 'dark' ||
    savedTheme === 'system'
  ) {
    themePreference.value =
      savedTheme as ThemePreference
  } else if (
    legacyTheme === 'light' ||
    legacyTheme === 'dark' ||
    legacyTheme === 'system'
  ) {
    themePreference.value =
      legacyTheme as ThemePreference
  } else {
    themePreference.value =
      'system'
  }

  applyTheme(
    themePreference.value,
  )
}


function setTheme(
  theme: ThemePreference,
) {
  themePreference.value =
    theme

  applyTheme(theme)

  appearanceMessage.value = {
    text:
      theme === 'system'
        ? 'Using your device appearance.'
        : theme === 'dark'
          ? 'Dark mode enabled.'
          : 'Light mode enabled.',
    type: 'success',
  }

  window.setTimeout(() => {
    appearanceMessage.value = {
      text: '',
      type: '',
    }
  }, 2200)
}


function handleSystemThemeChange() {
  if (
    themePreference.value ===
    'system'
  ) {
    applyTheme('system')
  }
}


/* ============================================================
   COMPUTED USER DATA
============================================================ */

const displayName = computed(() => {
  return (
    auth.user?.name?.trim() ||
    'Smart Adama User'
  )
})


const userInitial = computed(() => {
  return (
    displayName.value
      .charAt(0)
      .toUpperCase() ||
    'A'
  )
})


const avatarUrl = computed(() => {
  const user =
    auth.user as any

  if (!user) {
    return null
  }

  return (
    user.avatar_url ||
    user.profile_picture ||
    user.profile_image ||
    user.avatar ||
    user.photo_url ||
    user.image ||
    user.photo ||
    null
  )
})


const memberSince = computed(() => {
  if (!auth.user?.created_at) {
    return 'Member'
  }

  const date =
    new Date(
      auth.user.created_at,
    )

  return `Member since ${date.toLocaleDateString(
    'en-US',
    {
      month: 'short',
      year: 'numeric',
    },
  )}`
})


const accountRole = computed(() => {
  return auth.user?.role === 'admin'
    ? 'Administrator'
    : 'Learner'
})


const profileCompletionPct =
  computed(() => {
    let score = 50

    if (auth.user?.name?.trim()) {
      score += 25
    }

    if (avatarUrl.value) {
      score += 25
    }

    return score
  })


/* ============================================================
   LEARNING DATA
============================================================ */

const d = computed(() =>
  progress.dashboard,
)


const completedChapters =
  computed(() =>
    d.value?.completed_chapters ||
    0,
  )


const totalChapters =
  computed(() =>
    d.value?.total_chapters ||
    11,
  )


const completionPct =
  computed(() =>
    d.value?.completion_pct ||
    0,
  )


const currentStreak =
  computed(() =>
    d.value?.current_streak ||
    0,
  )


const quizzesPassed =
  computed(() =>
    d.value?.quizzes_passed ||
    0,
  )


const averageQuizScore =
  computed(() =>
    d.value?.average_quiz_score ??
    null,
  )


const badgesEarned =
  computed(() =>
    d.value?.earned_badge_count ||
    0,
  )


/* ============================================================
   PASSWORD STRENGTH
============================================================ */

const passwordStrength =
  computed(() => {
    const value =
      passwordForm.value.new_password

    if (!value) {
      return {
        score: 0,
        label: '',
      }
    }

    let score = 0

    if (value.length >= 8) score++
    if (/[A-Z]/.test(value)) score++
    if (/[a-z]/.test(value)) score++
    if (/\d/.test(value)) score++
    if (/[^A-Za-z0-9]/.test(value)) score++

    if (score <= 2) {
      return {
        score,
        label: 'Weak',
      }
    }

    if (score === 3) {
      return {
        score,
        label: 'Fair',
      }
    }

    if (score === 4) {
      return {
        score,
        label: 'Strong',
      }
    }

    return {
      score,
      label: 'Very strong',
    }
  })


/* ============================================================
   PROFILE UPDATE
============================================================ */

async function saveProfile() {
  profileMessage.value = {
    text: '',
    type: '',
  }

  const name =
    formData.value.name.trim()

  if (!name) {
    profileMessage.value = {
      text:
        'Please enter your name.',
      type: 'error',
    }

    return
  }

  try {
    await auth.updateProfile({
      name,
      notify_badges:
        formData.value
          .notify_badges,
    })

    profileMessage.value = {
      text:
        'Profile updated successfully.',
      type: 'success',
    }

    window.setTimeout(() => {
      profileMessage.value = {
        text: '',
        type: '',
      }
    }, 3000)
  } catch (error) {
    console.error(
      'Profile update failed:',
      error,
    )

    profileMessage.value = {
      text:
        'Unable to update your profile.',
      type: 'error',
    }
  }
}


/* ============================================================
   NOTIFICATION PREFERENCE
============================================================ */

async function toggleNotifications() {
  const nextValue =
    !formData.value
      .notify_badges

  formData.value
    .notify_badges =
    nextValue

  try {
    await auth.updateProfile({
      name:
        formData.value.name.trim(),

      notify_badges:
        nextValue,
    })
  } catch (error) {
    console.error(
      'Notification preference update failed:',
      error,
    )

    formData.value
      .notify_badges =
      !nextValue

    profileMessage.value = {
      text:
        'Unable to save notification settings.',
      type: 'error',
    }
  }
}


/* ============================================================
   AVATAR
============================================================ */

function openAvatarPicker() {
  fileInputRef.value?.click()
}


async function handleFileSelected(
  event: Event,
) {
  const target =
    event.target as HTMLInputElement

  const file =
    target.files?.[0]

  if (!file) {
    return
  }

  if (
    !file.type.startsWith(
      'image/',
    )
  ) {
    profileMessage.value = {
      text:
        'Please choose a valid image file.',
      type: 'error',
    }

    target.value = ''
    return
  }

  if (
    file.size >
    5 * 1024 * 1024
  ) {
    profileMessage.value = {
      text:
        'Please choose an image smaller than 5 MB.',
      type: 'error',
    }

    target.value = ''
    return
  }

  isUploadingAvatar.value =
    true

  try {
    await auth.uploadAvatar(file)

    profileMessage.value = {
      text:
        'Profile photo updated successfully.',
      type: 'success',
    }
  } catch (error) {
    console.error(
      'Avatar upload failed:',
      error,
    )

    profileMessage.value = {
      text:
        'Unable to upload the profile photo.',
      type: 'error',
    }
  } finally {
    isUploadingAvatar.value =
      false

    target.value = ''

    window.setTimeout(() => {
      profileMessage.value = {
        text: '',
        type: '',
      }
    }, 3000)
  }
}


/* ============================================================
   PASSWORD
============================================================ */

async function updatePassword() {
  securityMessage.value = {
    text: '',
    type: '',
  }

  const currentPassword =
    passwordForm.value
      .current_password

  const newPassword =
    passwordForm.value
      .new_password

  const confirmation =
    passwordForm.value
      .new_password_confirmation

  if (!currentPassword) {
    securityMessage.value = {
      text:
        'Enter your current password.',
      type: 'error',
    }

    return
  }

  if (
    newPassword.length < 8
  ) {
    securityMessage.value = {
      text:
        'The new password must be at least 8 characters.',
      type: 'error',
    }

    return
  }

  if (
    newPassword !==
    confirmation
  ) {
    securityMessage.value = {
      text:
        'New passwords do not match.',
      type: 'error',
    }

    return
  }

  isSavingSecurity.value =
    true

  try {
    await apiClient.put(
      '/v1/users/me/password',
      {
        current_password:
          currentPassword,

        new_password:
          newPassword,

        new_password_confirmation:
          confirmation,
      },
    )

    securityMessage.value = {
      text:
        'Password updated successfully.',
      type: 'success',
    }

    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation:
        '',
    }
  } catch (error: any) {
    console.error(
      'Password update failed:',
      error,
    )

    securityMessage.value = {
      text:
        error?.response?.data
          ?.error?.message ||
        error?.response?.data
          ?.message ||
        'Unable to update your password.',
      type: 'error',
    }
  } finally {
    isSavingSecurity.value =
      false
  }
}


/* ============================================================
   ACCOUNT ACTIONS
============================================================ */

async function handleLogout() {
  try {
    await auth.logout()
  } finally {
    router.push('/')
  }
}


function openDeleteModal() {
  showDeleteConfirmation.value =
    true
}


function closeDeleteModal() {
  showDeleteConfirmation.value =
    false
}


async function confirmDeleteAccount() {
  if (
    isDeletingAccount.value
  ) {
    return
  }

  isDeletingAccount.value =
    true

  try {
    await auth.deleteAccount()

    router.push('/')
  } catch (error) {
    console.error(
      'Delete account failed:',
      error,
    )

    profileMessage.value = {
      text:
        'Unable to delete your account.',
      type: 'error',
    }

    showDeleteConfirmation.value =
      false
  } finally {
    isDeletingAccount.value =
      false
  }
}


/* ============================================================
   SPOTLIGHT
============================================================ */

let spotlightRaf:
  number | null = null

const lastPointerEvent =
  shallowRef<PointerEvent | null>(
    null,
  )


function handleSpotlightMove(
  event: PointerEvent,
) {
  lastPointerEvent.value =
    event

  if (spotlightRaf) {
    return
  }

  spotlightRaf =
    requestAnimationFrame(() => {
      spotlightRaf = null

      const ev =
        lastPointerEvent.value

      if (!ev) {
        return
      }

      const target =
        ev.target as
          | HTMLElement
          | null

      const card =
        target?.closest(
          '.spotlight-card',
        ) as
          | HTMLElement
          | null

      if (!card) {
        return
      }

      const rect =
        card.getBoundingClientRect()

      if (
        !rect.width ||
        !rect.height
      ) {
        return
      }

      const x =
        (
          (ev.clientX - rect.left) /
          rect.width
        ) * 100

      const y =
        (
          (ev.clientY - rect.top) /
          rect.height
        ) * 100

      card.style.setProperty(
        '--spot-x',
        `${x}%`,
      )

      card.style.setProperty(
        '--spot-y',
        `${y}%`,
      )
    })
}


/* ============================================================
   KEYBOARD
============================================================ */

function handleEscape(
  event: KeyboardEvent,
) {
  if (
    event.key === 'Escape' &&
    showDeleteConfirmation.value
  ) {
    closeDeleteModal()
  }
}


/* ============================================================
   LIFECYCLE
============================================================ */

onMounted(() => {
  progress.loadAll()

  initializeTheme()

  systemMediaQuery =
    window.matchMedia(
      '(prefers-color-scheme: dark)',
    )

  systemMediaQuery.addEventListener(
    'change',
    handleSystemThemeChange,
  )

  document.addEventListener(
    'pointermove',
    handleSpotlightMove,
    {
      passive: true,
    },
  )

  document.addEventListener(
    'keydown',
    handleEscape,
  )
})


onUnmounted(() => {
  systemMediaQuery?.removeEventListener(
    'change',
    handleSystemThemeChange,
  )

  document.removeEventListener(
    'pointermove',
    handleSpotlightMove,
  )

  document.removeEventListener(
    'keydown',
    handleEscape,
  )

  if (spotlightRaf) {
    cancelAnimationFrame(
      spotlightRaf,
    )
  }
})
</script>


<template>

  <AppShell>

    <main class="profile-page">

      <!-- DIAGONAL VIDEO BACKGROUND -->
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

      <div class="profile-container">


        <!-- ==================================================
             HEADER
        =================================================== -->

        <header class="profile-header fade-up">

          <div>

            <span class="profile-eyebrow">
              Account
            </span>

            <h1 class="profile-page-title">
              Profile & Settings
            </h1>

            <p class="profile-page-description">
              Manage your identity, preferences,
              security, and appearance.
            </p>

          </div>

        </header>


        <!-- ==================================================
             PROFILE HERO
        =================================================== -->

        <section
          class="profile-hero spotlight-card fade-up"
          style="animation-delay: 60ms"
        >

          <div class="hero-glow hero-glow-a"></div>
          <div class="hero-glow hero-glow-b"></div>

          <div class="profile-hero-grid">


            <!-- Avatar -->

            <div class="avatar-column">

              <button
                type="button"
                class="avatar-button"
                :disabled="
                  isUploadingAvatar
                "
                @click="
                  openAvatarPicker
                "
              >

                <img
                  v-if="avatarUrl"
                  :src="avatarUrl"
                  :alt="
                    `${displayName} profile picture`
                  "
                  class="avatar-image"
                />

                <span
                  v-else
                  class="avatar-fallback"
                >
                  {{ userInitial }}
                </span>


                <span class="avatar-overlay">

                  <svg
                    v-if="
                      !isUploadingAvatar
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                  >
                    <path
                      d="M4 7h3l1.4-2.1A2 2 0 0 1 10.1 4h3.8a2 2 0 0 1 1.7.9L17 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"
                    />

                    <circle
                      cx="12"
                      cy="13"
                      r="3.2"
                    />
                  </svg>

                  <svg
                    v-else
                    class="spinner"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                  >
                    <circle
                      cx="12"
                      cy="12"
                      r="9"
                      class="spinner-track"
                    />

                    <path
                      d="M21 12a9 9 0 0 0-9-9"
                    />
                  </svg>

                  <span>
                    {{
                      isUploadingAvatar
                        ? 'Uploading'
                        : 'Change photo'
                    }}
                  </span>

                </span>

              </button>

              <input
                ref="fileInputRef"
                type="file"
                class="sr-only"
                accept="
                  image/png,
                  image/jpeg,
                  image/webp,
                  image/gif
                "
                @change="
                  handleFileSelected
                "
              />

              <span class="avatar-hint">
                JPG, PNG or WEBP · Max 5 MB
              </span>

            </div>


            <!-- Identity -->

            <div class="identity">

              <span class="identity-label">
                Smart Adama Scholar
              </span>

              <h2 class="identity-name">
                {{ displayName }}
              </h2>

              <p class="identity-email">
                {{ auth.user?.email }}
              </p>

              <div class="identity-meta">

                <span>
                  {{ memberSince }}
                </span>

                <span
                  class="meta-divider"
                ></span>

                <span>
                  {{ accountRole }}
                </span>

              </div>

            </div>


            <!-- Completion -->

            <div class="completion-panel">

              <div class="completion-header">

                <span>
                  Profile completion
                </span>

                <strong>
                  {{ profileCompletionPct }}%
                </strong>

              </div>

              <div class="completion-track">

                <div
                  class="completion-value"
                  :style="{
                    width:
                      `${profileCompletionPct}%`,
                  }"
                ></div>

              </div>

              <p>
                Keep your account details current.
              </p>

            </div>

          </div>


          <transition name="notice">

            <div
              v-if="profileMessage.text"
              class="hero-notice"
              :class="profileMessage.type"
            >

              <svg
                v-if="
                  profileMessage.type ===
                  'success'
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
                stroke-width="2"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="9"
                />

                <path
                  d="M12 8v5"
                  stroke-linecap="round"
                />

                <path
                  d="M12 16h.01"
                />
              </svg>

              <span>
                {{ profileMessage.text }}
              </span>

            </div>

          </transition>

        </section>


        <!-- ==================================================
             LEARNING
        =================================================== -->

        <section
          class="profile-section fade-up"
          style="animation-delay: 120ms"
        >

          <div class="section-header">

            <div>

              <span class="section-eyebrow">
                Learning
              </span>

              <h2>
                Your learning journey
              </h2>

            </div>

            <span class="section-note">
              Personal snapshot
            </span>

          </div>


          <div class="metrics-grid">


            <!-- Chapters -->

            <article
              class="metric-card spotlight-card"
            >

              <div class="metric-top">

                <div class="metric-icon blue">

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

                <span>
                  Chapters read
                </span>

              </div>


              <div class="metric-number">

                {{ completedChapters }}

                <small>
                  /
                  {{ totalChapters }}
                </small>

              </div>


              <div
                class="metric-progress"
              >

                <div
                  :style="{
                    width:
                      `${completionPct}%`,
                  }"
                ></div>

              </div>


              <small class="metric-caption">
                {{ completionPct }}% complete
              </small>

            </article>


            <!-- Streak -->

            <article
              class="metric-card spotlight-card"
            >

              <div class="metric-top">

                <div class="metric-icon orange">

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

                <span>
                  Current streak
                </span>

              </div>


              <div class="metric-number">

                {{ currentStreak }}

                <small>
                  days
                </small>

              </div>


              <small class="metric-caption">
                Keep the momentum going.
              </small>

            </article>


            <!-- Quiz -->

            <article
              class="metric-card spotlight-card"
            >

              <div class="metric-top">

                <div class="metric-icon blue">

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

                <span>
                  Average quiz score
                </span>

              </div>


              <div class="metric-number">

                {{
                  averageQuizScore !== null
                    ? `${averageQuizScore}%`
                    : '—'
                }}

              </div>


              <small class="metric-caption">
                {{ quizzesPassed }} quizzes passed
              </small>

            </article>


            <!-- Badges -->

            <article
              class="metric-card spotlight-card"
            >

              <div class="metric-top">

                <div class="metric-icon brand">

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

                <span>
                  Badges earned
                </span>

              </div>


              <div class="metric-number">
                {{ badgesEarned }}
              </div>


              <small class="metric-caption">
                Milestones unlocked
              </small>

            </article>

          </div>

        </section>


        <!-- ==================================================
             PERSONAL + SECURITY
        =================================================== -->

        <section
          class="settings-grid fade-up"
          style="animation-delay: 180ms"
        >


          <!-- Personal -->

          <SaCard
            class="settings-card spotlight-card"
          >

            <div class="settings-header">

              <div>

                <span class="section-eyebrow">
                  Account
                </span>

                <h2>
                  Personal information
                </h2>

                <p>
                  Keep the information associated with
                  your Smart Adama account current.
                </p>

              </div>


              <div
                class="settings-icon brand"
              >

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <circle
                    cx="12"
                    cy="8"
                    r="3"
                  />

                  <path
                    d="M5 20a7 7 0 0 1 14 0"
                  />
                </svg>

              </div>

            </div>


            <form
              class="settings-form"
              @submit.prevent="saveProfile"
            >

              <div class="field">

                <label>
                  Full name
                </label>

                <input
                  v-model="formData.name"
                  type="text"
                  autocomplete="name"
                  maxlength="120"
                  class="input"
                  :class="{
                    error:
                      auth.fieldErrors.name,
                  }"
                  @input="
                    auth.fieldErrors.name = ''
                  "
                />

                <span
                  v-if="
                    auth.fieldErrors.name
                  "
                  class="field-error"
                >
                  {{ auth.fieldErrors.name }}
                </span>

              </div>


              <div class="field">

                <label>
                  Email address
                </label>

                <input
                  :value="
                    auth.user?.email || ''
                  "
                  type="email"
                  disabled
                  class="input disabled"
                />

                <span class="field-help">
                  Your email is managed by
                  your account identity.
                </span>

              </div>


              <button
                type="submit"
                class="form-button primary"
                :disabled="auth.loading"
              >

                <svg
                  v-if="auth.loading"
                  class="spinner"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="9"
                    class="spinner-track"
                  />

                  <path
                    d="M21 12a9 9 0 0 0-9-9"
                  />
                </svg>

                {{
                  auth.loading
                    ? 'Saving...'
                    : 'Save changes'
                }}

              </button>

            </form>

          </SaCard>


          <!-- Security -->

          <SaCard
            class="settings-card spotlight-card"
          >

            <div class="settings-header">

              <div>

                <span class="section-eyebrow">
                  Security
                </span>

                <h2>
                  Password & security
                </h2>

                <p>
                  Use a strong password to protect
                  your learning account.
                </p>

              </div>


              <div
                class="settings-icon brand"
              >

                <svg
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

                  <path
                    d="M12 14v2"
                  />
                </svg>

              </div>

            </div>


            <form
              class="settings-form"
              @submit.prevent="updatePassword"
            >

              <div class="field">

                <label>
                  Current password
                </label>

                <input
                  v-model="
                    passwordForm.current_password
                  "
                  type="password"
                  autocomplete="current-password"
                  required
                  class="input"
                />

              </div>


              <div class="field">

                <label>
                  New password
                </label>

                <input
                  v-model="
                    passwordForm.new_password
                  "
                  type="password"
                  autocomplete="new-password"
                  minlength="8"
                  required
                  class="input"
                />


                <div
                  v-if="
                    passwordForm.new_password
                  "
                  class="password-strength"
                >

                  <div
                    class="password-strength-track"
                  >

                    <div
                      class="password-strength-value"
                      :class="
                        passwordStrength.score <= 2
                          ? 'weak'
                          : passwordStrength.score === 3
                            ? 'fair'
                            : 'strong'
                      "
                      :style="{
                        width:
                          `${passwordStrength.score * 20}%`,
                      }"
                    ></div>

                  </div>

                  <span>
                    {{ passwordStrength.label }}
                  </span>

                </div>

              </div>


              <div class="field">

                <label>
                  Confirm new password
                </label>

                <input
                  v-model="
                    passwordForm
                      .new_password_confirmation
                  "
                  type="password"
                  autocomplete="new-password"
                  minlength="8"
                  required
                  class="input"
                  :class="{
                    error:
                      passwordForm
                        .new_password_confirmation &&
                      passwordForm.new_password !==
                        passwordForm
                          .new_password_confirmation,
                  }"
                />

              </div>


              <button
                type="submit"
                class="form-button secondary"
                :disabled="
                  isSavingSecurity
                "
              >

                <svg
                  v-if="isSavingSecurity"
                  class="spinner"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="9"
                    class="spinner-track"
                  />

                  <path
                    d="M21 12a9 9 0 0 0-9-9"
                  />
                </svg>

                {{
                  isSavingSecurity
                    ? 'Updating...'
                    : 'Update password'
                }}

              </button>


              <p
                v-if="
                  securityMessage.text
                "
                class="form-message"
                :class="
                  securityMessage.type
                "
              >
                {{ securityMessage.text }}
              </p>

            </form>

          </SaCard>

        </section>


        <!-- ==================================================
             PREFERENCES + APPEARANCE
        =================================================== -->

        <section
          class="settings-grid compact fade-up"
          style="animation-delay: 240ms"
        >


          <!-- Notifications -->

          <SaCard
            class="settings-card spotlight-card"
          >

            <div class="settings-header">

              <div>

                <span class="section-eyebrow">
                  Preferences
                </span>

                <h2>
                  Notifications
                </h2>

                <p>
                  Control when Smart Adama notifies
                  you about your progress.
                </p>

              </div>


              <div
                class="settings-icon brand"
              >

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"
                  />

                  <path
                    d="M10 21h4"
                  />

                </svg>

              </div>

            </div>


            <div class="preference-row">

              <div>

                <strong>
                  Badge notifications
                </strong>

                <span>
                  Notify me when I earn a badge
                  or milestone.
                </span>

              </div>


              <button
                type="button"
                class="switch"
                :class="{
                  on:
                    formData.notify_badges,
                }"
                role="switch"
                :aria-checked="
                  formData.notify_badges
                "
                @click="
                  toggleNotifications
                "
              >
                <span></span>
              </button>

            </div>

          </SaCard>


          <!-- Appearance -->

          <SaCard
            class="settings-card spotlight-card"
          >

            <div class="settings-header">

              <div>

                <span class="section-eyebrow">
                  Interface
                </span>

                <h2>
                  Appearance
                </h2>

                <p>
                  Choose the theme used across the
                  Smart Adama application.
                </p>

              </div>


              <div
                class="settings-icon brand"
              >

                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="3"
                  />

                  <path
                    d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"
                  />

                </svg>

              </div>

            </div>


            <div class="theme-options">


              <!-- Light -->

              <button
                type="button"
                class="theme-option"
                :class="{
                  active:
                    themePreference ===
                    'light',
                }"
                @click="
                  setTheme('light')
                "
              >

                <span
                  class="theme-icon"
                >

                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                  >
                    <circle
                      cx="12"
                      cy="12"
                      r="3"
                    />

                    <path
                      d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"
                    />
                  </svg>

                </span>


                <span class="theme-text">

                  <strong>
                    Light
                  </strong>

                  <small>
                    Bright interface
                  </small>

                </span>


                <svg
                  v-if="
                    themePreference ===
                    'light'
                  "
                  class="theme-check"
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

              </button>


              <!-- System -->

              <button
                type="button"
                class="theme-option"
                :class="{
                  active:
                    themePreference ===
                    'system',
                }"
                @click="
                  setTheme('system')
                "
              >

                <span
                  class="theme-icon"
                >

                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                  >
                    <rect
                      x="3"
                      y="4"
                      width="18"
                      height="13"
                      rx="2"
                    />

                    <path
                      d="M8 21h8M12 17v4"
                    />

                  </svg>

                </span>


                <span class="theme-text">

                  <strong>
                    System
                  </strong>

                  <small>
                    Follow device setting
                  </small>

                </span>


                <svg
                  v-if="
                    themePreference ===
                    'system'
                  "
                  class="theme-check"
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

              </button>


              <!-- Dark -->

              <button
                type="button"
                class="theme-option"
                :class="{
                  active:
                    themePreference ===
                    'dark',
                }"
                @click="
                  setTheme('dark')
                "
              >

                <span
                  class="theme-icon"
                >

                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                  >
                    <path
                      d="M20.8 15.4A8.5 8.5 0 0 1 8.6 3.2 8.5 8.5 0 1 0 20.8 15.4Z"
                    />
                  </svg>

                </span>


                <span class="theme-text">

                  <strong>
                    Dark
                  </strong>

                  <small>
                    Low-light interface
                  </small>

                </span>


                <svg
                  v-if="
                    themePreference ===
                    'dark'
                  "
                  class="theme-check"
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

              </button>

            </div>


            <p
              v-if="
                appearanceMessage.text
              "
              class="appearance-message"
            >
              {{ appearanceMessage.text }}
            </p>

          </SaCard>

        </section>


        <!-- ==================================================
             ACCOUNT ACTIONS
        =================================================== -->

        <section
          class="account-actions spotlight-card fade-up"
          style="animation-delay: 300ms"
        >

          <div>

            <span class="section-eyebrow danger">
              Account
            </span>

            <h2>
              Account actions
            </h2>

            <p>
              Sign out from this device or
              permanently remove your Smart Adama account.
            </p>

          </div>


          <div class="account-buttons">

            <button
              type="button"
              class="logout-button"
              @click="
                handleLogout
              "
            >

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path
                  d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4"
                />

                <path
                  d="m15 16 4-4-4-4"
                />

                <path
                  d="M19 12H9"
                />
              </svg>

              Sign out

            </button>


            <button
              type="button"
              class="delete-button"
              @click="
                openDeleteModal
              "
            >

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path
                  d="M4 7h16"
                />

                <path
                  d="M10 11v6M14 11v6"
                  stroke-linecap="round"
                />

                <path
                  d="M6 7l1 13h10l1-13M9 7V4h6v3"
                />
              </svg>

              Delete account

            </button>

          </div>

        </section>


        <!-- ==================================================
             FOOTER
        =================================================== -->

        <footer class="city-footer">

          <div class="city-footer-main">


            <!-- Brand -->

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
              Adama's smart city vision, initiatives,
              services, and civic development.
            </p>


            <!-- Platform -->

            <div class="city-footer-column">

              <h3>
                Platform
              </h3>

              <router-link
                to="/dashboard"
              >
                Dashboard
              </router-link>

              <router-link
                to="/study"
              >
                Study
              </router-link>

              <router-link
                to="/game"
              >
                Game
              </router-link>

              <router-link
                to="/profile"
              >
                Profile
              </router-link>

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

              <router-link
                to="/study"
              >
                Learning Center
              </router-link>

              <router-link
                to="/game"
              >
                Challenges
              </router-link>

              <router-link
                to="/profile"
              >
                Account Settings
              </router-link>

            </div>


            <!-- City -->

            <div class="city-footer-column">

              <h3>
                Smart Adama City
              </h3>

              <span>
                Learning
              </span>

              <span>
                Technology
              </span>

              <span>
                Innovation
              </span>

              <span>
                Community
              </span>

            </div>

          </div>


          <div
            class="city-government-bar"
          >

            <div
              class="city-government-inner"
            >

              <div
                class="government-identity"
              >

                <span
                  class="government-line"
                ></span>

                <span>
                  Smart Adama City
                </span>

                <span
                  class="government-separator"
                >
                  /
                </span>

                <span>
                  Digital Learning Platform
                </span>

              </div>


              <span
                class="government-message"
              >
                Public Service · Learning · Innovation
              </span>

            </div>

          </div>


          <div
            class="city-footer-bottom"
          >

            <span>
              ©
              {{
                new Date()
                  .getFullYear()
              }}
              Smart Adama City
            </span>

            <span>
              English · Afaan Oromoo · Amharic
            </span>

          </div>

        </footer>

      </div>


      <!-- ==================================================
           DELETE MODAL
      =================================================== -->

      <transition name="modal">

        <div
          v-if="
            showDeleteConfirmation
          "
          class="delete-overlay"
          @click.self="
            closeDeleteModal
          "
        >

          <div class="delete-modal">

            <div class="delete-icon">

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
              >
                <path
                  d="M12 9v4"
                  stroke-linecap="round"
                />

                <path
                  d="M12 17h.01"
                />

                <path
                  d="M10.4 4.5 2.8 17.7A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-3L13.6 4.5a2 2 0 0 0-3.2 0Z"
                />

              </svg>

            </div>


            <h3>
              Delete your account?
            </h3>

            <p>
              This is a permanent action.
              Your account and personal information
              will be anonymized and your access removed.
            </p>


            <div class="delete-actions">

              <button
                type="button"
                class="cancel-delete"
                @click="
                  closeDeleteModal
                "
              >
                Cancel
              </button>


              <button
                type="button"
                class="confirm-delete"
                :disabled="
                  isDeletingAccount
                "
                @click="
                  confirmDeleteAccount
                "
              >

                <svg
                  v-if="
                    isDeletingAccount
                  "
                  class="spinner"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="9"
                    class="spinner-track"
                  />

                  <path
                    d="M21 12a9 9 0 0 0-9-9"
                  />
                </svg>

                {{
                  isDeletingAccount
                    ? 'Deleting...'
                    : 'Delete account'
                }}

              </button>

            </div>

          </div>

        </div>

      </transition>

    </main>

  </AppShell>
</template>


<style scoped>

/* ============================================================
   THEME TOKENS
============================================================ */

:global(:root) {

  --sa-page-bg:
    #F0F3FA;

  --sa-surface:
    #FFFFFF;

  --sa-surface-soft:
    #F8FAFC;

  --sa-input-bg:
    #FFFFFF;

  --sa-border:
    #D5DEEF;

  --sa-border-strong:
    #B1C9EF;

  --sa-text:
    #0F172A;

  --sa-text-muted:
    #64748B;

  --sa-track:
    #D5DEEF;

  --sa-switch-off:
    #CBD5E1;

  --sa-button-primary:
    #395886;

  --sa-button-primary-text:
    #FFFFFF;

  --sa-theme-active:
    rgba(
      99,
      142,
      203,
      0.07
    );

  --sa-spotlight:
    rgba(
      99,
      142,
      203,
      0.07
    );

  --sa-card-shadow:
    0 6px 24px
    rgba(
      57,
      88,
      134,
      0.06
    );

  --sa-card-shadow-hover:
    0 14px 34px
    rgba(
      57,
      88,
      134,
      0.10
    );
}


:global(html.dark) {

  --sa-page-bg:
    #030712;

  --sa-surface:
    #0B1220;

  --sa-surface-soft:
    #111827;

  --sa-input-bg:
    #030712;

  --sa-border:
    rgba(
      177,
      201,
      239,
      0.14
    );

  --sa-border-strong:
    rgba(
      177,
      201,
      239,
      0.26
    );

  --sa-text:
    #F8FAFC;

  --sa-text-muted:
    #94A3B8;

  --sa-track:
    #24344F;

  --sa-switch-off:
    #475569;

  --sa-button-primary:
    #638ECB;

  --sa-button-primary-text:
    #FFFFFF;

  --sa-theme-active:
    rgba(
      99,
      142,
      203,
      0.10
    );

  --sa-spotlight:
    rgba(
      99,
      142,
      203,
      0.08
    );

  --sa-card-shadow:
    0 12px 35px
    rgba(
      0,
      0,
      0,
      0.20
    );

  --sa-card-shadow-hover:
    0 18px 42px
    rgba(
      0,
      0,
      0,
      0.28
    );
}


/* ============================================================
   PAGE
============================================================ */

.profile-page {

  min-height:
    100vh;

  padding:
    5rem 1rem 0;

  background:
    var(--sa-page-bg);

  color:
    var(--sa-text);

  transition:
    background-color
    0.3s ease,
    color
    0.3s ease;
}


.profile-container {

  width:
    min(
      100%,
      1160px
    );

  margin:
    0 auto;

  position:
    relative;

  z-index:
    10;
}


/* ============================================================
   HEADER
============================================================ */

.profile-header {

  margin-bottom:
    1.15rem;
}


.profile-eyebrow,
.section-eyebrow {

  display:
    inline-block;

  color:
    var(--sa-text-muted);

  font-size:
    0.5rem;

  font-weight:
    800;

  letter-spacing:
    0.15em;

  text-transform:
    uppercase;
}


.section-eyebrow.danger {

  color:
    #ef4444;
}


.profile-page-title {

  margin-top:
    0.28rem;

  color:
    var(--sa-text);

  font-size:
    clamp(
      2rem,
      4vw,
      2.75rem
    );

  line-height:
    1.02;

  font-weight:
    800;

  letter-spacing:
    -0.045em;
}


.profile-page-description {

  max-width:
    650px;

  margin-top:
    0.5rem;

  color:
    var(--sa-text-muted);

  font-size:
    0.7rem;

  line-height:
    1.6;
}


/* ============================================================
   HERO
============================================================ */

.profile-hero {

  position:
    relative;

  overflow:
    hidden;

  padding:
    1.2rem;

  border:
    1px solid
    rgba(
      177,
      201,
      239,
      0.20
    );

  border-radius:
    1.45rem;

  background:
    linear-gradient(
      118deg,
      #395886 0%,
      #4F76AD 48%,
      #638ECB 100%
    );

  box-shadow:
    0 22px 55px
    rgba(
      57,
      88,
      134,
      0.22
    );
}


.profile-hero-grid {

  position:
    relative;

  z-index:
    2;

  display:
    grid;

  grid-template-columns:
    auto
    minmax(0, 1fr)
    250px;

  align-items:
    center;

  gap:
    1.1rem;
}


.hero-glow {

  position:
    absolute;

  border-radius:
    50%;

  filter:
    blur(55px);

  pointer-events:
    none;
}


.hero-glow-a {

  width:
    260px;

  height:
    260px;

  top:
    -140px;

  right:
    -80px;

  background:
    rgba(
      255,
      255,
      255,
      0.13
    );
}


.hero-glow-b {

  width:
    180px;

  height:
    180px;

  left:
    28%;

  bottom:
    -120px;

  background:
    rgba(
      177,
      201,
      239,
      0.18
    );
}


/* ============================================================
   AVATAR
============================================================ */

.avatar-column {

  display:
    flex;

  flex-direction:
    column;

  align-items:
    center;

  gap:
    0.3rem;
}


.avatar-button {

  position:
    relative;

  width:
    6rem;

  height:
    6rem;

  overflow:
    hidden;

  padding:
    0;

  border:
    3px solid
    rgba(
      255,
      255,
      255,
      0.25
    );

  border-radius:
    50%;

  background:
    rgba(
      255,
      255,
      255,
      0.10
    );

  cursor:
    pointer;

  box-shadow:
    0 12px 30px
    rgba(
      0,
      0,
      0,
      0.18
    );

  transition:
    transform
    0.2s ease,
    border-color
    0.2s ease;
}


.avatar-button:hover {

  transform:
    scale(1.025);

  border-color:
    rgba(
      255,
      255,
      255,
      0.45
    );
}


.avatar-button:disabled {

  opacity:
    0.8;

  cursor:
    wait;
}


.avatar-image {

  width:
    100%;

  height:
    100%;

  object-fit:
    cover;
}


.avatar-fallback {

  width:
    100%;

  height:
    100%;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  background:
    rgba(
      255,
      255,
      255,
      0.10
    );

  color:
    white;

  font-size:
    2.2rem;

  font-weight:
    800;
}


.avatar-overlay {

  position:
    absolute;

  inset:
    0;

  display:
    flex;

  flex-direction:
    column;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.2rem;

  background:
    rgba(
      2,
      6,
      23,
      0.68
    );

  color:
    white;

  opacity:
    0;

  transition:
    opacity
    0.2s ease;
}


.avatar-button:hover
.avatar-overlay {

  opacity:
    1;
}


.avatar-overlay svg {

  width:
    1rem;

  height:
    1rem;
}


.avatar-overlay span {

  font-size:
    0.45rem;

  font-weight:
    800;

  letter-spacing:
    0.1em;

  text-transform:
    uppercase;
}


.avatar-hint {

  color:
    rgba(
      255,
      255,
      255,
      0.55
    );

  font-size:
    0.44rem;

  font-weight:
    600;
}


/* ============================================================
   IDENTITY
============================================================ */

.identity {

  min-width:
    0;
}


.identity-label {

  display:
    inline-flex;

  padding:
    0.27rem
    0.48rem;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.14
    );

  border-radius:
    999px;

  background:
    rgba(
      255,
      255,
      255,
      0.08
    );

  color:
    rgba(
      255,
      255,
      255,
      0.78
    );

  font-size:
    0.44rem;

  font-weight:
    800;

  letter-spacing:
    0.11em;

  text-transform:
    uppercase;
}


.identity-name {

  margin-top:
    0.5rem;

  overflow:
    hidden;

  color:
    white;

  font-size:
    clamp(
      1.7rem,
      4vw,
      2.35rem
    );

  line-height:
    1.03;

  font-weight:
    800;

  letter-spacing:
    -0.045em;

  text-overflow:
    ellipsis;
}


.identity-email {

  margin-top:
    0.3rem;

  overflow:
    hidden;

  color:
    rgba(
      255,
      255,
      255,
      0.72
    );

  font-size:
    0.64rem;

  text-overflow:
    ellipsis;

  white-space:
    nowrap;
}


.identity-meta {

  display:
    flex;

  align-items:
    center;

  gap:
    0.45rem;

  margin-top:
    0.42rem;

  color:
    rgba(
      255,
      255,
      255,
      0.56
    );

  font-size:
    0.49rem;

  font-weight:
    600;
}


.meta-divider {

  width:
    3px;

  height:
    3px;

  border-radius:
    50%;

  background:
    rgba(
      255,
      255,
      255,
      0.35
    );
}


/* ============================================================
   COMPLETION
============================================================ */

.completion-panel {

  padding:
    0.85rem;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.13
    );

  border-radius:
    0.85rem;

  background:
    rgba(
      255,
      255,
      255,
      0.08
    );

  backdrop-filter:
    blur(12px);
}


.completion-header {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    0.5rem;

  color:
    rgba(
      255,
      255,
      255,
      0.68
    );

  font-size:
    0.49rem;

  font-weight:
    700;
}


.completion-header strong {

  color:
    white;

  font-size:
    0.72rem;
}


.completion-track {

  height:
    0.32rem;

  margin-top:
    0.55rem;

  overflow:
    hidden;

  border-radius:
    999px;

  background:
    rgba(
      0,
      0,
      0,
      0.20
    );
}


.completion-value {

  height:
    100%;

  border-radius:
    inherit;

  background:
    #8AAEE0;

  transition:
    width 0.6s ease;
}


.completion-panel p {

  margin-top:
    0.38rem;

  color:
    rgba(
      255,
      255,
      255,
      0.44
    );

  font-size:
    0.45rem;
}


/* ============================================================
   HERO NOTICE
============================================================ */

.hero-notice {

  position:
    relative;

  z-index:
    2;

  display:
    flex;

  align-items:
    center;

  gap:
    0.4rem;

  margin-top:
    0.75rem;

  padding:
    0.52rem
    0.65rem;

  border-radius:
    0.6rem;

  background:
    rgba(
      255,
      255,
      255,
      0.08
    );

  color:
    rgba(
      255,
      255,
      255,
      0.86
    );

  font-size:
    0.56rem;

  font-weight:
    700;
}


.hero-notice svg {

  width:
    0.78rem;

  height:
    0.78rem;

  flex-shrink:
    0;
}


.hero-notice.success {

  color:
    #d1fae5;
}


.hero-notice.error {

  color:
    #fee2e2;
}


/* ============================================================
   SECTIONS
============================================================ */

.profile-section {

  margin-top:
    1.8rem;
}


.section-header {

  display:
    flex;

  align-items:
    flex-end;

  justify-content:
    space-between;

  gap:
    1rem;

  margin-bottom:
    0.7rem;
}


.section-header h2 {

  margin-top:
    0.18rem;

  color:
    var(--sa-text);

  font-size:
    1.16rem;

  font-weight:
    800;

  letter-spacing:
    -0.025em;
}


.section-note {

  color:
    var(--sa-text-muted);

  font-size:
    0.47rem;

  font-weight:
    700;

  letter-spacing:
    0.1em;

  text-transform:
    uppercase;
}


/* ============================================================
   METRICS
============================================================ */

.metrics-grid {

  display:
    grid;

  grid-template-columns:
    repeat(
      4,
      minmax(
        0,
        1fr
      )
    );

  gap:
    0.65rem;
}


.metric-card {

  position:
    relative;

  overflow:
    hidden;

  min-height:
    9.25rem;

  padding:
    0.9rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.95rem;

  background:
    var(--sa-surface);

  box-shadow:
    var(--sa-card-shadow);

  transition:
    transform
    0.2s ease,
    border-color
    0.2s ease,
    box-shadow
    0.2s ease;
}


.metric-card:hover {

  transform:
    translateY(-2px);

  border-color:
    var(--sa-border-strong);

  box-shadow:
    var(--sa-card-shadow-hover);
}


.metric-top {

  display:
    flex;

  align-items:
    center;

  gap:
    0.5rem;
}


.metric-top > span {

  color:
    var(--sa-text-muted);

  font-size:
    0.47rem;

  font-weight:
    800;

  letter-spacing:
    0.07em;

  text-transform:
    uppercase;
}


.metric-icon {

  width:
    1.8rem;

  height:
    1.8rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  flex-shrink:
    0;

  border-radius:
    0.55rem;
}


.metric-icon svg {

  width:
    0.9rem;

  height:
    0.9rem;
}


.metric-icon.blue {

  color:
    #638ECB;

  background:
    rgba(
      99,
      142,
      203,
      0.10
    );
}


.metric-icon.orange {

  color:
    #C26D1B;

  background:
    rgba(
      194,
      109,
      27,
      0.10
    );
}


.metric-icon.brand {

  color:
    #395886;

  background:
    rgba(
      57,
      88,
      134,
      0.10
    );
}


html.dark
.metric-icon.brand {

  color:
    #B1C9EF;
}


.metric-number {

  margin-top:
    0.95rem;

  color:
    var(--sa-text);

  font-size:
    1.7rem;

  line-height:
    1;

  font-weight:
    800;

  letter-spacing:
    -0.04em;
}


.metric-number small {

  color:
    var(--sa-text-muted);

  font-size:
    0.58rem;

  font-weight:
    600;

  letter-spacing:
    normal;
}


.metric-progress {

  height:
    0.3rem;

  margin-top:
    0.7rem;

  overflow:
    hidden;

  border-radius:
    999px;

  background:
    var(--sa-track);
}


.metric-progress div {

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

  transition:
    width
    0.6s ease;
}


.metric-caption {

  display:
    block;

  margin-top:
    0.48rem;

  color:
    var(--sa-text-muted);

  font-size:
    0.48rem;
}


/* ============================================================
   SETTINGS
============================================================ */

.settings-grid {

  display:
    grid;

  grid-template-columns:
    repeat(
      2,
      minmax(
        0,
        1fr
      )
    );

  gap:
    0.65rem;

  margin-top:
    1.8rem;
}


.settings-grid.compact {

  margin-top:
    0.65rem;
}


.settings-card {

  position:
    relative;

  overflow:
    hidden;

  padding:
    1.1rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    1.05rem;

  background:
    var(--sa-surface);

  box-shadow:
    var(--sa-card-shadow);
}


.settings-header {

  display:
    flex;

  align-items:
    flex-start;

  justify-content:
    space-between;

  gap:
    1rem;

  margin-bottom:
    1rem;
}


.settings-header h2 {

  margin-top:
    0.15rem;

  color:
    var(--sa-text);

  font-size:
    0.98rem;

  line-height:
    1.15;

  font-weight:
    800;

  letter-spacing:
    -0.022em;
}


.settings-header p {

  max-width:
    470px;

  margin-top:
    0.3rem;

  color:
    var(--sa-text-muted);

  font-size:
    0.56rem;

  line-height:
    1.55;
}


.settings-icon {

  width:
    1.9rem;

  height:
    1.9rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  flex-shrink:
    0;

  border-radius:
    0.58rem;
}


.settings-icon svg {

  width:
    0.92rem;

  height:
    0.92rem;
}


.settings-icon.brand {

  color:
    #395886;

  background:
    rgba(
      57,
      88,
      134,
      0.09
    );
}


html.dark
.settings-icon.brand {

  color:
    #B1C9EF;

  background:
    rgba(
      99,
      142,
      203,
      0.10
    );
}


/* ============================================================
   FORMS
============================================================ */

.settings-form {

  display:
    flex;

  flex-direction:
    column;

  gap:
    0.72rem;
}


.field {

  display:
    flex;

  flex-direction:
    column;

  gap:
    0.3rem;
}


.field label {

  color:
    var(--sa-text);

  font-size:
    0.56rem;

  font-weight:
    800;
}


.input {

  width:
    100%;

  min-height:
    2.5rem;

  padding:
    0.58rem
    0.7rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.65rem;

  outline:
    none;

  background:
    var(--sa-input-bg);

  color:
    var(--sa-text);

  font-size:
    0.63rem;

  transition:
    border-color
    0.18s ease,
    box-shadow
    0.18s ease,
    background
    0.18s ease;
}


.input:focus {

  border-color:
    #638ECB;

  box-shadow:
    0 0 0 3px
    rgba(
      99,
      142,
      203,
      0.10
    );
}


.input.disabled {

  cursor:
    not-allowed;

  color:
    var(--sa-text-muted);

  opacity:
    0.7;
}


.input.error {

  border-color:
    rgba(
      239,
      68,
      68,
      0.5
    );
}


.field-error {

  color:
    #ef4444;

  font-size:
    0.5rem;

  font-weight:
    600;
}


.field-help {

  color:
    var(--sa-text-muted);

  font-size:
    0.47rem;

  line-height:
    1.45;
}


.form-button {

  width:
    fit-content;

  display:
    inline-flex;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.35rem;

  min-height:
    2.3rem;

  padding:
    0.55rem
    0.82rem;

  border-radius:
    0.65rem;

  font-size:
    0.6rem;

  font-weight:
    800;

  cursor:
    pointer;

  transition:
    transform
    0.18s ease,
    opacity
    0.18s ease,
    background
    0.18s ease,
    border-color
    0.18s ease;
}


.form-button.primary {

  background:
    #395886;

  color:
    white;

  border:
    1px solid
    #395886;
}


.form-button.primary:hover {

  background:
    #638ECB;

  transform:
    translateY(-1px);
}


.form-button.secondary {

  background:
    var(--sa-surface);

  color:
    var(--sa-text);

  border:
    1px solid
    var(--sa-border-strong);
}


.form-button.secondary:hover {

  border-color:
    #638ECB;

  transform:
    translateY(-1px);
}


.form-button:disabled {

  opacity:
    0.5;

  cursor:
    not-allowed;

  transform:
    none;
}


.form-message {

  font-size:
    0.5rem;

  font-weight:
    700;
}


.form-message.success {

  color:
    #059669;
}


.form-message.error {

  color:
    #ef4444;
}


/* ============================================================
   PASSWORD STRENGTH
============================================================ */

.password-strength {

  display:
    flex;

  align-items:
    center;

  gap:
    0.45rem;
}


.password-strength-track {

  flex:
    1;

  height:
    0.2rem;

  overflow:
    hidden;

  border-radius:
    999px;

  background:
    var(--sa-track);
}


.password-strength-value {

  height:
    100%;

  border-radius:
    inherit;

  transition:
    width
    0.2s ease;
}


.password-strength-value.weak {

  background:
    #ef4444;
}


.password-strength-value.fair {

  background:
    #f59e0b;
}


.password-strength-value.strong {

  background:
    #638ECB;
}


.password-strength span {

  color:
    var(--sa-text-muted);

  font-size:
    0.45rem;

  font-weight:
    800;
}


/* ============================================================
   PREFERENCE
============================================================ */

.preference-row {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    1rem;

  padding:
    0.78rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.8rem;

  background:
    var(--sa-surface-soft);
}


.preference-row strong {

  display:
    block;

  color:
    var(--sa-text);

  font-size:
    0.6rem;

  font-weight:
    800;
}


.preference-row span:not(.switch) {

  display:
    block;

  margin-top:
    0.18rem;

  color:
    var(--sa-text-muted);

  font-size:
    0.48rem;
}


/* ============================================================
   SWITCH
============================================================ */

.switch {

  position:
    relative;

  width:
    2.55rem;

  height:
    1.4rem;

  flex-shrink:
    0;

  padding:
    0;

  border:
    0;

  border-radius:
    999px;

  background:
    var(--sa-switch-off);

  cursor:
    pointer;

  transition:
    background
    0.2s ease;
}


.switch.on {

  background:
    #638ECB;
}


.switch span {

  position:
    absolute;

  left:
    0.17rem;

  top:
    0.17rem;

  width:
    1.06rem;

  height:
    1.06rem;

  border-radius:
    50%;

  background:
    white;

  box-shadow:
    0 2px 7px
    rgba(
      15,
      23,
      42,
      0.18
    );

  transition:
    transform
    0.2s
    cubic-bezier(
      .16,
      1,
      .3,
      1
    );
}


.switch.on span {

  transform:
    translateX(
      1.14rem
    );
}


/* ============================================================
   THEME OPTIONS
============================================================ */

.theme-options {

  display:
    flex;

  flex-direction:
    column;

  gap:
    0.4rem;
}


.theme-option {

  width:
    100%;

  display:
    grid;

  grid-template-columns:
    auto
    1fr
    auto;

  align-items:
    center;

  gap:
    0.55rem;

  padding:
    0.62rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.72rem;

  background:
    var(--sa-surface-soft);

  color:
    var(--sa-text);

  text-align:
    left;

  cursor:
    pointer;

  transition:
    border-color
    0.18s ease,
    background
    0.18s ease,
    transform
    0.18s ease;
}


.theme-option:hover {

  transform:
    translateY(-1px);

  border-color:
    var(--sa-border-strong);
}


.theme-option.active {

  border-color:
    rgba(
      99,
      142,
      203,
      0.38
    );

  background:
    var(--sa-theme-active);
}


.theme-icon {

  width:
    1.8rem;

  height:
    1.8rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border-radius:
    0.52rem;

  background:
    var(--sa-surface);

  color:
    var(--sa-text-muted);
}


.theme-icon svg {

  width:
    0.83rem;

  height:
    0.83rem;
}


.theme-text {

  display:
    flex;

  flex-direction:
    column;

  gap:
    0.06rem;
}


.theme-text strong {

  color:
    var(--sa-text);

  font-size:
    0.56rem;

  font-weight:
    800;
}


.theme-text small {

  color:
    var(--sa-text-muted);

  font-size:
    0.45rem;
}


.theme-check {

  width:
    0.74rem;

  height:
    0.74rem;

  color:
    #638ECB;
}


.appearance-message {

  margin-top:
    0.5rem;

  color:
    #638ECB;

  font-size:
    0.48rem;

  font-weight:
    700;
}


/* ============================================================
   ACCOUNT ACTIONS
============================================================ */

.account-actions {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    1rem;

  margin-top:
    0.7rem;

  padding:
    1rem
    1.1rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    1.05rem;

  background:
    var(--sa-surface);

  box-shadow:
    var(--sa-card-shadow);
}


.account-actions h2 {

  margin-top:
    0.18rem;

  color:
    var(--sa-text);

  font-size:
    0.93rem;

  font-weight:
    800;
}


.account-actions p {

  margin-top:
    0.24rem;

  color:
    var(--sa-text-muted);

  font-size:
    0.53rem;
}


.account-buttons {

  display:
    flex;

  align-items:
    center;

  gap:
    0.4rem;

  flex-shrink:
    0;
}


.logout-button,
.delete-button {

  display:
    inline-flex;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.34rem;

  min-height:
    2.25rem;

  padding:
    0.5rem
    0.72rem;

  border-radius:
    0.62rem;

  font-size:
    0.57rem;

  font-weight:
    800;

  cursor:
    pointer;

  transition:
    transform
    0.18s ease,
    background
    0.18s ease,
    border-color
    0.18s ease;
}


.logout-button {

  color:
    var(--sa-text);

  background:
    var(--sa-surface);

  border:
    1px solid
    var(--sa-border-strong);
}


.logout-button:hover {

  transform:
    translateY(-1px);

  border-color:
    #638ECB;
}


.delete-button {

  color:
    #ef4444;

  background:
    rgba(
      239,
      68,
      68,
      0.06
    );

  border:
    1px solid
    rgba(
      239,
      68,
      68,
      0.18
    );
}


.delete-button:hover {

  transform:
    translateY(-1px);

  background:
    rgba(
      239,
      68,
      68,
      0.10
    );
}


.logout-button svg,
.delete-button svg {

  width:
    0.76rem;

  height:
    0.76rem;
}


/* ============================================================
   SPOTLIGHT
============================================================ */

.spotlight-card {

  --spot-x:
    50%;

  --spot-y:
    50%;

  position:
    relative;
}


.spotlight-card::before {

  content:
    '';

  position:
    absolute;

  inset:
    0;

  z-index:
    1;

  pointer-events:
    none;

  border-radius:
    inherit;

  background:
    radial-gradient(
      280px circle at
      var(--spot-x)
      var(--spot-y),
      var(--sa-spotlight),
      transparent 48%
    );

  opacity:
    0;

  transition:
    opacity
    0.35s ease;
}


.spotlight-card:hover::before {

  opacity:
    1;
}


/* ============================================================
   CITY FOOTER
============================================================ */

.city-footer {

  margin-top:
    2.8rem;

  overflow:
    hidden;

  background:
    #243A5A;

  color:
    #D5DEEF;

  border-top:
    1px solid
    rgba(
      255,
      255,
      255,
      0.08
    );
}


.city-footer-main {

  display:
    grid;

  grid-template-columns:
    1.55fr
    1.4fr
    0.9fr
    0.9fr
    0.9fr;

  gap:
    1.5rem;

  padding:
    2.4rem 1.5rem
    2rem;
}


.city-footer-brand {

  display:
    flex;

  align-items:
    flex-start;

  gap:
    0.65rem;
}


.city-footer-logo {

  width:
    2.35rem;

  height:
    2.35rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  padding:
    0.25rem;

  border-radius:
    0.62rem;

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
}


.city-footer-brand span {

  display:
    block;

  margin-top:
    0.12rem;

  color:
    #B1C9EF;

  font-size:
    0.42rem;

  font-weight:
    700;

  letter-spacing:
    0.08em;

  text-transform:
    uppercase;
}


.city-footer-description {

  max-width:
    265px;

  color:
    #B1C9EF;

  font-size:
    0.49rem;

  line-height:
    1.7;
}


.city-footer-column {

  display:
    flex;

  flex-direction:
    column;

  align-items:
    flex-start;

  gap:
    0.45rem;
}


.city-footer-column h3 {

  margin-bottom:
    0.16rem;

  color:
    white;

  font-size:
    0.45rem;

  font-weight:
    800;

  letter-spacing:
    0.1em;

  text-transform:
    uppercase;
}


.city-footer-column a,
.city-footer-column span {

  color:
    #B1C9EF;

  font-size:
    0.47rem;

  font-weight:
    600;

  text-decoration:
    none;

  transition:
    color
    0.2s ease;
}


.city-footer-column a:hover {

  color:
    white;
}


/* Government bar */

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
    1rem;

  padding:
    0.7rem 1.5rem;
}


.government-identity {

  display:
    flex;

  align-items:
    center;

  gap:
    0.4rem;

  color:
    #D5DEEF;

  font-size:
    0.44rem;

  font-weight:
    700;
}


.government-line {

  width:
    3px;

  height:
    1rem;

  border-radius:
    2px;

  background:
    #638ECB;
}


.government-separator {

  color:
    #638ECB;
}


.government-message {

  color:
    #8AAEE0;

  font-size:
    0.42rem;

  font-weight:
    600;
}


.city-footer-bottom {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    1rem;

  padding:
    0.7rem 1.5rem;

  background:
    #182A43;

  color:
    #8AAEE0;

  font-size:
    0.4rem;
}


/* ============================================================
   SPINNER
============================================================ */

.spinner {

  width:
    0.78rem;

  height:
    0.78rem;

  animation:
    spin
    0.8s
    linear
    infinite;
}


.spinner-track {

  opacity:
    0.25;
}


@keyframes spin {

  to {
    transform:
      rotate(360deg);
  }
}


/* ============================================================
   FADE
============================================================ */

@keyframes fadeUp {

  from {
    opacity:
      0;

    transform:
      translateY(10px);
  }

  to {
    opacity:
      1;

    transform:
      translateY(0);
  }
}


.fade-up {

  animation:
    fadeUp
    0.6s
    cubic-bezier(
      .16,
      1,
      .3,
      1
    )
    both;
}


/* ============================================================
   MODAL
============================================================ */

.delete-overlay {

  position:
    fixed;

  inset:
    0;

  z-index:
    200;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  padding:
    1rem;

  background:
    rgba(
      2,
      6,
      23,
      0.62
    );

  backdrop-filter:
    blur(10px);
}


.delete-modal {

  width:
    min(
      100%,
      420px
    );

  padding:
    1.2rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    1.1rem;

  background:
    var(--sa-surface);

  color:
    var(--sa-text);

  box-shadow:
    0 30px 80px
    rgba(
      0,
      0,
      0,
      0.28
    );
}


.delete-icon {

  width:
    2.4rem;

  height:
    2.4rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border-radius:
    0.7rem;

  color:
    #ef4444;

  background:
    rgba(
      239,
      68,
      68,
      0.08
    );
}


.delete-icon svg {

  width:
    1.15rem;

  height:
    1.15rem;
}


.delete-modal h3 {

  margin-top:
    0.75rem;

  color:
    var(--sa-text);

  font-size:
    1rem;

  font-weight:
    800;
}


.delete-modal p {

  margin-top:
    0.35rem;

  color:
    var(--sa-text-muted);

  font-size:
    0.6rem;

  line-height:
    1.6;
}


.delete-actions {

  display:
    flex;

  justify-content:
    flex-end;

  gap:
    0.4rem;

  margin-top:
    1rem;
}


.cancel-delete,
.confirm-delete {

  display:
    inline-flex;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.3rem;

  min-height:
    2.25rem;

  padding:
    0.5rem
    0.72rem;

  border-radius:
    0.62rem;

  font-size:
    0.58rem;

  font-weight:
    800;

  cursor:
    pointer;
}


.cancel-delete {

  color:
    var(--sa-text);

  background:
    var(--sa-surface-soft);

  border:
    1px solid
    var(--sa-border);
}


.confirm-delete {

  color:
    white;

  background:
    #dc2626;

  border:
    1px solid
    #dc2626;
}


.confirm-delete:disabled {

  opacity:
    0.5;

  cursor:
    not-allowed;
}


/* ============================================================
   TRANSITIONS
============================================================ */

.modal-enter-active,
.modal-leave-active {

  transition:
    opacity
    0.2s ease;
}


.modal-enter-from,
.modal-leave-to {

  opacity:
    0;
}


.notice-enter-active,
.notice-leave-active {

  transition:
    opacity
    0.2s ease,
    transform
    0.2s ease;
}


.notice-enter-from,
.notice-leave-to {

  opacity:
    0;

  transform:
    translateY(-5px);
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1050px) {

  .profile-hero-grid {

    grid-template-columns:
      auto
      minmax(0, 1fr);
  }


  .completion-panel {

    grid-column:
      1 / -1;
  }


  .metrics-grid {

    grid-template-columns:
      repeat(
        2,
        minmax(
          0,
          1fr
        )
      );
  }


  .city-footer-main {

    grid-template-columns:
      1.4fr
      1.2fr
      0.9fr;
  }

}


@media (max-width: 760px) {

  .profile-page {

    padding:
      4.6rem 0.75rem 0;
  }


  .profile-hero-grid {

    grid-template-columns:
      1fr;

    text-align:
      center;
  }


  .identity {

    display:
      flex;

    flex-direction:
      column;

    align-items:
      center;
  }


  .identity-meta {

    justify-content:
      center;
  }


  .settings-grid {

    grid-template-columns:
      1fr;
  }


  .account-actions {

    align-items:
      flex-start;

    flex-direction:
      column;
  }


  .account-buttons {

    width:
      100%;
  }


  .logout-button,
  .delete-button {

    flex:
      1;
  }


  .city-footer-main {

    grid-template-columns:
      1fr 1fr;

    padding:
      2rem 1rem
      1.5rem;
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
      0.7rem 1rem;
  }


  .city-footer-bottom {

    align-items:
      flex-start;

    flex-direction:
      column;

    padding:
      0.7rem 1rem;
  }
}


@media (max-width: 500px) {

  .metrics-grid {

    grid-template-columns:
      1fr;
  }


  .section-header {

    align-items:
      flex-start;

    flex-direction:
      column;
  }


  .account-buttons {

    flex-direction:
      column;
  }


  .logout-button,
  .delete-button {

    width:
      100%;
  }


  .delete-actions {

    flex-direction:
      column-reverse;
  }


  .cancel-delete,
  .confirm-delete {

    width:
      100%;
  }


  .city-footer-main {

    grid-template-columns:
      1fr;
  }

}


/* ============================================================
   REDUCED MOTION
============================================================ */

@media (
  prefers-reduced-motion: reduce
) {

  .fade-up {

    animation:
      none !important;
  }

  *,
  *::before,
  *::after {

    transition-duration:
      0.01ms !important;
  }
}
</style>