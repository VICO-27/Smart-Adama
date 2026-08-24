<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useBooksStore } from '@/stores/books'
import api from '@/api/client'
import PageHeader from '@/components/admin/PageHeader.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import { Plus, Download, BookOpen, Layers, Sparkles, RefreshCw } from 'lucide-vue-next'

const books = useBooksStore()
onMounted(() => books.loadBooks())

const isGenerating = ref<Record<string, boolean>>({})

const generateQuiz = async (chapter: any) => {
  if (!confirm(`Generate a quiz for "${chapter.title}" using AI?`)) return
  isGenerating.value[chapter.id] = true
  try {
    const res = await api.post(`/admin/chapters/${chapter.id}/generate-quiz`)
    alert('Quiz generated successfully!')
    // We can reload the books or specifically the chapter here.
    await books.loadBooks()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to generate quiz.')
  } finally {
    isGenerating.value[chapter.id] = false
  }
}

const manageQuiz = (chapter: any) => {
  alert('Quiz management modal coming in next step.')
}

const exportData = () => {
  alert('Quiz data export initiated. Check your downloads shortly.')
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader 
      title="Learning Content & Quizzes" 
      description="Manage educational modules, chapters, and the quizzes associated with them."
    >
      <template #actions>
        <button @click="exportData" class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
          <Download class="h-4 w-4" />
          Export Data
        </button>
        <button class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors disabled:opacity-50" disabled>
          <Plus class="h-4 w-4" />
          Create Quiz
        </button>
      </template>
    </PageHeader>

    <div v-if="books.loading" class="space-y-6">
      <div v-for="i in 2" :key="i" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="h-6 w-1/4 rounded bg-slate-200 animate-pulse mb-6"></div>
        <div class="space-y-4">
          <div class="h-16 w-full rounded-lg bg-slate-100 animate-pulse"></div>
          <div class="h-16 w-full rounded-lg bg-slate-100 animate-pulse"></div>
        </div>
      </div>
    </div>

    <template v-else>
      <div v-if="books.books.length === 0" class="flex flex-col items-center justify-center py-16 px-4 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50">
        <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-4">
          <BookOpen class="h-6 w-6 text-slate-400" />
        </div>
        <h3 class="text-base font-semibold text-slate-900">No books found</h3>
        <p class="mt-1 text-sm text-slate-500">Ingest a book in the Document Manager first.</p>
      </div>
      
      <div
        v-for="book in books.books"
        :key="book.id"
        class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden"
      >
        <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-4 flex items-center gap-3">
          <BookOpen class="h-5 w-5 text-smart-blue-600" />
          <h2 class="font-semibold text-slate-900 text-lg">{{ book.title }}</h2>
        </div>
        
        <div class="p-0">
          <div v-if="!book.chapters || book.chapters.length === 0" class="p-6 text-center text-sm text-slate-500">
            No chapters available for this book yet.
          </div>
          
          <ul v-else class="divide-y divide-slate-100">
            <li 
              v-for="ch in (book.chapters?.data || book.chapters || [])"
              :key="ch.id"
              class="group flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 hover:bg-slate-50/50 transition-colors gap-4"
            >
              <div class="flex items-start gap-4 flex-1">
                <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-400">
                  <Layers class="h-4 w-4" />
                </div>
                <div>
                  <p class="font-medium text-slate-900">{{ ch.title }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <p class="text-xs text-slate-500">Ingestion Status:</p>
                    <StatusBadge 
                      :status="ch.ingestion_status === 'ready' ? 'Ready' : (ch.ingestion_status === 'failed' ? 'Failed' : 'Processing')" 
                      :variant="ch.ingestion_status === 'ready' ? 'success' : (ch.ingestion_status === 'failed' ? 'error' : 'warning')" 
                    />
                  </div>
                </div>
              </div>
              
              <div class="flex items-center gap-3 shrink-0">
                <div v-if="ch.quiz">
                   <StatusBadge 
                     :status="ch.quiz.status === 'published' ? 'Published' : 'Draft'" 
                     :variant="ch.quiz.status === 'published' ? 'success' : 'neutral'" 
                   />
                </div>
                <div v-else class="text-xs font-medium text-slate-400 bg-slate-100 px-2 py-1 rounded">No Quiz</div>

                <div class="h-6 w-px bg-slate-200 mx-2"></div>
                
                <button 
                  v-if="!ch.quiz"
                  @click="generateQuiz(ch)"
                  :disabled="isGenerating[ch.id] || ch.ingestion_status !== 'ready'"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-smart-blue-50 px-3 py-1.5 text-xs font-semibold text-smart-blue-700 hover:bg-smart-blue-100 disabled:opacity-50 transition-colors"
                >
                  <RefreshCw v-if="isGenerating[ch.id]" class="h-3.5 w-3.5 animate-spin" />
                  <Sparkles v-else class="h-3.5 w-3.5" />
                  AI Generate
                </button>
                
                <button 
                  v-if="ch.quiz"
                  @click="manageQuiz(ch)"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors"
                >
                  Manage Quiz
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </template>
  </div>
</template>