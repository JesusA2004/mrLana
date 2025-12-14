<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Swal from 'sweetalert2'

defineProps<{ current?: string }>()

const open = ref(false)
const reducedMotion = ref(false)

const page = usePage()
const userRole = computed(() => ((page.props as any)?.auth?.user?.rol ?? 'COLABORADOR') as 'ADMIN' | 'CONTADOR' | 'COLABORADOR')

onMounted(() => {
  reducedMotion.value = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false
})

const isDarkTheme = () => document.documentElement.classList.contains('dark')

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
    background: dark ? '#18181b' : '#ffffff',
    color: dark ? '#e4e4e7' : '#111827',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#52525b',
  })

  if (!result.isConfirmed) return

  router.post(route('logout'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Sesión cerrada',
        text: 'Has salido del sistema correctamente.',
        timer: 1600,
        showConfirmButton: false,
        background: dark ? '#18181b' : '#ffffff',
        color: dark ? '#e4e4e7' : '#111827',
        iconColor: '#22c55e',
      })
    },
  })
}

const can = (roles: Array<'ADMIN' | 'CONTADOR' | 'COLABORADOR'>) => roles.includes(userRole.value)

const safeRoute = (name: string): string | null => {
  try {
    // @ts-ignore
    return route(name) as string
  } catch {
    return null
  }
}

const isActive = (name: string) => {
  try {
    return route().current(name)
  } catch {
    return false
  }
}

const onEnter = () => {
  open.value = true
  if (reducedMotion.value) return
  const el = document.getElementById('erp-sidebar')
  el?.animate(
    [{ transform: 'translateX(-2px)', opacity: 0.96 }, { transform: 'translateX(0px)', opacity: 1 }],
    { duration: 180, easing: 'ease-out' }
  )
}
const onLeave = () => (open.value = false)

const navItemBase =
  'group relative flex items-center rounded-md px-3 py-2 text-sm font-medium cursor-pointer ' +
  'transition-all duration-200 hover:-translate-y-[1px] ' +
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400/40 dark:focus-visible:ring-zinc-500/40'

const navItemIdle =
  'text-slate-600 hover:bg-slate-100 hover:text-slate-900 ' +
  'dark:text-zinc-200 dark:hover:bg-zinc-900/40 dark:hover:text-zinc-50'

const navItemActive =
  'bg-zinc-900/5 text-zinc-900 shadow-[inset_0_0_0_1px_rgba(15,23,42,0.06)] ' +
  'dark:bg-zinc-100/10 dark:text-zinc-100 dark:shadow-[inset_0_0_0_1px_rgba(244,244,245,0.10)]'

const showTip = computed(() => !open.value)

// 8 iconos + logout (SVG inline)
const icons = {
  dashboard: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M4 10.5V21h6v-6h4v6h6V10.5" />`,
  empresa: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 21V9h6v12" />`,
  personas: `<path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8M22 21v-2a4 4 0 00-3-3.87M18 3.13a4 4 0 010 7.75" />`,
  catalogo: `<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />`,
  proveedores: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4v10l-9 4-9-4V7zM12 7v14" />`,
  inversiones: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-3.314 0-6 2.239-6 5s2.686 5 6 5 6-2.239 6-5-2.686-5-6-5zM12 3v2M12 19v2" />`,
  finanzas: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 1v22M17 5H9a3 3 0 000 6h6a3 3 0 110 6H6" />`,
  documentos: `<path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l3 3v15a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM14 3v4h4" />`,
  logs: `<path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />`,
  logout: `<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0-4-4m4 4H9m4 4v1a3 3 0 01-3 3H7a3 3 0 01-3-3V7a3 3 0 013-3h3a3 3 0 013 3v1" />`,
} as const

type Role = 'ADMIN' | 'CONTADOR' | 'COLABORADOR'
type IconKey = keyof typeof icons

type RawItem = { label: string; routeName: string; iconKey: IconKey; roles: Role[] }
type RawGroup = { title: string; roles: Role[]; items: RawItem[] }

