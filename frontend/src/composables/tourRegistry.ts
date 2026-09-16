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
    },
    {
      target: '[data-tour="ai-toggle"]',
      title: 'tour.home.step4.title',
      content: 'tour.home.step4.content',
      placement: 'left',
      gesture: 'tap',
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
      target: '[data-tour="chapters"]',
      title: 'tour.study.step2.title',
      content: 'tour.study.step2.content',
      placement: 'right',
      gesture: 'tap',
      onBeforeShow: async () => {
        const chaptersBtn = document.querySelector<HTMLButtonElement>('[data-tour="chapters"]')
        if (chaptersBtn) chaptersBtn.click()
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
    },
    {
      target: '[data-tour="quiz"]',
      title: 'tour.study.step6.title',
      content: 'tour.study.step6.content',
      placement: 'right',
      gesture: 'tap',
      onBeforeShow: async () => {
        const chaptersBtn = document.querySelector<HTMLButtonElement>('[data-tour="chapters"]')
        if (chaptersBtn) chaptersBtn.click()
        if (window.innerWidth < 768) {
          const mobileToggle = document.querySelector<HTMLButtonElement>('.mobile-sidebar-toggle[title="Open sidebar"]')
          if (mobileToggle) mobileToggle.click()
        }
      },
    },
  ],
}

