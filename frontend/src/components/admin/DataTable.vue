<script setup lang="ts">
import { Search } from 'lucide-vue-next'

defineProps<{
  columns: { key: string; label: string; width?: string; align?: 'left' | 'center' | 'right' }[]
  data: any[]
  loading?: boolean
  searchPlaceholder?: string
}>()

defineEmits(['row-click'])
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
    <!-- Toolbar -->
    <div class="border-b border-slate-200 px-4 py-4 sm:px-6 flex items-center justify-between gap-4 bg-slate-50/50">
      <div class="relative max-w-sm w-full">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <Search class="h-4 w-4 text-slate-400" />
        </div>
        <input 
          type="text" 
          class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-smart-blue-500 bg-white" 
          :placeholder="searchPlaceholder || 'Search...'" 
        />
      </div>
      <div class="flex items-center gap-2">
        <slot name="toolbar-actions"></slot>
      </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto min-h-[300px]">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th 
              v-for="col in columns" 
              :key="col.key"
              scope="col" 
              class="px-4 py-3.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap"
              :class="[
                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                col.width || 'auto'
              ]"
            >
              {{ col.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-if="loading">
            <td :colspan="columns.length" class="p-8 text-center">
              <div class="flex flex-col items-center justify-center text-slate-400">
                <svg class="h-8 w-8 animate-spin mb-4" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium">Loading data...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="data.length === 0">
            <td :colspan="columns.length" class="p-12 text-center">
              <slot name="empty">
                <div class="flex flex-col items-center justify-center text-slate-400">
                  <span class="text-sm font-medium">No results found.</span>
                </div>
              </slot>
            </td>
          </tr>
          <tr 
            v-else
            v-for="(row, idx) in data" 
            :key="row.id || idx"
            class="hover:bg-slate-50/80 transition-colors cursor-pointer group"
            @click="$emit('row-click', row)"
          >
            <td 
              v-for="col in columns" 
              :key="col.key"
              class="whitespace-nowrap px-4 py-4 text-sm"
              :class="[
                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left'
              ]"
            >
              <slot :name="col.key" :row="row" :value="row[col.key]">
                <span class="text-slate-700 font-medium">{{ row[col.key] }}</span>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination Footer -->
    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6 flex items-center justify-between">
      <div class="text-sm text-slate-500">
        Showing <span class="font-medium text-slate-900">{{ data.length > 0 ? 1 : 0 }}</span> to <span class="font-medium text-slate-900">{{ data.length }}</span> of <span class="font-medium text-slate-900">{{ data.length }}</span> results
      </div>
      <div class="flex gap-2">
        <button class="relative inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:opacity-50" disabled>Previous</button>
        <button class="relative inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:opacity-50" disabled>Next</button>
      </div>
    </div>
  </div>
</template>
