<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import PageHeader from '@/components/admin/PageHeader.vue'
import { Mail, Key, Shield, User, Camera } from 'lucide-vue-next'

const auth = useAuthStore()

const form = ref({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const isSaving = ref(false)

const handleSave = () => {
  isSaving.value = true
  setTimeout(() => {
    isSaving.value = false
    alert('Profile saved successfully (Mock API)')
  }, 800)
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader 
      title="Admin Profile" 
      description="Manage your personal administrator account settings and credentials."
    />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Left col: Avatar & Role -->
      <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm flex flex-col items-center text-center">
          <div class="relative group cursor-pointer mb-4">
            <img 
              :src="auth.user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin&background=F0F3FA&color=395886'" 
              alt="Avatar" 
              class="h-24 w-24 rounded-full ring-4 ring-slate-50 object-cover"
            />
            <div class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <Camera class="h-6 w-6 text-white" />
            </div>
          </div>
          <h2 class="text-xl font-bold text-slate-900">{{ auth.user?.name || 'Administrator' }}</h2>
          <p class="text-sm text-slate-500 mt-1">{{ auth.user?.email || 'admin@smartadama.gov.et' }}</p>
          
          <div class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-smart-blue-50 px-3 py-1 text-xs font-semibold text-smart-blue-700 ring-1 ring-inset ring-smart-blue-700/10">
            <Shield class="h-3.5 w-3.5" />
            Platform Admin
          </div>
        </div>
      </div>

      <!-- Right col: Form -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="border-b border-slate-200 px-6 py-4 bg-slate-50 flex items-center gap-2">
            <User class="h-4 w-4 text-slate-500" />
            <h3 class="font-semibold text-slate-900">Personal Information</h3>
          </div>
          
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Full Name</label>
                <input type="text" v-model="form.name" class="block w-full rounded-lg border-slate-300 py-2 shadow-sm focus:border-smart-blue-500 focus:ring-smart-blue-500 sm:text-sm" />
              </div>
              <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Email Address</label>
                <div class="relative">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <Mail class="h-4 w-4 text-slate-400" />
                  </div>
                  <input type="email" v-model="form.email" class="block w-full rounded-lg border-slate-300 py-2 pl-10 shadow-sm focus:border-smart-blue-500 focus:ring-smart-blue-500 sm:text-sm" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="border-b border-slate-200 px-6 py-4 bg-slate-50 flex items-center gap-2">
            <Key class="h-4 w-4 text-slate-500" />
            <h3 class="font-semibold text-slate-900">Security</h3>
          </div>
          
          <div class="p-6 space-y-4">
            <div class="space-y-1 max-w-md">
              <label class="block text-sm font-medium text-slate-700">Current Password</label>
              <input type="password" v-model="form.currentPassword" class="block w-full rounded-lg border-slate-300 py-2 shadow-sm focus:border-smart-blue-500 focus:ring-smart-blue-500 sm:text-sm" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
              <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">New Password</label>
                <input type="password" v-model="form.newPassword" class="block w-full rounded-lg border-slate-300 py-2 shadow-sm focus:border-smart-blue-500 focus:ring-smart-blue-500 sm:text-sm" />
              </div>
              <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <input type="password" v-model="form.confirmPassword" class="block w-full rounded-lg border-slate-300 py-2 shadow-sm focus:border-smart-blue-500 focus:ring-smart-blue-500 sm:text-sm" />
              </div>
            </div>
          </div>
          
          <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end">
            <button 
              @click="handleSave"
              :disabled="isSaving"
              class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 disabled:opacity-50 transition-colors"
            >
              {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
