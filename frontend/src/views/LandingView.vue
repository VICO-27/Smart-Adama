<template>
  <div class="landing-page">

    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <AppNav />


    <!-- =====================================================
         HERO
    ====================================================== -->

    <main class="landing-main">

      <section
        class="hero-section"
      >

        <div class="hero-shell spotlight-card">

          <div class="hero-grid">

            <!-- HERO CONTENT -->

            <div
              v-reveal
              class="hero-content"
            >

              <span class="hero-kicker">
                Smart Adama City
              </span>

              <h1
                v-reveal
                style="transition-delay: 100ms;"
              >
                {{ $t('landing.hero_title') }}
              </h1>

              <p
                v-reveal
                style="transition-delay: 200ms;"
              >
                {{ $t('landing.hero_subtitle') }}
              </p>


              <div
                v-reveal
                style="transition-delay: 300ms;"
                class="hero-actions"
              >

                <button
                  type="button"
                  class="primary-button spring-button"
                  @click="handleStudyAI"
                >
                  {{ $t('landing.hero_btn') }}

                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="M5 12h14"
                      stroke-linecap="round"
                    />

                    <path
                      d="m13 6 6 6-6 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </button>

              </div>

            </div>


            <!-- HERO VIDEO -->

            <div
              v-reveal
              style="transition-delay: 400ms;"
              class="hero-video-shell"
            >

              <div class="hero-video-frame">

                <video
                  ref="heroVideoRef"
                  class="hero-video hero-video-parallax"
                  autoplay
                  loop
                  muted
                  playsinline
                  preload="auto"
                  fetchpriority="high"
                  poster="/logo.png"
                >
                  <source
                    src="/videos/hero.mp4"
                    type="video/mp4"
                  />
                </video>

                <div class="hero-video-overlay"></div>

              </div>

            </div>

          </div>

        </div>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="quick-actions">

          <!-- STUDY -->

          <button
            v-reveal
            style="transition-delay: 100ms;"
            type="button"
            class="quick-action"
            @click="handleStudyAI"
          >

            <div
              class="quick-icon quick-icon-blue"
            >

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <path
                  d="M12 4a7 7 0 0 0-7 7v3a3 3 0 0 0 3 3h1v-6H7a5 5 0 0 1 10 0h-2v6h1a3 3 0 0 0 3-3v-3a7 7 0 0 0-7-7Z"
                />

                <path
                  d="M12 20v-3"
                  stroke-linecap="round"
                />
              </svg>

            </div>

            <div class="quick-copy">

              <h3>
                {{ $t('landing.cards.study_title') }}
              </h3>

              <p>
                {{ $t('landing.cards.study_desc') }}
              </p>

            </div>

            <svg
              class="quick-arrow"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="M5 12h14"
                stroke-linecap="round"
              />

              <path
                d="m13 6 6 6-6 6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </button>


          <!-- BOOK -->

          <button
            v-reveal
            style="transition-delay: 200ms;"
            type="button"
            class="quick-action"
            @click="handleDownloadBook"
          >

            <div
              class="quick-icon quick-icon-soft"
            >

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <path
                  d="M5 4h14v16H5V4Z"
                />

                <path
                  d="M9 8h6M9 12h6M9 16h4"
                />
              </svg>

            </div>

            <div class="quick-copy">

              <h3>
                {{ $t('landing.cards.book_title') }}
              </h3>

              <p>
                {{ $t('landing.cards.book_desc') }}
              </p>

            </div>

            <svg
              class="quick-arrow"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="M5 12h14"
                stroke-linecap="round"
              />

              <path
                d="m13 6 6 6-6 6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </button>


          <!-- DEVELOPERS -->

          <button
            v-reveal
            style="transition-delay: 300ms;"
            type="button"
            class="quick-action"
            @click="$router.push('/about')"
          >

            <div
              class="quick-icon quick-icon-amber"
            >

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <circle
                  cx="12"
                  cy="8"
                  r="3.5"
                />

                <path
                  d="M5 20a7 7 0 0 1 14 0"
                />
              </svg>

            </div>

            <div class="quick-copy">

              <h3>
                {{ $t('landing.cards.devs_title') }}
              </h3>

              <p>
                {{ $t('landing.cards.devs_desc') }}
              </p>

            </div>

            <svg
              class="quick-arrow"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="M5 12h14"
                stroke-linecap="round"
              />

              <path
                d="m13 6 6 6-6 6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </button>

        </section>

      </section>


      <!-- =====================================================
           SMART CITY FRAMEWORK
      ====================================================== -->

      <section
        id="framework-section"
        class="framework-section"
      >

        <div class="section-container">

          <div class="framework-intro">

            <span
              v-reveal
              class="section-kicker section-kicker-light"
            >
              Smart Adama Framework
            </span>

            <h2
              v-reveal
              class="framework-title"
            >
              <span>
                {{ $t('landing.framework_title_1') }}
              </span>

              <span>
                {{ $t('landing.framework_title_2') }}
              </span>
            </h2>

          </div>


          <div class="initiative-grid">

            <div
              v-for="
                (item, index)
                  in coreInitiatives
              "
              :key="index"
              v-reveal
              :style="{
                transitionDelay:
                  `${(index + 1) * 100}ms`,
              }"
              class="initiative"
            >

              <div
                class="initiative-glow"
                :class="item.glowClass"
              ></div>


              <div class="initiative-top">

                <span
                  class="initiative-number"
                >
                  {{
                    $t('landing.cores.label')
                  }}
                  0{{ index + 1 }}
                </span>


                <div
                  class="initiative-icon"
                >

                  <svg
                    v-if="
                      item.icon === 'government'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                  >
                    <path
                      d="M3 20h18"
                    />

                    <path
                      d="M5 17V9l7-5 7 5v8"
                    />

                    <path
                      d="M8 17v-5M12 17v-5M16 17v-5"
                    />
                  </svg>


                  <svg
                    v-else-if="
                      item.icon === 'innovation'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                  >
                    <path
                      d="M9 18h6"
                    />

                    <path
                      d="M10 21h4"
                    />

                    <path
                      d="M8.5 14.5A6 6 0 1 1 15.5 14.5c-.9.8-1.5 1.9-1.5 3.5h-4c0-1.6-.6-2.7-1.5-3.5Z"
                    />
                  </svg>


                  <svg
                    v-else-if="
                      item.icon === 'growth'
                    "
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                  >
                    <path
                      d="M4 18V10"
                    />

                    <path
                      d="M10 18V6"
                    />

                    <path
                      d="M16 18v-4"
                    />

                    <path
                      d="M4 20h16"
                    />
                  </svg>


                  <svg
                    v-else
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                  >
                    <path
                      d="M12 20V9"
                    />

                    <path
                      d="M12 12 8 8"
                    />

                    <path
                      d="M12 12l4-4"
                    />

                    <path
                      d="M7 5c0 2.2 1.8 4 4 4"
                    />

                    <path
                      d="M17 5c0 2.2-1.8 4-4 4"
                    />

                    <path
                      d="M5 20h14"
                    />
                  </svg>

                </div>

              </div>


              <div
                class="initiative-content"
              >

                <h3>

                  {{ item.title }}

                  <svg
                    class="initiative-arrow"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="M5 12h14"
                      stroke-linecap="round"
                    />

                    <path
                      d="m13 6 6 6-6 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </h3>

                <p>
                  {{ item.desc }}
                </p>

              </div>

            </div>

          </div>

        </div>

      </section>


      <!-- =====================================================
           BOOK
      ====================================================== -->

      <section
        class="book-section"
      >

        <div
          v-reveal
          class="book-heading"
        >

          <span class="section-kicker">
            Smart Adama Book
          </span>

          <h2>
            {{ $t('landing.book_title') }}
          </h2>

          <p>
            {{ $t('landing.book_desc') }}
          </p>

        </div>


        <div
          v-reveal
          class="book-video-shell"
        >

          <video
            ref="bookVideoRef"
            data-src="/videos/smart-adama-book.mp4"
            autoplay
            loop
            muted
            playsinline
            preload="none"
            fetchpriority="low"
            class="book-video"
          ></video>

        </div>

      </section>


      <!-- =====================================================
           VISION
      ====================================================== -->

      <section
        class="vision-section"
      >

        <div
          class="vision-glow vision-glow-one"
        ></div>

        <div
          class="vision-glow vision-glow-two"
        ></div>


        <div
          v-reveal
          class="vision-content"
        >

          <span class="vision-badge">
            {{ $t('landing.vision_badge') }}
          </span>

          <h3>
            {{ $t('landing.vision_quote') }}
          </h3>

          <div
            class="vision-line"
          ></div>

        </div>

      </section>


      <!-- =====================================================
           VISIT / VIDEO CAROUSEL
      ====================================================== -->

      <section
        class="visit-section"
      >

        <div
          v-reveal
          class="visit-heading"
        >

          <span
            class="section-kicker section-kicker-light"
          >
            Smart Adama City
          </span>

          <h2>
            {{ $t('landing.visit_title') }}
          </h2>

          <p>
            {{ $t('landing.visit_desc') }}
          </p>

        </div>


        <div
          class="video-carousel-shell"
        >

          <div
            ref="carouselRef"
            class="video-carousel no-scrollbar"
          >

            <div
              v-for="(id, i) in tiktokVideos"
              :key="id"
              class="carousel-item"
            >

              <video
                :ref="(
                  el,
                ) =>
                  setCarouselRef(
                    el as
                      HTMLVideoElement |
                      null,
                    i,
                  )
                "
                :data-src="
                  `/videos/tiktok-${id}.mp4`
                "
                loop
                muted
                playsinline
                preload="none"
                fetchpriority="low"
                class="carousel-video"
              ></video>

            </div>

          </div>


          <button
            type="button"
            class="carousel-next spring-button"
            aria-label="Next Video"
            @click="scrollNext"
          >

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.2"
            >
              <path
                d="m9 5 7 7-7 7"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </button>

        </div>

      </section>


      <!-- =====================================================
           CTA
      ====================================================== -->

      <section
        class="cta-section"
      >

        <div
          v-reveal
          class="cta-content"
        >

          <span
            class="section-kicker"
          >
            Smart Adama
          </span>

          <h2>
            {{ $t('landing.cta_title') }}
          </h2>

          <p>
            {{ $t('landing.cta_desc') }}
          </p>


          <div class="cta-actions">

            <button
              type="button"
              class="primary-button spring-button"
              @click="handleStudyAI"
            >

              {{ $t('landing.cta_btn_1') }}

              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  d="M5 12h14"
                  stroke-linecap="round"
                />

                <path
                  d="m13 6 6 6-6 6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>

            </button>


            <button
              type="button"
              class="secondary-button spring-button"
              @click="handleDownloadBook"
            >

              {{ $t('landing.cta_btn_2') }}

            </button>

          </div>

        </div>

      </section>

    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer
      class="landing-footer"
    >

      <div
        class="footer-container"
      >

        <div class="footer-grid">


          <!-- BRAND -->

          <div
            class="footer-brand"
          >

            <div
              class="footer-brand-row"
            >

              <div
                class="footer-logo"
              >

                <img
                  src="/logo.png"
                  alt="Smart Adama Logo"
                />

              </div>

              <div>

                <div
                  class="footer-brand-title"
                >
                  {{ $t('nav.brand') }}
                </div>

                <div
                  class="footer-brand-subtitle"
                >
                  Smart City Learning Platform
                </div>

              </div>

            </div>


            <p>
              {{ $t('landing.footer.tagline') }}
            </p>

          </div>


          <!-- ECOSYSTEM -->

          <div
            class="footer-column"
          >

            <h4>
              {{ $t('landing.footer.ecosystem') }}
            </h4>

            <router-link to="/study">
              {{ $t('landing.footer.study') }}
            </router-link>

            <router-link to="/game">
              {{ $t('landing.footer.game') }}
            </router-link>

            <router-link to="/language">
              {{ $t('landing.footer.language') }}
            </router-link>

            <a
              href="/books/SA-Book.pdf"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ $t('landing.footer.download') }}
            </a>

          </div>


          <!-- LEGAL -->

          <div
            class="footer-column"
          >

            <h4>
              {{ $t('landing.footer.legal') }}
            </h4>

            <router-link to="/about">
              {{ $t('landing.footer.about') }}
            </router-link>

            <a href="#">
              {{ $t('landing.footer.privacy') }}
            </a>

            <a href="#">
              {{ $t('landing.footer.terms') }}
            </a>

            <a href="#">
              {{ $t('landing.footer.contact') }}
            </a>

          </div>


          <!-- CITY -->

          <div
            class="footer-column"
          >

            <h4>
              Smart Adama City
            </h4>

            <span>
              Smart City Learning
            </span>

            <span>
              Technology
            </span>

            <span>
              Innovation
            </span>

            <span>
              Community
            </span>

          </div>

        </div>


        <div
          class="footer-bottom"
        >

          <p>
            {{ $t('landing.footer.copyright') }}
          </p>

          <span>
            English /
            Afaan Oromoo /
            Amharic
          </span>

        </div>

      </div>

    </footer>

  </div>
