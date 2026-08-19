<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { authApi } from '@/api/auth'
import SaInput  from '@/components/ui/SaInput.vue'
import SaButton from '@/components/ui/SaButton.vue'

const auth   = useAuthStore()
const { themePreference, setTheme, initializeTheme, handleSystemThemeChange } = useTheme()
const router = useRouter()
const route  = useRoute()

// Modes and flows
type Mode = 'login' | 'register'
type Flow = 'phone' | 'email' | 'forgot_pin_phone' | 'forgot_pin_code' | 'forgot_pin_reset' | 'help_center'

const mode = ref<Mode>((route.query.mode as Mode) || 'login')
const flow = ref<Flow>('phone')

const form = reactive({
  name: '',
  phone: '',
  pin: '',
  pinConfirm: '',
  recoveryEmail: '',
  email: '',
  password: '',
  resetCode: '',
  signature: '',
})

const maskedEmail = ref('')
const recoveryError = ref('')
const recoveryLoading = ref(false)

// Function to handle OAuth redirects to Laravel backend
function loginWith(provider: 'google' | 'facebook' | 'apple') {
  window.location.href = `http://localhost:8000/api/v1/auth/${provider}/redirect`
}

async function submitAuth() {
  auth.clearErrors()
  try {
    if (mode.value === 'register') {
      if (flow.value === 'phone') {
        await auth.register({
          name: form.name,
          phone_number: form.phone,
          pin: form.pin,
          pin_confirmation: form.pinConfirm,
          email: form.recoveryEmail || undefined
        })
      } else {
        await auth.register({
          name: form.name,
          email: form.email,
          password: form.password
        })
      }
    } else {
      if (flow.value === 'phone') {
        await auth.login({ identifier: form.phone, credential: form.pin })
      } else {
        await auth.login({ identifier: form.email, credential: form.password })
      }
    }
    
    // Success - redirect to intended or dashboard
    const redirect = route.query.redirect as string || '/dashboard'
    router.push(redirect)
  } catch (e) {
    // Errors handled by auth store
  }
}

// PIN RECOVERY FLOW
async function submitVerifyPhone() {
  recoveryError.value = ''
  recoveryLoading.value = true
  try {
    const { data } = await authApi.verifyPhone(form.phone)
    if (data.fallback === 'help_center') {
      flow.value = 'help_center'
    } else if (data.masked_email) {
      maskedEmail.value = data.masked_email
      flow.value = 'forgot_pin_code'
    }
  } catch (e: any) {
    recoveryError.value = e.userMessage || 'Could not verify phone number.'
  } finally {
    recoveryLoading.value = false
  }
}

async function submitVerifyCode() {
  recoveryError.value = ''
  recoveryLoading.value = true
  try {
    const { data } = await authApi.verifyCode(form.phone, form.resetCode)
    form.signature = data.signature
    flow.value = 'forgot_pin_reset'
  } catch (e: any) {
    recoveryError.value = e.userMessage || 'Invalid reset code.'
  } finally {
    recoveryLoading.value = false
  }
}

async function submitResetPin() {
  recoveryError.value = ''
  recoveryLoading.value = true
  try {
    await authApi.resetPin({
      phone_number: form.phone,
      signature: form.signature,
      pin: form.pin,
      pin_confirmation: form.pinConfirm
    })
    // Reset successful, go back to login
    form.pin = ''
    form.pinConfirm = ''
    mode.value = 'login'
    flow.value = 'phone'
    auth.error = 'Your PIN has been reset successfully. You can now sign in with your new PIN.'
  } catch (e: any) {
    recoveryError.value = e.userMessage || 'Could not reset PIN.'
  } finally {
    recoveryLoading.value = false
  }
}

function toggleMode() {
  auth.clearErrors()
  mode.value = mode.value === 'login' ? 'register' : 'login'
  flow.value = 'phone'
}

function goBackToAuth() {
  flow.value = 'phone'
  recoveryError.value = ''
}

// Ensure non-numeric input is removed for PIN
function formatPin(e: Event, field: 'pin' | 'pinConfirm' | 'resetCode') {
  const target = e.target as HTMLInputElement
  form[field] = target.value.replace(/\D/g, '').slice(0, 6)
}

