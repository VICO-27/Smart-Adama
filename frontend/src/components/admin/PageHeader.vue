<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
      <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ title }}</h1>
      <p v-if="description" class="mt-2 text-sm text-slate-500">{{ description }}</p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <slot name="actions"></slot>
    </div>
  </div>
</template>