</template>


<script setup lang="ts">
import {
  ref,
  computed,
  onMounted,
  onUnmounted,
  watch,
} from 'vue'

import {
  useRouter,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

import AppNav from '@/components/layout/AppNav.vue'

import {
  useI18n,
} from 'vue-i18n'


/* ============================================================
   ROUTER / AUTH / I18N
============================================================ */

const router =
  useRouter()

const auth =
  useAuthStore()

const {
  t,
} = useI18n()


/* ============================================================
   AUTH REDIRECT
============================================================ */

watch(
  () => auth.isAuthenticated,
  (isAuthenticated) => {

    if (
      isAuthenticated &&
      router.currentRoute.value.path === '/'
    ) {
      router.push('/dashboard')
    }

  },
  {
    immediate: true,
  },
)


/* ============================================================
   VIDEO REFS
============================================================ */

const heroVideoRef =
  ref<HTMLVideoElement | null>(null)

const bookVideoRef =
  ref<HTMLVideoElement | null>(null)

const carouselVideoRefs:
  (HTMLVideoElement | null)[] =
  []

const carouselRef =
  ref<HTMLElement | null>(null)

const tiktokVideos = [1, 2, 3, 4, 5, 6, 8, 10]


/* ============================================================
   INITIATIVES
============================================================ */

const coreInitiatives =
  computed(() => [

    {
      title:
        t(
          'landing.cores.c1_title',
        ),

      desc:
        t(
          'landing.cores.c1_desc',
        ),

      icon:
        'government',

      glowClass:
        'glow-blue',
    },

    {
      title:
        t(
          'landing.cores.c2_title',
        ),

      desc:
        t(
          'landing.cores.c2_desc',
        ),

      icon:
        'innovation',

      glowClass:
        'glow-amber',
    },

    {
      title:
        t(
          'landing.cores.c3_title',
        ),

      desc:
        t(
          'landing.cores.c3_desc',
        ),

      icon:
        'growth',

      glowClass:
        'glow-cyan',
    },

    {
      title:
        t(
          'landing.cores.c4_title',
        ),

      desc:
        t(
          'landing.cores.c4_desc',
        ),

      icon:
        'agriculture',

      glowClass:
        'glow-green',
    },

  ])


/* ============================================================
   CAROUSEL REFS
============================================================ */

function setCarouselRef(
  el:
    HTMLVideoElement |
    null,

  index:
    number,
) {

  carouselVideoRefs[
    index - 1
  ] = el
}


/* ============================================================
   VIDEO MANAGEMENT
============================================================ */

let loadObserver:
  IntersectionObserver |
  null = null

let playObserver:
  IntersectionObserver |
  null = null

let visibilityHandler:
  (() => void) |
  null = null


function lazyLoadVideo(
  video:
    HTMLVideoElement,
) {

  const src =
    video.dataset.src

  if (
    src &&
    !video.src
  ) {

    video.src =
      src

    video.load()
  }
}


/* ============================================================
   SPOTLIGHT
============================================================ */

let spotlightRaf:
  number |
  null = null

let lastPointerEvent:
  PointerEvent |
  null = null


function handleSpotlightMove(
  event:
    PointerEvent,
) {

  lastPointerEvent =
    event

  if (
    spotlightRaf
  ) {
    return
  }

  spotlightRaf =
    requestAnimationFrame(
      () => {

        spotlightRaf =
          null

        const pointer =
          lastPointerEvent

        if (
          !pointer
        ) {
          return
        }

        const target =
          pointer.target as HTMLElement

        const card =
          target.closest(
            '.spotlight-card',
          ) as
            HTMLElement |
            null

        if (
          !card
        ) {
          return
        }

        const rect =
          card.getBoundingClientRect()

        const x =
          (
            (
              pointer.clientX -
              rect.left
            ) /
              rect.width
          ) *
          100

        const y =
          (
            (
              pointer.clientY -
              rect.top
            ) /
              rect.height
          ) *
          100

        card.style.setProperty(
          '--spot-x',
          `${x}%`,
        )

        card.style.setProperty(
          '--spot-y',
          `${y}%`,
        )
      },
    )
}


/* ============================================================
   MOUNT
============================================================ */

onMounted(() => {

  /* ----------------------------------------------------------
     Lazy loading
  ----------------------------------------------------------- */

  loadObserver =
    new IntersectionObserver(
      (entries) => {

        entries.forEach(
          (entry) => {

            if (
              entry.isIntersecting
            ) {

              lazyLoadVideo(
                entry.target as HTMLVideoElement,
              )

              loadObserver?.unobserve(
                entry.target,
              )
            }

          },
        )

      },
      {
        rootMargin:
          '600px 0px',

        threshold:
          0,
      },
    )


  /* ----------------------------------------------------------
     Play / pause
  ----------------------------------------------------------- */

  playObserver =
    new IntersectionObserver(
      (entries) => {

        entries.forEach(
          (entry) => {

            const video =
              entry.target as HTMLVideoElement

            if (
              entry.isIntersecting
            ) {

              video
                .play()
                .catch(
                  () => {},
                )

            } else {

              video.pause()

            }

          },
        )

      },
      {
        threshold:
          0.15,
      },
    )


  const lazyTargets =
    [
      bookVideoRef.value,
      ...carouselVideoRefs,
    ].filter(
      (
        video,
      ): video is HTMLVideoElement =>
        !!video,
    )


  const playTargets =
    [
      ...carouselVideoRefs,
    ].filter(
      (
        video,
      ): video is HTMLVideoElement =>
        !!video,
    )


  lazyTargets.forEach(
    (video) => {

      loadObserver?.observe(
        video,
      )

    },
  )


  playTargets.forEach(
    (video) => {

      playObserver?.observe(
        video,
      )

    },
  )


  /* ----------------------------------------------------------
     Visibility
  ----------------------------------------------------------- */

  visibilityHandler =
    () => {

      playTargets.forEach(
        (video) => {

          if (
            document.hidden
          ) {

            video.pause()

            return
          }


          const rect =
            video.getBoundingClientRect()


          const isVisible =
            rect.top <
              window.innerHeight &&
            rect.bottom >
              0


          if (
            isVisible
          ) {

            video
              .play()
              .catch(
                () => {},
              )

          }

        },
      )
    }


  document.addEventListener(
    'visibilitychange',
    visibilityHandler,
  )


  document.addEventListener(
    'pointermove',
    handleSpotlightMove,
    {
      passive: true,
    },
  )
})


/* ============================================================
   UNMOUNT
============================================================ */

onUnmounted(() => {

  loadObserver?.disconnect()

  playObserver?.disconnect()


  if (
    visibilityHandler
  ) {

    document.removeEventListener(
      'visibilitychange',
      visibilityHandler,
    )

  }


  document.removeEventListener(
    'pointermove',
    handleSpotlightMove,
  )


  if (
    spotlightRaf
  ) {

    cancelAnimationFrame(
      spotlightRaf,
    )

  }

})


/* ============================================================
   ACTIONS
============================================================ */

function handleStudyAI() {

  if (
    auth.isAuthenticated
  ) {

    router.push(
      '/study',
    )

    return
  }


  auth.openAuthModal(
    'login',
    '/study',
  )
}


function handleDownloadBook() {
  window.open('/books/SA-Book.pdf', '_blank')
}


/* ============================================================
   CAROUSEL
============================================================ */

function scrollNext() {

  if (
    carouselRef.value
  ) {

    carouselRef.value.scrollBy(
      {
        left:
          320,

        behavior:
          'smooth',
      },
    )

  }

}
</script>


<style scoped>

/* ============================================================
   LANDING THEME
============================================================ */

.landing-page {

  --landing-page-bg:
    var(--sa-page-bg);

  --landing-surface:
    var(--sa-surface);

  --landing-surface-soft:
    var(--sa-surface-soft);

  --landing-border:
    var(--sa-border);

  --landing-text:
    var(--sa-text);

  --landing-text-secondary:
    var(--sa-text-secondary);

  --landing-text-muted:
    var(--sa-text-muted);

  --landing-brand:
    #395886;

  --landing-brand-light:
    #638ECB;

  --landing-brand-soft:
    #8AAEE0;

  --landing-brand-pale:
    #B1C9EF;

  --landing-blue-section:
    #395886;

  min-height:
    100vh;

  background:
    var(--landing-page-bg);

  color:
    var(--landing-text);

  transition:
    background-color 0.3s ease,
    color 0.3s ease;
}


html.dark .landing-page {

  --landing-blue-section:
    #243A5A;
}


/* ============================================================
   MAIN
============================================================ */

.landing-main {
  background:
    var(--landing-page-bg);
}


/* ============================================================
   HERO
============================================================ */

.hero-section {

  width:
    100%;

  padding:
    7rem
    1rem
    2rem;
}


.hero-shell {

  width:
    min(100%, 1180px);

  min-height:
    380px;

  margin:
    0 auto;

  overflow:
    hidden;

  border:
    1px solid
    var(--landing-border);

  border-radius:
    2rem;

  background:
    var(--landing-surface);

  box-shadow:
    var(--shadow-lg);
}


.hero-grid {

  display:
    grid;

  grid-template-columns:
    2fr 3fr;

  min-height:
    380px;
}


.hero-content {

  display:
    flex;

  flex-direction:
    column;

  justify-content:
    center;

  align-items:
    flex-start;

  gap:
    1.2rem;

  padding:
    2rem 3rem;
}


.hero-kicker {

  display:
    inline-flex;

  align-items:
    center;

  padding:
    0.4rem
    0.65rem;

  border:
    1px solid
    rgba(
      99,
      142,
      203,
      0.2
    );

  border-radius:
    999px;

  background:
    rgba(
      99,
      142,
      203,
      0.06
    );

  color:
    var(--landing-brand-light);

  font-size:
    0.5rem;

  font-weight:
    800;

  letter-spacing:
    0.13em;

  text-transform:
    uppercase;
}


.hero-content h1 {

  max-width:
    580px;

  color:
    var(--landing-text);

  font-size:
    clamp(
      2.6rem,
      5vw,
      4.7rem
    );

  line-height:
    1.03;

  font-weight:
    800;

  letter-spacing:
    -0.055em;
}


.hero-content p {

  max-width:
    510px;

  color:
    var(--landing-text-muted);

  font-size:
    1rem;

  line-height:
    1.65;

  font-weight:
    500;
}


.hero-actions {

  width:
    100%;

  max-width:
    380px;

  margin-top:
    0.5rem;
}


.primary-button {

  display:
    inline-flex;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.45rem;

  width:
    100%;

  min-height:
    3.3rem;

  padding:
    0.85rem
    1.2rem;

  border:
    0;

  border-radius:
    999px;

  background:
    #395886;

  color:
    white;

  font-size:
    0.72rem;

  font-weight:
    800;

  cursor:
    pointer;

  box-shadow:
    0 10px 30px
    rgba(
      57,
      88,
      134,
      0.25
    );

  transition:
    background
      0.3s ease,
    box-shadow
      0.3s ease,
    transform
      0.3s ease;
}


.primary-button:hover {

  background:
    #638ECB;

  box-shadow:
    0 15px 35px
    rgba(
      57,
      88,
      134,
      0.35
    );
}


.primary-button svg {

  width:
    0.9rem;

  height:
    0.9rem;
}


.hero-video-shell {

  height:
    100%;

  padding:
    1.5rem
    1.5rem
    1.5rem
    0;
}


.hero-video-frame {

  position:
    relative;

  width:
    100%;

  height:
    100%;

  min-height:
    450px;

  overflow:
    hidden;

  border-radius:
    1.7rem;

  background:
    #8AAEE0;

  box-shadow:
    var(--shadow-lg);
}


.hero-video {

  position:
    absolute;

  inset:
    0;

  width:
    100%;

  height:
    100%;

  object-fit:
    cover;
}


.hero-video-overlay {

  position:
    absolute;

  inset:
    0;

  pointer-events:
    none;

  background:
    linear-gradient(
      180deg,
      rgba(0,0,0,0.02),
      rgba(0,0,0,0.18)
    );
}


/* ============================================================
   QUICK ACTIONS
============================================================ */

.quick-actions {

  width:
    min(100%, 1180px);

  margin:
    1rem auto
    0;

  display:
    grid;

  grid-template-columns:
    repeat(
      3,
      minmax(
        0,
        1fr
      )
    );

  gap:
    1rem;
}


.quick-action {

  position:
    relative;

  display:
    flex;

  align-items:
    center;

  gap:
    0.9rem;

  min-width:
    0;

  padding:
    1.2rem;

  border:
    1px solid
    var(--landing-border);

  border-radius:
    1.2rem;

  background:
    var(--landing-surface);

  color:
    var(--landing-text);

  text-align:
    left;

  cursor:
    pointer;

  box-shadow:
    var(--shadow-sm);

  transition:
    transform
      0.3s var(--ease-out),
    box-shadow
      0.3s ease,
    border-color
      0.3s ease,
    background
      0.3s ease;
}


.quick-action:hover {

  transform:
    translateY(-4px);

  box-shadow:
    var(--shadow-md);

  border-color:
    var(--landing-brand-soft);
}


.quick-icon {

  width:
    3.5rem;

  height:
    3.5rem;

  flex-shrink:
    0;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border-radius:
    1rem;
}


.quick-icon svg {

  width:
    1.45rem;

  height:
    1.45rem;
}


.quick-icon-blue {

  color:
    #638ECB;

  background:
    rgba(
      99,
      142,
      203,
      0.1
    );

  border:
    1px solid
    rgba(
      99,
      142,
      203,
      0.12
    );
}


.quick-icon-soft {

  color:
    #395886;

  background:
    rgba(
      177,
      201,
      239,
      0.16
    );

  border:
    1px solid
    rgba(
      177,
      201,
      239,
      0.22
    );
}


.quick-icon-amber {

  color:
    #B7791F;

  background:
    rgba(
      245,
      158,
      11,
      0.08
    );

  border:
    1px solid
    rgba(
      245,
      158,
      11,
      0.14
    );
}


.quick-copy {

  min-width:
    0;

  flex:
    1;
}


.quick-copy h3 {

  overflow:
    hidden;

  color:
    var(--landing-text);

  font-size:
    0.85rem;

  font-weight:
    800;

  text-overflow:
    ellipsis;

  white-space:
    nowrap;
}


.quick-copy p {

  margin-top:
    0.25rem;

  color:
    var(--landing-text-muted);

  font-size:
    0.55rem;

  line-height:
    1.55;
}


.quick-arrow {

  width:
    0.85rem;

  height:
    0.85rem;

  flex-shrink:
    0;

  color:
    var(--sa-text-faint);

  transition:
    transform
    0.25s ease,
    color
    0.25s ease;
}


.quick-action:hover
.quick-arrow {

  color:
    #638ECB;

  transform:
    translateX(3px);
}


/* ============================================================
   FRAMEWORK
============================================================ */

.framework-section {

  width:
    100%;

  margin-top:
    2.5rem;

  padding:
    6rem 0;

  background:
    var(--landing-blue-section);

  color:
    white;

  overflow:
    hidden;
}


.section-container {

  width:
    min(100%, 1180px);

  margin:
    0 auto;

  padding:
    0 1.5rem;
}


.section-kicker {

  display:
    inline-block;

  color:
    var(--landing-brand-light);

  font-size:
    0.52rem;

  font-weight:
    800;

  letter-spacing:
    0.14em;

  text-transform:
    uppercase;
}


.section-kicker-light {

  color:
    #B1C9EF;
}


.framework-intro {

  margin-bottom:
    4rem;
}


.framework-title {

  max-width:
    900px;

  margin-top:
    0.6rem;

  font-size:
    clamp(
      2.5rem,
      6vw,
      5.5rem
    );

  line-height:
    1.02;

  letter-spacing:
    -0.05em;
}


.framework-title span:first-child {

  display:
    block;

  color:
    white;
}


.framework-title span:last-child {

  display:
    block;

  color:
    #8AAEE0;
}


.initiative-grid {

  display:
    grid;

  grid-template-columns:
    repeat(
      4,
      minmax(
        0,
        1fr
      )
    );

  gap:
    0.9rem;
}


.initiative {

  position:
    relative;

  min-height:
    20rem;

  display:
    flex;

  flex-direction:
    column;

  padding:
    1.5rem;

  overflow:
    hidden;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.1
    );

  border-radius:
    1.25rem;

  background:
    rgba(
      255,
      255,
      255,
      0.035
    );

  color:
    white;

  text-align:
    left;

  cursor:
    pointer;

  backdrop-filter:
    blur(18px);

  -webkit-backdrop-filter:
    blur(18px);

  transition:
    transform
      0.35s var(--ease-out),
    background
      0.35s ease,
    border-color
      0.35s ease;
}


