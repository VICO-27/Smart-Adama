import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import { merge } from 'lodash-es'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

const en = {
  chat: {
    err_load_chapter: "Unable to load chapter content",
    err_load_course: "Unable to load course materials",
    select_a_chapter: "Select a chapter",
    loading_quiz: "Loading Quiz..."
  }
}

const am = {
  chat: {
    err_load_chapter: "የምዕራፍ ይዘት መጫን አልተቻለም",
    err_load_course: "የኮርስ ቁሳቁሶችን መጫን አልተቻለም",
    select_a_chapter: "ምዕራፍ ይምረጡ",
    loading_quiz: "ፈተናን በመጫን ላይ..."
  }
}

const om = {
  chat: {
    err_load_chapter: "Qabiyyee boqonnaa fe'uun hin danda'amne",
    err_load_course: "Meeshaalee koorasii fe'uun hin danda'amne",
    select_a_chapter: "Boqonnaa filadhu",
    loading_quiz: "Qormaata fe'uutti jira..."
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
