<script setup>
/**
 * UpdateProfileInformationForm.vue (Read-only)
 * ------------------------------------------------------------
 * - Muestra nombre y correo sin permitir edición
 * - Se ve como input deshabilitado (premium), pero es un <div>
 * - Modo oscuro neutro (zinc/slate), sin negros puros ni azules
 */

import InputLabel from '@/Components/InputLabel.vue'
import { usePage } from '@inertiajs/vue3'

const user = usePage().props.auth.user
</script>

<template>
  <div class="space-y-6">
    <!-- Nombre -->
    <div>
      <InputLabel value="Nombre" />
      <div
        class="mt-1 w-full rounded-lg border px-3 py-2 text-sm shadow-sm
               cursor-not-allowed select-text
               border-slate-200 bg-slate-50 text-slate-800
               dark:border-zinc-800/70 dark:bg-zinc-900/40 dark:text-zinc-100"
      >
        {{ user.name }}
      </div>
    </div>

    <!-- Correo -->
    <div>
      <InputLabel value="Correo electrónico" />
      <div
        class="mt-1 w-full rounded-lg border px-3 py-2 text-sm shadow-sm
               cursor-not-allowed select-text
               border-slate-200 bg-slate-50 text-slate-800
               dark:border-zinc-800/70 dark:bg-zinc-900/40 dark:text-zinc-100"
      >
        {{ user.email }}
      </div>
    </div>
  </div>
</template>
