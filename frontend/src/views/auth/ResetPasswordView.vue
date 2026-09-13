<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import SaInput  from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'

const route   = useRoute()
const router  = useRouter()
const auth    = useAuthStore()
const password = ref('')
const loading  = ref(false)
const error    = ref('')
const success  = ref(false)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await authApi.resetPassword({
      token:    route.query.token as string,
      email:    route.query.email as string,
      password: password.value,
    })
    success.value = true
    setTimeout(() => {
      auth.openAuthModal('login')
      router.push('/')
    }, 2000)
  } catch (e: any) {
    error.value = e.response?.data?.error?.message ?? 'Invalid or expired token.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[var(--sa-bg)] dark:bg-gray-950 flex items-center justify-center p-4 relative">

    <!-- Back Button -->
    <RouterLink to="/" class="absolute top-6 left-6 flex items-center gap-2 text-sm font-medium text-[var(--sa-taupe)] dark:text-gray-400 hover:text-[var(--sa-dark)] dark:hover:text-white transition-colors">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
      Back to Home
    </RouterLink>

    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <h1 class="font-display text-2xl font-semibold text-[var(--sa-dark)] dark:text-white">Set new password</h1>
      </div>

      <div class="glass dark:bg-gray-900/80 rounded-2xl border border-[var(--sa-gray)] dark:border-gray-800 shadow-[var(--shadow-md)] p-7">
        <div v-if="success" class="text-center py-4">
          <div class="text-3xl mb-3">✅</div>
          <p class="text-sm font-medium text-[var(--sa-dark)] dark:text-white">Password updated! Redirecting…</p>
        </div>
        <form v-else @submit.prevent="submit" novalidate class="flex flex-col gap-5">
          <SaInput v-model="password" label="New password" type="password" placeholder="Min. 8 chars with a number" autocomplete="new-password" required />
          <p v-if="error" class="text-sm text-red-600 dark:text-red-400" role="alert">{{ error }}</p>
          <SaButton type="submit" :loading="loading" class="w-full justify-center">Update password</SaButton>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-[var(--sa-taupe)] dark:text-gray-400">
        <button type="button" @click="auth.openAuthModal('login'); router.push('/')" class="font-medium text-[var(--sa-dark)] dark:text-white hover:underline">← Back to sign in</button>
      </p>
    </div>
  </div>
</template>