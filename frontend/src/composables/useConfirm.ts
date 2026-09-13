import { ref } from 'vue'

export interface ConfirmOptions {
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  confirmColor?: 'red' | 'blue'
}

const isOpen = ref(false)
const options = ref<ConfirmOptions>({ message: '' })
let resolvePromise: ((value: boolean) => void) | null = null

export function useConfirm() {
  const confirm = (opts: string | ConfirmOptions): Promise<boolean> => {
    if (typeof opts === 'string') {
      options.value = { message: opts, confirmColor: 'red', confirmText: 'Confirm' }
    } else {
      options.value = { ...opts, confirmColor: opts.confirmColor || 'red', confirmText: opts.confirmText || 'Confirm' }
    }
    isOpen.value = true

    return new Promise((resolve) => {
      resolvePromise = resolve
    })
  }

  const proceed = () => {
    isOpen.value = false
    if (resolvePromise) resolvePromise(true)
  }

  const cancel = () => {
    isOpen.value = false
    if (resolvePromise) resolvePromise(false)
  }

  return {
    confirm,
    isOpen,
    options,
    proceed,
    cancel
  }
}
