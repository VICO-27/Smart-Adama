<template>
  <div class="bg-[#030303] text-[#e2e8f0] min-h-screen font-sans selection:bg-brand-500 selection:text-white relative overflow-hidden">

    <!-- Cinematic Film Grain Overlay -->
    <div class="pointer-events-none fixed inset-0 z-50 opacity-[0.03] mix-blend-overlay" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noiseFilter%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noiseFilter)%22/%3E%3C/svg%3E');"></div>

    <!-- Minimal Header -->
    <header class="fixed top-0 w-full px-6 md:px-12 py-8 z-40 flex justify-between items-center mix-blend-difference">
      <router-link to="/" class="text-sm font-bold tracking-[0.2em] uppercase text-white hover:text-brand-300 transition-colors">
        {{ $t('nav.brand') }}
      </router-link>
      <router-link to="/dashboard" class="text-xs font-mono font-bold uppercase tracking-widest text-white/60 hover:text-white transition-colors relative group">
        Return to Dashboard
        <span class="absolute -bottom-2 left-0 w-0 h-px bg-white transition-all duration-500 group-hover:w-full"></span>
      </router-link>
    </header>

    <!-- 1. THE HERO (Origin) -->
    <section class="relative w-full min-h-screen flex flex-col justify-center px-6 md:px-12 pt-20">
      <div class="max-w-7xl mx-auto w-full">
        <p class="reveal-text text-xs md:text-sm font-mono font-bold tracking-[0.3em] text-white/50 uppercase mb-8 md:mb-12">
          {{ $t('about.tag') }}
        </p>

        <h1 class="font-display font-medium text-[12vw] md:text-[8vw] leading-[0.9] tracking-tighter text-white mb-12">
          <span class="block reveal-up">{{ $t('about.hero1') }}</span>
          <span class="block reveal-up delay-100">{{ $t('about.hero2') }}</span>
          <span class="block reveal-up delay-200 text-white/40">{{ $t('about.hero3') }}</span>
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mt-20 md:mt-32">
          <div class="md:col-span-1 reveal-opacity delay-300">
            <p class="text-white/50 text-xs font-mono tracking-[0.2em] uppercase mb-4">{{ $t('about.mission') }}</p>
            <div class="w-8 h-px bg-white/20 mb-6"></div>
          </div>
          <div class="md:col-span-2 reveal-opacity delay-400">
            <p class="text-xl md:text-3xl font-medium leading-tight text-white/80 max-w-3xl">
              {{ $t('about.mission_desc') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Scroll Indicator -->
      <div class="absolute bottom-12 left-6 md:left-12 flex items-center gap-4 reveal-opacity delay-700">
        <div class="w-[1px] h-12 bg-gradient-to-b from-white to-transparent"></div>
        <span class="text-[10px] font-mono tracking-widest text-white/40 uppercase rotate-180" style="writing-mode: vertical-rl;">Scroll</span>
      </div>
    </section>

    <!-- 2. THE PHILOSOPHY (Cinematic Typographic Scroll) -->
    <section class="py-40 md:py-64 px-6 md:px-12 bg-[#030303] relative z-10">
      <div class="max-w-6xl mx-auto text-center">
        <p class="text-brand-400 text-xs font-mono tracking-[0.3em] uppercase mb-12 reveal-up">{{ $t('about.approach') }}</p>

        <h2 class="text-3xl md:text-5xl lg:text-7xl font-display font-medium leading-[1.1] tracking-tight uppercase">
          <span class="block text-white/30 reveal-up">{{ $t('about.approach1') }}</span>
          <span class="block text-white/30 reveal-up delay-100">{{ $t('about.approach2') }}</span>
          <span class="block text-white reveal-up delay-200 mt-8">{{ $t('about.approach3') }}</span>
          <span class="block text-white reveal-up delay-300">{{ $t('about.approach4') }}</span>
        </h2>


      </div>
    </section>

    <!-- 3. THE ARCHITECTURE (Craft & Technology) -->
    <section class="py-32 px-6 md:px-12 border-t border-white/5 bg-gradient-to-b from-[#030303] to-[#0a0a0a]">
      <div class="max-w-7xl mx-auto">
        <div class="mb-24 reveal-opacity">
          <p class="text-white/50 text-xs font-mono tracking-[0.2em] uppercase mb-4">{{ $t('about.ecosystem') }}</p>
          <h2 class="text-4xl md:text-6xl font-display font-bold text-white">{{ $t('about.tools') }}</h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-4 gap-x-3 sm:gap-x-8 gap-y-6 sm:gap-y-12 mt-4">
          <!-- PostgreSQL -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-100 group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">01 / FOUNDATION</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">PostgreSQL</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M23.011 11.238l-1.026.974c-1.391 1.34-3.528 2.378-6.19 3.003L14.47 24l-2.073-1.013-1.745.823-1.688-1.554.492-2.18-.588-1.71c-3.79-.115-6.938-1.79-6.938-3.766 0-.315.172-.618.492-.904.708-1.524 2.822-2.903 5.483-3.753.115-.436.315-.815.545-1.16-.018-.016-.035-.034-.052-.05L2.83 5.372 4.417 3.32l2.395.275 2.126-2.112 1.391 1.765.928 2.21 2.235-.453.762.648.241.137-.023.23c2.446 1.05 4.022 2.65 4.022 4.407 0 1.25-.87 2.4-2.337 3.268.04.143.08.286.114.43 1.954-.424 3.516-1.123 4.39-1.965l.504-.492a39.467 39.467 0 0 1 1.01-1.05zm-9.014 1.157c-2.36.03-4.57.86-6.177 2.36 2.012-1.34 4.54-2.172 7.234-2.315-.35-1.077-.872-2.05-1.542-2.887 1.375 1.583 2.184 3.543 2.184 5.666 0 .917-.15 1.8-.425 2.624-.046-.2-.115-.4-.184-.596-.344-.928-.888-1.776-1.576-2.504.607.722 1.031 1.552 1.25 2.44.137.584.206 1.19.206 1.81 0 1.76-1.07 3.42-2.97 4.56l-.504.286c1.237-.89 1.993-2.28 1.993-3.8 0-1.282-.573-2.486-1.553-3.328l-.39-.32c.596.48 1.043 1.11 1.295 1.815.114.34.183.71.183 1.09 0 1.014-.527 1.92-1.387 2.53l.413 1.066L9.6 15.655c-.24.504-.54 1.002-.89 1.49l-.49 1.272-1.18 1.627-.803.95.836-1.84.448-1.5.767-1.17c-2.028-.27-3.79-1.36-4.995-2.98.814 1.38 2.155 2.48 3.823 3.03l.366-2.8c-.894-1.002-1.42-2.29-1.42-3.666 0-.895.228-1.743.606-2.5-.544.75-.853 1.68-.853 2.65 0 2.222 1.626 4.1 3.782 4.6l.01-3.6-1.637 1.49a5.16 5.16 0 0 1-.223-1.52c0-1.85 1.026-3.483 2.52-4.32-.424.63-.664 1.4-.664 2.2 0 1.386.72 2.623 1.82 3.344l.263-2.12c-.52-.303-.943-.755-1.226-1.294-.2-.407-.32-.864-.32-1.34 0-.614.195-1.18.528-1.666.38-.568.905-1.02 1.523-1.32-.618 1.204-.378 2.76.677 3.768L12.553 10l-.447.38c.63-.493 1.398-.8 2.235-.8 1.432 0 2.67.8 3.306 1.984a3.805 3.805 0 0 0-1.42-3.668.514.514 0 0 0-.17-.114.502.502 0 0 0-.07-.023h-.02c-.08-.035-.17-.06-.25-.093l-.403-.138c-1.353-.39-2.82-.413-4.17-.057.23-.057.48-.115.722-.16-.963-.207-1.96-.207-2.923 0h-.01c-.138.034-.286.07-.435.126.31-.103.62-.195.94-.275-.78-.172-1.57-.206-2.36-.126 2.373-.39 4.88-.138 7.15.71.745.287 1.444.665 2.086 1.134.195-1.21-.034-2.454-.64-3.51-.55-1.02-1.41-1.83-2.45-2.33 1.25.32 2.33.985 3.12 1.89l.34.402.26.344c.48.64.837 1.365 1.055 2.143z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Robust relational database design powering the entire platform architecture.</p>
          </div>

          <!-- Laravel -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-200 group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">02 / LOGIC</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">Laravel</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M23.111 8.243l-9.67-5.594c-.87-.499-1.929-.499-2.798 0L.971 8.243c-.87.499-1.385 1.428-1.385 2.453v11.18c0 .248.14.475.362.593.22.119.49.102.696-.046l6.82-4.993c.123-.09.2-.236.2-.395v-7.25c0-.665.348-1.272.91-1.597l2.87-1.657a1.8 1.8 0 0 1 1.798 0l8.761 5.068a1.79 1.79 0 0 1 .91 1.597v6.621c0 .211.111.408.293.513l6.577 3.8a.673.673 0 0 0 .341.092.654.654 0 0 0 .668-.652V10.697c.001-1.025-.514-1.955-1.384-2.454H23.111zM7.65 17.039l-6.264 4.582a.668.668 0 0 1-.362.107.653.653 0 0 1-.668-.652V20.26l6.82-4.994c.122-.09.2-.235.2-.394v-2.072l.274.158Z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Secure, scalable backend infrastructure managing RESTful APIs and authentication.</p>
          </div>

          <!-- Vue.js -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-300 group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">03 / INTERFACE</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">Vue.js</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M24,1.61H14.06L12,5.16,9.94,1.61H0L12,22.39ZM12,14.08,5.16,2.23H9.59L12,6.41l2.41-4.18h4.43Z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Reactive frontend development delivering a seamless, premium user experience.</p>
          </div>

          <!-- AI / RAG -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-400 group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">04 / INTELLIGENCE</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">AI / RAG</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Advanced LLM integration providing grounded answers with exact book references.</p>
          </div>

          <!-- Redis -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-[500ms] group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">05 / CACHING</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">Redis</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M21.115 15.688c-.687-.417-1.156-1.147-1.458-1.854-1.229 1.406-4.22 3.125-7.741 3.125-4.428 0-7.856-2.584-7.856-5.782 0-3.156 3.428-5.74 7.856-5.74.875 0 1.625.104 2.375.292.177-.115.354-.25.563-.354C13.543 2.053 10.397.667 7.021.667 3.031.667 0 3.823 0 7.781c0 3.25 2.104 6.094 5.094 7.219-.531.677-1.115.823-1.927.792h-.365c.531 1.25 1.573 2.01 2.876 2.052.791.031 1.666-.302 2.396-.75.052 0 .093.01.145.02a8.773 8.773 0 0 0 3.699.823 8.877 8.877 0 0 0 4.146-1.02l.063-.032c1.781 1.011 4.542 1.625 7.647 1.636 0 0-.104-.625-.198-.948-.25-1.01-.896-1.573-2.46-1.886ZM9.709 9.876c0 1.25-1.74 2.27-3.876 2.27-2.146 0-3.876-1.02-3.876-2.27S3.709 7.615 5.834 7.615c2.135 0 3.875 1.01 3.875 2.26Z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">High-performance in-memory data store accelerating application responsiveness and queue processing.</p>
          </div>

          <!-- Supabase -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-[600ms] group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">06 / BACKEND OS</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">Supabase</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M11.954 1.218a.385.385 0 0 1 .419.068l11.082 10.155a.384.384 0 0 1-.26 1.019l-9.143-.003.957 9.827a.385.385 0 0 1-.418.423.38.38 0 0 1-.225-.118L3.285 12.433a.385.385 0 0 1 .25-1.02h9.136L11.724 1.59a.384.384 0 0 1 .23-.372z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Seamlessly integrating real-time database capabilities, vector storage, and edge functions.</p>
          </div>

          <!-- Git/GitHub -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-[700ms] group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">07 / VERSIONING</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">Git & GitHub</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Ensuring code reliability, collaborative pipelines, and robust continuous integration workflows.</p>
          </div>

          <!-- Vercel & Render -->
          <div class="border-t border-white/10 pt-3 sm:pt-6 reveal-up delay-[800ms] group">
            <div class="flex items-center justify-between mb-2 sm:mb-4">
              <div>
                <span class="text-brand-400 font-mono text-[8px] sm:text-[10px] tracking-widest uppercase block mb-0.5 sm:mb-1">08 / DEPLOYMENT</span>
                <h4 class="text-sm sm:text-xl text-white font-medium">Vercel & Render</h4>
              </div>
              <div class="w-8 h-8 sm:w-12 sm:h-12 shrink-0 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white/50 group-hover:text-brand-400 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all duration-300">
                <svg class="w-4 h-4 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M24 22.525H0l12-21.05 12 21.05z"/></svg>
              </div>
            </div>
            <p class="text-white/50 text-xs sm:text-sm leading-relaxed line-clamp-3 sm:line-clamp-none">Providing high-availability edge networks and reliable cloud orchestration for the full stack.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. THE BUILDERS (Sticky Scroll Storytelling) -->
    <section class="py-32 md:py-48 px-6 md:px-12 relative">
      <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-20">

        <!-- Sticky Left Column -->
        <div class="lg:w-1/3 relative">
          <div class="sticky top-40 reveal-opacity">
            <p class="text-white/50 text-xs font-mono tracking-[0.2em] uppercase mb-4">{{ $t('about.interns') }}</p>
            <h2 class="text-5xl md:text-7xl font-display font-medium text-white mb-6">{{ $t('about.engineers') }}</h2>
            <p class="text-white/60 leading-relaxed mb-8">
              {{ $t('about.engineers_desc') }}
            </p>
            <div class="flex items-center gap-3 text-xs font-mono tracking-widest text-brand-400 uppercase">
              <span class="w-2 h-2 bg-brand-400 rounded-full animate-pulse"></span>
              Scroll to meet the team
            </div>
          </div>
        </div>

        <!-- Scrolling Right Column -->
        <div class="lg:w-2/3 flex flex-col gap-32 pt-20 lg:pt-0">

          <!-- Ashenafi -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start">
              <div class="w-full md:w-48 h-64 md:h-64 shrink-0 overflow-hidden rounded-sm bg-white/5 relative">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-600/20 to-black mix-blend-multiply z-10 transition-opacity group-hover:opacity-0"></div>
                <img src="/profile/ashenafi.png" alt="Ashenafi Deresa" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700 scale-105 group-hover:scale-100" />
              </div>
              <div>
                <span class="text-brand-400 font-mono text-[10px] tracking-widest uppercase border border-brand-400/30 px-3 py-1 rounded-full mb-6 inline-block">Project Manager & Technical Integration Lead</span>
                <h3 class="text-3xl md:text-4xl font-display font-bold text-white mb-4">Ashenafi Deresa (Vico)</h3>
                <p class="text-white/60 text-base leading-relaxed mb-6">
                  Computer Science and Engineering student responsible for architecture oversight, full-stack integration, and stakeholder communication. Passionate about leveraging AI to build systems that scale and solve meaningful problems.
                </p>
                <div class="flex gap-4">
                  <a href="https://github.com/VICO-27" target="_blank" class="text-white hover:text-brand-400 transition-colors uppercase text-xs font-mono tracking-widest flex items-center gap-2">
                    Github <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  </a>
                  <a href="https://www.linkedin.com/in/ashenafi-deresa-computer-science-engineer/" target="_blank" class="text-white hover:text-brand-400 transition-colors uppercase text-xs font-mono tracking-widest flex items-center gap-2">
                    LinkedIn <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Nigusu -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-32 h-32 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/nigusu.png" alt="Nigusu Wario" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Nigusu Wario</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">QA, DevOps, Documentation & Release Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Driving testing, CI/CD pipelines, and ensuring seamless release management across the ecosystem.
                </p>
              </div>
            </div>
          </div>

          <!-- Kidus -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-32 h-32 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/kidus.png" alt="Kidus Tilahun" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Kidus Tilahun Shibeshi</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">Backend & PostgreSQL Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Architecting high-performance RESTful APIs and database structures to power the platform's core.
                </p>
              </div>
            </div>
          </div>

          <!-- Getamesay -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-32 h-32 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/getinet.png" alt="Getamesay Mekcha" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Getamesay Mekcha Abebe</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">Frontend & UI/UX Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Crafting responsive, intuitive user interfaces and defining the visual experience using Vue.js.
                </p>
              </div>
            </div>
          </div>

          <!-- Abinet -->
          <div class="group reveal-up">
            <div class="flex flex-col md:flex-row gap-8 items-start opacity-70 hover:opacity-100 transition-opacity duration-500">
              <div class="w-32 h-32 shrink-0 overflow-hidden rounded-full bg-white/5 border border-white/10 relative">
                <img src="/profile/abinet.jpg" alt="Abinet Tesfaye" class="w-full h-full object-cover filter grayscale group-hover:grayscale-0 transition-all duration-700" />
              </div>
              <div>
                <h3 class="text-2xl font-display font-bold text-white mb-2">Abinet Tesfaye Ayele</h3>
                <p class="text-white/40 text-sm font-mono tracking-wide uppercase mb-4">AI/RAG & Learning Intelligence Lead</p>
                <p class="text-white/60 text-sm leading-relaxed">
                  Integrating advanced Language Models and Natural Language Processing to ground answers with references.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- 5. CONTACT US FORM -->
    <section class="py-32 px-6 md:px-12 bg-[#0a0a0a] border-t border-white/5 relative z-10">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-16">

        <div class="reveal-opacity">
          <p class="text-brand-400 text-xs font-mono tracking-[0.2em] uppercase mb-4">Get in Touch</p>
          <h2 class="text-4xl md:text-6xl font-display font-bold text-white mb-6">Contact Us.</h2>
          <p class="text-white/60 leading-relaxed mb-10 max-w-md">
            Have a question about the Smart Adama platform, the book, or our tech stack? Reach out directly to the lead developer.
          </p>

          <div class="flex flex-col gap-6">
            <a href="mailto:ashenafi.deresa.cse@gmail.com" class="flex items-center gap-4 text-white hover:text-brand-400 transition-colors group">
              <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand-400/50 transition-colors text-xl">
                ✉️
              </div>
              <span class="font-mono text-sm tracking-wide">ashenafi.deresa.cse@gmail.com</span>
            </a>
            <a href="https://t.me/vico_27" target="_blank" class="flex items-center gap-4 text-white hover:text-brand-400 transition-colors group">
              <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand-400/50 transition-colors text-xl">
                ✈️
              </div>
              <span class="font-mono text-sm tracking-wide">@vico_27 on Telegram</span>
            </a>
            <a href="tel:+251930034371" class="flex items-center gap-4 text-white hover:text-brand-400 transition-colors group">
              <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-brand-400/50 transition-colors text-xl">
                📞
              </div>
              <span class="font-mono text-sm tracking-wide">+251 93 003 4371</span>
            </a>
          </div>
        </div>

        <div class="reveal-up delay-200">
          <div class="bg-white/5 border border-white/10 p-8 md:p-10 rounded-[2.5rem] backdrop-blur-sm shadow-2xl">
            <form @submit.prevent class="flex flex-col gap-6">
              <div class="flex flex-col gap-2">
                <label class="text-xs font-mono tracking-widest text-white/50 uppercase">Your Name</label>
                <input v-model="contactForm.name" type="text" required class="bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-brand-500 transition-colors" placeholder="John Doe" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-xs font-mono tracking-widest text-white/50 uppercase">Your Email</label>
                <input v-model="contactForm.email" type="email" required class="bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-brand-500 transition-colors" placeholder="john@example.com" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-xs font-mono tracking-widest text-white/50 uppercase">Message</label>
                <textarea v-model="contactForm.message" required rows="4" class="bg-black/50 border border-white/10 rounded-xl px-5 py-4 text-white focus:outline-none focus:border-brand-500 transition-colors resize-none" placeholder="How can we help you?"></textarea>
              </div>

              <div class="flex flex-col sm:flex-row gap-4 mt-4">
                <button type="button" @click="sendViaEmail" class="flex-1 bg-white text-black font-bold py-4 rounded-xl hover:bg-brand-50 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                  Send via Email
                </button>
                <button type="button" @click="sendViaTelegram" class="flex-1 bg-[#0088cc] text-white font-bold py-4 rounded-xl hover:bg-[#0099e6] hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2">
                  Send via Telegram
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </section>

    <!-- 6. FINAL CTA (Vision) -->
    <section class="py-40 md:py-64 px-6 md:px-12 bg-black flex flex-col items-center justify-center text-center relative">
      <div class="absolute inset-0 bg-gradient-to-t from-brand-900/20 to-transparent pointer-events-none"></div>
      <div class="relative z-10 max-w-4xl">
        <h2 class="text-5xl md:text-8xl font-display font-medium text-white mb-12 tracking-tight reveal-up">
          {{ $t('about.future') }} <br/><span class="text-white/40">{{ $t('about.future_sub') }}</span>
        </h2>
        <div class="flex flex-wrap justify-center gap-8 reveal-opacity delay-200">
          <router-link to="/study" class="text-white hover:text-brand-400 text-sm font-mono uppercase tracking-widest transition-colors pb-1 border-b border-white/20 hover:border-brand-400">
            {{ $t('about.exp') }}
          </router-link>
          <a href="https://github.com/VICO-27" target="_blank" class="text-white hover:text-brand-400 text-sm font-mono uppercase tracking-widest transition-colors pb-1 border-b border-white/20 hover:border-brand-400">
            {{ $t('about.code') }}
          </a>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// Form State
const contactForm = ref({
  name: '',
  email: '',
  message: ''
})

// Submission Handlers
const sendViaEmail = () => {
  if (!contactForm.value.name || !contactForm.value.message) {
    alert('Please fill in your name and message before sending.')
    return
  }
  const subject = encodeURIComponent(`Smart Adama Inquiry from ${contactForm.value.name}`)
  const body = encodeURIComponent(`Name: ${contactForm.value.name}\nEmail: ${contactForm.value.email}\n\nMessage:\n${contactForm.value.message}`)
  window.location.href = `mailto:ashenafi.deresa.cse@gmail.com?subject=${subject}&body=${body}`
}

const sendViaTelegram = () => {
  if (!contactForm.value.name || !contactForm.value.message) {
    alert('Please fill in your name and message before sending.')
    return
  }
  const text = encodeURIComponent(`*New Smart Adama Inquiry*\n\n*Name:* ${contactForm.value.name}\n*Email:* ${contactForm.value.email}\n\n*Message:*\n${contactForm.value.message}`)
  window.open(`https://t.me/vico_27?text=${text}`, '_blank')
}

// Scroll Animations
let observer: IntersectionObserver | null = null

onMounted(() => {
  observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible')
        observer?.unobserve(entry.target)
      }
    })
  }, {
    rootMargin: '0px 0px -10% 0px',
    threshold: 0.1
  })

  const elements = document.querySelectorAll('.reveal-up, .reveal-opacity, .reveal-text')
  elements.forEach((el) => observer?.observe(el))
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})
</script>

