<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  status: string
  variant?: 'success' | 'warning' | 'error' | 'info' | 'neutral'
}>()

const statusColors = computed(() => {
  switch (props.variant) {
    case 'success':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
    case 'warning':
      return 'bg-amber-50 text-amber-700 ring-amber-600/20'
    case 'error':
      return 'bg-red-50 text-red-700 ring-red-600/10'
    case 'info':
      return 'bg-smart-blue-50 text-smart-blue-700 ring-smart-blue-600/20'
    default:
      return 'bg-slate-50 text-slate-600 ring-slate-500/10'
  }
})
</script>

<template>
  <span 
    :class="[
      'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
      statusColors
    ]"
  >
    {{ status }}
  </span>
</template>
