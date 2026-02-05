<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useLogin, type LoginData } from '@/Composables/useLogin'
import { ref, watch } from 'vue'
import { useLoginMotion } from '@/Composables/useLoginMotion'
import logoMr from '@/img/favicon-mr-lana-16.ico'

defineProps({
  canResetPassword: { type: Boolean, default: false },
  status: { type: String, default: '' },
})

const form = useForm<LoginData>({
  email: '',
  password: '',
  remember: false,
})

const { submit, errors, isSubmitting, canSubmit } = useLogin(form)

const cardRef = ref<HTMLElement | null>(null)
const { pulseOnError } = useLoginMotion()

watch(
  () => [errors.value.email, errors.value.password],
  ([e1, e2]) => {
    if ((e1 || e2) && cardRef.value) pulseOnError(cardRef.value)
  }
)
</script>

<template>
  <GuestLayout>
    <Head title="Inicio de sesión" />

    <div class="fixed inset-0 grid place-items-center px-4 bg-no-repeat bg-cover bg-center login-bg">
      <form
        @submit.prevent="submit()"
        class="relative w-full max-w-[22rem] sm:max-w-[24rem] md:max-w-md lg:max-w-md xl:max-w-lg 2xl:max-w-xl"
      >
        <div
          ref="cardRef"
          class="rounded-2xl p-8 shadow-2xl backdrop-blur bg-white/90 dark:bg-neutral-900/85"
        >
          <!-- Logo -->
          <div class="flex justify-center mb-6">
            <img :src="logoMr" class="h-9 w-9 drop-shadow" alt="Logo" />
          </div>

          <!-- Email -->
          <div class="mb-4">
            <InputLabel value="Correo electrónico" class="text-slate-700 dark:text-neutral-300" />

            <input
              id="email"
              name="email"
              v-model="form.email"
              type="email"
              placeholder="correo@empresa.com"
              autocomplete="username"
              inputmode="email"
              class="w-full mt-1 px-3 py-2 rounded-lg text-sm transition
                     bg-white text-slate-900 border border-slate-300
                     placeholder:text-slate-400
                     focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                     dark:bg-neutral-900 dark:text-slate-100
                     dark:border-neutral-700
                     dark:placeholder:text-neutral-400
                     dark:focus:ring-indigo-400 dark:focus:border-indigo-400"
            />

            <InputError :message="errors.email" />
          </div>

          <!-- Password -->
          <div class="mb-4">
            <InputLabel value="Contraseña" class="text-slate-700 dark:text-neutral-300" />

            <!-- Asegúrate que el botón del ojito dentro sea type="button" -->
            <PasswordInput
              v-model="form.password"
              id="password"
              name="password"
              autocomplete="current-password"
            />

            <InputError :message="errors.password" />
          </div>

          <div class="flex items-center justify-between mt-2">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-neutral-300 select-none">
              <input
                v-model="form.remember"
                type="checkbox"
                class="rounded border-slate-300 dark:border-neutral-700"
              />
              Recordarme
            </label>

            <Link
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-sm text-indigo-700 hover:underline dark:text-indigo-300
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
            >
              ¿Olvidaste tu contraseña?
            </Link>
          </div>

          <button
            type="submit"
            @click.prevent="submit()"
            :disabled="isSubmitting || !canSubmit"
            class="group w-full mt-6 py-2.5 rounded-lg font-medium transition
                   bg-indigo-600 text-white hover:bg-indigo-700
                   disabled:opacity-60 disabled:cursor-not-allowed
                   active:scale-[0.99]"
          >
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

<style scoped>
.login-bg { background-image: url('@/img/BgMovil.jpg'); }
@media (min-width: 640px) { .login-bg { background-image: url('@/img/bgC.jpg'); } }
@media (min-width: 1280px) { .login-bg { background-image: url('@/img/BgDesktop.jpg'); } }

/* loader simple */
.loader {
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: rgba(255, 255, 255, 0.95);
  display: inline-block;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