.initiative:hover {

  transform:
    translateY(-6px);

  background:
    rgba(
      255,
      255,
      255,
      0.08
    );

  border-color:
    rgba(
      177,
      201,
      239,
      0.25
    );
}


.initiative-glow {

  position:
    absolute;

  width:
    12rem;

  height:
    12rem;

  top:
    -5rem;

  right:
    -5rem;

  border-radius:
    50%;

  filter:
    blur(3.5rem);

  opacity:
    0.15;

  transition:
    opacity
    0.5s ease;
}


.initiative:hover
.initiative-glow {

  opacity:
    0.3;
}


.glow-blue {

  background:
    #638ECB;
}


.glow-amber {

  background:
    #D9A441;
}


.glow-cyan {

  background:
    #56A6C7;
}


.glow-green {

  background:
    #5EA66F;
}


.initiative-top {

  position:
    relative;

  z-index:
    2;

  display:
    flex;

  justify-content:
    space-between;

  align-items:
    flex-start;
}


.initiative-number {

  padding:
    0.35rem
    0.55rem;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.1
    );

  border-radius:
    999px;

  background:
    rgba(
      255,
      255,
      255,
      0.04
    );

  color:
    rgba(
      255,
      255,
      255,
      0.65
    );

  font-size:
    0.48rem;

  font-family:
    monospace;

  font-weight:
    800;

  letter-spacing:
    0.12em;
}


