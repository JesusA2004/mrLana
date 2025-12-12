<script setup lang="ts">
/**
 * Sidebar.vue (partial)
 * ------------------------------------------------------
 * Sidebar colapsable (hover):
 * - open=true al entrar, open=false al salir
 * - Items base (ajusta rutas cuando las tengas)
 * - Preparado para iconos PNG con dark:invert
 */

import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'

const props = defineProps<{
  current?: string // opcional, para marcar activo si lo necesitas
}>()

const open = ref(false)
</script>

<template>
  <aside class="group flex h-screen flex-col justify-between border-r bg-slate-50 text-slate-800
                border-slate-200 dark:bg-neutral-900 dark:text-white dark:border-black
                sticky top-0 z-30 transition-[width] duration-300 ease-out transition-colors"
         :class="open ? 'w-52' : 'w-16'"
         @mouseenter="open = true" @mouseleave="open = false">

    <div>
      <!-- Logo -->
      <div class="flex items-center justify-center h-16 border-b px-2
                  border-slate-200 dark:border-slate-800">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-slate-100
                    dark:bg-slate-100 dark:text-slate-900 shadow-md">
          <ApplicationLogo class="w-7 h-7" />
        </div>

        <span v-show="open" class="ml-3 text-sm font-semibold tracking-wide">
          MR-LanaERP
        </span>
      </div>

      <!-- Menu -->
      <nav class="mt-4 px-2 space-y-1">
        <Link :href="route('dashboard')"
              class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200"
              :class="route().current('dashboard')
                ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-white dark:hover:bg-slate-800 dark:hover:text-indigo-400'">
          <svg xmlns="http://www.w3.org/2000/svg"
               class="size-5 opacity-90 transition-colors text-black dark:text-slate-300 dark:group-hover:text-indigo-400"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l9-9 9 9M4 10.5V21h6v-6h4v6h6V10.5" />
          </svg>
          <span v-show="open" class="ml-3">Dashboard</span>
        </Link>

        <a href="#"
           class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-white dark:hover:bg-slate-800 dark:hover:text-indigo-400">
          <img src="/img/usersIcon.png" class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Empleados</span>
        </a>

        <a href="#"
           class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-white dark:hover:bg-slate-800 dark:hover:text-indigo-400">
          <img src="/img/solicitudIcon.png" class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Solicitudes</span>
        </a>

        <a href="#"
           class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-white dark:hover:bg-slate-800 dark:hover:text-indigo-400">
          <img src="/img/inversionIcon.png" class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Inversiones</span>
        </a>

        <a href="#"
           class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-white dark:hover:bg-slate-800 dark:hover:text-indigo-400">
          <img src="/img/reportesIcon.png" class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Reportes</span>
        </a>

        <a href="#"
           class="group flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors duration-200
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-white dark:hover:bg-slate-800 dark:hover:text-indigo-400">
          <img src="/img/bdIcon.png" class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Base de datos</span>
        </a>
      </nav>
    </div>

    <!-- Logout -->
    <div class="border-t p-2 border-slate-200 dark:border-slate-800">
      <Link :href="route('logout')" method="post" as="button"
            class="group flex items-center w-full rounded-md px-3 py-2 text-sm transition-colors duration-200
                   text-slate-500 hover:bg-red-50 hover:text-red-600 dark:text-slate-500 dark:hover:bg-red-900/20 dark:hover:text-red-300">
        <svg xmlns="http://www.w3.org/2000/svg"
             class="size-5 opacity-90 transition-colors text-slate-500 group-hover:text-red-500
                    dark:text-slate-400 dark:group-hover:text-red-300"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M17 16l4-4m0 0-4-4m4 4H9m4 4v1a3 3 0 01-3 3H7a3 3 0 01-3-3V7a3 3 0 013-3h3a3 3 0 013 3v1"/>
        </svg>
        <span v-show="open" class="ml-3">Cerrar sesión</span>
      </Link>
    </div>
  </aside>
</template>
