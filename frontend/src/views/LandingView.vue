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

          <!-- TOP-LEFT CORNER BADGE -->
          <span class="hero-kicker">
            {{ $t('landing.mayor.mayor_city') }}
          </span>

          <div class="hero-grid">

            <!-- HERO CONTENT -->

            <div
              class="hero-content"
            >

              <h1
                v-reveal="60"
              >
                {{ $t('landing.hero_title') }}
              </h1>

              <p
                v-reveal="140"
              >
                {{ $t('landing.hero_subtitle') }}
              </p>


              <div
                v-reveal="220"
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
              v-reveal="180"
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
                  preload="metadata"
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

        <section class="quick-actions" v-reveal>

          <!-- STUDY -->

          <button
            type="button"
            class="quick-action reveal-child"
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

          </button>


          <!-- BOOK -->

          <button
            type="button"
            class="quick-action reveal-child"
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

          </button>


          <!-- DEVELOPERS -->

          <button
            type="button"
            class="quick-action reveal-child"
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

          </button>

        </section>

      </section>


      <!-- =====================================================
           TRANSITION DIVIDER
      ====================================================== -->
      <div class="w-full relative h-16 sm:h-24 bg-transparent overflow-hidden" aria-hidden="true">
        <!-- Soft Gradient Blend into Navy -->
        <div class="absolute inset-0 bg-gradient-to-b from-[#F0F3FA] dark:from-gray-900 to-[#395886] dark:to-gray-950 opacity-90"></div>

        <!-- Subtle Gold/Navy Angled SVG Divider -->
        <svg
          class="absolute bottom-0 w-full h-auto text-[#395886] dark:text-gray-950"
          preserveAspectRatio="none"
          viewBox="0 0 1440 48"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <!-- Thin gold accent line -->
          <path d="M0 46L1440 12V48H0V46Z" fill="#d4af37" opacity="0.4" />
          <!-- Main navy block -->
          <path d="M0 48L1440 16V48H0V48Z" fill="currentColor" />
        </svg>
      </div>

      <!-- =====================================================
           EXECUTIVE LEADERSHIP MESSAGE (TAILWIND LUXURY COMPONENT)
      ====================================================== -->
      <section
        id="executive-section"
        class="relative w-full py-24 px-4 sm:px-6 lg:px-8 bg-[#395886] dark:bg-gray-950 overflow-hidden flex justify-center"
      >
        <!-- Background: Subtle Luxury Gradient & Grain Texture -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#395886] to-[#253957] dark:from-gray-950 dark:to-gray-900 opacity-90"></div>
        <div class="absolute inset-0 opacity-[0.15] mix-blend-overlay noise-bg pointer-events-none"></div>

        <!-- Scroll Reveal Wrapper (Using existing v-reveal directive) -->
        <div
          class="relative z-10 w-full max-w-4xl"
          v-reveal
        >
          <!-- Glassmorphism Letter Panel -->
          <div class="bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-8 sm:p-12 md:p-16 shadow-2xl">

            <!-- Official Seal / Header -->
            <div class="flex flex-col items-center mb-12 text-center reveal-child">
              <div class="bg-white/5 p-3 rounded-full border border-white/10 mb-4 shadow-inner flex items-center justify-center">
                <img src="/logo.png" alt="Smart Adama Logo" class="w-14 h-14 object-contain opacity-95 drop-shadow-md" />
              </div>
              <span class="text-[#B1C9EF] text-sm uppercase tracking-[0.2em] font-semibold">{{ $t('landing.mayor.mayor_office') }}</span>
            </div>

            <!-- Salutation (Serif) -->
            <h2 class="text-3xl sm:text-4xl text-white mb-8 font-serif italic text-center sm:text-left reveal-child">
              {{ $t('landing.mayor.mayor_greeting') }}
            </h2>

            <!-- Letter Body (Sans-serif) -->
            <div class="space-y-6 text-[#F0F3FA] text-lg leading-relaxed max-w-3xl mx-auto font-sans font-light reveal-child">
              <p>
                {{ $t('landing.mayor.mayor_p1') }}
              </p>
              <p>
                {{ $t('landing.mayor.mayor_p2') }}
              </p>

              <!-- Editorial Pull-Quote -->
              <blockquote class="my-10 border-l-4 border-[#d4af37] pl-6 py-2 text-xl sm:text-2xl font-serif italic text-white/95 leading-snug">
                {{ $t('landing.mayor.mayor_quote') }}
              </blockquote>

              <p>
                {{ $t('landing.mayor.mayor_p3') }}
              </p>
              <p>
                {{ $t('landing.mayor.mayor_p4') }}
              </p>
            </div>

            <!-- Footer / Signature Block -->
            <div class="mt-14 pt-8 border-t border-[#d4af37]/30 max-w-3xl mx-auto flex flex-col items-start reveal-child">
              <p class="text-xl font-serif italic text-white mb-4">{{ $t('landing.mayor.mayor_signoff') }}</p>

              <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-6 space-y-4 sm:space-y-0 mt-2">
                <!-- Simulated Stylized Signature -->
                <div class="text-4xl text-white transform -rotate-3 opacity-95 signature-font">
                  {{ $t('landing.mayor.mayor_name') }}
                </div>

                <div class="hidden sm:block h-12 w-px bg-white/20"></div>

                <!-- Formal Title -->
                <div class="flex flex-col">
                  <span class="text-white font-bold tracking-wider text-base uppercase">{{ $t('landing.mayor.mayor_name') }}</span>
                  <span class="text-[#B1C9EF] text-sm tracking-[0.1em] uppercase mt-0.5">{{ $t('landing.mayor.mayor_title') }}</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>


      <!-- =====================================================
           BOOK (TAILWIND LUXURY COMPONENT)
      ====================================================== -->
      <section class="relative w-full pt-24 pb-0 bg-[#F0F3FA] dark:bg-gray-950 overflow-hidden flex flex-col items-center">

        <!-- Faint background grid texture for depth (~4% visual opacity) -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#3958860a_1px,transparent_1px),linear-gradient(to_bottom,#3958860a_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#ffffff0a_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0a_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none"></div>

        <div class="relative z-10 w-full max-w-5xl mx-auto flex flex-col items-center text-center px-4 sm:px-6 lg:px-8 mb-16" v-reveal>

          <!-- Kicker -->
          <span class="text-[#638ECB] dark:text-blue-400 text-sm uppercase tracking-[0.15em] font-bold mb-4 reveal-child">
            {{ $t('landing.mayor.book_label') }}
          </span>

          <!-- Headline (High contrast, editorial weight) -->
          <h2 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-[#395886] dark:text-white mb-6 tracking-tight sf-pro-display reveal-child">
            {{ $t('landing.book_title') }}
          </h2>

          <!-- Subtext (Restrained line length for readability) -->
          <p class="text-lg sm:text-xl text-[#395886]/80 dark:text-gray-300 max-w-2xl leading-relaxed reveal-child">
            {{ $t('landing.book_desc') }}
          </p>
        </div>

        <!-- Full-Screen Media Container -->
        <div
          class="relative w-full z-10 shadow-[0_-10px_40px_rgba(57,88,134,0.1)]"
          v-reveal
        >
          <!-- Main Media Wrapper -->
          <div class="relative w-full aspect-video md:aspect-[21/9] bg-[#395886] dark:bg-gray-900 overflow-hidden flex items-center justify-center">
            <video
              ref="bookVideoRef"
              data-src="/videos/smart-adama-book.mp4"
              autoplay
              loop
              muted
              playsinline
              preload="none"
              fetchpriority="low"
              class="w-full h-full object-cover opacity-90"
            ></video>
          </div>
        </div>

      </section>


      <!-- =====================================================
           SMART CITY FRAMEWORK
      ====================================================== -->
      <section class="w-full py-24 bg-[#F0F3FA] dark:bg-gray-950 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

          <!-- Section Header -->
          <div class="mb-16 text-center max-w-3xl mx-auto" v-reveal>
            <span class="text-[#638ECB] dark:text-blue-400 text-sm font-bold uppercase tracking-[0.2em] reveal-child">{{ $t('landing.mayor.fw_title') }}</span>
            <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-[#395886] dark:text-white tracking-tight sf-pro-display reveal-child">
              {{ $t('landing.mayor.fw_sub') }}
            </h2>
            <p class="mt-4 text-[#395886]/70 dark:text-gray-400 text-lg reveal-child">
              {{ $t('landing.mayor.fw_desc') }}
            </p>
          </div>

          <!-- Framework Stepper Grid -->
          <div class="relative w-full">
            <!-- Mobile Vertical Connecting Line (Hidden for multi-column grid) -->
            <div class="hidden"></div>

            <!-- Responsive Grid -->
            <div ref="frameworkGridRef" class="framework-grid grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 lg:gap-8 relative">

              <!-- Pillar 1: e-Governance -->
              <div class="relative flex flex-col group fw-pillar" style="--fw-delay: 0s;">
                <!-- Badge & Line Wrapper -->
                <div class="flex items-center w-full mb-2 sm:mb-6 relative">
                  <!-- Official Number Marker -->
                  <div class="fw-marker z-10 w-11 h-11 sm:w-[4.5rem] sm:h-[4.5rem] shrink-0 rounded-full bg-[#F0F3FA] dark:bg-gray-800 border-[2px] border-[#395886] dark:border-gray-600 flex items-center justify-center text-[#395886] dark:text-gray-200 font-bold text-sm sm:text-xl transition-all duration-300 group-hover:border-[#10b981] dark:group-hover:border-[#10b981] group-hover:text-[#10b981] dark:group-hover:text-[#10b981] group-hover:shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    01
                    <!-- Inner Ring -->
                    <div class="fw-marker-ring absolute inset-[2px] sm:inset-[3px] border border-[#395886]/20 dark:border-gray-500/30 rounded-full group-hover:border-[#10b981]/40 transition-colors"></div>
                  </div>
                  <!-- Horizontal Segment Connecting Line (Desktop/Tablet) -->
                  <div class="fw-line hidden sm:block absolute top-1/2 left-[4.5rem] w-[calc(100%-4.5rem+2rem)] h-[2px] bg-[#D5DEEF] dark:bg-gray-700 -translate-y-1/2 z-0 transition-colors duration-500 group-hover:bg-gradient-to-r group-hover:from-[#10b981] group-hover:to-[#D5DEEF] dark:group-hover:to-gray-700 overflow-hidden">
                    <div class="fw-line-highlight absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-[#638ECB] to-transparent opacity-0 mix-blend-multiply"></div>
                  </div>
                </div>

                <!-- Content Card -->
                <div class="fw-card w-full p-3 sm:pl-10 sm:pr-8 sm:py-8 bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-[#D5DEEF] dark:border-gray-700 transition-all duration-300 flex flex-col h-full relative z-10 shadow-sm sm:shadow-none">
                  <div class="fw-icon text-[#638ECB] dark:text-blue-400 mb-2 sm:mb-3 group-hover:text-[#10b981] dark:group-hover:text-[#10b981] transition-colors">
                    <!-- Governance Icon -->
                    <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <h3 class="text-sm sm:text-lg font-bold text-[#395886] dark:text-white mb-1 sm:mb-2">{{ $t('landing.mayor.fw_gov') }}</h3>
                  <p class="text-[#395886]/80 dark:text-gray-400 text-[0.72rem] sm:text-[0.9rem] leading-snug sm:leading-relaxed line-clamp-4 sm:line-clamp-none">
                    {{ $t('landing.mayor.fw_gov_desc') }}
                  </p>
                </div>
              </div>

              <!-- Pillar 2: Innovation -->
              <div class="relative flex flex-col group fw-pillar" style="--fw-delay: 0.12s;">
                <div class="flex items-center w-full mb-2 sm:mb-6 relative">
                  <div class="fw-marker z-10 w-11 h-11 sm:w-[4.5rem] sm:h-[4.5rem] shrink-0 rounded-full bg-[#F0F3FA] dark:bg-gray-800 border-[2px] border-[#395886] dark:border-gray-600 flex items-center justify-center text-[#395886] dark:text-gray-200 font-bold text-sm sm:text-xl transition-all duration-300 group-hover:border-[#10b981] dark:group-hover:border-[#10b981] group-hover:text-[#10b981] dark:group-hover:text-[#10b981] group-hover:shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    02
                    <div class="fw-marker-ring absolute inset-[2px] sm:inset-[3px] border border-[#395886]/20 dark:border-gray-500/30 rounded-full group-hover:border-[#10b981]/40 transition-colors"></div>
                  </div>
                  <div class="fw-line hidden sm:block absolute top-1/2 left-[4.5rem] w-[calc(100%-4.5rem+2rem)] lg:w-[calc(100%-4.5rem+2rem)] h-[2px] bg-[#D5DEEF] dark:bg-gray-700 -translate-y-1/2 z-0 transition-colors duration-500 group-hover:bg-gradient-to-r group-hover:from-[#10b981] group-hover:to-[#D5DEEF] dark:group-hover:to-gray-700 overflow-hidden">
                    <div class="fw-line-highlight absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-[#638ECB] to-transparent opacity-0 mix-blend-multiply"></div>
                  </div>
                </div>

                <div class="fw-card w-full p-3 sm:pl-10 sm:pr-8 sm:py-8 bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-[#D5DEEF] dark:border-gray-700 transition-all duration-300 flex flex-col h-full relative z-10 shadow-sm sm:shadow-none">
                  <div class="fw-icon text-[#638ECB] dark:text-blue-400 mb-2 sm:mb-3 group-hover:text-[#10b981] dark:group-hover:text-[#10b981] transition-colors">
                    <!-- Innovation Icon -->
                    <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                  </div>
                  <h3 class="text-sm sm:text-lg font-bold text-[#395886] dark:text-white mb-1 sm:mb-2">{{ $t('landing.mayor.fw_inno') }}</h3>
                  <p class="text-[#395886]/80 dark:text-gray-400 text-[0.72rem] sm:text-[0.9rem] leading-snug sm:leading-relaxed line-clamp-4 sm:line-clamp-none">
                    {{ $t('landing.mayor.fw_inno_desc') }}
                  </p>
                </div>
              </div>

              <!-- Pillar 3: Enterprise -->
              <div class="relative flex flex-col group fw-pillar" style="--fw-delay: 0.24s;">
                <div class="flex items-center w-full mb-2 sm:mb-6 relative">
                  <div class="fw-marker z-10 w-11 h-11 sm:w-[4.5rem] sm:h-[4.5rem] shrink-0 rounded-full bg-[#F0F3FA] dark:bg-gray-800 border-[2px] border-[#395886] dark:border-gray-600 flex items-center justify-center text-[#395886] dark:text-gray-200 font-bold text-sm sm:text-xl transition-all duration-300 group-hover:border-[#10b981] dark:group-hover:border-[#10b981] group-hover:text-[#10b981] dark:group-hover:text-[#10b981] group-hover:shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    03
                    <div class="fw-marker-ring absolute inset-[2px] sm:inset-[3px] border border-[#395886]/20 dark:border-gray-500/30 rounded-full group-hover:border-[#10b981]/40 transition-colors"></div>
                  </div>
                  <div class="fw-line hidden sm:block absolute top-1/2 left-[4.5rem] w-[calc(100%-4.5rem+2rem)] lg:w-[calc(100%-4.5rem+2rem)] h-[2px] bg-[#D5DEEF] dark:bg-gray-700 -translate-y-1/2 z-0 transition-colors duration-500 group-hover:bg-gradient-to-r group-hover:from-[#10b981] group-hover:to-[#D5DEEF] dark:group-hover:to-gray-700 overflow-hidden">
                    <div class="fw-line-highlight absolute inset-0 w-full h-full bg-gradient-to-r from-transparent via-[#638ECB] to-transparent opacity-0 mix-blend-multiply"></div>
                  </div>
                </div>

                <div class="fw-card w-full p-3 sm:pl-10 sm:pr-8 sm:py-8 bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-[#D5DEEF] dark:border-gray-700 transition-all duration-300 flex flex-col h-full relative z-10 shadow-sm sm:shadow-none">
                  <div class="fw-icon text-[#638ECB] dark:text-blue-400 mb-2 sm:mb-3 group-hover:text-[#10b981] dark:group-hover:text-[#10b981] transition-colors">
                    <!-- Enterprise Icon -->
                    <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                  </div>
                  <h3 class="text-sm sm:text-lg font-bold text-[#395886] dark:text-white mb-1 sm:mb-2">{{ $t('landing.mayor.fw_ent') }}</h3>
                  <p class="text-[#395886]/80 dark:text-gray-400 text-[0.72rem] sm:text-[0.9rem] leading-snug sm:leading-relaxed line-clamp-4 sm:line-clamp-none">
                    {{ $t('landing.mayor.fw_ent_desc') }}
                  </p>
                </div>
              </div>

              <!-- Pillar 4: Food Security -->
              <div class="relative flex flex-col group fw-pillar" style="--fw-delay: 0.36s;">
                <div class="flex items-center w-full mb-2 sm:mb-6 relative">
                  <div class="fw-marker z-10 w-11 h-11 sm:w-[4.5rem] sm:h-[4.5rem] shrink-0 rounded-full bg-[#F0F3FA] dark:bg-gray-800 border-[2px] border-[#395886] dark:border-gray-600 flex items-center justify-center text-[#395886] dark:text-gray-200 font-bold text-sm sm:text-xl transition-all duration-300 group-hover:border-[#10b981] dark:group-hover:border-[#10b981] group-hover:text-[#10b981] dark:group-hover:text-[#10b981] group-hover:shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                    04
                    <div class="fw-marker-ring absolute inset-[2px] sm:inset-[3px] border border-[#395886]/20 dark:border-gray-500/30 rounded-full group-hover:border-[#10b981]/40 transition-colors"></div>
                  </div>
                </div>

                <div class="fw-card w-full p-3 sm:pl-10 sm:pr-8 sm:py-8 bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl border border-[#D5DEEF] dark:border-gray-700 transition-all duration-300 flex flex-col h-full relative z-10 shadow-sm sm:shadow-none">
                  <div class="fw-icon text-[#638ECB] dark:text-blue-400 mb-2 sm:mb-3 group-hover:text-[#10b981] dark:group-hover:text-[#10b981] transition-colors">
                    <!-- Food Security Icon -->
                    <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <h3 class="text-sm sm:text-lg font-bold text-[#395886] dark:text-white mb-1 sm:mb-2">{{ $t('landing.mayor.fw_food') }}</h3>
                  <p class="text-[#395886]/80 dark:text-gray-400 text-[0.72rem] sm:text-[0.9rem] leading-snug sm:leading-relaxed line-clamp-4 sm:line-clamp-none">
                    {{ $t('landing.mayor.fw_food_desc') }}
                  </p>
                </div>
              </div>

            </div>
          </div>
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
            class="section-kicker section-kicker-light reveal-child"
          >
            {{ $t('landing.mayor.city') }}
          </span>

          <h2 class="reveal-child">
            {{ $t('landing.visit_title') }}
          </h2>

          <p class="reveal-child">
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
            class="section-kicker reveal-child"
          >
            {{ $t('landing.mayor.brand') }}
          </span>

          <h2 class="reveal-child">
            {{ $t('landing.cta_title') }}
          </h2>

          <p class="reveal-child">
            {{ $t('landing.cta_desc') }}
          </p>


          <div class="cta-actions reveal-child">

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

    <AppFooter />

  </div>
</template>


<script setup lang="ts">
import AppFooter from '@/components/layout/AppFooter.vue'
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

const frameworkGridRef = ref<HTMLElement | null>(null)


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
    index
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
     Framework Grid Animation Observer
  ----------------------------------------------------------- */
  if (frameworkGridRef.value) {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
      frameworkGridRef.value.classList.add('is-active');
    } else {
      const observer = new IntersectionObserver(([entry]) => {
        if (entry.isIntersecting) {
          frameworkGridRef.value?.classList.add('is-active');
          observer.unobserve(entry.target);
        }
      }, { threshold: 0.25 });
      observer.observe(frameworkGridRef.value);
    }
  }


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
          '120px 0px',

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

              // Intentionally removed pause to maintain persistent playback

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
      heroVideoRef.value,
      bookVideoRef.value,
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

            // Intentionally removed pause

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
/* Fallback for SF Pro Display on non-Apple devices */
.sf-pro-display {
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

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
    100dvh;

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
    6.5rem
    1rem
    2rem;
}


.hero-shell {

  width:
    min(100%, 1400px);

  min-height:
    200px;

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
    610px;

  max-height:
    740px;
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
    0.6rem;

  padding:
    3.8rem 2rem 1.5rem;
}


.hero-kicker {
  position:
    absolute;

  top:
    1.6rem;

  left:
    2rem;

  z-index:
    20;

  display:
    inline-flex;

  align-items:
    center;

  padding:
    0.38rem
    0.8rem;

  border:
    1px solid
    rgba(
      99,
      142,
      203,
      0.28
    );

  border-radius:
    999px;

  background:
    rgba(
      99,
      142,
      203,
      0.1
    );

  backdrop-filter:
    blur(8px);

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

  box-shadow:
    0 4px 14px rgba(57, 88, 134, 0.08);

  animation:
    hero-kicker-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}


@keyframes hero-kicker-in {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


.hero-content h1 {

  max-width:
    580px;

  color:
    var(--landing-text);

  font-size:
    clamp(
      2.2rem,
      4vw,
      3.6rem
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
    0.9rem;

  line-height:
    1.5;

  font-weight:
    500;
}


.hero-actions {

  width:
    100%;

  max-width:
    380px;

  margin-top:
    0.3rem;
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
    2.8rem;

  padding:
    0.6rem
    1rem;

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
    1rem
    1rem
    1rem
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
    200px;

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
    min(100%, 1400px);

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
    7rem 0;

  background:
    var(--landing-surface);

}


.book-heading {

  width:
    min(100%, 720px);

  margin:
    0 auto 4.5rem;

  padding:
    0 1rem;

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
  content-visibility:
    auto;

  contain-intrinsic-size:
    1px 700px;

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
  content-visibility:
    auto;

  contain-intrinsic-size:
    1px 500px;

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
   EXECUTIVE & MINIMAL FRAMEWORK
============================================================ */

/* Inline SVG Noise generator for a subtle luxury paper grain */
.noise-bg {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.8' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
}

/* Fallback cursive stack for the signature */
.signature-font {
  font-family: 'Brush Script MT', 'Dancing Script', 'Playfair Display', Georgia, cursive, serif;
}


.framework-section-new {
  width: 100%;
  padding: 6rem 0;
}

.framework-container {
  width: min(100%, 1180px);
  margin: 0 auto;
  padding: 0 1.5rem;
}

.framework-intro-minimal {
  text-align: center;
  margin-bottom: 4rem;
}

.framework-intro-minimal h2 {
  font-size: clamp(2rem, 4vw, 3rem);
  color: var(--landing-text);
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  letter-spacing: -0.03em;
}

.framework-intro-minimal p {
  font-size: 1.1rem;
  color: var(--landing-text-secondary);
}

.framework-minimal-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 1.25rem;
}

.framework-minimal-card {
  background: var(--landing-surface);
  border: 1px solid var(--landing-border);
  border-radius: 1.25rem;
  padding: 2.25rem;
  display: flex;
  flex-direction: column;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.framework-minimal-card::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  padding: 2px;
  background: linear-gradient(135deg, var(--landing-brand-light), var(--landing-brand));
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  opacity: 0;
  transition: opacity 0.4s ease;
}

.framework-minimal-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 15px 35px rgba(57, 88, 134, 0.15);
}

.framework-minimal-card:hover::before {
  opacity: 1;
}

.framework-number {
  font-family: monospace;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--landing-brand);
  background: var(--landing-brand-soft);
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  align-self: flex-start;
  margin-bottom: 1.5rem;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 12px rgba(57, 88, 134, 0.1);
}

.framework-details h3 {
  font-size: 1.25rem;
  color: var(--landing-text);
  margin-bottom: 0.75rem;
  font-weight: 600;
}

.framework-details p {
  font-size: 0.95rem;
  line-height: 1.5;
  color: var(--landing-text-secondary);
}

@media (max-width: 1024px) {
  .framework-minimal-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .framework-minimal-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.6rem;
  }
  .framework-minimal-card {
    padding: 1rem 0.75rem;
    border-radius: 0.85rem;
  }
  .executive-card {
    padding: 2rem 1.25rem;
  }
}