const rawGroups = computed<RawGroup[]>(() => [
  {
    title: 'General',
    roles: ['ADMIN','CONTADOR','COLABORADOR'],
    items: [
      { label: 'Dashboard', routeName: 'dashboard', iconKey: 'dashboard', roles: ['ADMIN','CONTADOR','COLABORADOR'] },
    ],
  },
  {
    title: 'Organización',
    roles: ['ADMIN','CONTADOR'],
    items: [
      { label: 'Corporativos', routeName: 'corporativos.index', iconKey: 'empresa', roles: ['ADMIN','CONTADOR'] },

      // OJO: aquí pon el nombre real cuando lo tengas. Mientras tanto, si no existe, no se renderiza y no truena.
      { label: 'Sucursales', routeName: 'sucursales.index', iconKey: 'empresa', roles: ['ADMIN','CONTADOR'] },

      { label: 'Áreas', routeName: 'areas.index', iconKey: 'catalogo', roles: ['ADMIN','CONTADOR'] },
    ],
  },
  {
    title: 'Personas',
    roles: ['ADMIN'],
    items: [
      { label: 'Empleados', routeName: 'empleados.index', iconKey: 'personas', roles: ['ADMIN'] },
      { label: 'Usuarios', routeName: 'users.index', iconKey: 'personas', roles: ['ADMIN'] },
    ],
  },
  {
    title: 'Catálogos',
    roles: ['ADMIN','CONTADOR'],
    items: [
      { label: 'Conceptos', routeName: 'conceptos.index', iconKey: 'catalogo', roles: ['ADMIN','CONTADOR'] },
      { label: 'Proveedores', routeName: 'proveedors.index', iconKey: 'proveedores', roles: ['ADMIN','CONTADOR'] },
      { label: 'Inversionistas', routeName: 'inversionistas.index', iconKey: 'inversiones', roles: ['ADMIN','CONTADOR'] },
    ],
  },
  {
    title: 'Inversiones',
    roles: ['ADMIN','CONTADOR'],
    items: [
      { label: 'Contratos', routeName: 'contratos.index', iconKey: 'inversiones', roles: ['ADMIN','CONTADOR'] },
      { label: 'Pagos', routeName: 'pagos.index', iconKey: 'finanzas', roles: ['ADMIN','CONTADOR'] },
    ],
  },
  {
    title: 'Operación',
    roles: ['ADMIN','CONTADOR','COLABORADOR'],
    items: [
      { label: 'Requisiciones', routeName: 'requisicions.index', iconKey: 'documentos', roles: ['ADMIN','CONTADOR','COLABORADOR'] },
      { label: 'Comprobantes', routeName: 'comprobantes.index', iconKey: 'documentos', roles: ['ADMIN','CONTADOR','COLABORADOR'] },
      { label: 'Folios', routeName: 'folios.index', iconKey: 'documentos', roles: ['ADMIN','CONTADOR','COLABORADOR'] },
      { label: 'Ajustes', routeName: 'ajustes.index', iconKey: 'documentos', roles: ['ADMIN','CONTADOR'] },
      { label: 'Detalles', routeName: 'detalles.index', iconKey: 'documentos', roles: ['ADMIN','CONTADOR'] },
    ],
  },
  {
    title: 'Finanzas',
    roles: ['ADMIN','CONTADOR'],
    items: [
      { label: 'Ingresos', routeName: 'ingresos.index', iconKey: 'finanzas', roles: ['ADMIN','CONTADOR'] },
      { label: 'Gastos', routeName: 'gastos.index', iconKey: 'finanzas', roles: ['ADMIN','CONTADOR'] },
    ],
  },
  {
    title: 'Auditoría',
    roles: ['ADMIN'],
    items: [
      { label: 'System Log', routeName: 'systemlogs.index', iconKey: 'logs', roles: ['ADMIN'] },
    ],
  },
])

/**
 * Build final menu:
 * - Filtra por rol
 * - Calcula href con safeRoute()
 * - Si href es null, no se renderiza (no crashea Inertia Link)
 */
const groups = computed(() => {
  return rawGroups.value
    .filter(g => can(g.roles))
    .map(g => ({
      title: g.title,
      items: g.items
        .filter(i => can(i.roles))
        .map(i => ({
          ...i,
          href: safeRoute(i.routeName), // string | null
        }))
        .filter(i => !!i.href),
    }))
    .filter(g => g.items.length > 0)
})
</script>

