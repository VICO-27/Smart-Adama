import {
  createClient,
  type SupabaseClient,
  type Session,
  type User,
  type AuthChangeEvent,
  type AuthError,
} from '@supabase/supabase-js'

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY

/**
 * Validates whether Supabase configuration is present and non-empty.
 */
export function isSupabaseConfigured(): boolean {
  if (!supabaseUrl || !supabaseAnonKey) return false
  if (supabaseUrl.includes('your-project.supabase.co') || supabaseAnonKey.includes('your-anon-key')) {
    return false
  }
  try {
    const parsed = new URL(supabaseUrl)
    return Boolean(parsed.protocol && parsed.host)
  } catch {
    return false
  }
}

let supabaseInstance: SupabaseClient | null = null

export function getSupabaseClient(): SupabaseClient {
  if (!isSupabaseConfigured()) {
    throw new Error(
      'Supabase Auth is not configured. Please verify VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY in your .env file.'
    )
  }

  if (!supabaseInstance) {
    supabaseInstance = createClient(supabaseUrl!, supabaseAnonKey!, {
      auth: {
        persistSession: true,
        autoRefreshToken: true,
        detectSessionInUrl: typeof window !== 'undefined',
        storage: typeof window !== 'undefined' ? window.localStorage : undefined,
      },
    })
  }

  return supabaseInstance
}

/**
 * Formats Supabase Auth errors into readable messages while preserving exact details.
 */
export function formatSupabaseError(error: AuthError | Error | unknown): Error {
  if (!error) return new Error('An unexpected authentication error occurred.')

  const err = error as AuthError
  const message = err.message || 'Authentication request failed.'

  // Surface helpful context for common Supabase Auth scenarios without hiding real details
  if (message.toLowerCase().includes('error sending sms') || message.includes('sms_send_failed')) {
    return new Error(
      `SMS delivery failed: ${message}. (Please ensure an SMS provider is enabled in your Supabase project dashboard, or use Email OTP.)`
    )
  }

  if (message.toLowerCase().includes('token has expired') || message.includes('otp_expired')) {
    return new Error('The verification code has expired. Please request a new code.')
  }

  if (message.toLowerCase().includes('invalid token') || message.toLowerCase().includes('token is invalid')) {
    return new Error('Invalid verification code. Please check the 6-digit code and try again.')
  }

  if (message.toLowerCase().includes('rate limit') || message.includes('too_many_requests')) {
    return new Error('Too many requests. Please wait a minute before requesting another code.')
  }

  if (message.toLowerCase().includes('signups not allowed')) {
    return new Error('Signups are currently disabled in the Supabase Auth settings.')
  }

  return new Error(message)
}

/**
 * Maps a Supabase user object into the Smart Adama App.UserProfile structure.
 */
export function mapSupabaseUserToProfile(user: User): App.UserProfile {
  const metadata = user.user_metadata || {}
  const appMetadata = user.app_metadata || {}

  const displayName =
    metadata.full_name ||
    metadata.name ||
    user.phone ||
    (user.email ? user.email.split('@')[0] : 'Learner')

  return {
    id: user.id,
    name: displayName,
    email: user.email || '',
    role: (appMetadata.role as 'learner' | 'admin') || 'learner',
    avatar_url: metadata.avatar_url || null,
    locale: metadata.locale || 'en',
    notify_badges: metadata.notify_badges ?? true,
    created_at: user.created_at || new Date().toISOString(),
    progress_summary: {
      completed_chapters: 0,
      total_chapters: 11,
      completion_pct: 0,
      average_quiz_score: null,
    },
  }
}

/**
 * Centralized Supabase Authentication Service
 */
export const supabaseAuthService = {
  /**
   * Request a 6-digit one-time password via SMS to an E.164 phone number.
   */
  async sendPhoneOtp(phone: string) {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.signInWithOtp({
      phone,
      options: {
        channel: 'sms',
      },
    })
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Verify a 6-digit phone OTP code.
   */
  async verifyPhoneOtp(phone: string, token: string) {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.verifyOtp({
      phone,
      token,
      type: 'sms',
    })
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Request a passwordless magic sign-in link via Email.
   * Ensures the redirect URL points to /auth/callback instead of /login.
   */
  async sendEmailOtp(email: string, targetRedirect?: string) {
    const supabase = getSupabaseClient()
    const callbackUrl = targetRedirect || (typeof window !== 'undefined'
      ? `${window.location.origin}/auth/callback`
      : undefined)

    const { data, error } = await supabase.auth.signInWithOtp({
      email,
      options: {
        shouldCreateUser: true,
        emailRedirectTo: callbackUrl,
      },
    })
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Verify an email OTP code.
   */
  async verifyEmailOtp(email: string, token: string) {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.verifyOtp({
      email,
      token,
      type: 'email',
    })
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Verify an email token_hash from a magic link / confirmation email.
   */
  async verifyTokenHash(
    token_hash: string,
    type: 'email' | 'signup' | 'magiclink' | 'recovery' | 'invite' = 'email'
  ) {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.verifyOtp({
      token_hash,
      type,
    })
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Initiate third-party OAuth redirect via Supabase.
   */
  async signInWithOAuth(provider: 'google' | 'facebook' | 'apple') {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.signInWithOAuth({
      provider,
      options: {
        redirectTo: typeof window !== 'undefined' ? `${window.location.origin}/auth/callback` : undefined,
      },
    })
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Get the current active session.
   */
  async getSession(): Promise<Session | null> {
    if (!isSupabaseConfigured()) return null
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.getSession()
    if (error) throw formatSupabaseError(error)
    return data.session
  },

  /**
   * Get the authenticated user.
   */
  async getUser(): Promise<User | null> {
    if (!isSupabaseConfigured()) return null
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.getUser()
    if (error) throw formatSupabaseError(error)
    return data.user
  },

  /**
   * Manually refresh the user session.
   */
  async refreshSession(): Promise<Session | null> {
    if (!isSupabaseConfigured()) return null
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.refreshSession()
    if (error) throw formatSupabaseError(error)
    return data.session
  },

  /**
   * Exchange PKCE authorization code for an active session.
   */
  async exchangeCodeForSession(code: string) {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.exchangeCodeForSession(code)
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Explicitly set Supabase session from access and refresh tokens.
   */
  async setSession(tokens: { access_token: string; refresh_token: string }) {
    const supabase = getSupabaseClient()
    const { data, error } = await supabase.auth.setSession(tokens)
    if (error) throw formatSupabaseError(error)
    return data
  },

  /**
   * Sign out the active user session.
   */
  async signOut() {
    if (!isSupabaseConfigured()) return
    const supabase = getSupabaseClient()
    const { error } = await supabase.auth.signOut()
    if (error) throw formatSupabaseError(error)
  },

  /**
   * Subscribe to auth state changes (SIGNED_IN, SIGNED_OUT, TOKEN_REFRESHED, etc.).
   */
  onAuthStateChange(callback: (event: AuthChangeEvent, session: Session | null) => void) {
    if (!isSupabaseConfigured()) {
      return {
        data: {
          subscription: {
            unsubscribe: () => {},
          },
        },
      }
    }
    const supabase = getSupabaseClient()
    return supabase.auth.onAuthStateChange(callback)
  },
}