.initiative-icon {

  width:
    3rem;

  height:
    3rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.08
    );

  border-radius:
    0.85rem;

  background:
    rgba(
      255,
      255,
      255,
      0.05
    );

  color:
    #B1C9EF;

  transition:
    transform
    0.4s var(--ease-out),
    color
    0.3s ease;
}


.initiative:hover
.initiative-icon {

  transform:
    scale(1.08)
    rotate(3deg);

  color:
    white;
}


.initiative-icon svg {

  width:
    1.3rem;

  height:
    1.3rem;
}


.initiative-content {

  position:
    relative;

  z-index:
    2;

  margin-top:
    auto;
}


.initiative-content h3 {

  display:
    flex;

  align-items:
    center;

  gap:
    0.45rem;

  color:
    white;

  font-size:
    1.35rem;

  line-height:
    1.1;

  font-weight:
    800;
}


.initiative-content p {

  margin-top:
    0.65rem;

  color:
    rgba(
      213,
      222,
      239,
      0.7
    );

  font-size:
    0.6rem;

  line-height:
    1.6;
}


.initiative-arrow {

  width:
    0.95rem;

  height:
    0.95rem;

  opacity:
    0;

  transform:
    translateX(-0.35rem);

  transition:
    opacity
    0.25s ease,
    transform
    0.25s ease;
}


