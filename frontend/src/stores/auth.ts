import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, type LoginPayload, type RegisterPayload } from '@/api/auth'
import { userApi } from '@/api/user'
import { isApiError, fieldError } from '@/api/client'
import {
  isSupabaseConfigured,
  supabaseAuthService,
  mapSupabaseUserToProfile,
} from '@/services/supabaseAuth'

const TOKEN_KEY = 'sa_token'

export const useAuthStore = defineStore('auth', () => {
  const user    = ref<App.UserProfile | null>(null)
  const token   = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const loading = ref(false)
  const error   = ref<string | null>(null)

  // Modal State
  const isAuthModalOpen = ref(false)
  const authModalMode = ref<'login' | 'register'>('login')
  const authRedirectUrl = ref<string | null>(null)

  // Per-field validation errors from 422 responses
  const fieldErrors = ref<Record<string, string>>({})

  const isAuthenticated = computed(() => !!token.value)
// Check the boolean is_admin flag provided by the backend API
// Tell TS to ignore the missing property using 'as any'
  const isAdmin = computed(() => (user.value as any)?.is_admin === true || user.value?.role === 'admin')  // ── Helpers ────────────────────────────────────────────────────────────────

  function setToken(t: string) {
    token.value = t
    localStorage.setItem(TOKEN_KEY, t)
  }

  function clearAuth() {
    user.value    = null
    token.value   = null
    error.value   = null
    fieldErrors.value = {}
    localStorage.removeItem(TOKEN_KEY)
    closeAuthModal()
  }

  function clearErrors() {
    error.value = null
    fieldErrors.value = {}
  }

  function openAuthModal(mode: 'login' | 'register' = 'login', redirectUrl: string | null = null) {
    authModalMode.value = mode
    authRedirectUrl.value = redirectUrl
    isAuthModalOpen.value = true
  }

  function closeAuthModal() {
    isAuthModalOpen.value = false
    authRedirectUrl.value = null
  }

  function handleError(e: unknown, fallback: string) {
    if (isApiError(e)) {
      error.value = e.userMessage ?? fallback
      // Flatten field errors to first-message-per-field for easy binding
      fieldErrors.value = Object.fromEntries(
        Object.entries(e.fieldErrors ?? {}).map(([k, v]) => [k, v[0] ?? ''])
      )
    } else {
      error.value = fallback
    }
  }

  // ── Auth actions ───────────────────────────────────────────────────────────

  async function register(payload: RegisterPayload) {
    loading.value = true
    clearErrors()
    try {
      const { data } = await authApi.register(payload)
      setToken(data.token)
      user.value = data.user as App.UserProfile
    } catch (e) {
      handleError(e, 'Registration failed. Please try again.')
      throw e
    } finally {
      loading.value = false
    }
  }

  async function login(payload: LoginPayload) {
    loading.value = true
    clearErrors()
    try {
      const { data } = await authApi.login(payload)
      setToken(data.token)
      user.value = data.user as App.UserProfile
    } catch (e) {
      handleError(e, 'Invalid credentials.')
      throw e
    } finally {
      loading.value = false
    }
  }

  // ── Realtime Supabase Auth Listener ───────────────────────────────────────
  if (isSupabaseConfigured()) {
    try {
      supabaseAuthService.onAuthStateChange((event, session) => {
        if (event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') {
          if (session?.access_token) {
            setToken(session.access_token)
          }
          if (session?.user && !user.value) {
            user.value = mapSupabaseUserToProfile(session.user)
          }
        } else if (event === 'SIGNED_OUT') {
          clearAuth()
        }
      })
    } catch (e) {
      console.warn('Could not initialize Supabase auth listener:', e)
    }
  }

  async function logout() {
    try {
      if (isSupabaseConfigured()) {
        await supabaseAuthService.signOut()
      }
    } catch {
      // Swallow
    }
    try {
      await authApi.logout()
    } catch {
      // Swallow — token cleared regardless
    } finally {
      clearAuth()
      window.location.href = '/'
    }
  }

  /**
   * Restore session on app boot if a token exists in localStorage or Supabase.
   * Called once from the router beforeEach guard.
   */
  async function fetchMe() {
    // 1. Check Supabase active session first
    if (isSupabaseConfigured()) {
      try {
        const session = await supabaseAuthService.getSession()
        if (session?.access_token) {
          setToken(session.access_token)
          if (session.user) {
            user.value = mapSupabaseUserToProfile(session.user)
          }
        }
      } catch (err) {
        console.warn('Supabase session fetch error:', err)
      }
    }

    if (!token.value) return

    // 2. Fetch full backend user profile if available
    try {
      const { data } = await authApi.me()
      if (data?.user) {
        user.value = {
          ...user.value,
          ...data.user,
        }
      }
    } catch {
      // If we don't have a Supabase user session either, clear auth
      if (!user.value) {
        clearAuth()
      }
    }
  }

  // ── Profile actions ────────────────────────────────────────────────────────

  async function updateProfile(data: Partial<{ name: string; locale: string; notify_badges: boolean }>) {
    loading.value = true
    clearErrors()
    try {
      const res = await userApi.updateProfile(data)
      user.value = res.data.user
    } catch (e) {
      handleError(e, 'Could not update profile.')
      throw e
    } finally {
      loading.value = false
    }
  }

  async function uploadAvatar(file: File) {
    loading.value = true
    clearErrors()
    try {
      const res = await userApi.uploadAvatar(file)
      if (user.value) user.value.avatar_url = res.data.avatar_url
      return res.data.avatar_url
    } catch (e) {
      handleError(e, 'Avatar upload failed.')
      throw e
    } finally {
      loading.value = false
    }
  }

  async function deleteAccount() {
    loading.value = true
    try {
      await userApi.deleteAccount()
      clearAuth()
    } catch (e) {
      handleError(e, 'Could not delete account.')
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    fieldErrors,
    isAuthenticated,
    isAdmin,
    register,
    login,
    logout,
    fetchMe,
    updateProfile,
    uploadAvatar,
    deleteAccount,
    clearErrors,
    setToken,
    clearAuth,
    isAuthModalOpen,
    authModalMode,
    authRedirectUrl,
    openAuthModal,
    closeAuthModal,
  }
})
