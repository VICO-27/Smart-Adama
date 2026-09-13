<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import api from '@/api/client'
import { Users, BrainCircuit, BookOpen, AlertTriangle, Activity, BarChart2 } from 'lucide-vue-next'

// SVG Chart State
const hoveredIndex = ref<number | null>(null)
const chartMode = ref<'smooth' | 'step'>('smooth')
const chartFill = ref('')
const chartLineBlue = ref('')
const chartLinePurple = ref('')
const chartLineEmerald = ref('')
const loaded = ref(false)

// Real Data State
const dataEmerald = ref<number[]>([]) // Users
const dataBlue = ref<number[]>([])    // Queries
const dataPurple = ref<number[]>([])  // Quizzes
const dates = ref<string[]>([])

const stats = ref({
  books: 0,
  booksTrend: 0,
  chapters: 0,
  sections: 0,
  chunks: 0,
  chunksTrend: 0,
  users: 0,
  usersTrend: 0,
  quizAttempts: 0,
  completionRate: 0
})

const fetchAnalytics = async () => {
  try {
    const res = await api.get('/admin/analytics')
    const data = res.data.analytics

    stats.value = {
      books: data.total_books,
      booksTrend: data.books_this_week,
      chapters: data.total_chapters,
      sections: 0,
      chunks: data.total_chunks,
      chunksTrend: data.chunks_since_yesterday,
      users: data.total_users,
      usersTrend: data.users_trend_pct,
      quizAttempts: data.total_quiz_attempts,
      completionRate: data.avg_completion_pct
    }

    // Parse timeline
    if (data.timeline && data.timeline.length > 0) {
      // Use the last 13 days to fit the grid perfectly
      const recent = data.timeline.slice(-13)
      dates.value = recent.map((t: any) => {
        const d = new Date(t.date)
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      })
      dataEmerald.value = recent.map((t: any) => t.users)
      dataBlue.value = recent.map((t: any) => t.queries)
      dataPurple.value = recent.map((t: any) => t.quizzes)
    } else {
      // Fallback if no timeline
      dates.value = Array.from({length: 13}, (_, i) => `Day ${i+1}`)
      dataEmerald.value = Array(13).fill(0)
      dataBlue.value = Array(13).fill(0)
      dataPurple.value = Array(13).fill(0)
    }

    generateChartData()
    setTimeout(() => {
      loaded.value = true
    }, 100)

  } catch (e) {
    console.error('Failed to load dashboard stats', e)
  }
}

const generateChartData = () => {
  if (dataEmerald.value.length === 0) return

  const width = 1000
  const height = 240
  const segments = dataEmerald.value.length - 1
  const segmentWidth = width / segments

  // Find max value for dynamic scaling
  const maxVal = Math.max(
    ...dataEmerald.value,
    ...dataBlue.value,
    ...dataPurple.value,
    10 // ensure at least some height if all 0
  )
  const scale = (height * 0.8) / maxVal // leave 20% padding at top

  const generatePath = (data: number[]) => {
    let path = `M 0 ${height - data[0] * scale}`

    if (chartMode.value === 'smooth') {
      for(let i = 0; i < segments; i++) {
        const x1 = i * segmentWidth + segmentWidth / 2
        const y1 = height - data[i] * scale
        const x2 = i * segmentWidth + segmentWidth / 2
        const y2 = height - data[i+1] * scale
        const x3 = (i+1) * segmentWidth
        const y3 = height - data[i+1] * scale
        path += ` C ${x1} ${y1}, ${x2} ${y2}, ${x3} ${y3}`
      }
    } else {
      // Step chart
      for(let i = 0; i < segments; i++) {
        const xHalf = i * segmentWidth + segmentWidth / 2
        const y1 = height - data[i] * scale
        const xNext = (i+1) * segmentWidth
        const yNext = height - data[i+1] * scale
        path += ` L ${xHalf} ${y1} L ${xHalf} ${yNext} L ${xNext} ${yNext}`
      }
    }
    return path
  }

  chartLineEmerald.value = generatePath(dataEmerald.value)
  chartLineBlue.value = generatePath(dataBlue.value)
  chartLinePurple.value = generatePath(dataPurple.value)

  // Fill for the main blue line
  chartFill.value = `${chartLineBlue.value} L ${width} ${height} L 0 ${height} Z`
}

