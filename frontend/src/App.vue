<!-- src/App.vue -->
<template>
  <ErrorBoundary>
    <router-view v-slot="{ Component, route }">
      <transition name="page" mode="out-in">
        <component :is="Component" :key="route.name === 'study' ? 'study-view' : route.fullPath" />
      </transition>
    </router-view>

    <!-- The ONE and ONLY Global Assistant rendered globally -->
    <GlobalAssistant v-if="authStore.isAuthenticated && route.name !== 'study'" />

    <!-- Global Auth Modal -->
    <AuthModal v-if="authStore.isAuthModalOpen" />

    <!-- Global Confirm Modal -->
    <GlobalConfirmModal />
  </ErrorBoundary>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useRoute } from 'vue-router'
import GlobalAssistant from '@/components/layout/GlobalAssistant.vue'
import AuthModal from '@/components/auth/AuthModal.vue'
import ErrorBoundary from '@/components/ErrorBoundary.vue'
import GlobalConfirmModal from '@/components/ui/GlobalConfirmModal.vue'
import { useTheme } from '@/composables/useTheme'
import { onMounted } from 'vue'

const authStore = useAuthStore()
const route = useRoute()
const { initializeTheme } = useTheme()

onMounted(() => {
  initializeTheme()
})
</script>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.15s ease;
}
.page-enter-from,
.page-leave-to {
  opacity: 0;
}
#app {
  width: 100%;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
body {
  margin: 0;
  padding: 0;
  background-color: var(--sa-bg);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>
