<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { useAuthState } from '@/composables/useAuthState'
import { mapSupabaseUserToProfile } from '@/services/supabaseAuth'
import SaInput from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'
import OtpInput from '@/components/auth/OtpInput.vue'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const { initializeTheme } = useTheme()
const router = useRouter()
const { t } = useI18n()

// View state for primary selection vs email input sub-flow
const activeView = ref<'providers' | 'email_input'>('providers')
const isGoogleLoading = ref(false)
const isGuestLoading = ref(false)

// Structured authentication state machine
const {
  channel,
  step,
  phone,
  email,
  otpDigits,
  errorMessage,
  isInvalidOtp,
  resendCooldown,
  isPhoneChannel,
  isEmailChannel,
  isInputStep,
  isOtpStep,
  isEmailSentStep,
  isLoading,
  normalizedPhoneData,
  targetIdentifierDisplay,
  switchChannel,
  changeTarget,
  requestOtp,
  verifyOtp,
  resendOtp,
  triggerSocialAuth,
  triggerGuestAuth,
  clearErrors,
} = useAuthState()

// Reset active view whenever the modal opens
watch(
  () => auth.isAuthModalOpen,
  (isOpen) => {
    if (isOpen) {
      activeView.value = 'providers'
      clearErrors()
    }
  }
)

// Automatically dismiss modal and redirect if session completes (e.g. from magic link in another tab)
watch(
  () => auth.isAuthenticated,
  (isAuth) => {
    if (isAuth) {
      auth.closeAuthModal()
      const redirect =
        auth.authRedirectUrl && auth.authRedirectUrl !== '/login'
          ? auth.authRedirectUrl
          : '/dashboard'
      router.push(redirect)
    }
  }
)

async function handleGoogleSignIn() {
  if (isGoogleLoading.value || isGuestLoading.value || isLoading.value) return
  isGoogleLoading.value = true
  try {
    await triggerSocialAuth('google')
  } finally {
    // Graceful fallback if redirect didn't unload page
    setTimeout(() => {
      isGoogleLoading.value = false
    }, 3000)
  }
}

function openEmailFlow() {
  clearErrors()
  switchChannel('email')
  activeView.value = 'email_input'
}

function backToProviders() {
  clearErrors()
  activeView.value = 'providers'
}

async function handleSendOtp() {
  await requestOtp()
}

async function handleVerifyOtp(code?: string) {
  const result = await verifyOtp(code)
  if (result.success) {
    if (result.data?.session?.access_token) {
      auth.setToken(result.data.session.access_token)
      if (result.data.session.user) {
        auth.user = mapSupabaseUserToProfile(result.data.session.user)
      }
      // Sync backend profile in background
      auth.fetchMe().catch(() => {})
    }
    const redirect =
      auth.authRedirectUrl && auth.authRedirectUrl !== '/login'
        ? auth.authRedirectUrl
        : '/dashboard'
    auth.closeAuthModal()
    router.push(redirect)
  }
}

async function handleContinueAsGuest() {
  if (isGuestLoading.value || isGoogleLoading.value || isLoading.value) return
  isGuestLoading.value = true
  try {
    const result = await triggerGuestAuth()
    if (result.success) {
      if (result.data?.session?.access_token) {
        auth.setToken(result.data.session.access_token)
        if (result.data.session.user) {
          auth.user = mapSupabaseUserToProfile(result.data.session.user)
        }
        // Sync backend profile in background
        auth.fetchMe().catch(() => {})
      }
      const redirect =
        auth.authRedirectUrl && auth.authRedirectUrl !== '/login'
          ? auth.authRedirectUrl
          : '/dashboard'
      auth.closeAuthModal()
      router.push(redirect)
    }
  } finally {
    isGuestLoading.value = false
  }
}

function handleBack() {
  changeTarget()
  activeView.value = 'providers'
}

onMounted(() => {
  initializeTheme()
  if (auth.authRedirectUrl) {
    auth.error = t('auth.err_must_signin')
  }
})
</script>