watch(chartMode, () => {
  generateChartData()
})

onMounted(() => {
  fetchAnalytics()
})

const getHoverY = (val: number) => {
  const height = 240
  const maxVal = Math.max(...dataEmerald.value, ...dataBlue.value, ...dataPurple.value, 10)
  const scale = (height * 0.8) / maxVal
  return height - (val * scale)
}
</script>

<template>
  <div class="space-y-8 pb-12 w-full max-w-7xl mx-auto">
    <!-- 1. The Global Command Hero Section -->
    <div
      class="bg-white/60 dark:bg-slate-900/80 backdrop-blur-2xl border border-white/40 dark:border-slate-800/80 shadow-[0_12px_40px_rgba(0,0,0,0.06)] dark:shadow-[0_12px_40px_rgba(0,0,0,0.4)] rounded-[40px] p-8 lg:p-10 w-full mb-8 relative overflow-hidden transition-all duration-700 transform"
      :class="loaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'"
    >
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-8 relative z-10 gap-4">
        <h2 class="text-3xl font-heavy text-slate-900 dark:text-white tracking-tight">Platform Pulse</h2>

        <div class="flex items-center gap-4">
          <!-- Chart Mode Toggle -->
          <div class="flex items-center bg-white/40 dark:bg-slate-800/40 p-1 rounded-full border border-slate-200/50 dark:border-slate-700/50">
            <button
              @click="chartMode = 'smooth'"
              class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all"
              :class="chartMode === 'smooth' ? 'bg-white dark:bg-slate-700 shadow-sm text-blue-600 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
            >
              <Activity class="w-4 h-4 inline-block mr-1" /> Smooth
            </button>
            <button
              @click="chartMode = 'step'"
              class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider transition-all"
              :class="chartMode === 'step' ? 'bg-white dark:bg-slate-700 shadow-sm text-blue-600 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
            >
              <BarChart2 class="w-4 h-4 inline-block mr-1" /> Step
            </button>
          </div>

          <div class="inline-flex items-center gap-3 bg-white/50 dark:bg-[#0B1220]/50 backdrop-blur-md px-4 py-2 rounded-full border border-slate-200/50 dark:border-slate-700/50 shadow-sm">
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">System Health: Optimal</span>
          </div>
        </div>
      </div>

      <!-- The Visualization -->
      <div class="w-full h-[280px] relative z-10 border-b border-slate-200/50 dark:border-slate-800/80">
        <!-- Grid Lines -->
        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-[1px]">
          <div class="w-full h-px bg-slate-200/40 dark:bg-slate-700/30"></div>
          <div class="w-full h-px bg-slate-200/40 dark:bg-slate-700/30"></div>
          <div class="w-full h-px bg-slate-200/40 dark:bg-slate-700/30"></div>
          <div class="w-full h-px bg-slate-200/40 dark:bg-slate-700/30"></div>
        </div>

        <svg viewBox="0 0 1000 240" preserveAspectRatio="none" class="w-full h-full overflow-visible relative z-20" @mouseleave="hoveredIndex = null">
          <defs>
            <linearGradient id="blueGradient" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.2" />
              <stop offset="100%" stop-color="#3B82F6" stop-opacity="0" />
            </linearGradient>
            <linearGradient id="emeraldGradient" x1="0" x2="0" y1="0" y2="1">
              <stop offset="0%" stop-color="#10B981" stop-opacity="0.15" />
              <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
            </linearGradient>
          </defs>

          <!-- Fills -->
          <path
            :d="chartFill"
            fill="url(#blueGradient)"
            class="transition-all duration-700 ease-in-out origin-bottom pointer-events-none"
            :class="loaded ? 'scale-y-100 opacity-100' : 'scale-y-0 opacity-0'"
          />

          <!-- Purple Line (Quizzes) -->
          <path
            :d="chartLinePurple"
            fill="none"
            stroke="#8B5CF6"
            stroke-width="3"
            stroke-linecap="round"
            class="transition-all duration-700 ease-in-out drop-shadow-[0_4px_12px_rgba(139,92,246,0.3)] pointer-events-none"
          />

          <!-- Emerald Line (Users) -->
          <path
            :d="chartLineEmerald"
            fill="none"
            stroke="#10B981"
            stroke-width="3"
            stroke-linecap="round"
            class="transition-all duration-700 ease-in-out drop-shadow-[0_4px_12px_rgba(16,185,129,0.3)] pointer-events-none"
          />

          <!-- Blue Line (Queries) -->
          <path
            :d="chartLineBlue"
            fill="none"
            stroke="#3B82F6"
            stroke-width="4"
            stroke-linecap="round"
            class="transition-all duration-700 ease-in-out drop-shadow-[0_6px_16px_rgba(59,130,246,0.4)] pointer-events-none"
          />

          <!-- Hover Target Zones (Invisible but catch mouse events) -->
          <rect
            v-if="dates.length > 0"
            v-for="(n, i) in dates.length" :key="`zone-${i}`"
            :x="i === 0 ? 0 : (i - 0.5) * (1000 / (dates.length - 1))"
            y="0"
            :width="i === 0 || i === dates.length - 1 ? (1000 / ((dates.length - 1) * 2)) : (1000 / (dates.length - 1))"
            height="240"
            fill="transparent"
            class="cursor-pointer"
            @mouseenter="hoveredIndex = i"
          />

          <!-- Hover Indicators (Vertical Line + Data Dots) -->
          <g v-if="hoveredIndex !== null && dataEmerald.length > 0" class="pointer-events-none transition-opacity duration-200">
            <!-- Vertical Guide Line -->
            <line
              :x1="hoveredIndex * (1000 / (dates.length - 1))" y1="0"
              :x2="hoveredIndex * (1000 / (dates.length - 1))" y2="240"
              stroke="currentColor"
              class="text-slate-300 dark:text-slate-600/50"
              stroke-width="2"
              stroke-dasharray="4 4"
            />

            <!-- Data Dots -->
            <circle :cx="hoveredIndex * (1000 / (dates.length - 1))" :cy="getHoverY(dataEmerald[hoveredIndex])" r="5" fill="#10B981" stroke="white" stroke-width="2" />
            <circle :cx="hoveredIndex * (1000 / (dates.length - 1))" :cy="getHoverY(dataBlue[hoveredIndex])" r="5" fill="#3B82F6" stroke="white" stroke-width="2" />
            <circle :cx="hoveredIndex * (1000 / (dates.length - 1))" :cy="getHoverY(dataPurple[hoveredIndex])" r="5" fill="#8B5CF6" stroke="white" stroke-width="2" />
          </g>
        </svg>

        <!-- Dynamic Hover Tooltip -->
        <div
          v-if="hoveredIndex !== null && dataEmerald.length > 0"
          class="absolute z-50 top-4 pointer-events-none bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-700 shadow-[0_8px_30px_rgb(0,0,0,0.12)] dark:shadow-[0_8px_30px_rgb(0,0,0,0.5)] rounded-2xl p-4 min-w-[170px] transform -translate-x-1/2 transition-all duration-150 ease-out"
          :style="{
            left: `${(hoveredIndex / (dates.length - 1)) * 100}%`,
            // Keep tooltip from bleeding off the edge
            transform: hoveredIndex === 0 ? 'translateX(0)' : hoveredIndex === dates.length - 1 ? 'translateX(-100%)' : 'translateX(-50%)'
          }"
        >
          <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mb-3 uppercase tracking-wider">{{ dates[hoveredIndex] }}</div>
          <div class="space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="flex items-center gap-2 font-semibold text-slate-600 dark:text-slate-300">
                <span class="w-2 h-2 rounded-full bg-[#10B981]"></span> Users
              </span>
              <span class="font-bold text-slate-900 dark:text-white">{{ dataEmerald[hoveredIndex].toLocaleString() }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="flex items-center gap-2 font-semibold text-slate-600 dark:text-slate-300">
                <span class="w-2 h-2 rounded-full bg-[#3B82F6]"></span> Queries
              </span>
              <span class="font-bold text-slate-900 dark:text-white">{{ dataBlue[hoveredIndex].toLocaleString() }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="flex items-center gap-2 font-semibold text-slate-600 dark:text-slate-300">
                <span class="w-2 h-2 rounded-full bg-[#8B5CF6]"></span> Quizzes
              </span>
              <span class="font-bold text-slate-900 dark:text-white">{{ dataPurple[hoveredIndex].toLocaleString() }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart Legend -->
      <div class="flex items-center gap-6 mt-6 relative z-10">
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-[#10B981]"></div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Active Users</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-[#3B82F6]"></div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">AI Document Queries</span>
        </div>
        <div class="flex items-center gap-2">
          <div class="w-3 h-3 rounded-full bg-[#8B5CF6]"></div>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Quizzes Completed</span>
        </div>
      </div>
    </div>

    <!-- 2. High-Level Telemetry Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

      <!-- Card 1 -->
      <div
        class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-white/60 dark:border-slate-800/80 rounded-[32px] p-6 shadow-sm transition-all duration-700 transform delay-100 hover:-translate-y-1 hover:shadow-md"
        :class="loaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'"
      >
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-500 dark:text-blue-400">
            <Users class="w-5 h-5" />
          </div>
          <span
            class="text-xs font-bold px-2 py-1 rounded-lg border"
            :class="stats.usersTrend >= 0 ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 border-transparent dark:border-emerald-500/20' : 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-500/10 border-transparent dark:border-red-500/20'"
          >
            {{ stats.usersTrend > 0 ? '+' : '' }}{{ stats.usersTrend }}% this month
          </span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Citizens</p>
        <h3 class="text-3xl font-heavy text-slate-900 dark:text-white mt-1">{{ stats.users.toLocaleString() }}</h3>
      </div>

      <!-- Card 2 -->
      <div
        class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-white/60 dark:border-slate-800/80 rounded-[32px] p-6 shadow-sm transition-all duration-700 transform delay-200 hover:-translate-y-1 hover:shadow-md"
        :class="loaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'"
      >
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-full bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center text-purple-500 dark:text-purple-400">
            <BrainCircuit class="w-5 h-5" />
          </div>
          <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg border border-transparent dark:border-slate-700">Live API</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Vector Embeddings</p>
        <h3 class="text-3xl font-heavy text-slate-900 dark:text-white mt-1">{{ stats.chunks.toLocaleString() }} <span class="text-sm text-slate-400 font-medium tracking-normal">Chunks</span></h3>
      </div>

      <!-- Card 3 -->
      <div
        class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-white/60 dark:border-slate-800/80 rounded-[32px] p-6 shadow-sm transition-all duration-700 transform delay-300 hover:-translate-y-1 hover:shadow-md"
        :class="loaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'"
      >
        <div class="flex items-center justify-between mb-4">
          <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center text-emerald-500 dark:text-emerald-400">
            <BookOpen class="w-5 h-5" />
          </div>
          <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-lg border border-transparent dark:border-emerald-500/20">{{ stats.completionRate }}% avg complete</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Learning Engagement</p>
        <h3 class="text-3xl font-heavy text-slate-900 dark:text-white mt-1">{{ stats.chapters.toLocaleString() }} <span class="text-sm text-slate-400 font-medium tracking-normal">Ch. Total</span></h3>
      </div>

      <!-- Card 4 -->
      <div
        class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-white/60 dark:border-slate-800/80 rounded-[32px] p-6 shadow-sm transition-all duration-700 transform delay-400 hover:-translate-y-1 hover:shadow-md relative overflow-hidden"
        :class="loaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-red-500/5 dark:from-amber-500/10 dark:to-red-500/10 pointer-events-none"></div>
        <div class="flex items-center justify-between mb-4 relative z-10">
          <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-500 dark:text-amber-400">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <span class="text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-lg border border-transparent dark:border-amber-700/30">Monitoring</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 relative z-10">System Load</p>
        <div class="mt-2 space-y-1 relative z-10">
          <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> {{ stats.quizAttempts.toLocaleString() }} Quiz Attempts
          </p>
          <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> {{ stats.books }} Books Ingested
          </p>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
</style>
