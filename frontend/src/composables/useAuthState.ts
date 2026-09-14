import { ref, computed, onUnmounted, getCurrentInstance } from 'vue'
import {
  isSupabaseConfigured,
  supabaseAuthService,
  mapSupabaseUserToProfile,
} from '@/services/supabaseAuth'
import { normalizeEthiopianPhone } from '@/utils/phone'

export type AuthChannel = 'phone' | 'email'
export type AuthStep = 'input' | 'otp' | 'email_sent'
export type AuthStatus = 'idle' | 'sending_otp' | 'verifying_otp' | 'success' | 'error'

export function useAuthState() {
  // Primary state machine
  const channel = ref<AuthChannel>('phone')
  const step = ref<AuthStep>('input')
  const status = ref<AuthStatus>('idle')

  // Inputs
  const phone = ref('')
  const email = ref('')
  const otpDigits = ref<string[]>(['', '', '', '', '', ''])

  // Feedback & Validation
  const errorMessage = ref<string | null>(null)
  const isInvalidOtp = ref(false)

  // Resend Timer
  const resendCooldown = ref(0)
  let cooldownTimer: ReturnType<typeof setInterval> | null = null

  // Computed properties
  const isPhoneChannel = computed(() => channel.value === 'phone')
  const isEmailChannel = computed(() => channel.value === 'email')
  const isInputStep = computed(() => step.value === 'input')
  const isOtpStep = computed(() => step.value === 'otp')
  const isEmailSentStep = computed(() => step.value === 'email_sent')
  const isLoading = computed(
    () => status.value === 'sending_otp' || status.value === 'verifying_otp'
  )

  const normalizedPhoneData = computed(() => normalizeEthiopianPhone(phone.value))
  const targetIdentifierDisplay = computed(() => {
    if (channel.value === 'phone') {
      return normalizedPhoneData.value.display || phone.value
    }
    return email.value.trim()
  })

  const fullOtpCode = computed(() => otpDigits.value.join(''))
  const isOtpComplete = computed(() => fullOtpCode.value.length === 6 && /^\d{6}$/.test(fullOtpCode.value))

  // ── Cooldown management ───────────────────────────────────────────────
  function startCooldown(seconds = 60) {
    if (cooldownTimer) clearInterval(cooldownTimer)
    resendCooldown.value = seconds

    cooldownTimer = setInterval(() => {
      if (resendCooldown.value > 0) {
        resendCooldown.value--
      } else {
        if (cooldownTimer) clearInterval(cooldownTimer)
        cooldownTimer = null
      }
    }, 1000)
  }

  function clearErrors() {
    errorMessage.value = null
    isInvalidOtp.value = false
  }

  // ── Switching Channels & Steps ─────────────────────────────────────────
  function switchChannel(newChannel: AuthChannel) {
    clearErrors()
    channel.value = newChannel
    step.value = 'input'
    status.value = 'idle'
    otpDigits.value = ['', '', '', '', '', '']
  }

  function changeTarget() {
    clearErrors()
    step.value = 'input'
    status.value = 'idle'
    otpDigits.value = ['', '', '', '', '', '']
  }

  // ── Send OTP (Default Phone or Email) ──────────────────────────────────
  async function requestOtp(): Promise<boolean> {
    clearErrors()

    if (channel.value === 'phone') {
      const validation = normalizedPhoneData.value
      if (!validation.isValid) {
        errorMessage.value = validation.error || 'Please enter a valid phone number.'
        status.value = 'error'
        return false
      }
    } else {
      const trimmedEmail = email.value.trim()
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!trimmedEmail || !emailRegex.test(trimmedEmail)) {
        errorMessage.value = 'Please enter a valid email address.'
        status.value = 'error'
        return false
      }
    }

    status.value = 'sending_otp'

    try {
      if (!isSupabaseConfigured()) {
        throw new Error(
          'Supabase Auth is not yet configured. Please set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY in your frontend .env file.'
        )
      }

      if (channel.value === 'phone') {
        await supabaseAuthService.sendPhoneOtp(normalizedPhoneData.value.e164)
        step.value = 'otp'
      } else {
        await supabaseAuthService.sendEmailOtp(email.value.trim())
        step.value = 'email_sent'
      }

      status.value = 'idle'
      otpDigits.value = ['', '', '', '', '', '']
      startCooldown(60)
      return true
    } catch (err: any) {
      status.value = 'error'
      errorMessage.value = err.message || (channel.value === 'phone' ? 'Failed to send verification code. Please try again.' : 'Failed to send sign-in link. Please try again.')
      return false
    }
  }

  // ── Verify OTP ─────────────────────────────────────────────────────────
  async function verifyOtp(codeToVerify?: string): Promise<{ success: boolean; data?: any }> {
    clearErrors()

    const code = codeToVerify || fullOtpCode.value
    if (!code || code.length !== 6 || !/^\d{6}$/.test(code)) {
      errorMessage.value = 'Please enter a complete 6-digit verification code.'
      isInvalidOtp.value = true
      status.value = 'error'
      return { success: false }
    }

    status.value = 'verifying_otp'

    try {
      if (!isSupabaseConfigured()) {
        throw new Error(
          'Supabase Auth is not yet configured. Please set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY in your frontend .env file.'
        )
      }

      let data: any
      if (channel.value === 'phone') {
        data = await supabaseAuthService.verifyPhoneOtp(normalizedPhoneData.value.e164, code)
      } else {
        data = await supabaseAuthService.verifyEmailOtp(email.value.trim(), code)
      }

      status.value = 'success'
      return { success: true, data }
    } catch (err: any) {
      status.value = 'error'
      isInvalidOtp.value = true
      errorMessage.value = err.message || 'Invalid or expired verification code. Please try again.'
      return { success: false }
    }
  }

  // ── Resend OTP ─────────────────────────────────────────────────────────
  async function resendOtp(): Promise<boolean> {
    if (resendCooldown.value > 0 || isLoading.value) return false
    return requestOtp()
  }

  // ── Social OAuth ───────────────────────────────────────────────────────
  async function triggerSocialAuth(provider: 'google' | 'facebook' | 'apple') {
    clearErrors()
    try {
      if (!isSupabaseConfigured()) {
        throw new Error(
          'Supabase Auth is not yet configured. Please set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY in your frontend .env file.'
        )
      }
      await supabaseAuthService.signInWithOAuth(provider)
    } catch (err: any) {
      status.value = 'error'
      errorMessage.value = err.message || `Failed to initiate ${provider} login.`
    }
  }

  // ── Anonymous Guest Auth ───────────────────────────────────────────────
  async function triggerGuestAuth() {
    clearErrors()
    status.value = 'sending_otp'
    try {
      if (!isSupabaseConfigured()) {
        throw new Error(
          'Supabase Auth is not yet configured. Please set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY in your frontend .env file.'
        )
      }
      const data = await supabaseAuthService.signInAnonymously()
      status.value = 'success'
      return { success: true, data }
    } catch (err: any) {
      status.value = 'error'
      errorMessage.value = err.message || 'Failed to continue as guest.'
      return { success: false, error: err }
    }
  }

  if (getCurrentInstance()) {
    onUnmounted(() => {
      if (cooldownTimer) {
        clearInterval(cooldownTimer)
        cooldownTimer = null
      }
    })
  }

  return {
    // State
    channel,
    step,
    status,
    phone,
    email,
    otpDigits,
    errorMessage,
    isInvalidOtp,
    resendCooldown,

    // Computeds
    isPhoneChannel,
    isEmailChannel,
    isInputStep,
    isOtpStep,
    isEmailSentStep,
    isLoading,
    normalizedPhoneData,
    targetIdentifierDisplay,
    fullOtpCode,
    isOtpComplete,

    // Actions
    switchChannel,
    changeTarget,
    requestOtp,
    verifyOtp,
    resendOtp,
    triggerSocialAuth,
    triggerGuestAuth,
    clearErrors,
  }
}
