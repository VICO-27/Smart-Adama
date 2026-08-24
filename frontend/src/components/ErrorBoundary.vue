<script setup lang="ts">
import { ref, onErrorCaptured } from 'vue'

const hasError = ref(false)
const errorDetails = ref<string>('')

onErrorCaptured((err: unknown, instance: any, info: string) => {
  hasError.value = true
  if (err instanceof Error) {
    errorDetails.value = `${err.name}: ${err.message}`
  } else {
    errorDetails.value = String(err)
  }
  console.error('ErrorBoundary caught an error:', err, 'Component:', instance, 'Info:', info)
  // Prevent the error from propagating further up to stop the "White Screen of Death"
  return false 
})

const reload = () => {
  window.location.reload()
}
</script>

<template>
  <div v-if="hasError" class="min-h-screen flex items-center justify-center bg-slate-50 p-6">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-red-100 p-8 text-center space-y-6">
      <div class="mx-auto h-16 w-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
          <path d="M12 9v4"/>
          <path d="M12 17h.01"/>
        </svg>
      </div>
      
      <div>
        <h2 class="text-xl font-bold text-slate-900">Oops! Something went wrong.</h2>
        <p class="text-sm text-slate-500 mt-2">The application encountered an unexpected rendering error.</p>
      </div>
      
      <div v-if="errorDetails" class="bg-red-50 p-4 rounded-lg border border-red-100 text-left overflow-auto max-h-32">
        <p class="text-xs font-mono text-red-700 whitespace-pre-wrap">{{ errorDetails }}</p>
      </div>
      
      <button 
        @click="reload" 
        class="w-full inline-flex justify-center items-center rounded-lg bg-smart-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-smart-blue-500 transition-colors"
      >
        Reload Page
      </button>
    </div>
  </div>
  
  <slot v-else />
</template>
