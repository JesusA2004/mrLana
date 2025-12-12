<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { useTheme } from '@/Composables/useTheme'

const page = usePage()
const user = computed(() => {
  const u = (page.props as any)?.auth?.user
  return (u ?? { name: 'Usuario', email: '' }) as { name?: string; email?: string }
})

const initials = computed(() => {
  const name = String(user.value?.name ?? 'ML').trim()
  const parts = name.split(' ').filter(Boolean)
  const take = parts.slice(0, 2).map(p => p[0] ?? '').join('')
  return (take || 'ML').toUpperCase()
})

/** Theme global */
const { isDark, toggle, init } = useTheme()

/** Inicializa el tema al montar el navbar */
onMounted(() => init())
</script>

<template>
  <nav class="h-16 border-b backdrop-blur flex items-center justify-between px-4 sm:px-6 lg:px-8
              bg-slate-50/80 border-slate-200 dark:bg-neutral-900/80 dark:border-slate-800">
    <div class="flex items-center gap-3">
      <h1 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-slate-100">
        <slot name="title">Dashboard</slot>
      </h1>
    </div>

    <div class="flex items-center gap-3">
      <!-- Toggle Dark Mode -->
      <button type="button" @click="toggle"
              class="relative inline-flex h-9 w-16 items-center rounded-full border bg-slate-100 border-slate-300
                     shadow-sm transition-colors duration-300 ease-out dark:bg-slate-800 dark:border-slate-600"
              aria-label="Cambiar tema">
        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white shadow transform
                     transition-transform duration-300 ease-out text-amber-500 dark:text-sky-300"
              :class="isDark ? 'translate-x-7' : 'translate-x-0'">
          <svg v-if="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 4.5V3m0 18v-1.5M5.636 5.636L4.5 4.5m15 15-1.136-1.136M4.5 12H3m18 0h-1.5M5.636 18.364 4.5 19.5m15-15-1.136 1.136M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>

          <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
               viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
          </svg>
        </span>
      </button>

      <!-- Dropdown usuario -->
      <div class="relative">
        <Dropdown align="right" width="48">
          <template #trigger>
            <button type="button"
                    class="flex items-center gap-2 rounded-full border px-2.5 py-1.5 text-left text-sm
                           bg-sky-50 text-sky-900 border-sky-100 shadow-sm hover:bg-sky-100 hover:border-sky-200
                           dark:bg-neutral-800 dark:text-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-700
                           focus:outline-none transition-colors duration-200">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-400 text-xs font-semibold text-white
                          dark:bg-neutral-700 dark:text-neutral-100">
                {{ initials }}
              </div>

              <div class="hidden sm:flex flex-col">
                <span class="text-xs font-medium leading-tight">{{ user.name ?? 'Usuario' }}</span>
                <span class="text-[10px] leading-tight text-sky-700 dark:text-neutral-400">{{ user.email ?? '' }}</span>
              </div>

              <svg class="h-4 w-4 text-sky-500 dark:text-neutral-300" xmlns="http://www.w3.org/2000/svg"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
              </svg>
            </button>
          </template>

          <template #content>
            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
            <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
          </template>
        </Dropdown>
      </div>
    </div>
  </nav>
</template>