.initiative:hover
.initiative-arrow {

  opacity:
    1;

  transform:
    translateX(0);
}


/* ============================================================
   BOOK
============================================================ */

.book-section {

  width:
    100%;

  padding:
    7rem 1rem;

  background:
    var(--landing-surface);

}


.book-heading {

  width:
    min(100%, 720px);

  margin:
    0 auto 3.5rem;

  text-align:
    center;
}


.book-heading h2 {

  margin-top:
    0.65rem;

  color:
    var(--landing-text);

  font-size:
    clamp(
      2.4rem,
      5vw,
      4rem
    );

  line-height:
    1.05;

  font-weight:
    800;

  letter-spacing:
    -0.045em;
}


.book-heading p {

  margin-top:
    1rem;

  color:
    var(--landing-text-muted);

  font-size:
    0.95rem;

  line-height:
    1.65;
}


.book-video-shell {

  width:
    100%;

  overflow:
    hidden;

  border-top:
    1px solid
    var(--landing-border);

  border-bottom:
    1px solid
    var(--landing-border);

  background:
    var(--landing-surface-soft);

  box-shadow:
    var(--shadow-lg);
}


.book-video {

  display:
    block;

  width:
    100%;

  max-height:
    800px;

  object-fit:
    cover;

  background:
    var(--landing-surface-soft);

  mix-blend-mode:
    normal;
}


