import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import { merge } from 'lodash-es'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

const en = {
  dashboard: {
    reading_complete: "Reading complete",
    reading_progress: "Reading {pct}%",
    no_quiz_attempts: "No quiz attempts",
    resume_reading: "Resume reading.",
    complete: "complete",
    overall_progress: "overall progress",
    next_chapter: "Next: Chapter",
    review_learning: "Review learning",
    continue_reading: "Continue reading",
    course_complete: "Course complete",
    start_learning: "Start Learning"
  },
  chat: {
    try_again: "Try Again",
    reload_course: "Reload Course",
    open_first: "Open First Chapter",
    conn_error: "Could not connect to the course server. Please check your connection and tap below to retry.",
    select_chapter: "Please select a chapter from the course menu to start reading.",
    complete_to_unlock: "Complete this chapter to unlock its quiz.",
    take_quiz: "Take Quiz",
    mind_map: "Mind Map",
    new_conversation: "New Conversation",
    active_reader: "Active Reader"
  },
  tour: {
    home: {
      step1: { title: "Welcome to Smart Adama.", content: "Your digital gateway to a smarter city — and a smarter you." },
      step2: { title: "Everything lives here.", content: "Home, Study, AI, Progress, Profile. Navigate your journey from this bar." },
      step3: { title: "The Smart Adama Book", content: "Read, learn, and track your progress automatically." },
      step4: { title: "Ask anything.", content: "Smart Adama AI explains, summarizes, and answers — grounded in the book." },
      step5: { title: "Watch your knowledge compound.", content: "Quizzes, streaks, and achievements keep you moving forward." }
    },
    study: {
      step1: { title: "This is the Smart Adama Book.", content: "Your progress is tracked automatically as you read." },
      step2: { title: "Browse chapters here.", content: "Pick one to begin." },
      step3: { title: "Read at your pace.", content: "We'll remember exactly where you left off." },
      step4: { title: "Stuck on something?", content: "Study AI is right here." },
      step5: { title: "Study AI in action.", content: "Try: \"Explain this simply,\" \"Summarize this chapter,\" or \"Give me a real-world example.\"" },
      step6: { title: "Test yourself.", content: "Finish a chapter, then test yourself. Every quiz builds your progress." }
    },
    controls: {
      skip: "Skip",
      back: "Back",
      next: "Next",
      finish: "Finish"
    }
  }
}

const am = {
  dashboard: {
    reading_complete: "ማንበብ ተጠናቋል",
    reading_progress: "ንባብ {pct}%",
    no_quiz_attempts: "ምንም የፈተና ሙከራዎች የሉም",
    resume_reading: "ማንበብ ይቀጥሉ.",
    complete: "ተጠናቋል",
    overall_progress: "አጠቃላይ ሂደት",
    next_chapter: "ቀጣይ: ምዕራፍ",
    review_learning: "ትምህርቱን ይከልሱ",
    continue_reading: "ማንበብ ይቀጥሉ",
    course_complete: "ኮርስ ተጠናቋል",
    start_learning: "መማር ይጀምሩ"
  },
  chat: {
    try_again: "እንደገና ይሞክሩ",
    reload_course: "ኮርሱን እንደገና ይጫኑ",
    open_first: "የመጀመሪያውን ምዕራፍ ይክፈቱ",
    conn_error: "ከኮርስ አገልጋዩ ጋር መገናኘት አልተቻለም። እባክዎ ግንኙነትዎን ያረጋግጡ እና እንደገና ለመሞከር ከታች ይጫኑ።",
    select_chapter: "እባክዎ ማንበብ ለመጀመር ከኮርሱ ምናሌ ውስጥ አንድ ምዕራፍ ይምረጡ።",
    complete_to_unlock: "የዚህን ምዕራፍ ፈተና ለመክፈት ምዕራፉን ያጠናቅቁ።",
    take_quiz: "ፈተና ይውሰዱ",
    mind_map: "የአእምሮ ካርታ",
    new_conversation: "አዲስ ውይይት",
    active_reader: "ንቁ አንባቢ"
  },
  tour: {
    home: {
      step1: { title: "እንኳን ወደ ስማርት አዳማ በደህና መጡ።", content: "ወደ ዘመናዊ ከተማ እና ብልህነትዎ ዲጂታል መግቢያ።" },
      step2: { title: "ሁሉም ነገር እዚህ አለ።", content: "መነሻ፣ ጥናት፣ AI፣ ሂደት፣ መገለጫ። ጉዞዎን ከዚህ ጀምረው ያስሱ።" },
      step3: { title: "ስማርት አዳማ መጽሐፍ", content: "ያንብቡ፣ ይማሩ፣ እና ሂደትዎን በራስ-ሰር ይከታተሉ።" },
      step4: { title: "ማንኛውንም ጥያቄ ይጠይቁ።", content: "ስማርት አዳማ AI ያብራራል፣ ያጠቃልላል፣ እና ይመልሳል — በመጽሐፉ ላይ ተመስርቶ።" },
      step5: { title: "እውቀትዎን ሲያድግ ይመልከቱ።", content: "ፈተናዎች እና ስኬቶች ወደፊት እንዲጓዙ ያግዙዎታል።" }
    },
    study: {
      step1: { title: "ይህ የስማርት አዳማ መጽሐፍ ነው።", content: "እያነበቡ ሲሄዱ ሂደትዎ በራስ-ሰር ይከታተላል።" },
      step2: { title: "ምዕራፎችን እዚህ ያስሱ።", content: "ለመጀመር አንዱን ይምረጡ።" },
      step3: { title: "በራስዎ ፍጥነት ያንብቡ።", content: "በትክክል ያቆሙበትን እናስታውሳለን።" },
      step4: { title: "አንድ ነገር ላይ ተጣብቀዋል?", content: "የጥናት AI እዚሁ አለ።" },
      step5: { title: "የጥናት AI በተግባር ላይ።", content: "ይሞክሩ፡ \"ይህን በቀላል አብራራ፣\" \"ይህን ምዕራፍ አጠቃልል፣\" ወይም \"እውነተኛ ዓለም ምሳሌ ስጠኝ።\"" },
      step6: { title: "እራስዎን ይፈትኑ።", content: "አንድን ምዕራፍ ያጠናቅቁ፣ ከዚያ እራስዎን ይፈትኑ። እያንዳንዱ ፈተና ሂደትዎን ይገነባል።" }
    },
    controls: {
      skip: "ዝለል",
      back: "ወደ ኋላ",
      next: "ቀጣይ",
      finish: "ጨርስ"
    }
  }
}

