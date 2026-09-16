import { reactive, nextTick, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

export interface TourStep {
  target: string // CSS selector — prefer [data-tour="..."]
  title: string
  content: string
  placement?: 'top' | 'bottom' | 'left' | 'right' | 'center'
  gesture?: 'tap' | 'swipe-right' | 'swipe-left' | 'scroll' | null
  onBeforeShow?: () => Promise<void> | void
}

export interface TourDefinition {
  id: string
  steps: TourStep[]
}

const state = reactive({
  isActive: false,
  currentTourId: null as string | null,
  currentStepIndex: 0,
  steps: [] as TourStep[],
})

const tours: Record<string, TourDefinition> = {}

// Scrollbar width compensation (prevents layout shift on overflow:hidden)
let _scrollbarWidth = 0
function getScrollbarWidth(): number {
  if (_scrollbarWidth) return _scrollbarWidth
  const div = document.createElement('div')
  div.style.cssText = 'width:100px;height:100px;overflow:scroll;position:absolute;top:-9999px'
  document.body.appendChild(div)
  _scrollbarWidth = div.offsetWidth - div.clientWidth
  document.body.removeChild(div)
  return _scrollbarWidth
}

function lockScroll() {
  const scrollbarWidth = getScrollbarWidth()
  document.body.style.paddingRight = `${scrollbarWidth}px`
  document.body.style.overflow = 'hidden'
}

function unlockScroll() {
  document.body.style.paddingRight = ''
  document.body.style.overflow = ''
}

export function useTour() {
  const registerTour = (tour: TourDefinition) => {
    tours[tour.id] = tour
  }

  const getStorageKey = (id: string) => {
    try {
      const authStore = useAuthStore()
      const userId = authStore.user?.id || 'guest'
      return `sa_tour_${id}_completed_${userId}`
    } catch {
      return `sa_tour_${id}_completed_guest`
    }
  }

  const startTour = async (tourId: string) => {
    if (!tours[tourId]) return
    try {
      const isCompleted = localStorage.getItem(getStorageKey(tourId)) === 'true'
      if (isCompleted) return
    } catch {
      // Ignore storage errors — just show the tour
    }

    // Store the element that was focused before opening the tour
    ;(state as any)._previousFocus = document.activeElement

    state.currentTourId = tourId
    state.steps = tours[tourId].steps
    state.currentStepIndex = 0
    state.isActive = true

    lockScroll()

    // Watch for route change and teardown
    try {
      const router = useRouter()
      const stop = watch(
        () => router.currentRoute.value.fullPath,
        () => {
          closeTour()
          stop()
        },
      )
      ;(state as any)._routeWatchStop = stop
    } catch {
      // Not inside a component context — skip route watch
    }

    await prepareCurrentStep()
  }

  const prepareCurrentStep = async () => {
    const step = state.steps[state.currentStepIndex]
    if (step?.onBeforeShow) {
      await step.onBeforeShow()
      await nextTick()
      // Give transitions time to settle before ProductTour measures
      await new Promise(r => setTimeout(r, 350))
    }
  }

  const nextStep = async () => {
    if (state.currentStepIndex < state.steps.length - 1) {
      state.currentStepIndex++
      await prepareCurrentStep()
    } else {
      finishTour()
    }
  }

  const prevStep = async () => {
    if (state.currentStepIndex > 0) {
      state.currentStepIndex--
      await prepareCurrentStep()
    }
  }

  const skipTour = () => {
    if (state.currentTourId) {
      try {
        localStorage.setItem(getStorageKey(state.currentTourId), 'true')
      } catch {}
    }
    closeTour()
  }

  const finishTour = () => {
    if (state.currentTourId) {
      try {
        localStorage.setItem(getStorageKey(state.currentTourId), 'true')
      } catch {}
    }
    closeTour()
  }

  const closeTour = () => {
    state.isActive = false
    state.currentTourId = null
    state.steps = []
    state.currentStepIndex = 0
    unlockScroll()

    // Restore focus
    const prev = (state as any)._previousFocus
    if (prev && typeof prev.focus === 'function') {
      try {
        prev.focus()
      } catch {}
    }
    ;(state as any)._previousFocus = null

    // Stop route watcher if active
    const stop = (state as any)._routeWatchStop
    if (typeof stop === 'function') {
      stop()
      ;(state as any)._routeWatchStop = null
    }
  }

  const resetTour = (tourId: string) => {
    try {
      localStorage.removeItem(getStorageKey(tourId))
    } catch {}
  }

  return {
    state,
    registerTour,
    startTour,
    nextStep,
    prevStep,
    skipTour,
    finishTour,
    closeTour,
    resetTour,
  }
}

