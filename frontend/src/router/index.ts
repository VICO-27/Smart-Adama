import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import LandingView from '@/views/LandingView.vue'
import OAuthCallbackView from '@/views/auth/OAuthCallbackView.vue'
import DashboardView from '@/views/DashboardView.vue'
import ProfileView from '@/views/ProfileView.vue'
import ChatView from '@/views/ChatView.vue'
import ChapterView from '@/views/ChapterView.vue'
import QuizView from '@/views/QuizView.vue'
import QuizzesView from '@/views/QuizzesView.vue'
import AboutView from '@/views/AboutView.vue'
import AdminDocumentManager from '@/views/admin/AdminDocumentManager.vue'
import AdminQuizView from '@/views/admin/AdminQuizView.vue'
import AdminChapterEditor from '@/views/admin/AdminChapterEditor.vue'
import AdminRetrievalDebugger from '@/views/admin/AdminRetrievalDebugger.vue'
import AdminLayout from '@/views/admin/AdminLayout.vue'
import AdminDashboardView from '@/views/admin/AdminDashboardView.vue'
import AdminSystemHealthView from '@/views/admin/AdminSystemHealthView.vue'
import AdminUsersView from '@/views/admin/AdminUsersView.vue'
import AdminAiSettingsView from '@/views/admin/AdminAiSettingsView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import GameView from '@/views/GameView.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/game',
    name: 'game',
    component: GameView,
    meta: { requiresAuth: true },
  },
  {
    path: '/',
    name: 'landing',
    component: LandingView,
    meta: { public: true },
  },
  {
    path: '/about',
    name: 'about',
    component: AboutView,
    meta: { public: true },
  },
  {
    path: '/auth/callback',
    name: 'oauth-callback',
    component: OAuthCallbackView,
    meta: { public: true },
  },
  {
    // Safety redirect: If magic link or provider callback lands on /login, forward tokens to /auth/callback
    path: '/login',
    redirect: (to) => ({
      path: '/auth/callback',
      query: to.query,
      hash: to.hash,
    }),
    meta: { public: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/study/:sessionId?',
    name: 'study',
    component: ChatView,
    meta: { requiresAuth: true, hideNav: true },
  },
  {
    path: '/chapters/:chapterId',
    name: 'chapter',
    component: ChapterView,
    meta: { requiresAuth: true },
  },
  {
    path: '/chapters/:chapterId/quiz',
    name: 'chapter-quiz',
    component: QuizView,
    meta: { requiresAuth: true },
  },
  {
    path: '/quizzes',
    name: 'quizzes',
    component: QuizzesView,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: AdminDashboardView,
      },
      {
        path: 'documents',
        name: 'admin-documents',
        component: AdminDocumentManager,
      },
      {
        path: 'quizzes',
        name: 'admin-quizzes',
        component: AdminQuizView,
      },
      {
        path: 'manual-authoring/:id?',
        name: 'admin.manual-authoring',
        component: () => import('@/views/admin/AdminManualAuthoring.vue'),
      },
      {
        path: 'chapters/:id',
        name: 'admin-chapter-editor',
        component: AdminChapterEditor,
      },
      // Placeholders for future views
      {
        path: 'users',
        name: 'admin-users',
        component: AdminUsersView,
      },
      {
        path: 'users/:id',
        name: 'admin-user-detail',
        component: () => import('@/views/admin/AdminUserDetailView.vue'),
      },
      {
        path: 'ai-settings',
        name: 'admin-ai-settings',
        component: AdminAiSettingsView,
      },
      {
        path: 'rag-debugger',
        name: 'admin-rag-debugger',
        component: AdminRetrievalDebugger,
      },
      {
        path: 'profile',
        name: 'admin-profile',
        component: () => import('@/views/admin/AdminProfileView.vue'),
      },
      {
        path: 'system',
        name: 'admin-system',
        component: AdminSystemHealthView,
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
    meta: { public: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

let sessionHydrated = false

router.beforeEach(async (to, from) => {
  const auth = useAuthStore()

  if (!sessionHydrated && auth.token && !auth.user) {
    await auth.fetchMe()
  }
  sessionHydrated = true

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    auth.openAuthModal('login', to.fullPath)
    if (from.name === undefined) {
      return { name: 'landing' }
    }
    return false
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'dashboard' }
  }
})

// Global Error Catcher: Recovers instantly from any navigation rendering failure
router.onError((err, to) => {
  console.error('Router navigation error:', err)
  window.location.href = to.fullPath
})

export default router