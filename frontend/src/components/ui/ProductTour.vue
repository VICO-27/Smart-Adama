<template>
  <Teleport to="body">
    <Transition name="tour-fade">
      <div v-if="state.isActive" class="tour-overlay">
        <!-- Spotlight Cutout -->
        <div 
          v-if="currentStep?.placement !== 'center'" 
          class="tour-spotlight" 
          :style="spotlightStyle"
        ></div>
        <div v-else class="tour-backdrop-full"></div>

        <!-- Tooltip Card -->
        <div class="tour-card-wrapper" :style="wrapperStyle">
          <div class="tour-card">
            <div class="tour-card__header">
              <h3 class="tour-card__title">{{ currentStep?.title ? t(currentStep.title) : '' }}</h3>
              <button class="tour-card__close" @click="skipTour" :aria-label="t('tour.controls.skip')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" />
                </svg>
              </button>
            </div>
            
            <p class="tour-card__content">{{ currentStep?.content ? t(currentStep.content) : '' }}</p>
            
            <div class="tour-card__footer">
              <div class="tour-card__progress">
                <div 
                  v-for="(step, index) in state.steps" 
                  :key="index"
                  class="tour-card__dot"
                  :class="{ 'is-active': index === state.currentStepIndex }"
                ></div>
              </div>
              
              <div class="tour-card__actions">
                <button v-if="state.currentStepIndex > 0" class="btn-tour-secondary" @click="prevStep">{{ t('tour.controls.back') }}</button>
                <button class="btn-tour-primary" @click="nextStep">
                  {{ isLastStep ? t('tour.controls.finish') : t('tour.controls.next') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useTour } from '@/composables/useTour'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { state, nextStep, prevStep, skipTour } = useTour()

const spotlightRect = ref({ top: 0, left: 0, width: 0, height: 0 })
const cardRect = ref({ width: 320, height: 160 })

const currentStep = computed(() => state.steps[state.currentStepIndex])
const isLastStep = computed(() => state.currentStepIndex === state.steps.length - 1)

const updatePosition = () => {
  if (!state.isActive || !currentStep.value) return

  if (currentStep.value.placement === 'center') {
    return // Centered mode needs no target rect
  }

  const getVisibleTarget = (selector: string) => {
    const elements = document.querySelectorAll(selector)
    for (const el of elements) {
      const rect = el.getBoundingClientRect()
      if (rect.width > 0 && rect.height > 0) {
        return el
      }
    }
    return null
  }

  const targetEl = getVisibleTarget(currentStep.value.target)
  if (targetEl) {
    // Smoothly scroll element into view if mostly off-screen
    const rect = targetEl.getBoundingClientRect()
    const isInViewport = rect.top >= 0 && rect.left >= 0 && 
                         rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                         rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    
    if (!isInViewport) {
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' })
      // Wait for scroll to finish before measuring
      setTimeout(measureTarget, 400)
    } else {
      measureTarget()
    }
  } else {
    // Target not found, wait and retry (might be rendering)
    setTimeout(measureTarget, 200)
  }
}

const measureTarget = () => {
  if (!currentStep.value) return
  const elements = document.querySelectorAll(currentStep.value.target)
  let targetEl = null
  for (const el of elements) {
    const rect = el.getBoundingClientRect()
    if (rect.width > 0 && rect.height > 0) {
      targetEl = el
      break
    }
  }
  
  if (targetEl) {
    const rect = targetEl.getBoundingClientRect()
    // Add small padding around the element
    const padding = 8
    spotlightRect.value = {
      top: rect.top - padding,
      left: rect.left - padding,
      width: rect.width + padding * 2,
      height: rect.height + padding * 2
    }
  }
}

watch(() => state.currentStepIndex, updatePosition)
watch(() => state.isActive, (active) => {
  if (active) {
    nextTick(updatePosition)
    window.addEventListener('resize', updatePosition)
    window.addEventListener('scroll', updatePosition, { passive: true })
    window.addEventListener('keydown', handleKeydown)
  } else {
    window.removeEventListener('resize', updatePosition)
    window.removeEventListener('scroll', updatePosition)
    window.removeEventListener('keydown', handleKeydown)
  }
})

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Escape') skipTour()
  if (e.key === 'ArrowRight' || e.key === 'Enter') nextStep()
  if (e.key === 'ArrowLeft') prevStep()
}