/* ============================================================
   VISION
============================================================ */

.vision-section {

  position:
    relative;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  min-height:
    620px;

  padding:
    6rem 1.5rem;

  overflow:
    hidden;

  background:
    var(--landing-surface);
}


.vision-glow {

  position:
    absolute;

  left:
    50%;

  top:
    50%;

  border-radius:
    50%;

  pointer-events:
    none;

  transform:
    translate(-50%, -50%);
}


.vision-glow-one {

  width:
    700px;

  height:
    700px;

  background:
    rgba(
      240,
      243,
      250,
      0.75
    );

  filter:
    blur(100px);

  animation:
    breathe-one
    10s
    ease-in-out
    infinite;
}


.vision-glow-two {

  width:
    480px;

  height:
    480px;

  background:
    rgba(
      213,
      222,
      239,
      0.45
    );

  filter:
    blur(80px);

  animation:
    breathe-two
    14s
    ease-in-out
    infinite;
}


html.dark
.vision-glow-one {

  background:
    rgba(
      99,
      142,
      203,
      0.11
    );
}


html.dark
.vision-glow-two {

  background:
    rgba(
      57,
      88,
      134,
      0.16
    );
}


@keyframes breathe-one {

  0%,
  100% {

    transform:
      translate(
        -50%,
        -50%
      )
      scale(1);

    opacity:
      0.65;
  }

  50% {

    transform:
      translate(
        -50%,
        -50%
      )
      scale(1.05);

    opacity:
      1;
  }
}


@keyframes breathe-two {

  0%,
  100% {

    transform:
      translate(
        -50%,
        -50%
      )
      scale(1);

    opacity:
      0.55;
  }

  50% {

    transform:
      translate(
        -50%,
        -50%
      )
      scale(1.08);

    opacity:
      0.9;
  }
}


.vision-content {

  position:
    relative;

  z-index:
    2;

  width:
    min(
      100%,
      850px
    );

  text-align:
    center;
}


.vision-badge {

  display:
    inline-flex;

  align-items:
    center;

  padding:
    0.45rem
    0.8rem;

  border:
    1px solid
    var(--landing-border);

  border-radius:
    999px;

  background:
    var(--landing-surface);

  color:
    var(--landing-brand-light);

  font-size:
    0.5rem;

  font-family:
    monospace;

  font-weight:
    700;

  letter-spacing:
    0.2em;

  text-transform:
    uppercase;
}


