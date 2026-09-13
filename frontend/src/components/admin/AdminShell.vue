<script setup lang="ts">
import { ref } from 'vue'
import AdminSidebar from './AdminSidebar.vue'
import AdminHeader from './AdminHeader.vue'
import CommandPaletteModal from './CommandPaletteModal.vue'

const isSidebarOpen = ref(true)
const isMobileOpen = ref(false)

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const toggleMobileSidebar = () => {
  isMobileOpen.value = !isMobileOpen.value
}
</script>

<template>
  <div class="flex h-screen w-full bg-[#F8FAFC] dark:bg-[#0B1220] font-sans text-slate-900 dark:text-slate-100 overflow-hidden transition-colors duration-300">
    <!-- Desktop Sidebar -->
    <AdminSidebar
      class="hidden lg:flex"
      :is-collapsed="!isSidebarOpen"
    />

    <!-- Mobile Sidebar Backdrop -->
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-sm lg:hidden transition-opacity"
      @click="toggleMobileSidebar"
    ></div>

    <!-- Mobile Sidebar -->
    <div
      class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col transform transition-transform duration-300 ease-in-out lg:hidden shadow-2xl"
      :class="isMobileOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <AdminSidebar :is-collapsed="false" @close="toggleMobileSidebar" />
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-1 flex-col min-w-0 overflow-hidden transition-all duration-300">
      <AdminHeader
        @toggle-sidebar="toggleSidebar"
        @toggle-mobile-sidebar="toggleMobileSidebar"
      />

      <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 custom-scrollbar">
        <div class="mx-auto max-w-7xl w-full">
          <slot></slot>
        </div>
      </main>
    </div>

    <!-- Global UI Modals -->
    <CommandPaletteModal />
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1; /* slate-300 */
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8; /* slate-400 */
}
</style>
