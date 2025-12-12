<script setup lang="ts">
/**
 * Sidebar.vue (partial)
 * ------------------------------------------------------
 * Sidebar colapsable (hover)
 * - Iconos PNG cargados vía Vite (@/img)
 * - Confirmación SweetAlert2 antes de cerrar sesión
 * - Dark mode neutro (sin negro puro, sin azules chillones)
 */

import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Swal from 'sweetalert2'

// ICONOS (Vite assets)
import usersIcon from '@/img/usersIcon.png'
import solicitudIcon from '@/img/solicitudIcon.png'
import inversionIcon from '@/img/inversionIcon.png'
import reportesIcon from '@/img/reportesIcon.png'
import bdIcon from '@/img/bdIcon.png'

defineProps<{
  current?: string
}>()

/**
 * Control de apertura por hover
 */
const open = ref(false)

/**
 * Helper: detecta si el tema actual es dark (clase global en <html>)
 */
const isDarkTheme = () => document.documentElement.classList.contains('dark')

/**
 * Confirmación ejecutiva para logout
 * - Intercepta click y si confirma ejecuta POST logout con Inertia router
 */
const confirmLogout = async () => {
  const dark = isDarkTheme()

  const result = await Swal.fire({
    title: '¿Cerrar sesión?',
    text: 'Perderás acceso hasta volver a iniciar sesión.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, cerrar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    // UI neutra, suave
    background: dark ? '#18181b' : '#ffffff', // zinc-900 / white
    color: dark ? '#e4e4e7' : '#111827',      // zinc-200 / gray-900
    confirmButtonColor: '#ef4444',            // rojo (acción crítica)
    cancelButtonColor: '#52525b',             // zinc-600
  })

  if (!result.isConfirmed) return

  router.post(route('logout'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Sesión cerrada',
        text: 'Has salido del sistema correctamente.',
        timer: 1800,
        showConfirmButton: false,
        background: dark ? '#18181b' : '#ffffff',
        color: dark ? '#e4e4e7' : '#111827',
        iconColor: '#22c55e',
      })
    },
  })
}
</script>

<template>
  <aside
    class="group flex h-screen flex-col justify-between border-r sticky top-0 z-30
           transition-[width] duration-300 ease-out transition-colors
           bg-slate-50 text-slate-800 border-slate-200
           dark:bg-zinc-950/60 dark:text-zinc-100 dark:border-zinc-800/70"
    :class="open ? 'w-52' : 'w-16'"
    @mouseenter="open = true"
    @mouseleave="open = false"
  >
    <div>
      <!-- Logo -->
      <div class="flex items-center justify-center h-16 border-b px-2
                  border-slate-200 dark:border-zinc-800/70">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl shadow-md
                 bg-slate-900 text-slate-100
                 dark:bg-zinc-100 dark:text-zinc-900
                 transition-transform duration-200 group-hover:scale-[1.03]"
        >
          <ApplicationLogo class="w-7 h-7" />
        </div>

        <span
          v-show="open"
          class="ml-3 text-sm font-semibold tracking-wide
                 text-slate-800 dark:text-zinc-100
                 transition-opacity duration-200"
        >
          MR-LanaERP
        </span>
      </div>

      <!-- Menu -->
      <nav class="mt-4 px-2 space-y-1">
        <!-- Dashboard -->
        <Link
          :href="route('dashboard')"
          class="group flex items-center rounded-md px-3 py-2 text-sm font-medium
                 transition-all duration-200
                 hover:-translate-y-[1px]"
          :class="route().current('dashboard')
          ? 'bg-zinc-900/5 text-zinc-900 dark:bg-zinc-100/10 dark:text-zinc-100'
          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50'"
        >
          <svg xmlns="http://www.w3.org/2000/svg"
               class="size-5 opacity-90 transition-colors
                      text-slate-700 group-hover:text-slate-900
                      dark:text-zinc-300 dark:group-hover:text-zinc-50"
               fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 12l9-9 9 9M4 10.5V21h6v-6h4v6h6V10.5" />
          </svg>
          <span v-show="open" class="ml-3">Dashboard</span>
        </Link>

        <!-- Item helper: mismo look para links simples -->
        <a
          class="group flex items-center rounded-md px-3 py-2 text-sm font-medium cursor-pointer
                 transition-all duration-200 hover:-translate-y-[1px]
                 text-slate-600 hover:bg-slate-100 hover:text-slate-900
                 dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50"
        >
          <img :src="usersIcon"
               class="size-5 opacity-90 dark:invert
                      transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Empleados</span>
        </a>

        <Link
            :href="route('requisiciones.index')"
            :preserve-state="false"
            :preserve-scroll="true"
            class="group flex items-center rounded-md px-3 py-2 text-sm font-medium cursor-pointer
                transition-all duration-200 hover:-translate-y-[1px]
                text-slate-700 hover:bg-slate-100 hover:text-slate-900
                dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50"
        >
            <img
            :src="solicitudIcon"
            class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110"
            alt=""
            />
            <span v-show="open" class="ml-3">Requisiciones</span>
        </Link>

        <a class="group flex items-center rounded-md px-3 py-2 text-sm font-medium cursor-pointer
                  transition-all duration-200 hover:-translate-y-[1px]
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900
                  dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50">
          <img :src="inversionIcon"
               class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Inversiones</span>
        </a>

        <a class="group flex items-center rounded-md px-3 py-2 text-sm font-medium cursor-pointer
                  transition-all duration-200 hover:-translate-y-[1px]
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900
                  dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50">
          <img :src="reportesIcon"
               class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Reportes</span>
        </a>

        <a class="group flex items-center rounded-md px-3 py-2 text-sm font-medium cursor-pointer
                  transition-all duration-200 hover:-translate-y-[1px]
                  text-slate-600 hover:bg-slate-100 hover:text-slate-900
                  dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50">
          <img :src="bdIcon"
               class="size-5 opacity-90 dark:invert transition-transform duration-200 group-hover:scale-110" />
          <span v-show="open" class="ml-3">Base de datos</span>
        </a>
      </nav>
    </div>

    <!-- Logout (intercepta click -> Swal -> POST logout) -->
    <div class="border-t p-2 border-slate-200 dark:border-zinc-800/70">
      <button
        type="button"
        @click="confirmLogout"
        class="group flex items-center w-full rounded-md px-3 py-2 text-sm
               transition-all duration-200 hover:-translate-y-[1px]
               text-slate-500 hover:bg-red-50 hover:text-red-600
               dark:text-zinc-300 dark:hover:bg-red-900/20 dark:hover:text-red-200"
      >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="size-5 opacity-90 transition-colors
                    text-slate-500 group-hover:text-red-500
                    dark:text-zinc-300 dark:group-hover:text-red-200"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M17 16l4-4m0 0-4-4m4 4H9m4 4v1a3 3 0 01-3 3H7a3 3 0 01-3-3V7a3 3 0 013-3h3a3 3 0 013 3v1"/>
        </svg>
        <span v-show="open" class="ml-3">Cerrar sesión</span>
      </button>
    </div>
  </aside>
</template>