.vision-content h3 {

  margin-top:
    2rem;

  color:
    var(--landing-text);

  font-size:
    clamp(
      2rem,
      5vw,
      4.6rem
    );

  line-height:
    1.08;

  font-weight:
    500;

  letter-spacing:
    -0.045em;
}


.vision-line {

  width:
    1px;

  height:
    7rem;

  margin:
    4rem auto 0;

  background:
    linear-gradient(
      to bottom,
      #8AAEE0,
      transparent
    );
}


/* ============================================================
   VISIT
============================================================ */

.visit-section {

  width:
    100%;

  padding:
    6rem 0;

  overflow:
    hidden;

  background:
    var(--landing-blue-section);

  color:
    white;
}


.visit-heading {

  width:
    min(100%, 1180px);

  margin:
    0 auto 3rem;

  padding:
    0 1.5rem;
}


.visit-heading h2 {

  margin-top:
    0.6rem;

  color:
    white;

  font-size:
    clamp(
      2.3rem,
      5vw,
      4rem
    );

  line-height:
    1.05;

  font-weight:
    800;

  letter-spacing:
    -0.045em;
}


.visit-heading p {

  max-width:
    650px;

  margin-top:
    0.7rem;

  color:
    #D5DEEF;

  font-size:
    0.9rem;

  line-height:
    1.6;
}


.video-carousel-shell {

  position:
    relative;

  width:
    100%;
}


.video-carousel {

  display:
    flex;

  width:
    100%;

  overflow-x:
    auto;

  scroll-behavior:
    smooth;
}


.carousel-item {

  width:
    18rem;

  height:
    31rem;

  flex-shrink:
    0;

  overflow:
    hidden;

  border-right:
    1px solid
    rgba(
      255,
      255,
      255,
      0.08
    );

  background:
    #638ECB;
}


.carousel-video {

  width:
    100%;

  height:
    100%;

  object-fit:
    cover;

  transition:
    transform
    0.7s
    var(--ease-out);
}


.carousel-item:hover
.carousel-video {

  transform:
    scale(1.05);
}


.carousel-next {

  position:
    absolute;

  right:
    1.5rem;

  top:
    50%;

  z-index:
    5;

  width:
    3.5rem;

  height:
    3.5rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  border:
    1px solid
    rgba(
      255,
      255,
      255,
      0.2
    );

  border-radius:
    50%;

  background:
    rgba(
      255,
      255,
      255,
      0.1
    );

  backdrop-filter:
    blur(14px);

  color:
    white;

  transform:
    translateY(-50%);

  cursor:
    pointer;

  transition:
    transform
      0.35s var(--ease-out),
    background
      0.25s ease;
}


.carousel-next:hover {

  background:
    rgba(
      255,
      255,
      255,
      0.18
    );

  transform:
    translateY(-50%)
    scale(1.08);
}


.carousel-next svg {

  width:
    1.3rem;

  height:
    1.3rem;
}


/* ============================================================
   CTA
============================================================ */

.cta-section {

  width:
    100%;

  padding:
    7rem 1.5rem;

  background:
    var(--landing-page-bg);

  text-align:
    center;
}


.cta-content {

  width:
    min(
      100%,
      850px
    );

  margin:
    0 auto;
}


.cta-content h2 {

  margin-top:
    0.8rem;

  color:
    var(--landing-text);

  font-size:
    clamp(
      2.6rem,
      6vw,
      5rem
    );

  line-height:
    1.02;

  font-weight:
    800;

  letter-spacing:
    -0.05em;
}


.cta-content p {

  max-width:
    680px;

  margin:
    1rem auto 0;

  color:
    var(--landing-text-muted);

  font-size:
    0.95rem;

  line-height:
    1.65;
}


.cta-actions {

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.8rem;

  margin-top:
    2rem;
}


.secondary-button {

  display:
    inline-flex;

  align-items:
    center;

  justify-content:
    center;

  min-height:
    3.3rem;

  padding:
    0.85rem
    2rem;

  border:
    1.5px solid
    #638ECB;

  border-radius:
    999px;

  background:
    transparent;

  color:
    var(--landing-brand);

  font-size:
    0.7rem;

  font-weight:
    800;

  cursor:
    pointer;

  transition:
    background
      0.25s ease,
    color
      0.25s ease,
    transform
      0.25s ease;
}


.secondary-button:hover {

  background:
    rgba(
      99,
      142,
      203,
      0.08
    );

  color:
    #395886;
}


/* ============================================================
   FOOTER
============================================================ */

.landing-footer {

  width:
    100%;

  background:
    #243A5A;

  color:
    #D5DEEF;

  border-top:
    1px solid
    rgba(
      255,
      255,
      255,
      0.08
    );
}


.footer-container {

  width:
    min(
      100%,
      1180px
    );

  margin:
    0 auto;

  padding:
    4rem 1.5rem 1.5rem;
}


.footer-grid {

  display:
    grid;

  grid-template-columns:
    1.7fr
    0.9fr
    0.9fr
    0.9fr;

  gap:
    3rem;
}


.footer-brand-row {

  display:
    flex;

  align-items:
    center;

  gap:
    0.7rem;
}


.footer-logo {

  width:
    2.3rem;

  height:
    2.3rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  padding:
    0.25rem;

  border-radius:
    0.65rem;

  background:
    white;
}


.footer-logo img {

  width:
    100%;

  height:
    100%;

  object-fit:
    contain;
}


.footer-brand-title {

  color:
    white;

  font-size:
    1.1rem;

  font-weight:
    800;
}


.footer-brand-subtitle {

  margin-top:
    0.15rem;

  color:
    #B1C9EF;

  font-size:
    0.43rem;

  font-weight:
    700;

  letter-spacing:
    0.12em;

  text-transform:
    uppercase;
}


.footer-brand p {

  max-width:
    360px;

  margin-top:
    1rem;

  color:
    #B1C9EF;

  font-size:
    0.6rem;

  line-height:
    1.7;
}


