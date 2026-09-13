<script setup lang="ts">
import { ref, nextTick, onMounted, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: string[]
    disabled?: boolean
    invalid?: boolean
    autoFocus?: boolean
  }>(),
  {
    disabled: false,
    invalid: false,
    autoFocus: true,
  }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string[]): void
  (e: 'complete', code: string): void
}>()

const inputRefs = ref<(HTMLInputElement | null)[]>([])

function focusInput(index: number) {
  nextTick(() => {
    if (index >= 0 && index < 6 && inputRefs.value[index]) {
      inputRefs.value[index]?.focus()
      inputRefs.value[index]?.select()
    }
  })
}

function handleInput(index: number, event: Event) {
  const target = event.target as HTMLInputElement
  const rawValue = target.value

  // Strip non-digits
  const cleanChar = rawValue.replace(/\D/g, '').slice(-1)

  const newValues = [...props.modelValue]
  newValues[index] = cleanChar
  emit('update:modelValue', newValues)

  if (cleanChar) {
    if (index < 5) {
      focusInput(index + 1)
    }
    // Check if full
    const full = newValues.join('')
    if (full.length === 6 && /^\d{6}$/.test(full)) {
      emit('complete', full)
    }
  }
}

function handleKeyDown(index: number, event: KeyboardEvent) {
  if (event.key === 'Backspace') {
    const currentVal = props.modelValue[index]
    if (!currentVal && index > 0) {
      event.preventDefault()
      const newValues = [...props.modelValue]
      newValues[index - 1] = ''
      emit('update:modelValue', newValues)
      focusInput(index - 1)
    } else if (currentVal) {
      const newValues = [...props.modelValue]
      newValues[index] = ''
      emit('update:modelValue', newValues)
    }
  } else if (event.key === 'ArrowLeft' && index > 0) {
    event.preventDefault()
    focusInput(index - 1)
  } else if (event.key === 'ArrowRight' && index < 5) {
    event.preventDefault()
    focusInput(index + 1)
  }
}

function handlePaste(event: ClipboardEvent) {
  event.preventDefault()
  const pasted = event.clipboardData?.getData('text') || ''
  const digits = pasted.replace(/\D/g, '').slice(0, 6)

  if (!digits) return

  const newValues = [...props.modelValue]
  for (let i = 0; i < 6; i++) {
    newValues[i] = digits[i] || ''
  }
  emit('update:modelValue', newValues)

  const focusTarget = Math.min(digits.length, 5)
  focusInput(focusTarget)

  if (digits.length === 6) {
    emit('complete', digits)
  }
}

onMounted(() => {
  if (props.autoFocus) {
    focusInput(0)
  }
})

// Focus first input when invalid state clears or is set
watch(
  () => props.invalid,
  (isInv) => {
    if (isInv) {
      focusInput(0)
    }
  }
)

defineExpose({
  focusFirst: () => focusInput(0),
})
</script>

<template>
  <div class="flex items-center justify-between gap-1.5 sm:gap-2.5 w-full my-2 select-none" @paste="handlePaste">
    <input
      v-for="(_, index) in 6"
      :key="index"
      :ref="(el) => (inputRefs[index] = el as HTMLInputElement | null)"
      type="text"
      inputmode="numeric"
      pattern="[0-9]*"
      maxlength="1"
      :value="modelValue[index] || ''"
      :disabled="disabled"
      class="w-11 h-13 sm:w-12 sm:h-14 text-center text-xl sm:text-2xl font-bold font-mono text-white bg-white/10 border rounded-xl focus:outline-none transition-all duration-150"
      :class="[
        invalid
          ? 'border-red-400/80 bg-red-500/15 text-red-200 focus:border-red-400 focus:ring-2 focus:ring-red-400/30'
          : 'border-white/20 focus:border-white focus:bg-white/20 focus:ring-2 focus:ring-white/25',
        disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-text hover:bg-white/15',
      ]"
      @input="(e) => handleInput(index, e)"
      @keydown="(e) => handleKeyDown(index, e)"
    />
  </div>
</template>
