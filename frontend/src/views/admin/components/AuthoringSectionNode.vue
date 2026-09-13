<script setup lang="ts">
import { ref } from 'vue'
import { FileText, Plus, Trash2, ChevronRight, ChevronDown } from 'lucide-vue-next'

const props = defineProps<{
  section: any
  activeNodeId?: string
}>()

const emit = defineEmits(['select', 'add', 'delete', 'update-title'])

const isExpanded = ref(true)

const handleTitleBlur = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.value !== props.section.title) {
    emit('update-title', { node: props.section, title: target.value })
  }
}
</script>

<template>
  <div class="space-y-0.5 mt-0.5">
    <div
      class="flex items-center justify-between px-2 py-1.5 rounded-lg transition-colors group"
      :class="activeNodeId === section.id ? 'bg-smart-blue-50 text-smart-blue-700' : 'hover:bg-slate-50 text-slate-700'"
    >
      <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-2 cursor-pointer" @click="emit('select', section)">
        <button
          v-if="section.descendants && section.descendants.length > 0"
          @click.stop="isExpanded = !isExpanded"
          class="p-0.5 rounded text-slate-400 hover:bg-slate-200 shrink-0"
        >
          <ChevronDown v-if="isExpanded" class="h-3.5 w-3.5" />
          <ChevronRight v-else class="h-3.5 w-3.5" />
        </button>
        <FileText v-else class="h-3 w-3 shrink-0" :class="activeNodeId === section.id ? 'text-smart-blue-500' : 'text-slate-400'" />

        <span class="truncate text-sm font-medium">{{ section.title }}</span>
      </div>

      <div class="flex items-center opacity-0 group-hover:opacity-100 transition-opacity shrink-0 gap-1">
        <button @click.stop="emit('add', section.id)" class="p-1 text-slate-400 hover:text-smart-blue-600 rounded hover:bg-slate-200">
          <Plus class="h-3.5 w-3.5" />
        </button>
        <button @click.stop="emit('delete', section.id)" class="p-1 text-slate-400 hover:text-red-600 rounded hover:bg-slate-200">
          <Trash2 class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>

    <!-- Recursive Children -->
    <div v-if="isExpanded && section.descendants && section.descendants.length > 0" class="pl-2 border-l border-slate-200 ml-2.5">
      <AuthoringSectionNode
        v-for="child in section.descendants"
        :key="child.id"
        :section="child"
        :active-node-id="activeNodeId"
        @select="emit('select', $event)"
        @add="emit('add', $event)"
        @delete="emit('delete', $event)"
        @update-title="emit('update-title', $event)"
      />
    </div>
  </div>
</template>