const spotlightStyle = computed(() => ({
  top: `${spotlightRect.value.top}px`,
  left: `${spotlightRect.value.left}px`,
  width: `${spotlightRect.value.width}px`,
  height: `${spotlightRect.value.height}px`,
}))

const wrapperStyle = computed(() => {
  if (currentStep.value?.placement === 'center') {
    return {
      top: '50%',
      left: '50%',
      transform: 'translate(-50%, -50%)'
    }
  }

  const padding = 16
  const { top, left, width, height } = spotlightRect.value
  const placement = currentStep.value?.placement || 'bottom'
  
  let x = left + width / 2 - cardRect.value.width / 2
  let y = top + height + padding

  if (placement === 'top') {
    y = top - cardRect.value.height - padding
  } else if (placement === 'left') {
    x = left - cardRect.value.width - padding
    y = top + height / 2 - cardRect.value.height / 2
  } else if (placement === 'right') {
    x = left + width + padding
    y = top + height / 2 - cardRect.value.height / 2
  }

  // Keep within bounds
  x = Math.max(16, Math.min(x, window.innerWidth - cardRect.value.width - 16))
  y = Math.max(16, Math.min(y, window.innerHeight - cardRect.value.height - 16))

  return {
    top: `${y}px`,
    left: `${x}px`,
  }
})
</script>

<style scoped>
.tour-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999; /* Above everything */
  pointer-events: none; /* Let clicks pass through to skip buttons, but block underlying? Wait. */
}

/* We want to block clicks on the rest of the app, but allow clicks inside the card. */
.tour-overlay {
  pointer-events: auto; /* Block background clicks */
}

.tour-backdrop-full {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(2px);
}

.tour-spotlight {
  position: absolute;
  border-radius: 12px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.65);
  pointer-events: none; /* Let clicks pass through the hole? The overlay blocks them anyway. */
  transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
  /* The glow */
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.65), 0 0 20px 4px rgba(255, 255, 255, 0.1) inset;
}

.tour-card-wrapper {
  position: absolute;
  width: 320px;
  transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1);
}

.tour-card {
  background: rgba(25, 30, 40, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 20px;
  color: #fff;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
  font-family: 'SF Pro Text', 'Inter', sans-serif;
}

.tour-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.tour-card__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  letter-spacing: -0.02em;
}

.tour-card__close {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  padding: 4px;
  margin: -4px;
  border-radius: 50%;
  transition: color 0.2s, background 0.2s;
}
.tour-card__close:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.1);
}
.tour-card__close svg {
  width: 16px;
  height: 16px;
}

.tour-card__content {
  margin: 0 0 20px 0;
  font-size: 0.925rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.8);
}

.tour-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tour-card__progress {
  display: flex;
  gap: 6px;
}
.tour-card__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  transition: background 0.3s, transform 0.3s;
}
.tour-card__dot.is-active {
  background: #fff;
  transform: scale(1.2);
}

.tour-card__actions {
  display: flex;
  gap: 12px;
}

.btn-tour-secondary {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  padding: 6px 12px;
  border-radius: 8px;
  transition: background 0.2s, color 0.2s;
}
.btn-tour-secondary:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.1);
}

.btn-tour-primary {
  background: #fff;
  color: #000;
  border: none;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  padding: 6px 16px;
  border-radius: 8px;
  transition: transform 0.2s, background 0.2s;
}
.btn-tour-primary:hover {
  transform: scale(1.02);
  background: #f0f0f0;
}
.btn-tour-primary:active {
  transform: scale(0.98);
}

/* Animations */
.tour-fade-enter-active,
.tour-fade-leave-active {
  transition: opacity 0.3s ease;
}
.tour-fade-enter-from,
.tour-fade-leave-to {
  opacity: 0;
}
.tour-fade-enter-active .tour-card-wrapper {
  animation: tourCardEnter 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes tourCardEnter {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
