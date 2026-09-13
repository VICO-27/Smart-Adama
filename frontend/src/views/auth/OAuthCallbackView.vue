<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  isSupabaseConfigured,
  supabaseAuthService,
  mapSupabaseUserToProfile,
} from '@/services/supabaseAuth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

function finishAuthentication() {
  auth.clearErrors()
  auth.closeAuthModal()
  const redirect =
    auth.authRedirectUrl && auth.authRedirectUrl !== '/login'
      ? auth.authRedirectUrl
      : '/dashboard'
  router.push(redirect)
}

function failAuthentication(message: string) {
  auth.error = message
  auth.openAuthModal('login')
  router.push('/')
}

onMounted(async () => {
  // 1. Check for URL errors in query or hash fragment
  const queryError = (route.query.error_description || route.query.error) as string
  if (queryError) {
    failAuthentication(decodeURIComponent(queryError.replace(/\+/g, ' ')))
    return
  }

  const hash = typeof window !== 'undefined' ? window.location.hash : ''
  if (hash.includes('error=')) {
    const hashParams = new URLSearchParams(hash.replace(/^#/, ''))
    const errorMsg = hashParams.get('error_description') || hashParams.get('error')
    if (errorMsg) {
      failAuthentication(decodeURIComponent(errorMsg.replace(/\+/g, ' ')))
      return
    }
  }

  // 2. Check for backend token (Laravel Sanctum social callback)
  const token = route.query.token as string
  if (token) {
    auth.setToken(token)
    try {
      await auth.fetchMe()
    } catch {
      // Backend profile optional if token valid
    }
    finishAuthentication()
    return
  }

  // 3. Process Supabase Authentication (Magic Link or OAuth)
  if (isSupabaseConfigured()) {
    try {
      // A. Check for PKCE authorization code in query
      const code = route.query.code as string
      if (code) {
        const { session } = await supabaseAuthService.exchangeCodeForSession(code)
        if (session?.access_token) {
          auth.setToken(session.access_token)
          if (session.user) {
            auth.user = mapSupabaseUserToProfile(session.user)
          }
          try {
            await auth.fetchMe()
          } catch {}
          finishAuthentication()
          return
        }
      }

      // B. Check for token_hash in query (Supabase email magic link / confirmation)
      const tokenHash = route.query.token_hash as string
      const otpType = (route.query.type as any) || 'email'
      if (tokenHash) {
        const { session } = await supabaseAuthService.verifyTokenHash(tokenHash, otpType)
        if (session?.access_token) {
          auth.setToken(session.access_token)
          if (session.user) {
            auth.user = mapSupabaseUserToProfile(session.user)
          }
          try {
            await auth.fetchMe()
          } catch {}
          finishAuthentication()
          return
        }
      }

      // B. Check for implicit hash tokens in URL
      if (hash.includes('access_token=')) {
        const hashParams = new URLSearchParams(hash.replace(/^#/, ''))
        const accessToken = hashParams.get('access_token')
        const refreshToken = hashParams.get('refresh_token') || ''
        if (accessToken) {
          try {
            const { session } = await supabaseAuthService.setSession({
              access_token: accessToken,
              refresh_token: refreshToken,
            })
            if (session?.access_token) {
              auth.setToken(session.access_token)
              if (session.user) {
                auth.user = mapSupabaseUserToProfile(session.user)
              }
              try {
                await auth.fetchMe()
              } catch {}
              finishAuthentication()
              return
            }
          } catch (e) {
            console.warn('Direct hash session initialization warning:', e)
          }
        }
      }

      // C. Check active Supabase session
      const existingSession = await supabaseAuthService.getSession()
      if (existingSession?.access_token) {
        auth.setToken(existingSession.access_token)
        if (existingSession.user) {
          auth.user = mapSupabaseUserToProfile(existingSession.user)
        }
        try {
          await auth.fetchMe()
        } catch {}
        finishAuthentication()
        return
      }

      // D. Listen for async auth resolution
      let resolved = false
      const { data: { subscription } } = supabaseAuthService.onAuthStateChange(
        async (event, newSession) => {
          if ((event === 'SIGNED_IN' || event === 'TOKEN_REFRESHED') && newSession?.access_token) {
            resolved = true
            subscription.unsubscribe()
            auth.setToken(newSession.access_token)
            if (newSession.user) {
              auth.user = mapSupabaseUserToProfile(newSession.user)
            }
            try {
              await auth.fetchMe()
            } catch {}
            finishAuthentication()
          }
        }
      )

      // E. Timeout fallback if no session is established within 4 seconds
      setTimeout(() => {
        if (!resolved && !auth.isAuthenticated) {
          subscription.unsubscribe()
          failAuthentication('Sign-in link is invalid or has expired. Please try again.')
        }
      }, 4000)
      return
    } catch (err: any) {
      failAuthentication(err.message || 'Sign-in failed. Please try requesting a new link.')
      return
    }
  }

  // If Supabase is not configured
  failAuthentication('Authentication service is not configured. Please try again.')
})
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] flex items-center justify-center p-4">
    <div class="text-center">
      <div class="w-12 h-12 border-4 border-[var(--sa-gray)] border-t-black rounded-full animate-spin mx-auto mb-4"></div>
      <h1 class="font-display text-xl font-semibold text-[var(--sa-dark)] tracking-tight animate-pulse">
        Securely logging you in...
      </h1>
    </div>
  </div>
</template>