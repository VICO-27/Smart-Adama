<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm'
import { AlertCircle, HelpCircle } from 'lucide-vue-next'

const { isOpen, options, proceed, cancel } = useConfirm()
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[400px] overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-200">
      <div class="p-5 flex gap-4">
        <div class="shrink-0 mt-0.5" :class="options.confirmColor === 'red' ? 'text-red-500' : 'text-smart-blue-500'">
          <AlertCircle v-if="options.confirmColor === 'red'" class="w-6 h-6" />
          <HelpCircle v-else class="w-6 h-6" />
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900 leading-tight">
            {{ options.title || 'Please Confirm' }}
          </h3>
          <p class="mt-1.5 text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">
            {{ options.message }}
          </p>
        </div>
      </div>
      <div class="px-5 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
        <button
          @click="cancel"
          class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-200"
        >
          {{ options.cancelText || 'Cancel' }}
        </button>
        <button
          @click="proceed"
          class="px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1"
          :class="options.confirmColor === 'red' ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' : 'bg-smart-blue-600 hover:bg-smart-blue-700 focus:ring-smart-blue-500'"
        >
          {{ options.confirmText || 'Confirm' }}
        </button>
      </div>
    </div>
  </div>
</template>
