import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useAuthState } from '../useAuthState'
import * as supabaseAuth from '@/services/supabaseAuth'

vi.mock('@/services/supabaseAuth', () => {
  return {
    isSupabaseConfigured: vi.fn(() => true),
    supabaseAuthService: {
      sendPhoneOtp: vi.fn().mockResolvedValue({ user: null, session: null }),
      verifyPhoneOtp: vi.fn().mockResolvedValue({ user: { id: 'test-phone-id' }, session: { access_token: 'fake-phone-token' } }),
      sendEmailOtp: vi.fn().mockResolvedValue({ user: null, session: null }),
      verifyEmailOtp: vi.fn().mockResolvedValue({ user: { id: 'test-email-id' }, session: { access_token: 'fake-email-token' } }),
      verifyTokenHash: vi.fn().mockResolvedValue({ user: { id: 'test-token-hash-id' }, session: { access_token: 'fake-token-hash' } }),
      signInWithOAuth: vi.fn().mockResolvedValue({ provider: 'google', url: 'https://example.com' }),
      signInAnonymously: vi.fn().mockResolvedValue({ user: { id: 'test-anon-id', is_anonymous: true }, session: { access_token: 'fake-anon-token' } }),
      getSession: vi.fn().mockResolvedValue(null),
      getUser: vi.fn().mockResolvedValue(null),
      signOut: vi.fn().mockResolvedValue(undefined),
      onAuthStateChange: vi.fn(() => ({ data: { subscription: { unsubscribe: vi.fn() } } })),
    },
    mapSupabaseUserToProfile: vi.fn((u) => ({ id: u.id, name: 'Test User', email: u.email })),
  }
})

describe('useAuthState Composable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('initializes with Phone channel as default on input step', () => {
    const authState = useAuthState()
    expect(authState.channel.value).toBe('phone')
    expect(authState.isPhoneChannel.value).toBe(true)
    expect(authState.isEmailChannel.value).toBe(false)
    expect(authState.step.value).toBe('input')
    expect(authState.isInputStep.value).toBe(true)
    expect(authState.isOtpStep.value).toBe(false)
    expect(authState.isEmailSentStep.value).toBe(false)
  })

  it('switches between Phone and Email channels cleanly', () => {
    const authState = useAuthState()
    authState.switchChannel('email')
    expect(authState.channel.value).toBe('email')
    expect(authState.isEmailChannel.value).toBe(true)
    expect(authState.isPhoneChannel.value).toBe(false)
    expect(authState.step.value).toBe('input')

    authState.switchChannel('phone')
    expect(authState.channel.value).toBe('phone')
    expect(authState.isPhoneChannel.value).toBe(true)
  })

  it('rejects invalid email addresses without calling Supabase', async () => {
    const authState = useAuthState()
    authState.switchChannel('email')
    authState.email.value = 'invalid-email'

    const success = await authState.requestOtp()
    expect(success).toBe(false)
    expect(authState.errorMessage.value).toContain('valid email address')
    expect(supabaseAuth.supabaseAuthService.sendEmailOtp).not.toHaveBeenCalled()
  })

  it('transitions to email_sent step on valid email request (Magic Link flow)', async () => {
    const authState = useAuthState()
    authState.switchChannel('email')
    authState.email.value = 'user@example.com'

    const success = await authState.requestOtp()
    expect(success).toBe(true)
    expect(supabaseAuth.supabaseAuthService.sendEmailOtp).toHaveBeenCalledWith('user@example.com')
    expect(authState.step.value).toBe('email_sent')
    expect(authState.isEmailSentStep.value).toBe(true)
    expect(authState.isOtpStep.value).toBe(false)
    expect(authState.resendCooldown.value).toBe(60)
  })

  it('allows changing email to return to input step', async () => {
    const authState = useAuthState()
    authState.switchChannel('email')
    authState.email.value = 'user@example.com'
    await authState.requestOtp()

    expect(authState.step.value).toBe('email_sent')
    authState.changeTarget()
    expect(authState.step.value).toBe('input')
    expect(authState.isInputStep.value).toBe(true)
  })

  it('transitions to otp step on valid phone request (Phone OTP flow preserved)', async () => {
    const authState = useAuthState()
    authState.phone.value = '0912345678'

    const success = await authState.requestOtp()
    expect(success).toBe(true)
    expect(supabaseAuth.supabaseAuthService.sendPhoneOtp).toHaveBeenCalledWith('+251912345678')
    expect(authState.step.value).toBe('otp')
    expect(authState.isOtpStep.value).toBe(true)
    expect(authState.isEmailSentStep.value).toBe(false)
  })

  it('triggers guest anonymous auth and returns session data', async () => {
    const authState = useAuthState()
    const result = await authState.triggerGuestAuth()

    expect(result.success).toBe(true)
    expect(supabaseAuth.supabaseAuthService.signInAnonymously).toHaveBeenCalled()
    expect(result.data?.session?.access_token).toBe('fake-anon-token')
  })

  it('handles guest anonymous auth errors gracefully', async () => {
    vi.mocked(supabaseAuth.supabaseAuthService.signInAnonymously).mockRejectedValueOnce(
      new Error('Anonymous sign-in disabled')
    )

    const authState = useAuthState()
    const result = await authState.triggerGuestAuth()

    expect(result.success).toBe(false)
    expect(authState.errorMessage.value).toBe('Anonymous sign-in disabled')
  })
})