onMounted(() => {
  initializeTheme()
  if (route.query.redirect) {
    auth.error = 'You must sign in to access that page.'
  }
})
</script>

<template>
  <!-- Modal Overlay Background (Blur effect) -->
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
    
    <!-- Wider Card Container with darker blue #273C5B color -->
    <div class="w-full max-w-md rounded-2xl shadow-2xl p-5 sm:p-6 text-white relative border border-white/10" style="background-color: #273C5B;">
      
      <RouterLink to="/" class="absolute top-4 right-4 text-blue-200 hover:text-white transition-colors bg-white/10 rounded-full p-1" title="Close">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </RouterLink>

      <div class="flex flex-col items-center justify-center mb-6">
        <img src="/logo.png" alt="Smart Adama Logo" class="w-12 h-12 object-contain mb-2 drop-shadow-md" />
        <h1 class="text-xl font-bold tracking-tight text-white">
          <template v-if="flow === 'phone' || flow === 'email'">
            {{ mode === 'login' ? 'Welcome back' : 'Create an account' }}
          </template>
          <template v-else-if="flow === 'forgot_pin_phone' || flow === 'forgot_pin_code'">Reset your PIN</template>
          <template v-else-if="flow === 'forgot_pin_reset'">Create a new PIN</template>
          <template v-else-if="flow === 'help_center'">Account Recovery</template>
        </h1>
      </div>

      <!-- Global Auth Error (from Store) -->
      <div v-if="auth.error && flow !== 'forgot_pin_phone' && flow !== 'forgot_pin_code' && flow !== 'forgot_pin_reset'" class="mb-4 text-sm font-medium p-3 rounded-lg bg-emerald-50 text-emerald-700" :class="{ 'bg-red-50 text-red-600': !auth.error.includes('successfully') }" role="alert">
        {{ auth.error }}
      </div>

      <!-- RECOVERY ERROR -->
      <div v-if="recoveryError" class="mb-4 text-sm font-medium p-3 rounded-lg bg-red-50 text-red-600" role="alert">
        {{ recoveryError }}
      </div>

      <!-- ================= FORGOT PIN: PHONE ================= -->
      <form v-if="flow === 'forgot_pin_phone'" @submit.prevent="submitVerifyPhone" novalidate class="flex flex-col gap-4">
        <p class="text-sm text-blue-100 mb-2">Enter the phone number associated with your Smart Adama account.</p>
        <div>
          <label class="block text-sm font-semibold text-white mb-1.5">Phone number</label>
          <SaInput
            v-model="form.phone"
            type="tel"
            placeholder="+251 9XX XXX XXX"
            required
          />
        </div>
        <SaButton type="submit" :loading="recoveryLoading" class="w-full justify-center font-bold" style="background-color: #ffffff; color: #1e293b;">
          Continue
        </SaButton>
        <button type="button" @click="goBackToAuth" class="text-sm text-blue-200 hover:text-white font-medium transition-colors">← Back to sign in</button>
      </form>

      <!-- ================= FORGOT PIN: CODE ================= -->
      <form v-else-if="flow === 'forgot_pin_code'" @submit.prevent="submitVerifyCode" novalidate class="flex flex-col gap-4">
        <div class="text-sm text-blue-100 space-y-1 bg-black/10 p-3 rounded-lg mb-2">
          <p>We've sent a reset code to:</p>
          <p class="font-bold text-base text-white">{{ maskedEmail }}</p>
        </div>
        <div>
          <label class="block text-sm font-semibold text-white mb-1.5">Reset Code</label>
          <SaInput
            v-model="form.resetCode"
            type="text"
            placeholder="• • • • • •"
            maxlength="6"
            @input="(e: Event) => formatPin(e, 'resetCode')"
            required
            class="text-center font-mono tracking-[0.5em] text-lg"
          />
        </div>
        <SaButton type="submit" :loading="recoveryLoading" class="w-full justify-center font-bold" style="background-color: #ffffff; color: #1e293b;">
          Verify Code
        </SaButton>
        <button type="button" @click="flow = 'forgot_pin_phone'" class="text-sm text-blue-200 hover:text-white font-medium">← Back</button>
      </form>

      <!-- ================= FORGOT PIN: RESET ================= -->
      <form v-else-if="flow === 'forgot_pin_reset'" @submit.prevent="submitResetPin" novalidate class="flex flex-col gap-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-white mb-1.5">New PIN</label>
            <SaInput
              v-model="form.pin"
              type="password"
              placeholder="• • • • • •"
              maxlength="6"
              @input="(e: Event) => formatPin(e, 'pin')"
              required
              class="font-mono text-center tracking-[0.3em]"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-white mb-1.5">Confirm PIN</label>
            <SaInput
              v-model="form.pinConfirm"
              type="password"
              placeholder="• • • • • •"
              maxlength="6"
              @input="(e: Event) => formatPin(e, 'pinConfirm')"
              required
              class="font-mono text-center tracking-[0.3em]"
            />
          </div>
        </div>
        <SaButton type="submit" :loading="recoveryLoading" class="w-full justify-center font-bold mt-2" style="background-color: #ffffff; color: #1e293b;">
          Reset PIN
        </SaButton>
      </form>

      <form v-else-if="flow === 'help_center'" class="flex flex-col gap-4 text-center">
        <div class="w-12 h-12 mx-auto bg-white/10 rounded-full flex items-center justify-center text-xl">🛡️</div>
        <p class="text-white text-sm font-bold">Manual Reset Required</p>
        <p class="text-sm text-blue-100">You didn't add a recovery email to your account, so our Help Center will guide you through account recovery.</p>
        <a href="#" class="w-full inline-flex justify-center items-center px-4 py-2.5 font-bold rounded-lg shadow-sm" style="background-color: #ffffff; color: #1e293b;">
          Visit Help Center
        </a>
        <button type="button" @click="goBackToAuth" class="text-sm text-blue-200 hover:text-white font-medium transition-colors">← Back to sign in</button>
      </form>

      <!-- ================= MAIN AUTH FLOWS ================= -->
      <template v-else>
        <!-- PHONE FLOW -->
        <form v-if="flow === 'phone'" @submit.prevent="submitAuth" novalidate class="flex flex-col gap-4">
          
          <div v-if="mode === 'register'">
            <label class="block text-sm font-semibold text-white mb-1.5">Full name</label>
            <SaInput
              v-model="form.name"
              placeholder="Enter your full name"
              autocomplete="name"
              :error="auth.fieldErrors?.name"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-white mb-1.5">Phone number</label>
            <SaInput
              v-model="form.phone"
              type="tel"
              placeholder="+251 9XX XXX XXX"
              autocomplete="tel"
              :error="auth.fieldErrors?.phone_number || auth.fieldErrors?.identifier"
              required
            />
          </div>

          <!-- Side-by-side PINs for Register to save height, Full width for Login -->
          <div :class="mode === 'register' ? 'grid grid-cols-2 gap-3' : ''">
            <div>
              <label class="block text-sm font-semibold text-white mb-1.5">{{ mode === 'register' ? 'Create PIN' : '6-digit PIN' }}</label>
              <SaInput
                v-model="form.pin"
                type="password"
                placeholder="• • • • • •"
                maxlength="6"
                @input="(e: Event) => formatPin(e, 'pin')"
                autocomplete="current-password"
                :error="auth.fieldErrors?.pin || auth.fieldErrors?.credential"
                required
                class="font-mono"
              />
            </div>

            <div v-if="mode === 'register'">
              <label class="block text-sm font-semibold text-white mb-1.5">Confirm PIN</label>
              <SaInput
                v-model="form.pinConfirm"
                type="password"
                placeholder="• • • • • •"
                maxlength="6"
                @input="(e: Event) => formatPin(e, 'pinConfirm')"
                :error="auth.fieldErrors?.pin_confirmation"
                required
                class="font-mono"
              />
            </div>
          </div>

          <div v-if="mode === 'register'">
            <label class="block text-sm font-semibold text-white mb-1.5">Recovery email (Optional)</label>
            <SaInput
              v-model="form.recoveryEmail"
              type="email"
              placeholder="For PIN resets"
              :error="auth.fieldErrors?.email"
            />
          </div>

          <div v-if="mode === 'login'" class="flex items-center justify-end text-sm -mt-2">
            <button type="button" @click="flow = 'forgot_pin_phone'" class="text-blue-200 hover:text-white font-medium transition-colors">
              Forgot PIN?
            </button>
          </div>

          <SaButton type="submit" :loading="auth.loading" class="w-full justify-center font-bold text-lg rounded-xl mt-1" style="background-color: #ffffff; color: #1e293b;">
            {{ mode === 'register' ? 'Create Account' : 'Sign In' }}
          </SaButton>
        </form>

        <!-- EMAIL FLOW (ALTERNATIVE) -->
        <form v-else-if="flow === 'email'" @submit.prevent="submitAuth" novalidate class="flex flex-col gap-4">
          <div v-if="mode === 'register'">
            <label class="block text-sm font-semibold text-white mb-1.5">Full name</label>
            <SaInput
              v-model="form.name"
              placeholder="Enter your full name"
              autocomplete="name"
              :error="auth.fieldErrors?.name"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-white mb-1.5">Email</label>
            <SaInput
              v-model="form.email"
              type="email"
              placeholder="you@example.com"
              autocomplete="email"
              :error="auth.fieldErrors?.email || auth.fieldErrors?.identifier"
              required
            />
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white mb-1.5">Password</label>
            <SaInput
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              autocomplete="current-password"
              :error="auth.fieldErrors?.password || auth.fieldErrors?.credential"
              required
            />
          </div>

          <div v-if="mode === 'login'" class="flex items-center justify-end text-sm -mt-2">
            <button type="button" class="text-blue-200/50 cursor-not-allowed font-medium" title="Not available in alternative flow">
              Forgot password?
            </button>
          </div>

          <SaButton type="submit" :loading="auth.loading" class="w-full justify-center font-bold text-lg rounded-xl mt-1" style="background-color: #ffffff; color: #1e293b;">
            {{ mode === 'register' ? 'Register' : 'Sign In' }}
          </SaButton>
        </form>

        <!-- DIVIDER -->
        <div class="relative flex items-center py-4">
          <div class="flex-grow border-t border-white/20"></div>
          <span class="flex-shrink-0 mx-4 text-xs font-semibold uppercase tracking-wider text-white/70">OR</span>
          <div class="flex-grow border-t border-white/20"></div>
        </div>
        
        <!-- ALTERNATIVE AUTH BUTTONS -->
        <div class="flex flex-col gap-2">
          <!-- Main alternatives inline to save space -->
          <div class="grid grid-cols-2 gap-2 mb-1">
            <button v-if="flow === 'phone'" @click="flow = 'email'" type="button" class="w-full flex items-center justify-center gap-2 bg-white/10 border border-white/20 text-xs font-semibold text-white px-2 py-2.5 rounded-lg hover:bg-white/20 transition-colors">
              📧 Email Login
            </button>
            <button v-if="flow === 'email'" @click="flow = 'phone'" type="button" class="w-full flex items-center justify-center gap-2 bg-black/20 border border-black/30 text-xs font-semibold text-white px-2 py-2.5 rounded-lg hover:bg-black/40 transition-colors">
              📱 Phone Login
            </button>

            <!-- Socials bundled into the grid -->
            <div class="flex justify-center items-center gap-2">
              <button @click="loginWith('google')" type="button" title="Google" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-full hover:bg-gray-100 transition-colors shadow-sm active:scale-95">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                  <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                  <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
              </button>
              
              <button @click="loginWith('facebook')" type="button" title="Facebook" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-full hover:bg-gray-100 transition-colors shadow-sm active:scale-95">
                <svg class="w-5 h-5 text-[#1877F2]" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </button>

              <button @click="loginWith('apple')" type="button" title="Apple" class="w-10 h-10 flex items-center justify-center bg-white border border-gray-200 rounded-full hover:bg-gray-100 transition-colors shadow-sm active:scale-95">
                <svg class="w-5 h-5 text-black" fill="currentColor" viewBox="0 0 384 512">
                  <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Toggle between Login and Register -->
      <p v-if="flow === 'phone' || flow === 'email'" class="mt-6 text-center text-sm text-blue-100">
        <template v-if="mode === 'login'">
          New user?
          <button @click="toggleMode" type="button" class="font-bold text-white hover:underline ml-1">Sign up here</button>
        </template>
        <template v-else>
          Already have an account?
          <button @click="toggleMode" type="button" class="font-bold text-white hover:underline ml-1">Sign in here</button>
        </template>
      </p>
    </div>
  </div>
</template>