.footer-column {

  display:
    flex;

  flex-direction:
    column;

  align-items:
    flex-start;

  gap:
    0.6rem;
}


.footer-column h4 {

  margin-bottom:
    0.2rem;

  color:
    white;

  font-size:
    0.48rem;

  font-weight:
    800;

  letter-spacing:
    0.12em;

  text-transform:
    uppercase;
}


.footer-column a,
.footer-column span {

  color:
    #B1C9EF;

  font-size:
    0.5rem;

  font-weight:
    600;

  text-decoration:
    none;

  transition:
    color
    0.2s ease;
}


.footer-column a:hover {

  color:
    white;
}


.footer-bottom {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    1rem;

  margin-top:
    3rem;

  padding-top:
    1.2rem;

  border-top:
    1px solid
    rgba(
      255,
      255,
      255,
      0.09
    );
}


.footer-bottom p,
.footer-bottom span {

  color:
    #8AAEE0;

  font-size:
    0.46rem;
}


/* ============================================================
   SPOTLIGHT
============================================================ */

.spotlight-card {

  position:
    relative;

  --spot-x:
    50%;

  --spot-y:
    50%;
}


.spotlight-card::before {

  content:
    '';

  position:
    absolute;

  inset:
    0;

  border-radius:
    inherit;

  background:
    radial-gradient(
      500px circle at
      var(--spot-x)
      var(--spot-y),
      rgba(
        99,
        142,
        203,
        0.09
      ),
      transparent
      45%
    );

  opacity:
    0;

  pointer-events:
    none;

  z-index:
    2;

  transition:
    opacity
    0.4s ease;
}


.spotlight-card:hover::before {

  opacity:
    1;
}


/* ============================================================
   VIDEO / CAROUSEL
============================================================ */

.no-scrollbar::-webkit-scrollbar {

  display:
    none;
}


.no-scrollbar {

  -ms-overflow-style:
    none;

  scrollbar-width:
    none;
}


/* ============================================================
   REVEAL / SPRING
============================================================ */

.spring-button {

  transition:
    transform
      0.45s
      cubic-bezier(
        0.34,
        1.56,
        0.64,
        1
      ),
    box-shadow
      0.4s ease,
    background-color
      0.3s ease;
}


/* ============================================================
   PERFORMANCE
============================================================ */

.cv-lg,
.cv-md,
.cv-sm {

  content-visibility:
    auto;
}


.cv-lg {

  contain-intrinsic-size:
    auto 1400px;
}


.cv-md {

  contain-intrinsic-size:
    auto 900px;
}


.cv-sm {

  contain-intrinsic-size:
    auto 600px;
}


/* ============================================================
   HERO PARALLAX
============================================================ */

@supports (
  animation-timeline: scroll()
) {

  .hero-video-parallax {

    animation:
      heroParallax
      linear
      both;

    animation-timeline:
      scroll(root);

    animation-range:
      0 70vh;
  }


  @keyframes heroParallax {

    from {
      transform:
        scale(1);
    }

    to {
      transform:
        scale(1.08);
    }

  }

}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1050px) {

  .hero-grid {

    grid-template-columns:
      1fr;
  }


  .hero-video-shell {

    padding:
      0
      1.5rem
      1.5rem;
  }


  .hero-content {

    padding:
      3.5rem 3.5rem 1.5rem;
  }


  .initiative-grid {

    grid-template-columns:
      repeat(
        2,
        minmax(
          0,
          1fr
        )
      );
  }


  .footer-grid {

    grid-template-columns:
      1.5fr
      1fr
      1fr;
  }

}


@media (max-width: 760px) {

  .hero-section {

    padding:
      5.5rem
      0.75rem
      1rem;
  }


  .hero-shell {

    border-radius:
      1.4rem;
  }


  .hero-content {

    padding:
      2.4rem 1.4rem 1.25rem;
  }


  .hero-content h1 {

    font-size:
      2.35rem;
  }


  .hero-content p {

    font-size:
      0.85rem;
  }


  .hero-video-shell {

    padding:
      0
      0.8rem
      0.8rem;
  }


  .hero-video-frame {

    min-height:
      300px;

    border-radius:
      1.1rem;
  }


  .quick-actions {

    grid-template-columns:
      1fr;

    padding:
      0
      0.75rem;
  }


  .framework-section,
  .visit-section {

    padding:
      4.5rem 0;
  }


  .initiative-grid {

    grid-template-columns:
      1fr;
  }


  .initiative {

    min-height:
      16rem;
  }


  .book-section {

    padding:
      5rem 0.75rem;
  }


  .book-heading {

    margin-bottom:
      2rem;
  }


  .vision-section {

    min-height:
      520px;

    padding:
      4rem 1rem;
  }


  .vision-content h3 {

    font-size:
      2rem;
  }


  .carousel-item {

    width:
      16rem;

    height:
      29rem;
  }


  .carousel-next {

    right:
      1rem;

    width:
      3rem;

    height:
      3rem;
  }


  .cta-section {

    padding:
      5rem 1rem;
  }


  .cta-actions {

    flex-direction:
      column;
  }


  .primary-button,
  .secondary-button {

    width:
      100%;
  }


  .footer-container {

    padding:
      3rem 1rem 1.2rem;
  }


  .footer-grid {

    grid-template-columns:
      1fr 1fr;

    gap:
      2rem;
  }


  .footer-brand {

    grid-column:
      1 / -1;
  }


  .footer-bottom {

    align-items:
      flex-start;

    flex-direction:
      column;
  }

}


@media (max-width: 430px) {

  .footer-grid {

    grid-template-columns:
      1fr;
  }


  .quick-action {

    padding:
      1rem;
  }


  .hero-content h1 {

    font-size:
      2rem;
  }

}


/* ============================================================
   REDUCED MOTION
============================================================ */

@media (
  prefers-reduced-motion: reduce
) {

  *,
  *::before,
  *::after {

    animation-duration:
      0.01ms !important;

    animation-iteration-count:
      1 !important;

    transition-duration:
      0.01ms !important;
  }


  .hero-video-parallax {

    animation:
      none !important;
  }


  .spotlight-card::before {

    transition:
      none !important;
  }

}
</style>