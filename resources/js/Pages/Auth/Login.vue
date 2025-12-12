<script setup lang="ts">
/**
 * ======================================================
 * Login.vue
 * Vista del login (UI only)
 * ======================================================
 */
import Checkbox from '@/Components/Checkbox.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useLogin, type LoginData } from '@/Composables/useLogin'
import { onMounted, ref } from 'vue'
import { useLoginMotion } from '@/Composables/useLoginMotion'

defineProps({
  canResetPassword: { type: Boolean, default: false },
  status: { type: String, default: '' },
})

const form = useForm<LoginData>({ email: '', password: '', remember: false })
const { submit, errors, isSubmitting } = useLogin(form)

/** Animaciones (JS ligero) */
const cardRef = ref<HTMLElement | null>(null)       // tilt
const shakeRef = ref<HTMLElement | null>(null)      // shake
const { mount, pulseOnError } = useLoginMotion()

/** Si hay errores, “shake” sutil (no payaso, UX pro) */
onMounted(() => {
  if (Object.keys(errors.value ?? {}).length) pulseOnError(cardRef.value)
})
</script>

<template>
  <GuestLayout>
    <Head title="Inicio de sesión" />

    <div class="fixed inset-0 grid place-items-center bg-cover bg-center px-4"
         style="background-image: url('/img/background-mrlana.webp');">
      <!-- Overlay para legibilidad (light/dark) -->
      <div class="absolute inset-0 bg-black/35 dark:bg-black/55"></div>

      <form @submit.prevent="submit" class="relative w-full max-w-md login-wrap">
        <div ref="cardRef"
             class="login-card bg-white/90 dark:bg-slate-900/80 border border-gray-200 dark:border-slate-700/80
                    rounded-2xl p-8 shadow-2xl backdrop-blur">
          <div v-if="status" class="mb-4 text-sm font-medium text-emerald-700 dark:text-emerald-400 text-center">
            {{ status }}
          </div>

          <div class="flex justify-center mb-6">
            <img src="/img/favicon-mr-lana-16.ico" class="h-9 w-9 drop-shadow" />
          </div>

          <div class="mb-4">
            <InputLabel value="Correo electrónico" />
            <input v-model="form.email" type="email" placeholder="correo@empresa.com" autocomplete="username"
                   class="w-full mt-1 px-3 py-2 rounded-lg border bg-white text-gray-900 border-gray-300
                          focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-slate-900 dark:text-gray-100 dark:border-slate-700" />
            <InputError :message="errors.email" />
          </div>

          <div class="mb-4">
            <InputLabel value="Contraseña" />
            <PasswordInput v-model="form.password" />
            <InputError :message="errors.password" />
          </div>

          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-gray-700 dark:text-gray-300 select-none">
              <Checkbox v-model:checked="form.remember" />
              Recordarme
            </label>

            <Link v-if="canResetPassword" :href="route('password.request')"
                  class="text-indigo-700 hover:underline dark:text-indigo-300 focus:outline-none focus:ring-2
                         focus:ring-indigo-500 rounded">
              ¿Olvidaste tu contraseña?
            </Link>
          </div>

          <button type="submit" :disabled="isSubmitting"
                  class="group w-full mt-6 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed
                         text-white py-2.5 rounded-lg font-medium transition active:scale-[0.99]">
            <span v-if="!isSubmitting" class="inline-flex items-center justify-center gap-2">
              Acceder
              <span class="opacity-0 group-hover:opacity-100 transition">→</span>
            </span>

            <span v-else class="inline-flex items-center justify-center gap-2">
              <span class="loader"></span>
              Accediendo...
            </span>
          </button>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>

<style scoped src="@/../css/login.css"></style>