const om = {
  dashboard: {
    reading_complete: "Dubbisuun xumurameera",
    reading_progress: "Dubbisuu {pct}%",
    no_quiz_attempts: "Qormaata hin yaalamne",
    resume_reading: "Dubbisuu itti fufi.",
    complete: "xumurameera",
    overall_progress: "guddina waliigalaa",
    next_chapter: "Itti aana: Boqonnaa",
    review_learning: "Barumsa irra deebi'i",
    continue_reading: "Dubbisuu itti fufi",
    course_complete: "Koorasiin xumurameera",
    start_learning: "Barachuu Eegali"
  },
  chat: {
    try_again: "Irra deebi'ii yaali",
    reload_course: "Koorasii irra deebi'ii fe'i",
    open_first: "Boqonnaa jalqabaa bani",
    conn_error: "Sarvaraa koorasii waliin wal qunnamsiisuun hin danda'amne. Maaloo toora kee mirkaneeffadhuutii irra deebi'uuf gadi tuqi.",
    select_chapter: "Maaloo dubbisuu eegaluuf baafata koorasii irraa boqonnaa tokko filadhu.",
    complete_to_unlock: "Qormaata isaa banuuf boqonnaa kana xumuri.",
    take_quiz: "Qormaata Fudhadhu",
    mind_map: "Kaartaa Sammuu",
    new_conversation: "Haasawaa Haaraa",
    active_reader: "Dubbisaa Si'aawaa"
  },
  tour: {
    home: {
      step1: { title: "Baga nagaan gara Smart Adama dhuftan.", content: "Karra dijitaalaa gara magaalaa qaroomaa — fi qarooma keetii." },
      step2: { title: "Hundinuu asuma jira.", content: "Gadaa, Qorannoo, AI, Guddina, Profaayilii. Daandii kee asirraa sakatta'i." },
      step3: { title: "Kitaaba Smart Adama", content: "Dubbisi, baradhu, fi guddina kee of-umaan hordofi." },
      step4: { title: "Waan feete gaafadhu.", content: "Smart Adama AI ni ibsa, ni gabaabsa, ni deebisa — kitaabicha irratti hundaa'uun." },
      step5: { title: "Beekumsa kee guddachaa deemu ilaali.", content: "Qormaatawwan fi milkaayinni akka fuulduratti tarkaanfattu si gargaaru." }
    },
    study: {
      step1: { title: "Kun Kitaaba Smart Adama ti.", content: "Guddinni kee yeroo dubbisu of-umaan ni hordofama." },
      step2: { title: "Boqonnaawwan asitti sakatta'i.", content: "Jalqabuuf tokko filadhu." },
      step3: { title: "Saffisa keetiin dubbisi.", content: "Iddoo sirriitti itti dhaabde ni yaadanna." },
      step4: { title: "Waan tokko irratti rakkattee?", content: "Qorannoon AI asuma jira." },
      step5: { title: "Qorannoon AI hojii irratti.", content: "Yaali: \"Kana salphaatti ibsi,\" \"Boqonnaa kana gabaabsi,\" ykn \"Fakkeenya addunyaa dhugaa naaf kenni.\"" },
      step6: { title: "Of qori.", content: "Boqonnaa tokko xumuri, sana booda of qori. Qormaanni hundi guddina kee ijaara." }
    },
    controls: {
      skip: "Dabarsi",
      back: "Duuba",
      next: "Itti Aanu",
      finish: "Xumuri"
    }
  }
}

const langs = [
  { name: 'en.json', data: en },
  { name: 'am.json', data: am },
  { name: 'om.json', data: om }
]

langs.forEach(lang => {
  const filePath = path.join(__dirname, 'src/lang', lang.name)
  const current = JSON.parse(fs.readFileSync(filePath, 'utf-8'))
  const merged = merge(current, lang.data)
  fs.writeFileSync(filePath, JSON.stringify(merged, null, 2))
  console.log(`Updated ${lang.name}`)
})
