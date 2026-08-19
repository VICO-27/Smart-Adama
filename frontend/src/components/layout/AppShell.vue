<template>
  <div class="min-h-screen bg-[var(--sa-bg)] text-[var(--sa-dark)] flex flex-col">

    <AppNav @open-auth="openAuthModal" />

    <!-- Added pt-24 md:pt-28 to give ~1cm of clean breathing room below the fixed navbar -->
    <main class="flex-grow w-full relative pt-24 md:pt-28">
      <slot />
    </main>

  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import AppNav from './AppNav.vue'

const authStore = useAuthStore()
</script>