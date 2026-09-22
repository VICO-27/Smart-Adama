<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useBooksStore }  from '@/stores/books'
import { useQuizStore }   from '@/stores/quiz'
import { useProgressStore } from '@/stores/progress'
import AppShell from '@/components/layout/AppShell.vue'
import SaButton from '@/components/ui/SaButton.vue'
import { useI18n } from 'vue-i18n'

const route    = useRoute()
const books    = useBooksStore()
const quiz     = useQuizStore()
const progress = useProgressStore()
const { t } = useI18n()

const chapterId = computed(() => route.params.chapterId as string)

type QuizStep = 'loading' | 'intro' | 'quiz' | 'result' | 'review' | 'error'
const step = ref<QuizStep>('loading')
const filter = ref<'all' | 'correct' | 'incorrect'>('all')

const submitting = ref(false)
const errorMsg   = ref('')
const currentQuestionIndex = ref(0)

onMounted(async () => {
  await loadQuizMetadata()
})

async function loadQuizMetadata() {
  step.value = 'loading'
  errorMsg.value = ''

  await progress.loadAll()
  

  try {
    await books.loadChapterQuiz(chapterId.value)
    if (!books.currentQuiz) {
      errorMsg.value = 'There are no quizzes available for this chapter yet.'
      step.value = 'error'
      return
    }
    step.value = 'intro'
  } catch(e: any) {
    if (e.response && e.response.status === 403) {
      alert('Finish reading this chapter first to unlock this quiz.')
      window.location.href = `/study`
      return
    }
    errorMsg.value = 'We couldn\'t load this quiz right now. Please try again.'
    step.value = 'error'
  }
}

async function beginQuiz() {
  step.value = 'loading'
  errorMsg.value = ''
  try {
    await quiz.startQuiz(books.currentQuiz.id)
    step.value = 'quiz'
    currentQuestionIndex.value = 0
  } catch(e) {
    errorMsg.value = 'Failed to start the quiz. Please try again.'
    step.value = 'error'
  }
}

// ----------------------------------------------------
// QUIZ TAKING LOGIC
// ----------------------------------------------------
const currentQ = computed(() => quiz.currentQuiz?.questions ?? [])
const activeQuestion = computed(() => currentQ.value[currentQuestionIndex.value])

const progressPercent = computed(() => {
  if (currentQ.value.length === 0) return 0
  return ((currentQuestionIndex.value + 1) / currentQ.value.length) * 100
})

function isSelected(questionId: string, optionId: string) {
  return (quiz.answers.get(questionId) ?? []).includes(optionId)
}

function toggleOption(questionId: string, optionId: string, type: App.QuizQuestion['type']) {
  const current = quiz.answers.get(questionId) ?? []
  if (type === 'single' || type === 'true_false') {
    quiz.setAnswer(questionId, [optionId])
  } else {
    if (current.includes(optionId)) {
      quiz.setAnswer(questionId, current.filter(id => id !== optionId))
    } else {
      quiz.setAnswer(questionId, [...current, optionId])
    }
  }
}

