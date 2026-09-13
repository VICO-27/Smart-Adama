/**
 * useScrollReveal
 * Applies a fade/slide-up reveal when elements enter the viewport.
 * Respects prefers-reduced-motion automatically (Req 15.5).
 *
 * Usage:
 *   const el = ref<HTMLElement | null>(null)
 *   useScrollReveal(el)
 *
 * The element needs the `.reveal` CSS class (or inline style) to
 * start invisible; this composable adds `.revealed` when in view.
 */

import { onMounted, onUnmounted, type Ref } from 'vue'

export interface ScrollRevealOptions {
  threshold?: number   // 0–1, default 0.15
  rootMargin?: string  // default '0px'
  once?: boolean       // default true — stop observing after first reveal
}

export function useScrollReveal(
  target: Ref<HTMLElement | null> | HTMLElement | null,
  options: ScrollRevealOptions = {},
) {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

  let observer: IntersectionObserver | null = null

  onMounted(() => {
    const el = target instanceof HTMLElement ? target : target?.value
    if (!el) return

    // Reduced-motion: reveal immediately without animation
    if (prefersReduced) {
      el.classList.add('revealed')
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('revealed')
            if (options.once !== false) observer?.unobserve(entry.target)
          }
        })
      },
      {
        threshold:  options.threshold  ?? 0.15,
        rootMargin: options.rootMargin ?? '0px',
      },
    )

    observer.observe(el)
  })

  onUnmounted(() => observer?.disconnect())
}

/**
 * Directive version — v-reveal — for declarative use in templates.
 * Supports:
 * - High performance fluid spring animations (cubic-bezier(0.16, 1, 0.3, 1))
 * - Child stagger cascade via .reveal-child
 * - Instant animation for elements already in viewport upon mount/refresh
 * - Respects prefers-reduced-motion automatically
 */
export const scrollRevealDirective = {
  mounted(el: HTMLElement, binding?: { value?: number | { delay?: number; distance?: number } }) {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (prefersReduced) {
      el.style.opacity = '1'
      el.style.transform = 'none'
      el.classList.add('is-revealed')
      return
    }

    const customDelay = typeof binding?.value === 'number'
      ? binding.value
      : (binding?.value?.delay || 0)
    const customDistance = (typeof binding?.value === 'object' && binding?.value?.distance)
      ? binding.value.distance
      : 24

    const children = el.querySelectorAll<HTMLElement>('.reveal-child')

    if (children.length > 0) {
      // Container remains visible so children can stagger individually
      el.style.opacity = '1'
      el.style.transform = 'none'
      children.forEach((child, idx) => {
        child.style.opacity = '0'
        child.style.transform = `translateY(${customDistance}px)`
        child.style.willChange = 'opacity, transform'
        child.style.transition = `opacity 0.65s cubic-bezier(0.16, 1, 0.3, 1), transform 0.65s cubic-bezier(0.16, 1, 0.3, 1)`
        child.style.transitionDelay = `${customDelay + idx * 110}ms`
      })
    } else {
      el.style.opacity = '0'
      el.style.transform = `translateY(${customDistance}px)`
      el.style.willChange = 'opacity, transform'
      el.style.transition = 'opacity 0.65s cubic-bezier(0.16, 1, 0.3, 1), transform 0.65s cubic-bezier(0.16, 1, 0.3, 1)'
      if (customDelay > 0) {
        el.style.transitionDelay = `${customDelay}ms`
      }
    }

    function revealElement() {
      if (children.length > 0) {
        children.forEach((child) => {
          child.style.opacity = '1'
          child.style.transform = 'translateY(0)'
        })
      } else {
        el.style.opacity = '1'
        el.style.transform = 'translateY(0)'
      }
      el.classList.add('is-revealed')
    }

    // If element is already in the viewport on initial mount/refresh, reveal smoothly with RAF
    const rect = el.getBoundingClientRect()
    if (rect.top < window.innerHeight - 20 && rect.bottom > 0) {
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          revealElement()
        })
      })
      return
    }

    // Otherwise, observe with IntersectionObserver as the user scrolls
    const obs = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          revealElement()
          obs.unobserve(el)
        }
      },
      {
        threshold: 0.08,
        rootMargin: '0px 0px -40px 0px',
      },
    )
    obs.observe(el)
  },
}
