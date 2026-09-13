export {
  isSupabaseConfigured,
  getSupabaseClient,
  formatSupabaseError,
  mapSupabaseUserToProfile,
  supabaseAuthService,
} from '@/services/supabaseAuth'

export const sendPhoneOtp = (phone: string) =>
  import('@/services/supabaseAuth').then((m) => m.supabaseAuthService.sendPhoneOtp(phone))
export const verifyPhoneOtp = (phone: string, token: string) =>
  import('@/services/supabaseAuth').then((m) => m.supabaseAuthService.verifyPhoneOtp(phone, token))
export const sendEmailOtp = (email: string) =>
  import('@/services/supabaseAuth').then((m) => m.supabaseAuthService.sendEmailOtp(email))
export const verifyEmailOtp = (email: string, token: string) =>
  import('@/services/supabaseAuth').then((m) => m.supabaseAuthService.verifyEmailOtp(email, token))
export const signInWithSocial = (provider: 'google' | 'facebook' | 'apple') =>
  import('@/services/supabaseAuth').then((m) => m.supabaseAuthService.signInWithOAuth(provider))