<style scoped>
.reveal-up {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 1s cubic-bezier(0.2, 0.8, 0.2, 1),
              transform 1.2s cubic-bezier(0.2, 0.8, 0.2, 1);
  will-change: opacity, transform;
}

.reveal-opacity {
  opacity: 0;
  transition: opacity 1.5s cubic-bezier(0.2, 0.8, 0.2, 1);
  will-change: opacity;
}

.reveal-text {
  clip-path: polygon(0 100%, 100% 100%, 100% 100%, 0% 100%);
  opacity: 0;
  transform: translateY(20px);
  transition: clip-path 1.2s cubic-bezier(0.16, 1, 0.3, 1),
              opacity 1.2s cubic-bezier(0.16, 1, 0.3, 1),
              transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
  will-change: clip-path, opacity, transform;
}

.is-visible.reveal-up {
  opacity: 1;
  transform: translateY(0);
}

.is-visible.reveal-opacity {
  opacity: 1;
}

.is-visible.reveal-text {
  clip-path: polygon(0 0, 100% 0, 100% 100%, 0% 100%);
  opacity: 1;
  transform: translateY(0);
}

.delay-100 { transition-delay: 100ms; }
.delay-200 { transition-delay: 200ms; }
.delay-300 { transition-delay: 300ms; }
.delay-400 { transition-delay: 400ms; }
.delay-700 { transition-delay: 700ms; }

@media (prefers-reduced-motion: reduce) {
  .reveal-up, .reveal-opacity, .reveal-text {
    opacity: 1 !important;
    transform: none !important;
    clip-path: none !important;
    transition: none !important;
  }
}
</style>