/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1024px) {

  .hero-grid {

    grid-template-columns:
      1fr;

    max-height:
      none;
  }


  .hero-video-shell {

    padding:
      0
      1.5rem
      1.5rem;
  }


  .hero-content {

    padding:
      2rem 2rem 1rem;
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


@media (max-width: 768px) {

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

  .hero-kicker {
    top: 1rem;
    left: 1.1rem;
    padding: 0.28rem 0.6rem;
    font-size: 0.44rem;
  }

  .hero-content {
    padding:
      3.2rem 1.4rem 1.25rem;
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
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.4rem;
    padding: 0 0.35rem;
  }

  .quick-action {
    padding: 0.65rem 0.35rem;
    flex-direction: column;
    text-align: center;
    gap: 0.3rem;
    border-radius: 0.75rem;
    min-height: 0;
  }

  .quick-icon {
    width: 1.8rem;
    height: 1.8rem;
  }

  .quick-icon svg {
    width: 1rem;
    height: 1rem;
  }

  .quick-copy h3 {
    font-size: 0.68rem;
    line-height: 1.2;
    margin-bottom: 0.1rem;
  }

  .quick-copy p {
    font-size: 0.46rem;
    line-height: 1.2;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .quick-arrow {
    display: none;
  }

  .framework-section,
  .visit-section {
    padding: 3rem 0;
  }

  .initiative-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.35rem;
    padding: 0 0.25rem;
  }

  .initiative {
    min-height: 9rem;
    padding: 0.6rem 0.35rem;
    border-radius: 0.75rem;
  }

  .initiative h3 {
    font-size: 0.68rem;
    line-height: 1.2;
    margin-bottom: 0.2rem;
  }

  .initiative p {
    font-size: 0.45rem;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
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
    flex-direction: row;
    justify-content: center;
    gap: 0.5rem;
  }

  .cta-actions .primary-button,
  .cta-actions .secondary-button {
    width: auto;
    padding: 0.65rem 1rem;
    font-size: 0.75rem;
  }

  .footer-container {
    padding: 1.8rem 0.75rem 0.8rem;
  }

  .footer-grid {
    grid-template-columns: 1.3fr 0.9fr 0.9fr 0.9fr;
    gap: 0.75rem;
  }

  .footer-brand {
    grid-column: auto;
  }

  .footer-bottom {
    align-items: center;
    flex-direction: row;
    justify-content: space-between;
  }

}

@media (max-width: 640px) {

  .footer-grid {
    grid-template-columns: 1.2fr 0.9fr 0.9fr 0.9fr;
    gap: 0.45rem;
  }

  .quick-action {
    padding: 0.55rem 0.25rem;
  }

  .hero-content h1 {
    font-size: 2rem;
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

}


/* ============================================================
   FRAMEWORK PROGRESSIVE ANIMATION
============================================================ */

.fw-card {
  opacity: 0;
  transform: translateY(14px) scale(0.985);
  will-change: transform, opacity;
}

.framework-grid.is-active .fw-card {
  animation: fw-card-reveal 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  animation-delay: calc(var(--fw-delay) + 0.35s);
}

@keyframes fw-card-reveal {
  0% {
    opacity: 0;
    transform: translateY(14px) scale(0.985);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.fw-marker {
  will-change: transform;
}

.framework-grid.is-active .fw-marker {
  animation: fw-marker-pulse 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  animation-delay: var(--fw-delay);
}

@keyframes fw-marker-pulse {
  0% {
    transform: scale(0.96);
  }
  40% {
    transform: scale(1.02);
  }
  100% {
    transform: scale(1);
  }
}

.fw-marker-ring {
  opacity: 0;
  will-change: opacity, transform, border-color;
}

.framework-grid.is-active .fw-marker-ring {
  animation: fw-ring-flash 0.7s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  animation-delay: var(--fw-delay);
}

@keyframes fw-ring-flash {
  0% {
    opacity: 0;
    transform: scale(0.9);
    border-color: rgba(99, 142, 203, 0);
  }
  30% {
    opacity: 1;
    transform: scale(1.1);
    border-color: rgba(99, 142, 203, 0.4);
  }
  100% {
    opacity: 0;
    transform: scale(1.15);
    border-color: rgba(99, 142, 203, 0);
  }
}

.fw-line-highlight {
  transform: translateX(-100%);
  will-change: transform, opacity;
}

.framework-grid.is-active .fw-line-highlight {
  animation: fw-line-move 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
  animation-delay: calc(var(--fw-delay) + 0.1s);
}

@keyframes fw-line-move {
  0% {
    opacity: 0;
    transform: translateX(-100%);
  }
  20% {
    opacity: 0.6;
  }
  80% {
    opacity: 0.6;
  }
  100% {
    opacity: 0;
    transform: translateX(100%);
  }
}

/* Premium Hover Interactions */
@media (hover: hover) {
  .fw-card {
    transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), border-color 0.3s ease-out !important;
  }
  .fw-icon {
    transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), color 0.3s ease-out !important;
  }

  .group:hover .fw-card {
    transform: translateY(-3px) scale(1) !important;
    box-shadow: 0 12px 24px rgba(57, 88, 134, 0.08) !important;
    border-color: rgba(57, 88, 134, 0.25) !important;
  }

  .group:hover .fw-icon {
    transform: scale(1.03) !important;
  }
}

/* Reduced Motion Overrides */
@media (prefers-reduced-motion: reduce) {
  .fw-card, .fw-marker, .fw-line-highlight, .fw-marker-ring {
    animation: none !important;
  }
  .fw-card {
    opacity: 1 !important;
    transform: none !important;
  }
}
</style>
