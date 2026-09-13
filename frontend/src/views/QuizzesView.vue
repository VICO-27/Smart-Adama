<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useBooksStore } from '@/stores/books'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'
import { quizApi } from '@/api/quiz'
import { useI18n } from 'vue-i18n'

const booksStore = useBooksStore()
const progressStore = useProgressStore()
const { t } = useI18n()
const router = useRouter()

const attempts = ref<any[]>([])
const loadingAttempts = ref(true)

onMounted(async () => {
  if (!booksStore.books || booksStore.books.length === 0) {
    await booksStore.loadBooks()
  }
  await progressStore.loadAll()

  try {
    const { data } = await quizApi.listMyAttempts()
    attempts.value = data.attempts || []
  } catch (error) {
    console.error('Failed to load quiz attempts:', error)
  } finally {
    loadingAttempts.value = false
  }
})

const availableChapters = computed(() => {
  const book = booksStore.books?.[0]
  if (!book) return []

  let chapters = [...(book.chapters?.data || book.chapters || [])]

  // SORTING LOGIC: System Context goes to the end
  chapters.sort((a, b) => {
    const aIntro = a.title === 'Introduction & Preface';
    const bIntro = b.title === 'Introduction & Preface';
    const aSys = a.title.includes('System Context');
    const bSys = b.title.includes('System Context');

    if (aIntro) return -1;
    if (bIntro) return 1;
    if (aSys) return 1;
    if (bSys) return -1;

    return a.order - b.order;
  });

  // Filter out Intro because it shouldn't have a quiz
  return chapters.filter((c: any) => c.title !== 'Introduction & Preface')
})

const isChapterCompleted = (chapterId: string) => {
  const prog = progressStore.chapterProgress(chapterId)
  return prog?.status === 'COMPLETED'
}

