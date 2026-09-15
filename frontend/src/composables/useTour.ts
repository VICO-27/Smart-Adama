import { reactive, ref, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'

export interface TourStep {
  target: string // CSS selector
  title: string
  content: string
  placement?: 'top' | 'bottom' | 'left' | 'right' | 'center'
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

export function useTour() {
  const registerTour = (tour: TourDefinition) => {
    tours[tour.id] = tour
  }

  const getStorageKey = (id: string) => {
    const authStore = useAuthStore()
    const userId = authStore.user?.id || 'guest'
    return `sa_tour_${id}_completed_${userId}`
  }

  const startTour = async (tourId: string) => {
    if (!tours[tourId]) return
    const isCompleted = localStorage.getItem(getStorageKey(tourId)) === 'true'
    if (isCompleted) return

    state.currentTourId = tourId
    state.steps = tours[tourId].steps
    state.currentStepIndex = 0
    state.isActive = true

    await prepareCurrentStep()
  }

  const prepareCurrentStep = async () => {
    const step = state.steps[state.currentStepIndex]
    if (step && step.onBeforeShow) {
      await step.onBeforeShow()
      await nextTick()
      await new Promise(r => setTimeout(r, 300))
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
      localStorage.setItem(getStorageKey(state.currentTourId), 'true')
    }
    closeTour()
  }

  const finishTour = () => {
    if (state.currentTourId) {
      localStorage.setItem(getStorageKey(state.currentTourId), 'true')
    }
    closeTour()
  }

  const closeTour = () => {
    state.isActive = false
    state.currentTourId = null
    state.steps = []
    state.currentStepIndex = 0
  }
  
  const resetTour = (tourId: string) => {
    localStorage.removeItem(getStorageKey(tourId))
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
    resetTour
  }
}
