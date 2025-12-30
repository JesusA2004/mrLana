<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Link } from '@inertiajs/vue3'

/**
 * variant:
 * - 'md'   -> default Breeze (max-w-md)   (login, forgot, etc.)
 * - 'wide' -> max-w-6xl (reset password avanzado / layouts más grandes)
 * - 'full' -> sin max width (pantallas especiales)
 */
const props = defineProps({
  variant: {
    type: String,
    default: 'md',
    validator: (v) => ['md', 'wide', 'full'].includes(v),
  },
  containerClass: { type: String, default: '' },
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-zinc-950">
    <div class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 px-4">
      <!-- Logo slot / default -->
      <div class="select-none">
        <slot name="logo">
          <Link href="/" class="inline-flex items-center justify-center">
            <ApplicationLogo class="h-20 w-20 fill-current text-gray-500 dark:text-zinc-300" />
          </Link>
        </slot>
      </div>

      <!-- Contenedor del contenido -->
      <main
        :class="[
          'mt-6 w-full overflow-hidden bg-white shadow-md sm:rounded-2xl dark:bg-zinc-900/40 dark:border dark:border-zinc-800/70',
          variant === 'md' ? 'sm:max-w-md px-6 py-4' : '',
          variant === 'wide' ? 'sm:max-w-6xl px-4 sm:px-6 lg:px-10 py-6' : '',
          variant === 'full' ? 'max-w-none px-0 py-0 bg-transparent shadow-none border-0' : '',
          containerClass,
        ]"
      >
        <slot />
      </main>
    </div>
  </div>
</template>
