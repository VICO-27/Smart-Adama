<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  trend?: string
  trendLabel?: string
  trendUp?: boolean
  icon?: any
}>()
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-medium text-slate-500">{{ title }}</h3>
      <div v-if="icon" class="flex h-10 w-10 items-center justify-center rounded-lg bg-smart-blue-50 text-smart-blue-500">
        <component :is="icon" class="h-5 w-5" />
      </div>
    </div>
    <div class="mt-4 flex items-baseline gap-2">
      <span class="font-display text-3xl font-bold tracking-tight text-slate-900">{{ value }}</span>
      <span 
        v-if="trend" 
        class="inline-flex items-baseline gap-1 text-sm font-medium"
        :class="trendUp ? 'text-emerald-600' : (trendUp === false ? 'text-red-600' : 'text-slate-500')"
      >
        <svg v-if="trendUp" class="h-3 w-3 self-center" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
        </svg>
        <svg v-else-if="trendUp === false" class="h-3 w-3 self-center" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 4.5l-15 15m0 0h11.25m-11.25 0V8.25" />
        </svg>
        {{ trend }}
      </span>
    </div>
    <p v-if="trendLabel" class="mt-1 text-xs text-slate-500">{{ trendLabel }}</p>
  </div>
</template>
