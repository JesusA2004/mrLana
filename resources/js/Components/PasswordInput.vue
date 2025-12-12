<script setup lang="ts">
/**
 * ======================================================
 * PasswordInput.vue
 * Input de contraseña reutilizable:
 * - Toggle mostrar / ocultar
 * - Compatible con v-model
 * - Sin lógica duplicada en Login/Register
 * ======================================================
 */

import { computed, ref } from 'vue'

/**
 * Props del componente
 */
const props = defineProps<{
  modelValue: string
  placeholder?: string
  autocomplete?: string
}>()

/**
 * Evento estándar para v-model
 */
const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

/**
 * Estado interno: mostrar u ocultar password
 */
const show = ref(false)

/**
 * Tipo dinámico del input
 */
const type = computed(() => (show.value ? 'text' : 'password'))

/**
 * Emite el valor al componente padre
 */
function onInput(e: Event) {
  emit('update:modelValue', (e.target as HTMLInputElement).value)
}
</script>

<template>
  <div class="relative flex items-center">
    <!-- Input principal -->
    <input
      :type="type"
      :value="modelValue"
      @input="onInput"
      required
      :autocomplete="autocomplete ?? 'current-password'"
      :placeholder="placeholder ?? '••••••••'"
      class="bg-slate-900 border border-slate-700 text-slate-100 text-sm rounded-lg
             focus:ring-indigo-500 focus:border-indigo-500
             block w-full px-3 py-2 pr-11 placeholder:text-slate-500"
    />

    <!-- Botón ojo -->
    <button
      type="button"
      class="absolute right-0 mr-3 text-slate-400 hover:text-slate-200 focus:outline-none"
      aria-label="Mostrar u ocultar contraseña"
      :aria-pressed="show ? 'true' : 'false'"
      @click="show = !show"
    >
      <!-- Ojo abierto -->
      <svg v-if="!show" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
           viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7
                 -1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>

      <!-- Ojo cerrado -->
      <svg v-else class="w-5 h-5" xmlns="http://www.w3.org/2000/svg"
           viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M3 3l18 18"/>
        <path d="M10.58 10.58A3 3 0 0012 15a3 3 0 002.42-4.42"/>
        <path d="M9.88 5.09A10.94 10.94 0 0112 5
                 c4.48 0 8.27 2.94 9.54 7"/>
      </svg>
    </button>
  </div>
</template>