<template>
  <!-- Modal Overlay Background (Blur effect) -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade">

    <!-- Branded Card Container with dark navy #273C5B color -->
    <div
      class="w-full max-w-md rounded-2xl shadow-2xl p-6 sm:p-7 text-white relative border border-white/10"
      style="background-color: #273C5B;"
    >
      <!-- Close Button -->
      <button
        @click="auth.closeAuthModal()"
        type="button"
        class="absolute top-4 right-4 text-blue-200/80 hover:text-white transition-colors bg-white/10 hover:bg-white/20 rounded-full p-1.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
        title="Close"
        aria-label="Close modal"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- ================= HEADER SECTION ================= -->
      <!-- 1. Primary Providers Header -->
      <div v-if="isInputStep && activeView === 'providers'" class="flex flex-col items-center justify-center mb-6 text-center">
        <img src="/logo.png" alt="Smart Adama Logo" class="w-12 h-12 object-contain mb-2.5 drop-shadow-md" />
        <h1 class="text-xl font-bold tracking-tight text-white">
          {{ $t('auth.welcome_to_smart_adama') }}
        </h1>
        <p class="text-xs text-blue-100/80 mt-1 max-w-xs leading-relaxed">
          {{ $t('auth.welcome_desc') }}
        </p>
      </div>

      <!-- 2. Email Input Step Header with Back Link -->
      <div v-else-if="isInputStep && activeView === 'email_input'" class="mb-5">
        <button
          @click="backToProviders"
          type="button"
          class="text-xs text-blue-200/90 hover:text-white font-medium flex items-center gap-1.5 transition-colors mb-3 focus:outline-none"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>{{ $t('auth.all_signin_options') }}</span>
        </button>
        <div class="flex flex-col items-center justify-center text-center">
          <img src="/logo.png" alt="Smart Adama Logo" class="w-10 h-10 object-contain mb-2 drop-shadow-md" />
          <h1 class="text-xl font-bold tracking-tight text-white">
            {{ $t('auth.sign_in_email') }}
          </h1>
          <p class="text-xs text-blue-100/80 mt-1 max-w-xs leading-relaxed">
            {{ $t('auth.enter_email_desc') }}
          </p>
        </div>
      </div>

      <!-- 3. Email Sent Header -->
      <div v-else-if="isEmailSentStep" class="flex flex-col items-center justify-center mb-5 text-center">
        <img src="/logo.png" alt="Smart Adama Logo" class="w-10 h-10 object-contain mb-2 drop-shadow-md" />
        <h1 class="text-xl font-bold tracking-tight text-white">
          {{ $t('auth.check_email_title') }}
        </h1>
        <p class="text-xs text-blue-100/80 mt-1 max-w-xs leading-relaxed">
          {{ $t('auth.check_email_link_sent_to') }}
          <span class="font-semibold text-white font-mono ml-1">{{ targetIdentifierDisplay }}</span>
        </p>
      </div>

      <!-- 4. OTP Verification Header -->
      <div v-else-if="isOtpStep" class="flex flex-col items-center justify-center mb-5 text-center">
        <img src="/logo.png" alt="Smart Adama Logo" class="w-10 h-10 object-contain mb-2 drop-shadow-md" />
        <h1 class="text-xl font-bold tracking-tight text-white">
          {{ $t('auth.enter_verification_code') }}
        </h1>
        <p class="text-xs text-blue-100/80 mt-1 max-w-xs leading-relaxed">
          {{ $t('auth.code_sent_to') }}
          <span class="font-semibold text-white font-mono ml-1">{{ targetIdentifierDisplay }}</span>
        </p>
      </div>

      <!-- ================= ERROR BANNERS ================= -->
      <div
        v-if="errorMessage"
        class="mb-4 text-xs sm:text-sm font-medium p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 flex items-start gap-2.5"
        role="alert"
      >
        <svg class="w-4 h-4 text-red-300 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span class="leading-snug">{{ errorMessage }}</span>
      </div>

      <div
        v-else-if="auth.error"
        class="mb-4 text-xs sm:text-sm font-medium p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 flex items-start gap-2.5"
        role="alert"
      >
        <svg class="w-4 h-4 text-red-300 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span class="leading-snug">{{ auth.error }}</span>
      </div>

      <!-- ================= VIEW 1: PRIMARY PROVIDER SELECTION ================= -->
      <template v-if="isInputStep && activeView === 'providers'">
        <div class="flex flex-col gap-3">
          <!-- 1. Continue with Google (Prominent Primary Button) -->
          <button
            @click="handleGoogleSignIn"
            type="button"
            :disabled="isGoogleLoading || isGuestLoading || isLoading"
            class="w-full h-12 flex items-center justify-center gap-3 px-4 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-800 font-semibold text-sm shadow-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.99]"
          >
            <svg v-if="!isGoogleLoading" class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <svg v-else class="w-4 h-4 animate-spin text-slate-700 flex-shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span>{{ isGoogleLoading ? $t('auth.signing_in') : $t('auth.continue_with_google') }}</span>
          </button>

          <!-- 2. Continue with Email (Elevated Navy Button) -->
          <button
            @click="openEmailFlow"
            type="button"
            :disabled="isGoogleLoading || isGuestLoading || isLoading"
            class="w-full h-12 flex items-center justify-center gap-3 px-4 rounded-xl bg-white/10 hover:bg-white/15 active:bg-white/20 border border-white/20 hover:border-white/30 text-white font-semibold text-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.99]"
          >
            <svg class="w-5 h-5 flex-shrink-0 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <span>{{ $t('auth.continue_with_email') }}</span>
          </button>

          <!-- 3. Continue as Guest (Active Anonymous Button) -->
          <button
            @click="handleContinueAsGuest"
            type="button"
            :disabled="isGoogleLoading || isGuestLoading || isLoading"
            class="w-full h-12 flex items-center justify-center gap-3 px-4 rounded-xl bg-white/10 hover:bg-white/15 active:bg-white/20 border border-white/20 hover:border-white/30 text-white font-semibold text-sm transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-white/50 disabled:opacity-60 disabled:cursor-not-allowed active:scale-[0.99]"
          >
            <svg v-if="!isGuestLoading" class="w-5 h-5 flex-shrink-0 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <svg v-else class="w-4 h-4 animate-spin text-white flex-shrink-0" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span>{{ isGuestLoading ? $t('auth.continuing_as_guest') : $t('auth.continue_as_guest') }}</span>
          </button>
        </div>

        <!-- Coming Soon Divider -->
        <div class="relative flex items-center py-4 my-1">
          <div class="flex-grow border-t border-white/15"></div>
          <span class="flex-shrink-0 mx-3 text-[11px] font-semibold text-white/50 uppercase tracking-widest">
            {{ $t('auth.coming_soon') }}
          </span>
          <div class="flex-grow border-t border-white/15"></div>
        </div>

        <!-- Coming Soon Providers (Horizontal compact row) -->
        <div class="grid grid-cols-3 gap-2 sm:gap-2.5">
          <!-- Phone (Disabled) -->
          <div
            class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] text-white/45 select-none cursor-not-allowed opacity-75 transition-opacity"
            role="button"
            aria-disabled="true"
            tabindex="-1"
            :title="$t('auth.provider_coming_soon', { provider: $t('auth.provider_phone') })"
          >
            <svg class="w-4 h-4 text-white/45 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span class="text-xs font-medium text-white/70">{{ $t('auth.provider_phone') }}</span>
            <span class="text-[9px] uppercase tracking-wider text-white/40 font-medium mt-0.5">{{ $t('auth.coming_soon') }}</span>
          </div>

          <!-- Facebook (Disabled) -->
          <div
            class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] text-white/45 select-none cursor-not-allowed opacity-75 transition-opacity"
            role="button"
            aria-disabled="true"
            tabindex="-1"
            :title="$t('auth.provider_coming_soon', { provider: $t('auth.provider_facebook') })"
          >
            <svg class="w-4 h-4 text-white/45 mb-1" fill="currentColor" viewBox="0 0 24 24">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            <span class="text-xs font-medium text-white/70">{{ $t('auth.provider_facebook') }}</span>
            <span class="text-[9px] uppercase tracking-wider text-white/40 font-medium mt-0.5">{{ $t('auth.coming_soon') }}</span>
          </div>

          <!-- Apple (Disabled) -->
          <div
            class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] text-white/45 select-none cursor-not-allowed opacity-75 transition-opacity"
            role="button"
            aria-disabled="true"
            tabindex="-1"
            :title="$t('auth.provider_coming_soon', { provider: $t('auth.provider_apple') })"
          >
            <svg class="w-4 h-4 text-white/45 mb-1" fill="currentColor" viewBox="0 0 384 512">
              <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
            </svg>
            <span class="text-xs font-medium text-white/70">{{ $t('auth.provider_apple') }}</span>
            <span class="text-[9px] uppercase tracking-wider text-white/40 font-medium mt-0.5">{{ $t('auth.coming_soon') }}</span>
          </div>
        </div>
      </template>

      <!-- ================= VIEW 2: EMAIL INPUT SUB-FLOW ================= -->
      <template v-else-if="isInputStep && activeView === 'email_input'">
        <form @submit.prevent="handleSendOtp" novalidate class="flex flex-col gap-4">
          <div>
            <label class="block text-sm font-semibold text-white mb-1.5">
              {{ $t('auth.email') }}
            </label>
            <SaInput
              v-model="email"
              type="email"
              placeholder="you@example.com"
              autocomplete="email"
              autofocus
              required
            />
          </div>

          <!-- Primary Submit Button -->
          <SaButton
            type="submit"
            :loading="isLoading"
            class="w-full justify-center font-bold text-base rounded-xl mt-1 py-3"
            style="background-color: #ffffff; color: #1e293b;"
          >
            {{ $t('auth.send_magic_link') }}
          </SaButton>
        </form>
      </template>

      <!-- ================= STEP 2: EMAIL MAGIC LINK SENT ================= -->
      <template v-else-if="isEmailSentStep">
        <div class="flex flex-col gap-4">
          <!-- Back / Change Target link -->
          <div class="flex items-center justify-between -mt-2">
            <button
              @click="handleBack"
              type="button"
              class="text-xs text-blue-200 hover:text-white font-medium flex items-center gap-1.5 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>{{ $t('auth.all_signin_options') }}</span>
            </button>
          </div>

          <!-- Prompt Card with explicit check email instruction -->
          <div class="p-4 rounded-xl bg-white/10 border border-white/20 text-center flex flex-col items-center gap-2.5">
            <div class="w-12 h-12 rounded-full bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-200 shadow-inner">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </div>

            <p class="text-sm font-semibold text-white leading-snug">
              {{ $t('auth.check_email_heading') }}
            </p>

            <p class="text-xs text-blue-100/75 leading-relaxed max-w-xs">
              {{ $t('auth.check_email_desc') }}
            </p>
          </div>

          <!-- Resend Section with Cooldown -->
          <div class="flex items-center justify-center text-xs text-center mt-1">
            <span v-if="resendCooldown > 0" class="text-blue-200/70 font-mono">
              {{ $t('auth.resend_link_in', { seconds: resendCooldown }) }}
            </span>
            <button
              v-else
              @click="resendOtp"
              type="button"
              :disabled="isLoading"
              class="font-semibold text-white hover:underline focus:outline-none transition-colors"
            >
              {{ $t('auth.resend_email_link') }}
            </button>
          </div>

          <!-- Change Email Button -->
          <SaButton
            type="button"
            class="w-full justify-center font-bold text-sm rounded-xl mt-1 py-2.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 transition-all"
            @click="handleBack"
          >
            {{ $t('auth.change_email') }}
          </SaButton>
        </div>
      </template>

      <!-- ================= STEP 3: OTP VERIFICATION STEP (PRESERVED) ================= -->
      <template v-else-if="isOtpStep">
        <div class="flex flex-col gap-4">
          <!-- Back / Change Target link -->
          <div class="flex items-center justify-between -mt-2">
            <button
              @click="handleBack"
              type="button"
              class="text-xs text-blue-200 hover:text-white font-medium flex items-center gap-1 transition-colors"
            >
              {{ isPhoneChannel ? $t('auth.change_phone') : $t('auth.change_email') }}
            </button>
          </div>

          <!-- OTP Input Component -->
          <div>
            <label class="block text-sm font-semibold text-white mb-2 text-center">
              {{ $t('auth.enter_six_digit') }}
            </label>

            <OtpInput
              v-model="otpDigits"
              :disabled="isLoading"
              :invalid="isInvalidOtp"
              @complete="(code) => handleVerifyOtp(code)"
            />
          </div>

          <!-- Resend Section with Cooldown -->
          <div class="flex items-center justify-center text-xs text-center mt-1">
            <span v-if="resendCooldown > 0" class="text-blue-200/70 font-mono">
              {{ $t('auth.resend_in', { seconds: resendCooldown }) }}
            </span>
            <button
              v-else
              @click="resendOtp"
              type="button"
              :disabled="isLoading"
              class="font-semibold text-white hover:underline focus:outline-none transition-colors"
            >
              {{ $t('auth.resend_code') }}
            </button>
          </div>

          <!-- Verify & Continue Button -->
          <SaButton
            type="button"
            :loading="isLoading"
            class="w-full justify-center font-bold text-base rounded-xl mt-2 py-3"
            style="background-color: #ffffff; color: #1e293b;"
            @click="() => handleVerifyOtp()"
          >
            {{ $t('auth.verify_and_continue') }}
          </SaButton>
        </div>
      </template>

    </div>
  </div>
</template>