const formatDate = (dateString: string) => {
  if (!dateString) return 'In Progress'
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const readChapter = (chapterId: string) => {
  localStorage.setItem('smart-adama-last-chapter', chapterId)
  router.push('/study')
}
</script>

<template>
  <AppShell>
    <div class="min-h-screen pt-24 pb-16 px-6 lg:px-10 bg-[#fbfbfd] dark:bg-[#0f172a] bg-[radial-gradient(circle_at_top_center,_rgba(0,102,204,0.03)_0%,_transparent_100%)] dark:bg-[radial-gradient(circle_at_top_center,_rgba(255,255,255,0.02)_0%,_transparent_100%)]" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Segoe UI', Roboto, sans-serif;">
      <div class="max-w-5xl mx-auto">

        <div class="flex items-start gap-4 mb-10">
          <RouterLink to="/dashboard" class="w-8 h-8 mt-1 shrink-0 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200/80 dark:hover:bg-slate-700/80 transition-all flex items-center justify-center backdrop-blur-md active:scale-[0.98]">
            <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
          </RouterLink>
          <div>
            <h1 class="text-[32px] md:text-[36px] font-semibold tracking-tight text-slate-900 dark:text-white leading-none">{{ $t('quiz.title') }}</h1>
            <p class="text-[15px] font-normal text-slate-500 dark:text-slate-400 max-w-xl mt-2 leading-relaxed">{{ $t('quiz.subtitle') }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

          <div class="lg:col-span-2 space-y-4">
            <h2 class="text-[17px] font-semibold text-slate-900 dark:text-white tracking-tight flex items-center gap-2 mb-5">
              {{ $t('quiz.available') }}
            </h2>

            <div v-if="booksStore.loading" class="text-sm text-slate-400 dark:text-slate-500 animate-pulse font-medium">{{ $t('quiz.loading') }}</div>

            <div class="grid grid-cols-2 gap-3 sm:gap-6">
                <div
                  v-for="(chapter, index) in availableChapters" :key="chapter.id"
                  class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-md rounded-xl sm:rounded-2xl p-3 sm:p-6 border border-black/[0.05] dark:border-white/[0.05] shadow-[0_2px_12px_rgba(0,0,0,0.03)] dark:shadow-none flex flex-col justify-between group transition-all duration-300 ease-out"
                  :class="isChapterCompleted(chapter.id) ? 'hover:shadow-[0_12px_32px_rgba(0,0,0,0.08)] dark:hover:shadow-[0_8px_24px_rgba(0,0,0,0.4)] hover:-translate-y-0.5 active:scale-[0.98]' : 'opacity-80 grayscale-[0.2]'"
                >
                  <div>
                    <div class="flex justify-between items-center mb-1.5 sm:mb-2.5">
                      <span class="text-[9px] sm:text-[11px] font-semibold uppercase tracking-wider text-[#0071e3]/90 dark:text-[#3b82f6]">CH {{ String(index + 1).padStart(2, '0') }}</span>
                      <span class="text-[9px] sm:text-[11px] font-medium" :class="isChapterCompleted(chapter.id) ? 'text-[#04AA6D] dark:text-[#34d399]' : 'text-amber-600 dark:text-amber-500'">
                        <span v-if="isChapterCompleted(chapter.id)">🔓 Unlocked</span>
                        <span v-else>🔒 Locked</span>
                      </span>
                    </div>
                    <h3 class="text-[12px] sm:text-[18px] font-medium tracking-tight text-slate-900 dark:text-slate-100 leading-snug line-clamp-2" :title="chapter.title">{{ chapter.title }} Quiz</h3>

                    <p v-if="!isChapterCompleted(chapter.id)" class="text-[11px] sm:text-sm text-slate-500 dark:text-slate-400 mt-1 sm:mt-2 font-medium line-clamp-2">
                      Complete Chapter {{ index + 1 }} first.
                    </p>
                  </div>

                  <RouterLink v-if="isChapterCompleted(chapter.id)" :to="`/chapters/${chapter.id}/quiz`" class="mt-3 sm:mt-6 w-full py-1.5 sm:py-2.5 px-2.5 sm:px-4 rounded-lg sm:rounded-xl bg-[#0071e3]/10 dark:bg-[#3b82f6]/20 hover:bg-[#0071e3] dark:hover:bg-[#3b82f6] text-[#0071e3] dark:text-[#93c5fd] hover:text-white dark:hover:text-white font-medium text-[11px] sm:text-[14px] transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 group/btn">
                    {{ $t('quiz.take') }}
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                  </RouterLink>
                  <button v-else @click="readChapter(chapter.id)" class="mt-3 sm:mt-6 w-full py-1.5 sm:py-2.5 px-2.5 sm:px-4 rounded-lg sm:rounded-xl border-2 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-300 font-medium text-[11px] sm:text-[14px] transition-all duration-200 flex items-center justify-center gap-1.5 sm:gap-2 group/btn">
                    Read
                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                  </button>
                </div>
            </div>
          </div>

          <div class="space-y-4">
            <h2 class="text-[17px] font-semibold text-slate-900 dark:text-white tracking-tight flex items-center gap-2 mb-5">
              {{ $t('quiz.history') }}
            </h2>

            <div class="bg-white/70 dark:bg-slate-800/70 backdrop-blur-lg rounded-2xl border border-black/[0.06] dark:border-white/[0.05] p-5 shadow-[0_2px_12px_rgba(0,0,0,0.02)] dark:shadow-none">
              <div class="flex items-center justify-between mb-4 pb-3 border-b border-black/[0.04] dark:border-white/[0.05]">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  <span class="text-[13px] font-medium text-slate-600 dark:text-slate-300">Past Attempts</span>
                </div>
                <span v-if="!loadingAttempts" class="text-[11px] font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 px-2.5 py-0.5 rounded-full">{{ attempts.length }} Completed</span>
              </div>

              <div v-if="loadingAttempts" class="py-6 text-[13px] text-slate-400 dark:text-slate-500 text-center animate-pulse font-medium">
                {{ $t('quiz.loading_hist') }}
              </div>

              <div v-else-if="attempts.length === 0" class="py-8 text-center">
                <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <p class="text-slate-600 dark:text-slate-400 font-medium text-[13px]">{{ $t('quiz.no_attempts') }}</p>
                <p class="text-slate-400 dark:text-slate-500 text-[11px] mt-0.5 font-medium">{{ $t('quiz.no_attempts_sub') }}</p>
              </div>

              <div v-else class="max-h-[500px] overflow-y-auto pr-1">
                <div v-for="attempt in attempts" :key="attempt.id" class="bg-slate-50/70 dark:bg-slate-900/50 hover:bg-slate-100/70 dark:hover:bg-slate-900/80 transition-colors p-3.5 rounded-xl mb-2.5 last:mb-0 border border-black/[0.02] dark:border-white/[0.02] flex items-center justify-between active:scale-[0.98]">
                  <div class="min-w-0 pr-3">
                    <p class="text-[14px] font-semibold text-slate-900 dark:text-white truncate">{{ attempt.quiz_title || 'Chapter Quiz' }}</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 font-medium">{{ formatDate(attempt.submitted_at) }}</p>
                  </div>

                  <div class="shrink-0 flex items-center gap-2">
                    <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                          :class="attempt.passed ? 'text-[#1d8751] dark:text-[#34d399] bg-[#ebf7ed] dark:bg-[#064e3b] border border-emerald-200/50 dark:border-emerald-800' : 'text-[#c53030] dark:text-[#f87171] bg-[#fef2f2] dark:bg-[#7f1d1d]/30 border border-rose-200/50 dark:border-rose-900/50'">
                      {{ attempt.score_pct }}% {{ attempt.passed ? 'Passed' : 'Retake' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AppShell>
</template>
