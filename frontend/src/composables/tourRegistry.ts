import { TourDefinition } from './useTour'

export const homeTour: TourDefinition = {
  id: 'home',
  steps: [
    {
      target: 'body',
      title: 'tour.home.step1.title',
      content: 'tour.home.step1.content',
      placement: 'center'
    },
    {
      target: '.smart-navbar',
      title: 'tour.home.step2.title',
      content: 'tour.home.step2.content',
      placement: 'bottom'
    },
    {
      target: 'a.app-nav-link[href="/study"], .mobile-menu-button',
      title: 'tour.home.step3.title',
      content: 'tour.home.step3.content',
      placement: 'bottom'
    },
    {
      target: '.assistant-toggle',
      title: 'tour.home.step4.title',
      content: 'tour.home.step4.content',
      placement: 'left'
    },
    {
      target: '.momentum-section',
      title: 'tour.home.step5.title',
      content: 'tour.home.step5.content',
      placement: 'top'
    }
  ]
}

export const studyTour: TourDefinition = {
  id: 'study',
  steps: [
    {
      target: '.reader-main',
      title: 'tour.study.step1.title',
      content: 'tour.study.step1.content',
      placement: 'left'
    },
    {
      target: '.sidebar-tab-switcher button:nth-child(2), .rail-button[title="Chapters"]',
      title: 'tour.study.step2.title',
      content: 'tour.study.step2.content',
      placement: 'right',
      onBeforeShow: async () => {
        const chaptersTab = document.querySelectorAll('.sidebar-tab-switcher button')[1] as HTMLButtonElement
        if (chaptersTab) chaptersTab.click()

        const chaptersRailBtn = document.querySelector('.rail-button[title="Chapters"]') as HTMLButtonElement
        if (chaptersRailBtn) chaptersRailBtn.click()

        if (window.innerWidth < 768) {
          const btn = document.querySelector('.mobile-sidebar-toggle[title="Open sidebar"]') as HTMLButtonElement
          if (btn) btn.click()
        }
      }
    },
    {
      target: '.reader-scroll',
      title: 'tour.study.step3.title',
      content: 'tour.study.step3.content',
      placement: 'center',
      onBeforeShow: async () => {
        if (window.innerWidth < 768) {
          const backdrop = document.querySelector('.mobile-backdrop') as HTMLButtonElement
          if (backdrop) backdrop.click()
        }
      }
    },
    {
      target: '.ai-fab-ring',
      title: 'tour.study.step4.title',
      content: 'tour.study.step4.content',
      placement: 'left',
      onBeforeShow: async () => {
        if (window.innerWidth < 768) {
          const aiToggle = document.querySelector('button[title="Open AI assistant"]') as HTMLButtonElement
          if (aiToggle) aiToggle.click()
        }
      }
    },
    {
      target: '.ai-composer',
      title: 'tour.study.step5.title',
      content: 'tour.study.step5.content',
      placement: 'top',
      onBeforeShow: async () => {
        const chatsTab = document.querySelector('.sidebar-tab-switcher button:first-child') as HTMLButtonElement
        if (chatsTab) chatsTab.click()
        if (window.innerWidth < 768) {
          const aiToggle = document.querySelector('button[title="Open AI assistant"]') as HTMLButtonElement
          if (aiToggle) aiToggle.click()
        }
      }
    },
    {
      target: '.chapter-quiz-link, .take-quiz-btn',
      title: 'tour.study.step6.title',
      content: 'tour.study.step6.content',
      placement: 'right',
      onBeforeShow: async () => {
        const chaptersTab = document.querySelectorAll('.sidebar-tab-switcher button')[1] as HTMLButtonElement
        if (chaptersTab) chaptersTab.click()

        const chaptersRailBtn = document.querySelector('.rail-button[title="Chapters"]') as HTMLButtonElement
        if (chaptersRailBtn) chaptersRailBtn.click()

        if (window.innerWidth < 768) {
          const btn = document.querySelector('.mobile-sidebar-toggle[title="Open sidebar"]') as HTMLButtonElement
          if (btn) btn.click()
        }
      }
    }
  ]
}
