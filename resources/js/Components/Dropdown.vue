<script setup lang="ts">
/**
 * Dropdown.vue
 * ------------------------------------------------------
 * Contenedor de dropdown “theme-aware”:
 * - Neutro (sin azules)
 * - Dark suave (sin negro puro)
 * - Animación de entrada/salida
 * - Evita doble background: ESTE componente es el único que pinta el panel.
 */

import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  align?: 'left' | 'right'
  width?: string | number
}>()

const open = ref(false)

const closeOnEscape = (e: KeyboardEvent) => {
  if (open.value && e.key === 'Escape') open.value = false
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape))

const alignmentClasses = () => {
  if (props.align === 'left') return 'origin-top-left left-0'
  return 'origin-top-right right-0'
}

const widthClass = () => {
  const w = String(props.width ?? '56')
  // Si pasas "56" => w-56. Si pasas "20rem" => style inline abajo no aplica; aquí usamos w-56 default.
  return `w-${w}`
}
</script>

<template>
  <div class="relative">
    <!-- Trigger -->
    <div @click="open = !open">
      <slot name="trigger" />
    </div>

    <!-- Backdrop click -->
    <div
      v-show="open"
      class="fixed inset-0 z-40"
      @click="open = false"
    />

    <!-- Panel -->
    <Transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 translate-y-1 scale-[0.98]"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition ease-in duration-120"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-1 scale-[0.98]"
    >
      <div
        v-show="open"
        class="absolute z-50 mt-2 rounded-2xl border shadow-xl backdrop-blur
               border-slate-200/80 bg-white/95
               dark:border-zinc-700/70 dark:bg-zinc-900/70"
        :class="[alignmentClasses(), widthClass()]"
      >
        <!-- Inner padding (sin colores aquí) -->
        <div class="py-1">
          <slot name="content" />
        </div>
      </div>
    </Transition>
  </div>
</template>
