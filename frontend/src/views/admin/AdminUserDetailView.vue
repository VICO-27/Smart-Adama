<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, User, Activity, BookOpen, Edit2, ShieldAlert, Shield, CheckCircle2, Clock, PlayCircle, MapPin } from 'lucide-vue-next'
import api from '@/api/client'

const route = useRoute()
const router = useRouter()
const userId = route.params.id

const user = ref<any>(null)
const loading = ref(true)

const loadUser = async () => {
  loading.value = true
  try {
    const res = await api.get(`/admin/users/${userId}`)
    user.value = res.data.user
  } catch (error) {
    console.error('Failed to load user:', error)
    alert('Failed to load user details.')
    router.push('/admin/users')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUser()
})

const getPlaceholderTitle = (index: number): string => {
  const titles = [
    'Introduction & Preface',
    'Smart Governance',
    'Digital Adama',
    'Smart Security',
    'Smart Urban Design',
    'Smart Environment',
    'Smart Mobility',
    'Smart Social Services',
    'Smart Tourism and Culture',
    'Smart Public Relation',
    'Smart People'
  ]
  return titles[index] || `Chapter ${index + 1}`
}
</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- Top Nav -->
    <button @click="router.push('/admin/users')" class="group inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">
      <div class="p-1.5 rounded-full bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 group-hover:scale-110 transition-transform">
        <ArrowLeft class="h-4 w-4" />
      </div>
      Back to Directory
    </button>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-pulse">
      <div class="h-[400px] lg:col-span-1 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-[32px] border border-slate-200/50 dark:border-slate-800/50"></div>
      <div class="h-[400px] lg:col-span-2 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-[32px] border border-slate-200/50 dark:border-slate-800/50"></div>
      <div class="h-[300px] lg:col-span-3 bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-[32px] border border-slate-200/50 dark:border-slate-800/50"></div>
    </div>

    <template v-else-if="user">
      <div class="flex items-center justify-between mb-2">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">User Intelligence Center</h1>
        <div class="flex items-center gap-3">
          <button class="inline-flex items-center gap-2 rounded-full bg-white dark:bg-[#0B1220] px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white shadow-sm ring-1 ring-inset ring-slate-300 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            <Edit2 class="h-4 w-4 text-slate-400" /> Edit User
          </button>
          <button class="inline-flex items-center gap-2 rounded-full bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 px-4 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset ring-red-200 dark:ring-red-900/50 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors">
            <ShieldAlert class="h-4 w-4" /> Suspend
          </button>
        </div>
      </div>

      <!-- 360 Dashboard Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- PANEL A: Registration & Identity -->
        <div class="lg:col-span-1 bg-gradient-to-br from-[#8AAEE0] to-[#638ECB] dark:from-[#0B1220] dark:to-[#0B1220] backdrop-blur-2xl rounded-[32px] border-transparent dark:border-slate-800/80 p-8 shadow-sm flex flex-col items-center text-center relative overflow-hidden">
          <div class="absolute top-0 inset-x-0 h-32 bg-white/10 dark:bg-white/5"></div>

          <img :src="user.avatar" class="relative z-10 w-28 h-28 rounded-full object-cover ring-4 ring-white/30 dark:ring-slate-800/50 shadow-md mb-4" />

          <h2 class="relative z-10 text-2xl font-bold text-white">{{ user.name }}</h2>
          <p class="relative z-10 text-blue-50 dark:text-slate-400 font-medium mt-1">{{ user.email }}</p>

          <span
            class="relative z-10 mt-4 px-3 py-1.5 text-xs font-bold uppercase tracking-widest rounded-full border border-transparent dark:border-transparent"
            :class="[
              user.status === 'Active'
                ? 'bg-emerald-500 text-white shadow-sm dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20'
                : 'bg-white/20 text-white dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
            ]"
          >
            {{ user.status }}
          </span>

          <div class="w-full mt-8 pt-8 border-t border-white/20 dark:border-slate-800 flex flex-col gap-5 text-left relative z-10">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-full bg-black/10 dark:bg-slate-800 flex items-center justify-center text-white dark:text-slate-300">
                <Shield class="w-5 h-5" />
              </div>
              <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-100 dark:text-slate-500">Platform Role</p>
                <p class="text-sm font-semibold text-white">Learner (Lvl {{ user.level }})</p>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-full bg-black/10 dark:bg-slate-800 flex items-center justify-center text-white dark:text-slate-300">
                <Activity class="w-5 h-5" />
              </div>
              <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-100 dark:text-slate-500">Total Experience</p>
                <p class="text-sm font-semibold text-white">{{ user.xp }} XP</p>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-full bg-black/10 dark:bg-slate-800 flex items-center justify-center text-white dark:text-slate-300">
                <Clock class="w-5 h-5" />
              </div>
              <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-100 dark:text-slate-500">Registered</p>
                <p class="text-sm font-semibold text-white">{{ user.registered }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- PANEL B: Curriculum & Chapter Progress -->
        <div class="lg:col-span-2 bg-white dark:bg-[#0B1220]/80 backdrop-blur-2xl rounded-[32px] border border-slate-100 dark:border-slate-800/80 p-8 shadow-sm flex flex-col">

          <div class="flex items-center justify-between mb-8">
            <div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white">Curriculum Journey</h3>
              <p class="text-sm text-slate-500 mt-1">Course progression and mastery analytics.</p>
            </div>

            <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-900 p-3 pr-6 rounded-2xl border border-slate-100 dark:border-slate-800">
              <div class="relative w-12 h-12 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                  <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="8" class="text-slate-200 dark:text-slate-800" />
                  <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="8"
                          :class="user.overall_progress >= 100 ? 'text-[#008A00]' : 'text-[#3B82F6]'"
                          :stroke-dasharray="283"
                          :stroke-dashoffset="283 - (283 * user.overall_progress / 100)"
                          stroke-linecap="round" />
                </svg>
              </div>
              <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Progress</div>
                <div class="text-xl font-heavy text-slate-900 dark:text-white">{{ user.overall_progress }}%</div>
              </div>
            </div>
          </div>

          <!-- Scrollable Inset Container for Chapters -->
          <div class="flex-1 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/60 p-2 overflow-y-auto max-h-[360px] custom-scrollbar">
            <div class="flex flex-col gap-2">
              <div v-for="(cp, index) in user.chapter_progress" :key="cp.chapter_id"
                   class="bg-white dark:bg-[#0B1220] rounded-xl p-4 border border-slate-100 dark:border-slate-800 flex items-center justify-between transition-all hover:shadow-md">

                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-full flex items-center justify-center font-heavy text-lg"
                       :class="[
                         cp.status === 'COMPLETED' ? 'bg-[#008A00]/10 text-[#008A00] dark:bg-[#008A00]/20 dark:text-emerald-400' :
                         cp.status === 'IN_PROGRESS' ? 'bg-[#3B82F6]/10 text-[#3B82F6] dark:bg-[#3B82F6]/20' :
                         'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                       ]">
                    {{ String(index + 1).padStart(2, '0') }}
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white text-sm">{{ getPlaceholderTitle(index) }}</h4>
                    <p class="text-xs text-slate-500 mt-0.5" v-if="cp.status === 'IN_PROGRESS'">{{ cp.reading_progress }}% Read</p>
                    <p class="text-xs text-slate-500 mt-0.5" v-else-if="cp.status === 'NOT_STARTED'">Not Started</p>
                    <p class="text-xs font-medium text-[#008A00] mt-0.5 flex items-center gap-1" v-else-if="cp.status === 'COMPLETED'">
                      <CheckCircle2 class="w-3 h-3" /> Mastered
                    </p>
                  </div>
                </div>

                <div class="text-right">
                  <div v-if="cp.status === 'COMPLETED' && cp.best_quiz_score_pct !== null">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Quiz Score</div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white">{{ cp.best_quiz_score_pct }}%</div>
                  </div>
                  <span v-else-if="cp.status === 'IN_PROGRESS'" class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 text-[11px] font-bold uppercase tracking-wider">
                    In Progress
                  </span>
                </div>

              </div>
            </div>
          </div>

        </div>

        <!-- PANEL C: Recent Activity & Audit Trail -->
        <div class="lg:col-span-3 bg-white dark:bg-[#0B1220]/80 backdrop-blur-2xl rounded-[32px] border border-slate-100 dark:border-slate-800/80 p-8 shadow-sm">
          <div class="flex items-center gap-3 mb-6">
            <div class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
              <MapPin class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white">Activity Footprint</h3>
              <p class="text-sm text-slate-500">Chronological trail of user interactions and quiz attempts.</p>
            </div>
          </div>

          <div class="bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/60 p-6">
            <div v-if="!user.quiz_attempts || user.quiz_attempts.length === 0" class="text-center py-12">
              <Clock class="mx-auto h-8 w-8 text-slate-300 mb-3" />
              <h4 class="text-slate-900 dark:text-white font-medium">No recent activity</h4>
              <p class="text-slate-500 text-sm">The user hasn't completed any quizzes yet.</p>
            </div>

            <!-- iOS Style Timeline -->
            <div v-else class="relative border-l-2 border-slate-200 dark:border-slate-700 ml-4 pl-8 py-2 space-y-8">
              <div v-for="attempt in user.quiz_attempts" :key="attempt.id" class="relative">
                <!-- Timeline Dot -->
                <div class="absolute -left-[41px] top-1 w-5 h-5 rounded-full border-4 border-white dark:border-[#0B1220] shadow-sm flex items-center justify-center"
                     :class="attempt.passed ? 'bg-[#008A00]' : 'bg-red-500'">
                </div>

                <div class="flex items-start justify-between">
                  <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">
                      {{ attempt.passed ? 'Passed' : 'Failed' }} {{ attempt.quiz?.title || 'Chapter Quiz' }}
                    </h4>
                    <p class="text-sm text-slate-500 mt-1">
                      Scored <span class="font-bold" :class="attempt.passed ? 'text-[#008A00]' : 'text-red-500'">{{ attempt.score_pct }}%</span>
                      on the assessment.
                    </p>
                  </div>
                  <span class="text-xs font-medium text-slate-400 bg-white dark:bg-slate-800 px-3 py-1 rounded-full shadow-sm border border-slate-100 dark:border-slate-700">
                    {{ new Date(attempt.created_at).toLocaleString() }}
                  </span>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </template>
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
  background-color: #cbd5e1;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #334155;
}
</style>
