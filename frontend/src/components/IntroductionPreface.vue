<script setup lang="ts">
defineProps<{
  currentPage: number;
}>();

defineEmits<{
  (e: 'goToChapter', chapterNum: string): void;
}>();

const authors = [
  "Shanko Chura Aredo (Ph.D)",
  "Hailu Jelde Wedajo (MA)",
  "Chala Seya Yadola (MA)",
  "Kedir Elka Amigna (MSc.)",
  "Deyas Deme Merga (MSc.)",
  "Fikadu Nigussie Haile (BSc.)"
];

const toc = [
  { chapter: "1", title: "Introduction", page: "1" },
  { chapter: "2", title: "Smart Governance", page: "26" },
  { chapter: "3", title: "Digital Adama", page: "65" },
  { chapter: "4", title: "Smart Security", page: "76" },
  { chapter: "5", title: "Smart Urban Design and Land Use Management", page: "212" },
  { chapter: "6", title: "Smart Environment and Organic Production", page: "235" },
  { chapter: "7", title: "Smart Mobility", page: "271" },
  { chapter: "8", title: "Smart Social Services", page: "348" },
  { chapter: "9", title: "Smart Tourism and Culture", page: "362" },
  { chapter: "10", title: "Smart Public Relation, Research and Knowledge Management", page: "395" },
  { chapter: "11", title: "Smart People", page: "404" },
];


</script>