function nextQuestion() {
  if (currentQuestionIndex.value < currentQ.value.length - 1) {
    currentQuestionIndex.value++
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function prevQuestion() {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const allAnswered = computed(() =>
  currentQ.value.every(q => (quiz.answers.get(q.id) ?? []).length > 0)
)

async function submit() {
  if (submitting.value) return
  submitting.value = true

  try {
    await quiz.submitQuiz()
    step.value = 'result'
    window.scrollTo({ top: 0, behavior: 'smooth' })
    if (quiz.result?.passed) {
      setTimeout(() => progress.loadBadges(), 2000)
    }
  } catch {
    // Show non-intrusive error if possible, or transition to error state
    alert("Submission failed. Your progress is saved. Please try again.")
  } finally {
    submitting.value = false
  }
}

// ----------------------------------------------------
// REVIEW LOGIC
// ----------------------------------------------------
const filteredReviewQuestions = computed(() => {
  if (!quiz.result) return []
  return quiz.result.per_question.filter(pq => {
    if (filter.value === 'correct') return pq.is_correct
    if (filter.value === 'incorrect') return !pq.is_correct
    return true
  })
})

function getQuestionOptions(questionId: string) {
  const q = currentQ.value.find(q => q.id === questionId)
  return q?.options || []
}

function getReviewOptionStyle(pq: any, optId: string) {
  const isCorrectOption = pq.correct_option_ids.includes(optId)
  const isSelectedOption = pq.selected_option_ids.includes(optId)

  if (isCorrectOption) {
    return 'border-emerald-500 bg-emerald-50'
  }
  if (isSelectedOption && !isCorrectOption) {
    return 'border-rose-300 bg-rose-50 opacity-80'
  }
  return 'border-(--sa-gray) bg-white opacity-60'
}

function getReviewOptionIconStyle(pq: any, optId: string) {
  const isCorrectOption = pq.correct_option_ids.includes(optId)
  const isSelectedOption = pq.selected_option_ids.includes(optId)

  if (isCorrectOption) {
    return 'border-emerald-500 bg-emerald-500'
  }
  if (isSelectedOption && !isCorrectOption) {
    return 'border-rose-500 bg-rose-500'
  }
  return 'border-gray-300 bg-transparent'
}
</script>

<template>
  <AppShell max-width="max-w-4xl">

    <!-- STATE: LOADING -->
    <div v-if="step === 'loading'" class="mt-20 flex flex-col items-center justify-center space-y-6 py-20">
      <div class="w-12 h-12 border-4 border-(--sa-gray) border-t-(--sa-dark) rounded-full animate-spin"></div>
      <p class="text-(--sa-taupe) font-medium text-lg">Loading quiz...</p>
    </div>

    <!-- STATE: ERROR -->
    <div v-else-if="step === 'error'" class="mt-20 max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-(--sa-gray) p-10 text-center">
      <div class="text-6xl mb-4">⚠️</div>
      <h2 class="text-2xl font-bold text-(--sa-dark) mb-2">Quiz Error</h2>
      <p class="text-(--sa-taupe) mb-8">{{ errorMsg }}</p>
      <div class="flex justify-center gap-4">
        <RouterLink :to="`/study`">
          <SaButton variant="secondary">Back to Study</SaButton>
        </RouterLink>
        <SaButton @click="loadQuizMetadata">Retry</SaButton>
      </div>
    </div>

    <!-- STATE A: INTRO -->
    <div v-else-if="step === 'intro' && books.currentQuiz" class="mt-16 sm:mt-24 max-w-3xl mx-auto bg-white dark:bg-[#0B1220] rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-(--sa-gray) dark:border-white/10 p-8 sm:p-16 text-center">
      <span class="inline-block px-4 py-1.5 mb-6 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-[#395886] dark:text-[#8AAEE0] bg-blue-50 dark:bg-[#1E293B] border border-blue-100 dark:border-white/10 rounded-full">
        Smart Adama Quiz
      </span>
      <h1 class="text-4xl sm:text-5xl font-display font-bold text-(--sa-dark) mb-5">Test Your Knowledge</h1>
      <p class="text-base sm:text-lg text-(--sa-taupe) mb-12 max-w-lg mx-auto leading-relaxed">
        Check how well you understand <strong>{{ books.currentQuiz.title }}</strong> and learn from your answers.
      </p>

      <div class="flex flex-wrap justify-center gap-4 sm:gap-6 mb-12">
        <div class="flex flex-col items-center justify-center bg-[#F8FAFC] dark:bg-[#1E293B] w-28 h-28 rounded-2xl border border-(--sa-gray) dark:border-white/5">
          <span class="text-3xl font-black text-(--sa-dark)">{{ books.currentQuiz.questions?.length || 5 }}</span>
          <span class="text-[0.6rem] uppercase text-(--sa-taupe) font-bold mt-2 tracking-wider">Questions</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-[#F8FAFC] dark:bg-[#1E293B] w-28 h-28 rounded-2xl border border-(--sa-gray) dark:border-white/5">
          <span class="text-3xl font-black text-(--sa-dark)">~3</span>
          <span class="text-[0.6rem] uppercase text-(--sa-taupe) font-bold mt-2 tracking-wider">Minutes</span>
        </div>
        <div class="flex flex-col items-center justify-center bg-[#F8FAFC] dark:bg-[#1E293B] w-28 h-28 rounded-2xl border border-(--sa-gray) dark:border-white/5">
          <span class="text-3xl font-black text-(--sa-dark)">{{ books.currentQuiz.passing_score_pct }}%</span>
          <span class="text-[0.6rem] uppercase text-(--sa-taupe) font-bold mt-2 tracking-wider">To Pass</span>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
        <button @click="beginQuiz" class="w-full sm:w-auto px-10 py-4 bg-(--sa-dark) text-white dark:text-[#0f172a] font-bold text-lg rounded-xl shadow-md hover:bg-[#2B4266] dark:hover:bg-[#E2E8F0] hover:-translate-y-0.5 transition-all">
          Start Quiz ❯
        </button>
        <RouterLink :to="`/study`" class="w-full sm:w-auto px-8 py-4 text-(--sa-taupe) font-bold hover:text-(--sa-dark) transition-colors">
          Back to Study
        </RouterLink>
      </div>
    </div>

    <!-- STATE B: TAKING QUIZ -->
    <div v-else-if="step === 'quiz' && activeQuestion" class="mt-8 max-w-3xl mx-auto pb-20">

      <!-- Compact Header -->
      <div class="mb-5 flex flex-col sm:flex-row justify-between items-end gap-4 border-b border-(--sa-gray) pb-4">
        <div>
          <p class="text-[0.65rem] text-(--sa-taupe) font-bold uppercase tracking-wider mb-1">{{ books.currentQuiz?.title }}</p>
          <h2 class="font-display text-xl font-bold text-(--sa-dark)">Question {{ currentQuestionIndex + 1 }} of {{ currentQ.length }}</h2>
        </div>
        <RouterLink :to="`/study`" class="text-xs font-bold text-(--sa-taupe) hover:text-(--sa-dark) uppercase tracking-wider bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-md transition-colors">
          Exit
        </RouterLink>
      </div>

      <!-- Progress Bar -->
      <div class="w-full h-1.5 bg-(--sa-gray) rounded-full mb-10 overflow-hidden">
        <div class="h-full bg-(--sa-dark) transition-all duration-300 ease-out" :style="{ width: `${progressPercent}%` }"></div>
      </div>

      <!-- Question -->
      <h1 class="text-2xl sm:text-3xl font-medium text-(--sa-dark) mb-10 leading-snug">
        {{ activeQuestion.question_text }}
      </h1>

      <!-- Answer Options -->
      <div class="space-y-4" role="radiogroup">
        <button
          v-for="opt in activeQuestion.options"
          :key="opt.id"
          @click="toggleOption(activeQuestion.id, opt.id, activeQuestion.type)"
          :class="[
            'w-full text-left p-5 sm:p-6 border-2 rounded-2xl transition-all flex items-start gap-4 group focus:outline-none focus:ring-4 focus:ring-blue-100',
            isSelected(activeQuestion.id, opt.id)
              ? 'border-[#395886] bg-[#F4F7FB] shadow-[0_4px_12px_rgb(57,88,134,0.08)]'
              : 'border-(--sa-gray) bg-white hover:border-[#8AAEE0] hover:bg-[#F8FAFC]'
          ]"
        >
          <div :class="[
            'w-6 h-6 shrink-0 rounded-full border-2 flex items-center justify-center transition-colors mt-0.5',
            isSelected(activeQuestion.id, opt.id) ? 'border-[#395886] bg-[#395886]' : 'border-[#B1C9EF] group-hover:border-[#395886]'
          ]">
            <div v-if="isSelected(activeQuestion.id, opt.id)" class="w-2 h-2 bg-white rounded-full"></div>
          </div>
          <span class="text-lg font-medium transition-colors leading-snug" :class="isSelected(activeQuestion.id, opt.id) ? 'text-[#395886]' : 'text-(--sa-dark)'">
            {{ opt.option_text }}
          </span>
        </button>
      </div>

      <!-- Navigation -->
      <div class="mt-10 pt-6 border-t border-(--sa-gray) flex flex-col sm:flex-row justify-between items-center gap-4">
        <button
          @click="prevQuestion"
          :disabled="currentQuestionIndex === 0"
          class="w-full sm:w-auto px-6 py-3.5 rounded-xl border-2 border-[#D5DEEF] text-(--sa-dark) font-bold hover:bg-[#F8FAFC] disabled:opacity-30 disabled:cursor-not-allowed transition-all"
        >
          ❮ Previous
        </button>

        <button
          v-if="currentQuestionIndex < currentQ.length - 1"
          @click="nextQuestion"
          class="w-full sm:w-auto px-10 py-3.5 rounded-xl bg-(--sa-dark) text-white font-bold hover:bg-[#2B4266] shadow-sm transition-all"
        >
          Next ❯
        </button>

        <button
          v-else
          @click="submit"
          :disabled="submitting || !allAnswered"
          class="w-full sm:w-auto px-10 py-3.5 rounded-xl font-bold shadow-sm transition-all flex items-center justify-center gap-2"
          :class="(submitting || !allAnswered) ? 'bg-[#D5DEEF] text-[#64748B] cursor-not-allowed' : 'bg-[#04AA6D] text-white hover:bg-[#039660] hover:-translate-y-0.5 shadow-lg shadow-emerald-600/20'"
        >
          <span v-if="submitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          {{ submitting ? 'Submitting...' : 'Finish Quiz ❯' }}
        </button>
      </div>

      <p v-if="currentQuestionIndex === currentQ.length - 1 && !allAnswered" class="text-center sm:text-right mt-4 text-xs font-bold uppercase tracking-wider text-rose-500">
        Please answer all questions before finishing
      </p>
    </div>

    <!-- STATE C: RESULT -->
    <div v-else-if="step === 'result' && quiz.result" class="mt-12 sm:mt-20 max-w-3xl mx-auto pb-20">
      <div class="bg-white rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-(--sa-gray) p-10 sm:p-16 text-center">
        <h1 class="text-3xl font-display font-bold text-(--sa-dark) mb-2">Quiz Complete</h1>
        <p class="text-(--sa-taupe) font-medium mb-12">{{ books.currentQuiz?.title }}</p>

        <!-- Score Indicator -->
        <div class="relative w-48 h-48 mx-auto mb-10 flex items-center justify-center rounded-full border-[10px]"
             :class="quiz.result.passed ? 'border-[#04AA6D] bg-emerald-50/30' : 'border-[#F59E0B] bg-amber-50/30'">
          <div class="text-center">
            <span class="block text-[3.5rem] leading-none font-black text-(--sa-dark)">{{ quiz.result.score_pct }}%</span>
            <span class="block text-[0.65rem] font-bold text-(--sa-taupe) uppercase tracking-widest mt-2">
              {{ quiz.result.correct_count }} / {{ quiz.result.total_questions }} Correct
            </span>
          </div>
        </div>

        <h2 class="text-2xl font-bold text-(--sa-dark) mb-3">
          {{ quiz.result.passed ? 'Excellent work.' : 'Keep learning.' }}
        </h2>
        <p class="text-(--sa-taupe) text-base sm:text-lg mb-10 max-w-md mx-auto leading-relaxed">
          {{ quiz.result.passed
             ? 'You have a strong understanding of this topic. Review any questions you missed below.'
             : 'Review the topic and the questions below, then try again to improve your score.'
          }}
        </p>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <button @click="step = 'review'; window.scrollTo({ top: 0, behavior: 'smooth' })" class="w-full sm:w-auto px-8 py-4 bg-(--sa-dark) text-white font-bold rounded-xl shadow-md hover:bg-[#2B4266] transition-all hover:-translate-y-0.5">
            Review Answers
          </button>
          <button @click="beginQuiz" class="w-full sm:w-auto px-8 py-4 bg-white border-2 border-(--sa-gray) text-(--sa-dark) font-bold rounded-xl hover:bg-gray-50 transition-colors">
            Try Again
          </button>
          <RouterLink :to="`/study`" class="w-full sm:w-auto px-8 py-4 text-(--sa-taupe) font-bold hover:text-(--sa-dark) transition-colors flex items-center justify-center">
            Back to Study
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- STATE D: REVIEW -->
    <div v-else-if="step === 'review' && quiz.result" class="mt-8 sm:mt-12 max-w-3xl mx-auto pb-24">

      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 mb-10 border-b border-(--sa-gray) pb-6">
        <div>
          <RouterLink @click.prevent="step = 'result'" to="#" class="inline-flex items-center text-xs font-bold uppercase tracking-wider text-(--sa-taupe) hover:text-(--sa-dark) mb-3 transition-colors">
            ❮ Back to Results
          </RouterLink>
          <h1 class="text-3xl font-display font-bold text-(--sa-dark)">Review Answers</h1>
          <p class="text-(--sa-taupe) mt-1 font-medium">{{ books.currentQuiz?.title }}</p>
        </div>

        <div class="flex bg-[#F8FAFC] p-1.5 rounded-xl border border-(--sa-gray)">
          <button @click="filter = 'all'" :class="['px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all', filter === 'all' ? 'bg-white text-(--sa-dark) shadow-sm' : 'text-(--sa-taupe) hover:text-(--sa-dark)']">All</button>
          <button @click="filter = 'correct'" :class="['px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all', filter === 'correct' ? 'bg-white text-[#04AA6D] shadow-sm' : 'text-(--sa-taupe) hover:text-(--sa-dark)']">Correct</button>
          <button @click="filter = 'incorrect'" :class="['px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all', filter === 'incorrect' ? 'bg-white text-[#E11D48] shadow-sm' : 'text-(--sa-taupe) hover:text-(--sa-dark)']">Incorrect</button>
        </div>
      </div>

      <div class="space-y-8">
        <div v-for="(pq, index) in filteredReviewQuestions" :key="pq.question_id" class="bg-white rounded-2xl border border-[#D5DEEF] shadow-[0_4px_20px_rgb(0,0,0,0.03)] overflow-hidden">

          <div :class="['px-6 sm:px-8 py-4 flex justify-between items-center border-b', pq.is_correct ? 'bg-[#F0FDF4] border-[#BBF7D0]' : 'bg-[#FFF1F2] border-[#FECDD3]']">
            <span class="font-bold text-xs uppercase tracking-widest flex items-center gap-1.5" :class="pq.is_correct ? 'text-[#166534]' : 'text-[#9F1239]'">
              <span v-if="pq.is_correct" class="text-base leading-none">✓</span>
              <span v-else class="text-base leading-none">✕</span>
              {{ pq.is_correct ? 'Correct' : 'Incorrect' }}
            </span>
            <span class="text-[#64748B] text-xs font-bold uppercase tracking-widest">Question {{ index + 1 }}</span>
          </div>

          <div class="p-6 sm:p-8">
            <h3 class="text-[1.35rem] font-medium text-(--sa-dark) mb-8 leading-snug">{{ pq.question_text }}</h3>

            <div class="space-y-3 mb-8">
              <div v-for="opt in getQuestionOptions(pq.question_id)" :key="opt.id"
                   :class="[
                     'px-5 py-4 rounded-xl border-2 flex items-start gap-4',
                     getReviewOptionStyle(pq, opt.id)
                   ]">
                 <div :class="['w-6 h-6 shrink-0 rounded-full border-2 flex items-center justify-center mt-0.5', getReviewOptionIconStyle(pq, opt.id)]">
                    <span v-if="pq.correct_option_ids.includes(opt.id)" class="text-xs font-bold text-white">✓</span>
                    <span v-else-if="pq.selected_option_ids.includes(opt.id) && !pq.is_correct" class="text-xs font-bold text-white">✕</span>
                 </div>

                 <div class="flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                   <span class="font-medium text-[1.05rem]" :class="pq.correct_option_ids.includes(opt.id) || pq.selected_option_ids.includes(opt.id) ? 'text-(--sa-dark)' : 'text-[#64748B]'">
                     {{ opt.option_text }}
                   </span>

                   <div class="flex flex-col items-start sm:items-end shrink-0 gap-1 mt-1 sm:mt-0">
                     <span v-if="pq.selected_option_ids.includes(opt.id)" class="text-[0.6rem] font-bold uppercase tracking-widest text-[#64748B] bg-gray-100 px-2 py-1 rounded">Your Answer</span>
                     <span v-if="pq.correct_option_ids.includes(opt.id) && !pq.selected_option_ids.includes(opt.id)" class="text-[0.6rem] font-bold uppercase tracking-widest text-[#166534] bg-[#F0FDF4] px-2 py-1 rounded">Correct Answer</span>
                   </div>
                 </div>
              </div>
            </div>

            <div v-if="pq.explanation" class="bg-[#F8FAFC] rounded-xl p-6 border border-[#E2E8F0]">
              <h4 class="text-[0.65rem] font-bold uppercase tracking-widest text-[#64748B] mb-2 flex items-center gap-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
                Explanation
              </h4>
              <p class="text-[#334155] text-sm leading-relaxed">{{ pq.explanation }}</p>
            </div>
          </div>
        </div>

        <div v-if="filteredReviewQuestions.length === 0" class="text-center py-16 bg-white border border-[#D5DEEF] rounded-2xl">
          <p class="text-[#64748B] font-medium text-lg">No questions match this filter.</p>
        </div>
      </div>

      <div class="mt-12 flex justify-center">
        <button @click="step = 'result'; window.scrollTo({ top: 0, behavior: 'smooth' })" class="px-8 py-3 bg-white border-2 border-[#D5DEEF] text-(--sa-dark) font-bold rounded-xl hover:bg-[#F8FAFC] transition-colors">
          ❮ Back to Results
        </button>
      </div>
    </div>

  </AppShell>
</template>