<template>
  <aside
    id="erp-sidebar"
    class="group flex h-screen flex-col justify-between border-r sticky top-0 z-30
           transition-[width] duration-300 ease-out transition-colors
           bg-slate-50 text-slate-800 border-slate-200
           dark:bg-zinc-950/60 dark:text-zinc-100 dark:border-zinc-800/70
           backdrop-blur supports-[backdrop-filter]:bg-slate-50/80 supports-[backdrop-filter]:dark:bg-zinc-950/40"
    :class="open ? 'w-60' : 'w-16'"
    @mouseenter="onEnter"
    @mouseleave="onLeave"
  >
    <div>
      <div class="flex items-center justify-center h-16 border-b px-2 border-slate-200 dark:border-zinc-800/70">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl shadow-md
                 bg-slate-900 text-slate-100 dark:bg-zinc-100 dark:text-zinc-900
                 transition-transform duration-200 group-hover:scale-[1.04]
                 motion-safe:animate-[sidebarPulse_2.8s_ease-in-out_infinite]"
        >
          <ApplicationLogo class="w-7 h-7" />
        </div>

        <span v-show="open" class="ml-3 text-sm font-semibold tracking-wide text-slate-800 dark:text-zinc-100 transition-opacity duration-200 select-none">
          MR-LanaERP
        </span>
      </div>

      <nav class="mt-4 px-2 space-y-3">
        <div v-for="g in groups" :key="g.title" class="space-y-1">
          <div v-show="open" class="px-3 pt-2 text-[11px] font-semibold tracking-wider uppercase text-slate-400 dark:text-zinc-400">
            {{ g.title }}
          </div>

          <Link
            v-for="item in g.items"
            :key="item.routeName"
            :href="item.href"
            :preserve-state="false"
            :preserve-scroll="true"
            class="sidebar-item"
            :class="[navItemBase, isActive(item.routeName) ? navItemActive : navItemIdle]"
          >
            <span class="relative flex items-center justify-center">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="size-5 opacity-90 transition-all duration-200
                       text-slate-700 group-hover:text-slate-900
                       dark:text-zinc-300 dark:group-hover:text-zinc-50
                       group-hover:scale-110"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
                v-html="icons[item.iconKey]"
              />
            </span>

            <span v-show="open" class="ml-3 truncate">{{ item.label }}</span>

            <span
              v-if="isActive(item.routeName)"
              class="absolute right-2 h-2 w-2 rounded-full
                     bg-emerald-500/80 dark:bg-emerald-400/80
                     motion-safe:animate-[dotPulse_1.6s_ease-in-out_infinite]"
            ></span>

            <span
              v-if="showTip"
              class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2
                     whitespace-nowrap rounded-lg px-2 py-1 text-xs font-medium
                     bg-slate-900 text-white shadow-lg opacity-0 translate-x-[-4px]
                     group-hover:opacity-100 group-hover:translate-x-0
                     transition-all duration-150
                     dark:bg-zinc-100 dark:text-zinc-900"
            >
              {{ item.label }}
            </span>
          </Link>
        </div>
      </nav>
    </div>

    <div class="border-t p-2 border-slate-200 dark:border-zinc-800/70">
      <div v-show="open" class="px-3 pb-2 text-xs text-slate-500 dark:text-zinc-400 select-none">
        Rol: <span class="font-semibold text-slate-700 dark:text-zinc-200">{{ userRole }}</span>
      </div>

      <button
        type="button"
        @click="confirmLogout"
        class="group relative flex items-center w-full rounded-md px-3 py-2 text-sm font-medium
               transition-all duration-200 hover:-translate-y-[1px]
               text-slate-500 hover:bg-red-50 hover:text-red-700
               dark:text-zinc-300 dark:hover:bg-red-900/20 dark:hover:text-red-200
               focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/30"
      >
        <svg xmlns="http://www.w3.org/2000/svg"
             class="size-5 opacity-90 transition-all duration-200
                    text-slate-500 group-hover:text-red-600
                    dark:text-zinc-300 dark:group-hover:text-red-200
                    group-hover:scale-110"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
             v-html="icons.logout">
        </svg>

        <span v-show="open" class="ml-3">Cerrar sesión</span>

        <span
          v-if="showTip"
          class="pointer-events-none absolute left-16 top-1/2 -translate-y-1/2
                 whitespace-nowrap rounded-lg px-2 py-1 text-xs font-medium
                 bg-red-600 text-white shadow-lg opacity-0 translate-x-[-4px]
                 group-hover:opacity-100 group-hover:translate-x-0
                 transition-all duration-150
                 dark:bg-red-500 dark:text-white"
        >
          Cerrar sesión
        </span>
      </button>
    </div>
  </aside>
</template>

<style scoped>
@keyframes sidebarPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}
@keyframes dotPulse {
  0%, 100% { transform: scale(1); opacity: .75; }
  50% { transform: scale(1.25); opacity: 1; }
}

.sidebar-item::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 0.375rem;
  opacity: 0;
  transform: translateY(1px);
  transition: opacity 180ms ease, transform 180ms ease;
  box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.06), 0 10px 24px rgba(15, 23, 42, 0.06);
  pointer-events: none;
}
.sidebar-item:hover::before {
  opacity: 1;
  transform: translateY(0);
}
:deep(.dark) .sidebar-item::before {
  box-shadow: 0 0 0 1px rgba(244, 244, 245, 0.08), 0 10px 28px rgba(0, 0, 0, 0.22);
}
</style>