<template>
  <div class="w-full text-(--rt-text-body) animate-fade" :key="currentPage">

    <!-- ================= PAGE 1 ================= -->
    <div v-if="currentPage === 1" class="space-y-16 pb-8">
      <section class="text-center space-y-8 pb-12 border-b border-(--rt-border)">
        <div>
          <h1 class="text-4xl md:text-5xl font-bold text-(--rt-text) mb-4 leading-tight">Building Smart Adama</h1>
          <h2 class="text-2xl text-(--rt-muted) font-light">Comprehensive Guide</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left max-w-2xl mx-auto bg-(--rt-surface-2) p-8 rounded-xl border border-(--rt-border) shadow-sm">
          <div>
            <h3 class="font-bold text-(--rt-accent) mb-3 uppercase tracking-wider text-sm flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Authors
            </h3>
            <ul class="space-y-2 text-(--rt-text-body)">
              <li v-for="(author, index) in authors" :key="index" class="flex items-center gap-2">
                <span class="text-(--rt-muted) text-xs w-4">{{ String.fromCharCode(97 + index) }})</span>
                {{ author }}
              </li>
            </ul>
          </div>
          <div>
            <h3 class="font-bold text-(--rt-accent) mb-3 uppercase tracking-wider text-sm flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              Advisor and Reviewer
            </h3>
            <p class="text-(--rt-text-body) flex items-center gap-2">
              <span class="w-1.5 h-1.5 rounded-full bg-(--rt-muted)"></span>
              Jima Gobena Dadi (MSc.)
            </p>

            <h3 class="font-bold text-(--rt-text) mt-8 mb-3 uppercase tracking-wider text-sm">Publication Details</h3>
            <div class="text-(--rt-muted) text-sm leading-relaxed border-l-2 border-(--rt-border) pl-4">
              <p>Published by Harambe University</p>
              <p>Adama, Ethiopia</p>
              <p class="mt-2 font-medium text-(--rt-text-body)">First Edition: February 2025</p>
            </div>
          </div>
        </div>

        <div class="text-xs text-(--rt-muted) max-w-2xl mx-auto leading-relaxed px-4 py-3 bg-(--rt-surface-2) rounded-lg border border-(--rt-border) opacity-80">
          <strong>Copyright © 2025.</strong> All rights reserved. No part of this book may be reproduced, stored in a retrieval system, or transmitted in any form or by any means, electronic, mechanical, photocopying, recording, or otherwise, without the prior written permission or consent of the publisher and Adama City Administration.
        </div>
      </section>

      <!-- ================= MAYOR'S OFFICIAL CIVIC MESSAGE ================= -->
      <section class="w-full max-w-5xl mx-auto pt-4">
        <!-- Letter Container with Executive Civic Styling -->
        <article class="relative overflow-hidden bg-(--rt-surface) rounded-2xl sm:rounded-3xl border border-(--rt-border) shadow-[0_12px_40px_-10px_rgba(36,58,90,0.08),0_1px_3px_rgba(0,0,0,0.03)] dark:shadow-[0_12px_40px_-10px_rgba(0,0,0,0.4)]">

          <!-- Top Civic Ribbon -->
          <div class="h-2 w-full bg-gradient-to-r from-[#243A5A] via-[#395886] via-[#638ECB] to-[#243A5A]"></div>

          <div class="p-6 sm:p-10 md:p-14 lg:p-16">
            <!-- Official Letterhead Header -->
            <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 pb-6">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-[#395886]/10 dark:bg-blue-500/10 border border-[#395886]/20 dark:border-blue-400/20 p-2.5 flex items-center justify-center shadow-inner flex-shrink-0">
                  <img src="/logo.png" alt="Smart Adama Official Crest" class="w-full h-full object-contain drop-shadow-sm" />
                </div>
                <div>
                  <div class="text-[0.68rem] sm:text-xs font-bold uppercase tracking-[0.25em] text-[#395886] dark:text-[#8AAEE0]">
                    {{ $t('landing.mayor.mayor_city') }}
                  </div>
                  <h3 class="text-base sm:text-lg font-bold text-(--rt-text) tracking-wide mt-0.5">
                    {{ $t('landing.mayor.mayor_office') }}
                  </h3>
                  <div class="text-xs text-(--rt-muted)">
                    Federal Democratic Republic of Ethiopia
                  </div>
                </div>
              </div>

              <!-- Official Document Reference Block -->
              <div class="text-left sm:text-right border-l-2 sm:border-l-0 pl-3 sm:pl-0 border-(--rt-border) sm:border-transparent">
                <div class="text-[0.72rem] font-mono uppercase tracking-wider text-(--rt-muted)">
                  Ref: <span class="font-semibold text-(--rt-text)">ADM-EXEC-2025/01</span>
                </div>
                <div class="text-xs text-(--rt-muted) font-medium mt-1">
                  Adama, Oromia, Ethiopia
                </div>
                <div class="text-xs text-(--rt-muted) mt-0.5">
                  February 2025
                </div>
              </div>
            </header>

            <!-- Government-Grade Double Hairline Divider -->
            <div class="mb-8 sm:mb-10 space-y-1">
              <div class="h-[2px] w-full bg-gradient-to-r from-[#243A5A] via-[#638ECB] to-[#243A5A] opacity-75 dark:opacity-60"></div>
              <div class="h-[0.5px] w-full bg-(--rt-border)"></div>
            </div>

            <!-- Message Classification Eyebrow -->
            <div class="flex items-center gap-2 mb-4">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold tracking-wider uppercase bg-[#395886]/10 text-[#395886] dark:bg-blue-400/15 dark:text-blue-300 border border-[#395886]/20 dark:border-blue-400/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/>
                </svg>
                Message from the Mayor
              </span>
              <span class="text-xs text-(--rt-muted) hidden sm:inline">• Official Civic Address</span>
            </div>

            <!-- Civic Headline -->
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-(--rt-text) tracking-tight leading-tight mb-8">
              Building a Smarter, Sustainable, and Inclusive Adama
            </h2>

            <!-- Formal Salutation -->
            <div class="text-xl sm:text-2xl font-serif italic text-(--rt-text) font-semibold mb-6">
              {{ $t('landing.mayor.mayor_greeting') }}
            </div>

            <!-- Letter Body with Generous Line-Height and Typography -->
            <div class="space-y-6 text-[1.05rem] sm:text-[1.125rem] leading-[2.0] sm:leading-[2.15] text-(--rt-text-body) font-normal text-justify sm:text-left">
              <p>
                {{ $t('landing.mayor.mayor_p1') }}
              </p>
              <p>
                {{ $t('landing.mayor.mayor_p2') }}
              </p>

              <!-- Editorial Vision Callout Box -->
              <aside class="my-8 sm:my-10 p-6 sm:p-8 rounded-xl bg-gradient-to-r from-[#395886]/10 via-[#638ECB]/5 to-transparent border-l-4 border-[#395886] dark:border-[#638ECB] relative shadow-sm">
                <div class="flex items-start gap-3 sm:gap-4">
                  <svg class="w-8 h-8 text-[#395886] dark:text-[#8AAEE0] opacity-40 flex-shrink-0 mt-1" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                  </svg>
                  <blockquote class="text-lg sm:text-xl md:text-[1.28rem] font-serif italic text-(--rt-text) leading-relaxed font-medium">
                    {{ $t('landing.mayor.mayor_quote') }}
                  </blockquote>
                </div>
              </aside>

              <p>
                {{ $t('landing.mayor.mayor_p3') }}
              </p>
              <p>
                {{ $t('landing.mayor.mayor_p4') }}
              </p>
            </div>

            <!-- Sign-Off & Official Authentication Footer -->
            <footer class="mt-12 sm:mt-16 pt-8 sm:pt-10 border-t border-(--rt-border) flex flex-col sm:flex-row items-start sm:items-end justify-between gap-8">
              <!-- Left: Formal Signature Block -->
              <div class="flex-1">
                <p class="text-base sm:text-lg font-serif italic text-(--rt-muted) mb-3">
                  {{ $t('landing.mayor.mayor_signoff') }}
                </p>

                <!-- Stylized Cursive Signature -->
                <div class="signature-font text-3xl sm:text-4xl text-(--rt-text) select-none py-1 transform -rotate-2 origin-left inline-block">
                  {{ $t('landing.mayor.mayor_name') }}
                </div>

                <div class="w-48 h-0.5 bg-gradient-to-r from-(--rt-accent) to-transparent my-2"></div>

                <!-- Formal Title and Institution -->
                <div class="mt-2">
                  <p class="font-bold text-base sm:text-lg text-(--rt-text) tracking-wide uppercase">
                    {{ $t('landing.mayor.mayor_name') }}
                  </p>
                  <p class="text-xs sm:text-sm text-[#395886] dark:text-[#8AAEE0] font-semibold tracking-wider uppercase mt-0.5">
                    {{ $t('landing.mayor.mayor_title') }}
                  </p>
                  <p class="text-xs text-(--rt-muted) mt-0.5">
                    Adama City Administration • Oromia Regional State
                  </p>
                </div>
              </div>

              <!-- Right: Civic Verification Stamp / Seal -->
              <div class="flex items-center gap-3.5 px-4 py-3 rounded-2xl bg-[#395886]/5 dark:bg-blue-400/5 border border-[#395886]/20 dark:border-blue-400/20 text-(--rt-muted) select-none flex-shrink-0">
                <div class="w-10 h-10 rounded-full border-2 border-dashed border-[#395886]/40 dark:border-blue-400/40 flex items-center justify-center text-[#395886] dark:text-blue-300">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="m9 12 2 2 4-4"/>
                  </svg>
                </div>
                <div class="text-left leading-tight">
                  <div class="text-[0.68rem] font-bold uppercase tracking-wider text-(--rt-text)">
                    Adama City Administration
                  </div>
                  <div class="text-[0.65rem] text-[#395886] dark:text-[#8AAEE0] font-medium tracking-wide mt-0.5">
                    Official Executive Seal Verified
                  </div>
                </div>
              </div>
            </footer>

          </div>
        </article>
      </section>
    </div>

    <!-- ================= PAGE 2 ================= -->
    <div v-else-if="currentPage === 2" class="space-y-16 pb-8">

      <!-- Table of Contents -->
      <section class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-(--rt-text) mb-8 text-center border-b border-(--rt-border) pb-4">Contents</h2>
        <div class="space-y-3">
          <div
            v-for="item in toc"
            :key="item.chapter"
            @click="$emit('goToChapter', item.chapter)"
            class="flex items-baseline gap-2 cursor-pointer group hover:bg-(--rt-surface-2) p-2 rounded-lg transition-all duration-200 -mx-2 hover:scale-[1.01] hover:shadow-sm border border-transparent hover:border-(--rt-border)"
            title="Click to read chapter"
          >
            <span class="font-bold text-(--rt-accent) w-6 text-right mr-2">{{ item.chapter }}</span>
            <span class="text-(--rt-text) font-medium group-hover:text-blue-500 transition-colors">{{ item.title }}</span>
            <div class="flex-1 border-b-2 border-dotted border-(--rt-border) opacity-50 mx-2 relative top-[-6px]"></div>
            <span class="font-mono text-sm text-(--rt-muted) bg-(--rt-surface-2) px-2 rounded group-hover:bg-blue-500/10 group-hover:text-blue-600 transition-colors">{{ item.page }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-transparent group-hover:text-blue-500 transition-colors ml-2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </div>
        </div>
      </section>

    </div>

    <!-- ================= PAGE 3 ================= -->
    <div v-else-if="currentPage === 3" class="space-y-8 pb-8 pt-4">
      <section class="max-w-4xl mx-auto bg-(--rt-surface) p-8 rounded-2xl shadow-sm border border-(--rt-border)">
        <h2 class="text-2xl font-bold text-(--rt-accent) mb-4 border-b border-(--rt-border) pb-3 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
          Preface
        </h2>
        <div class="prose prose-sm max-w-none text-(--rt-muted) leading-relaxed columns-1 md:columns-2 gap-8 text-justify">
          <p class="mb-4">As Adama City stands on the threshold of a transformation era in Ethiopian cities, this book serves as a comprehensive guide to envisioning and actualizing a smart city that is both innovative and inclusive. Achieving a smart city requires more than just incorporating innovative technologies; it also entails cultivating a well-balanced combination of sustainability, connectivity, and citizen involvement. We explore the many facets of smart city development in this book, offering a path that includes data-driven decision-making, digital infrastructure, and residents’ critical involvement in determining their urban environment. Three guiding principles improving everyone’s quality of life, guaranteeing effective resource management, and encouraging sustainable growth form the basis of Adama City’s smart city vision. With the use of big data, AI, and IoT technologies, we hope to establish an ecosystem that optimizes urban living through intelligent systems and creative solutions. Adapted to Adama City’s specific setting and goals, this book presents strategic initiatives and real-world examples from international smart city programs.</p>
          <p class="mb-4">The proactive participation of Adama’s residents in the smart city revolution is one of the main points addressed in this book. Residents’ involvement and engagement are essential to a smart city’s success because they add to the liveliness and sustainability of the city’s infrastructure. The chapters that follow will examine how community-driven initiatives and smart technologies may improve every aspect of urban living, from energy-efficient buildings and smart transportation to digital health care and e-governance.</p>
          <p>Legislators, corporate executives, urban planners, universities and Adama residents are all key stakeholders in this process. With the information and resources required to successfully traverse the difficulties of smart city development, this book aims to both inspire and instruct. By working together, we can create a future in which Adama City not only successfully navigates the difficulties posed by urbanization, but also becomes a model for exclusivity, creativity, and sustainability. Allow this book to serve as your travel companion and road-map as we set out on this thrilling and revolutionary path to a smarter Adama City.</p>
        </div>
      </section>

      <section class="max-w-4xl mx-auto bg-(--rt-surface) p-8 rounded-2xl shadow-sm border border-(--rt-border)">
        <h2 class="text-2xl font-bold text-(--rt-accent) mb-4 border-b border-(--rt-border) pb-3 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
          Acknowledgments
        </h2>
        <div class="prose prose-sm max-w-none text-(--rt-muted) leading-relaxed columns-1 md:columns-2 gap-8 text-justify">
          <p class="mb-4">We begin by expressing our deepest gratitude to the visionary leaders of Adama City, and in particular, to the current mayor, whose steadfast commitment to sustainability and innovation has been pivotal in bringing this ambitious project to fruition. The National Road Map of synergy has been a key driver of our initiatives, and we are truly appreciative of its role in shaping the direction of progress. We also extend our heartfelt thanks to the regional government officials, the public, and all key stakeholders for their valuable guidance and unwavering support throughout this journey.</p>
          <p class="mb-4">We are especially grateful to the head of Adama City’s Office of Science and Technology, as well as the skilled data analysts, IT specialists, and engineers from diverse disciplines. Their expertise and contributions have been indispensable in providing essential resources, technical assistance, and facilitation at every phase of the project. We would like to express our sincere gratitude to Mr. Jima Gobena for his invaluable contributions as both an advisor and a reviewer for this book. His expertise, insightful guidance, and thoughtful feedback have significantly shaped the quality and depth of the content, ensuring that it meets the highest standards. We also extend our heartfelt thanks to Mr. Bayisa Taye for his dedicated involvement in the review process. His contributions, marked by careful attention to detail and a commitment to excellence, have been instrumental in refining the manuscript.</p>
          <p class="mb-4">Moreover, extend our heartfelt gratitude to the local businesses, community organizations, academic institutions, research centers, and citizens of Adama who participated in surveys, focus groups, and public consultations. Their enthusiastic engagement and constructive feedback have been crucial in ensuring our plans are practical and aligned with community needs, helping shape our vision for an inclusive, sustainable, and responsive urban environment.</p>
          <p>Lastly, we deeply appreciate the publishing team of Harambe University for their dedication and expertise in bringing this book to fruition. Their diligent efforts, attention to detail, and professionalism have been vital throughout the publishing process. We hope this book inspires as Adama City strives to become a model smart city, reflecting the collaborative spirit and shared vision of everyone involved.</p>
        </div>
      </section>
    </div>

  </div>
</template>

<style scoped>
.signature-font {
  font-family: 'Brush Script MT', 'Dancing Script', 'Playfair Display', Georgia, cursive, serif;
}
</style>
