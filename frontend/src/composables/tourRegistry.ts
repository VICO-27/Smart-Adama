import { TourDefinition } from './useTour'

export const homeTour: TourDefinition = {
  id: 'home',
  steps: [
    {
      target: 'body',
      title: 'tour.home.step1.title',
      content: 'tour.home.step1.content',
      placement: 'center',
    },
    {
      target: '[data-tour="nav"]',
      title: 'tour.home.step2.title',
      content: 'tour.home.step2.content',
      placement: 'bottom',
    },
    {
      target: '[data-tour="study-link"]',
      title: 'tour.home.step3.title',
      content: 'tour.home.step3.content',
      placement: 'bottom',
      gesture: 'tap',
      onBeforeShow: async () => {
        if (window.innerWidth < 768) {
          const menuBtn = document.querySelector<HTMLButtonElement>('.mobile-menu-button')
          if (menuBtn && menuBtn.getAttribute('aria-expanded') !== 'true') {
            menuBtn.click()
          }
        }
      },
    },
    {
      target: '[data-tour="ai-toggle"]',
      title: 'tour.home.step4.title',
      content: 'tour.home.step4.content',
      placement: 'left',
      gesture: 'tap',
      onBeforeShow: async () => {
        if (window.innerWidth < 768) {
          const menuBtn = document.querySelector<HTMLButtonElement>('.mobile-menu-button')
          if (menuBtn && menuBtn.getAttribute('aria-expanded') === 'true') {
            menuBtn.click()
          }
        }
        // If the AI assistant panel is already open, the toggle button is hidden. Close it.
        const closeBtn = document.querySelector<HTMLButtonElement>('.assistant-panel [aria-label="Close assistant"]')
        if (closeBtn) closeBtn.click()
      },
    },
    {
      target: '[data-tour="momentum"]',
      title: 'tour.home.step5.title',
      content: 'tour.home.step5.content',
      placement: 'top',
    },
  ],
}

export const studyTour: TourDefinition = {
  id: 'study',
  steps: [
    {
      target: '[data-tour="reader"]',
      title: 'tour.study.step1.title',
      content: 'tour.study.step1.content',
      placement: 'center',
    },
    {
      target: '[data-tour="chapters-tab"]',
      title: 'tour.study.step2.title',
      content: 'tour.study.step2.content',
      placement: 'right',
      gesture: 'tap',
      onBeforeShow: async () => {
        // Click the rail button to open the sidebar, revealing the chapters-tab
        const chaptersRail = document.querySelector<HTMLButtonElement>('[data-tour="chapters"]')
        if (chaptersRail) chaptersRail.click()
        if (window.innerWidth < 768) {
          const mobileToggle = document.querySelector<HTMLButtonElement>('.mobile-sidebar-toggle[title="Open sidebar"]')
          if (mobileToggle) mobileToggle.click()
        }
      },
    },
    {
      target: '[data-tour="reader"]',
      title: 'tour.study.step3.title',
      content: 'tour.study.step3.content',
      placement: 'center',
      gesture: 'scroll',
      onBeforeShow: async () => {
        if (window.innerWidth < 768) {
          const backdrop = document.querySelector<HTMLButtonElement>('.mobile-backdrop')
          if (backdrop) backdrop.click()
        }
      },
    },
    {
      target: '[data-tour="study-ai"]',
      title: 'tour.study.step4.title',
      content: 'tour.study.step4.content',
      placement: 'left',
      gesture: 'tap',
    },
    {
      target: '[data-tour="ai-composer"]',
      title: 'tour.study.step5.title',
      content: 'tour.study.step5.content',
      placement: 'top',
      onBeforeShow: async () => {
        // Click the AI fab to open the AI sidebar so the composer is visible
        const aiToggle = document.querySelector<HTMLButtonElement>('.pdf-ai-button')
        if (aiToggle) aiToggle.click()
      }
    },
    {
      target: '[data-tour="quiz"]',
      title: 'tour.study.step6.title',
      content: 'tour.study.step6.content',
      placement: 'right',
      gesture: 'tap',
      onBeforeShow: async () => {
        // Ensure the chapters tab is active to show the quiz link
        const chaptersTab = document.querySelector<HTMLButtonElement>('[data-tour="chapters-tab"]')
        if (chaptersTab) chaptersTab.click()
        
        const chaptersRail = document.querySelector<HTMLButtonElement>('[data-tour="chapters"]')
        if (chaptersRail) chaptersRail.click()
        
        if (window.innerWidth < 768) {
          const mobileToggle = document.querySelector<HTMLButtonElement>('.mobile-sidebar-toggle[title="Open sidebar"]')
          if (mobileToggle) mobileToggle.click()
        }
      },
    },
  ],
}

