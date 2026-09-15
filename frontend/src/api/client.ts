import axios, { type AxiosInstance, type AxiosError } from 'axios'
import { isSupabaseConfigured, supabaseAuthService } from '@/services/supabaseAuth'

/**
 * Central Axios instance for all Smart Adama API calls.
 *
 * Error envelope from the backend:
 *   { error: { code: string, message: string, details?: Record<string, string[]> } }
 *
 * This client normalises that envelope so stores and components can read errors
 * without parsing the raw Axios error themselves.
 */

function isPrivateLan(host: string): boolean {
  return (
    /^192\.168\./.test(host) ||
    /^10\./.test(host) ||
    /^172\.(1[6-9]|2[0-9]|3[0-1])\./.test(host) ||
    host.endsWith('.local')
  )
}

/**
 * Normalizes backend URLs so that trailing slashes and redundant /api/v1 suffixes
 * are cleanly handled regardless of environment configuration.
 */
export function getBackendUrls() {
  const envUrl = (import.meta.env.VITE_API_BASE_URL || '').trim().replace(/\/+$/, '')
  // Default to production Render backend if in production or loaded over HTTPS without env override
  const isHttps = typeof window !== 'undefined' && window.location?.protocol === 'https:'
  const defaultRoot = (import.meta.env.PROD || isHttps)
    ? 'https://smart-adama-api.onrender.com'
    : 'http://localhost:8000'

  let serverRoot = envUrl ? envUrl.replace(/\/api\/v1$/, '') : defaultRoot

  // If accessed from a mobile phone or client on LAN (e.g. 192.168.x.x),
  // dynamically substitute localhost / 127.0.0.1 with the current host IP
  // so mobile devices reach the dev server instead of their own loopback.
  if (typeof window !== 'undefined' && window.location?.hostname) {
    const host = window.location.hostname
    if (isPrivateLan(host)) {
      serverRoot = serverRoot.replace(/localhost|127\.0\.0\.1/g, host)
    }
  }

  let apiBase = envUrl.endsWith('/api/v1') ? envUrl : `${serverRoot}/api/v1`
  if (typeof window !== 'undefined' && window.location?.hostname) {
    const host = window.location.hostname
    if (isPrivateLan(host)) {
      apiBase = apiBase.replace(/localhost|127\.0\.0\.1/g, host)
    }
  }

  return { serverRoot, apiBase }
}

export const { serverRoot, apiBase } = getBackendUrls()

let hasWarmedUp = false
/**
 * Wakes up the backend container in the background (preventing Render cold-start lag).
 */
export function warmUpBackend() {
  if (hasWarmedUp) return
  hasWarmedUp = true
  try {
    fetch(`${serverRoot}/up`, { mode: 'no-cors' }).catch(() => {})
  } catch {}
}

const apiClient: AxiosInstance = axios.create({
  baseURL: apiBase,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000, // 15 seconds default timeout for REST requests (prevents infinite hanging)
  withCredentials: false,
})

// ── Request: attach Bearer token ──────────────────────────────────────────────
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('sa_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// ── Response: normalise errors ────────────────────────────────────────────────
apiClient.interceptors.response.use(
  (res) => res,
  async (error: AxiosError<ApiErrorEnvelope>) => {
    const originalRequest = error.config as any
    const status = error.response?.status

    // 401 — attempt transparent token refresh via Supabase before clearing
    if (status === 401 && originalRequest && !originalRequest._retry) {
      originalRequest._retry = true
      try {
        if (isSupabaseConfigured()) {
          const session = await supabaseAuthService.refreshSession()
          if (session?.access_token) {
            localStorage.setItem('sa_token', session.access_token)
            originalRequest.headers = originalRequest.headers || {}
            originalRequest.headers.Authorization = `Bearer ${session.access_token}`
            return apiClient(originalRequest)
          }
        }
      } catch {
        // Refresh failed, proceed to clear token
      }
      localStorage.removeItem('sa_token')
    } else if (status === 401) {
      localStorage.removeItem('sa_token')
    }

    // Attach normalised error fields directly onto the error object
    // so callers can do: catch(e) { e.userMessage; e.fieldErrors }
    if (error.response?.data?.error) {
      const envelope = error.response.data.error
      ;(error as ApiError).userMessage = envelope.message
      ;(error as ApiError).fieldErrors  = envelope.details ?? {}
    }

    return Promise.reject(error)
  },
)

export default apiClient

// ── Shared types ──────────────────────────────────────────────────────────────

export interface ApiErrorEnvelope {
  error: {
    code: string
    message: string
    details?: Record<string, string[]>
  }
}

/** Extended AxiosError with normalised fields attached by the interceptor */
export interface ApiError extends AxiosError<ApiErrorEnvelope> {
  userMessage: string
  fieldErrors: Record<string, string[]>
}

/**
 * Type-guard — use in catch blocks to get typed error fields.
 * @example
 *   } catch (e) {
 *     if (isApiError(e)) toast(e.userMessage)
 *   }
 */
export function isApiError(e: unknown): e is ApiError {
  return axios.isAxiosError(e) && !!(e as ApiError).userMessage
}

/**
 * Extract the first validation error message for a specific field.
 * Useful in <script setup> to populate inline field errors.
 */
export function fieldError(e: unknown, field: string): string | undefined {
  if (isApiError(e)) return e.fieldErrors?.[field]?.[0]
  return undefined
}
