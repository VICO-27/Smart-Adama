import apiClient from './client'

export interface RegisterPayload {
  name: string
  email?: string
  phone_number?: string
  password?: string
  pin?: string
  pin_confirmation?: string
}

export interface LoginPayload {
  identifier: string
  credential: string
}

export interface AuthResponse {
  user: App.User
  token: string
}

export const authApi = {
  register: (data: RegisterPayload) =>
    apiClient.post<AuthResponse>('/auth/register', data),

  login: (data: LoginPayload) =>
    apiClient.post<AuthResponse>('/auth/login', data),

  logout: () =>
    apiClient.post('/auth/logout'),

  me: () =>
    apiClient.get<{ user: App.UserProfile }>('/auth/me'),

  forgotPassword: (email: string) =>
    apiClient.post('/auth/password/forgot', { email }),

  resetPassword: (data: { token: string; email: string; password: string }) =>
    apiClient.post('/auth/password/reset', data),

  verifyPhone: (phone_number: string) =>
    apiClient.post<{ masked_email?: string; fallback?: string }>('/auth/pin/forgot', { phone_number }),

  verifyCode: (phone_number: string, code: string) =>
    apiClient.post<{ signature: string }>('/auth/pin/verify-code', { phone_number, code }),

  resetPin: (data: { phone_number: string; signature: string; pin: string; pin_confirmation: string }) =>
    apiClient.post('/auth/pin/reset', data),
}
