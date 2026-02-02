<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useLogin, type LoginData } from '@/Composables/useLogin'
import { onMounted, ref } from 'vue'
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
const { submit, errors, isSubmitting } = useLogin(form)

/** Animaciones ligeras */
const cardRef = ref<HTMLElement | null>(null)
const { pulseOnError } = useLoginMotion()

onMounted(() => {
  if (Object.keys(errors.value ?? {}).length) {
    pulseOnError(cardRef.value)
  }
})
</script>

<template>
  <GuestLayout>
    <Head title="Inicio de sesión" />

    <div
      class="fixed inset-0 grid place-items-center px-4
             bg-no-repeat bg-cover bg-center login-bg"
    >
      <form
        @submit.prevent="submit"
        class="relative w-full
               max-w-[22rem] sm:max-w-[24rem] md:max-w-md lg:max-w-md
               xl:max-w-lg 2xl:max-w-xl"
      >
        <div
          ref="cardRef"
          class="rounded-2xl p-8 shadow-2xl
                 backdrop-blur bg-white/90 dark:bg-neutral-900/85"
        >
          <!-- Logo -->
          <div class="flex justify-center mb-6">
            <img :src="logoMr" class="h-9 w-9 drop-shadow" alt="Logo" />
          </div>

          <!-- Email -->
          <div class="mb-4">
            <InputLabel
              value="Correo electrónico"
              class="text-slate-700 dark:text-neutral-300"
            />

            <input
              v-model="form.email"
              type="email"
              placeholder="correo@empresa.com"
              autocomplete="username"
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
            <InputLabel
              value="Contraseña"
              class="text-slate-700 dark:text-neutral-300"
            />
            <PasswordInput v-model="form.password" />
            <InputError :message="errors.password" />
          </div>

          <!-- Restablecer contraseña -->
          <div class="flex justify-end text-sm">
            <Link
              v-if="canResetPassword"
              :href="route('password.request')"
              class="text-indigo-700 hover:underline dark:text-indigo-300
                     focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded"
            >
              ¿Olvidaste tu contraseña?
            </Link>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="group w-full mt-6 py-2.5 rounded-lg font-medium
                   transition bg-indigo-600 text-white hover:bg-indigo-700
                   disabled:opacity-60 disabled:cursor-not-allowed
                   active:scale-[0.99]"
          >
            <span
              v-if="!isSubmitting"
              class="inline-flex items-center justify-center gap-2"
            >
              Acceder
              <span class="opacity-0 group-hover:opacity-100 transition">
                →
              </span>
            </span>

            <span
              v-else
              class="inline-flex items-center justify-center gap-2"
            >
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
/* xs: móvil */
.login-bg {
  background-image: url('@/img/BgMovil.jpg');
}

/* sm, md, lg: cuadrada */
@media (min-width: 640px) {
  .login-bg {
    background-image: url('@/img/bgC.jpg');
  }
}

/* xl, 2xl: desktop */
@media (min-width: 1280px) {
  .login-bg {
    background-image: url('@/img/BgDesktop.jpg');
  }
}
</style>
