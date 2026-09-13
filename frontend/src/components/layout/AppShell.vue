<template>
  <div class="min-h-screen bg-[var(--sa-bg)] text-[var(--sa-dark)] flex flex-col">

    <AppNav />

    <!-- Clean breathing room below the fixed navbar -->
    <main :class="['flex-grow w-full relative', contentClass || 'pt-16 sm:pt-20 md:pt-28']">
      <slot />
    </main>

  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import AppNav from './AppNav.vue'

defineProps<{
  contentClass?: string
  maxWidth?: string
}>()

const authStore = useAuthStore()
</script>