<script setup lang="ts">
/**
 * SystemLogs/Index.vue
 * - Filtros en tiempo real (debounce + Inertia router.get)
 * - Responsive “anti-scroll horizontal” (móvil/tablet/cards, tabla solo en xl+)
 * - Dark mode sobrio (neutros, sin colores chillones)
 * - Detalle de log con SweetAlert2 bien presentado
 */

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'
import Swal from 'sweetalert2'

type UserMini = { id: number; name: string; email?: string; rol?: string }

type SystemLogRow = {
  id: number
  user_id: number | null
  accion: string | null
  tabla: string | null
  registro_id: string | number | null
  ip_address: string | null
  user_agent: string | null
  descripcion: string | null
  created_at: string
  user?: UserMini | null
}

type PaginationLink = { url: string | null; label: string; active: boolean }

const props = defineProps<{
  logs: {
    data: SystemLogRow[]
    links: PaginationLink[]
    current_page: number
    last_page: number
    total: number
    per_page: number
    from: number | null
    to: number | null
  }
  filters: {
    from: string
    to: string
    tabla: string
    accion: string
    user_id: number | null
    ip: string
    q: string
    perPage: number
  }
  tablas: string[]
  acciones: string[]
  usuarios: { id: number; name: string }[]
}>()

/**
 * Estado UI (OJO: user_id debe ser string|number para v-model en <select>)
 * - Mandamos "" cuando es “Todos”
 * - En el controller ya lo normalizamos
 */
const state = reactive({
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
  tabla: props.filters.tabla ?? '',
  accion: props.filters.accion ?? '',
  user_id: (props.filters.user_id ?? '') as number | '' ,
  ip: props.filters.ip ?? '',
  q: props.filters.q ?? '',
  perPage: props.filters.perPage ?? 15,
})

const hasActiveFilters = computed(() => {
  return Boolean(
    state.from ||
      state.to ||
      state.tabla ||
      state.accion ||
      state.user_id ||
      state.ip ||
      state.q ||
      (state.perPage && Number(state.perPage) !== 15)
  )
})

/**
 * Debounce realtime (sin botón “Buscar”)
 */
let t: number | undefined

function applyFilters() {
  router.get(
    route('systemlogs.index'),
    {
      from: state.from || undefined,
      to: state.to || undefined,
      tabla: state.tabla || undefined,
      accion: state.accion || undefined,
      user_id: state.user_id || undefined,
      ip: state.ip || undefined,
      q: state.q || undefined,
      perPage: state.perPage || 15,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  )
}

watch(
  () => ({ ...state }),
  () => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => applyFilters(), 320)
  },
  { deep: true }
)

function clearFilters() {
  state.from = ''
  state.to = ''
  state.tabla = ''
  state.accion = ''
  state.user_id = ''
  state.ip = ''
  state.q = ''
  state.perPage = 15
  applyFilters()
}

function goTo(url: string | null) {
  if (!url) return
  router.visit(url, { preserveState: true, preserveScroll: true })
}

/**
 * Labels paginación: Atrás / Siguiente y limpia HTML
 */
function formatLabel(label: string) {
  const raw = label.replace(/&laquo;|&raquo;/g, '').trim().toLowerCase()
  if (raw.includes('previous')) return 'Atrás'
  if (raw.includes('next')) return 'Siguiente'
  if (label.includes('&laquo;')) return 'Atrás'
  if (label.includes('&raquo;')) return 'Siguiente'
  return label.replace(/<[^>]*>/g, '').trim()
}

/**
 * SweetAlert2: (dark y light)
 */
const isDark = computed(() => document.documentElement.classList.contains('dark'))

function swalBaseClasses() {
  return {
    popup:
      'rounded-3xl shadow-2xl border ' +
      'border-slate-200/70 dark:border-white/10 ' +
      'bg-white dark:bg-neutral-950 text-slate-900 dark:text-neutral-100',
    title: 'text-slate-900 dark:text-neutral-100',
    htmlContainer: 'text-slate-700 dark:text-neutral-200 !m-0',
    confirmButton:
      'rounded-2xl px-4 py-2 font-semibold bg-slate-900 text-white hover:bg-slate-800 ' +
      'dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white transition active:scale-[0.98]',
  }
}

function escapeHtml(value: string) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

/**
 * Badges sobrios (sin “chillones”)
 */
function badgeForAccion(a: string | null) {
  const v = (a ?? '').toUpperCase()
  if (v.includes('ELIM')) return 'bg-neutral-900/5 text-neutral-800 border-neutral-200 dark:bg-white/5 dark:text-neutral-100 dark:border-white/10'
  if (v.includes('ACT')) return 'bg-neutral-900/5 text-neutral-800 border-neutral-200 dark:bg-white/5 dark:text-neutral-100 dark:border-white/10'
  if (v.includes('CRE')) return 'bg-neutral-900/5 text-neutral-800 border-neutral-200 dark:bg-white/5 dark:text-neutral-100 dark:border-white/10'
  if (v.includes('LOGIN')) return 'bg-neutral-900/5 text-neutral-800 border-neutral-200 dark:bg-white/5 dark:text-neutral-100 dark:border-white/10'
  return 'bg-neutral-900/5 text-neutral-800 border-neutral-200 dark:bg-white/5 dark:text-neutral-100 dark:border-white/10'
}

/**
 * Detalle “bonito y moderno”
 */
function openDetail(row: SystemLogRow) {
  const userName = row.user?.name ?? 'N/A'
  const userEmail = row.user?.email ?? ''
  const accion = row.accion ?? 'N/A'
  const tabla = row.tabla ?? 'N/A'
  const registro = row.registro_id ?? 'N/A'
  const ip = row.ip_address ?? 'N/A'
  const ua = row.user_agent ?? 'N/A'
  const desc = row.descripcion ?? 'Sin descripción'
  const fecha = row.created_at

  Swal.fire({
    title: 'Detalle del log',
    confirmButtonText: 'Cerrar',
    customClass: swalBaseClasses(),
    width: 820,
    html: `
      <div class="text-left">
        <div class="grid gap-3">
          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/60 dark:bg-white/5 p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100">${escapeHtml(userName)}</div>
                ${
                  userEmail
                    ? `<div class="text-xs text-slate-600 dark:text-neutral-300">${escapeHtml(userEmail)}</div>`
                    : `<div class="text-xs text-slate-500 dark:text-neutral-400">Sin email</div>`
                }
                <div class="text-xs text-slate-500 dark:text-neutral-400 mt-1">Usuario ID: ${row.user_id ?? 'N/A'}</div>
              </div>
              <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold ${badgeForAccion(
                accion
              )}">
                ${escapeHtml(accion)}
              </span>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950/40 p-4">
              <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">Tabla</div>
              <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100 mt-1">${escapeHtml(tabla)}</div>
              <div class="text-xs text-slate-500 dark:text-neutral-400 mt-1">Registro: ${escapeHtml(String(registro))}</div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950/40 p-4">
              <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">Origen</div>
              <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100 mt-1">${escapeHtml(ip)}</div>
              <div class="text-xs text-slate-500 dark:text-neutral-400 mt-1">Fecha: ${escapeHtml(fecha)}</div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950/40 p-4">
            <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">Descripción</div>
            <div class="text-sm text-slate-800 dark:text-neutral-100 mt-2 leading-relaxed">
              ${escapeHtml(desc).replace(/\n/g, '<br/>')}
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950/40 p-4">
            <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">Agente</div>
            <div class="text-xs text-slate-700 dark:text-neutral-200 mt-2 break-words">
              ${escapeHtml(ua)}
            </div>
          </div>
        </div>
      </div>
    `,
    didOpen: () => {
      const popup = Swal.getPopup()
      if (popup) popup.classList.toggle('dark', isDark.value)
    },
  })
}
</script>

<template>
  <Head title="Logs" />

  <AuthenticatedLayout>
    <template #header>
      <div class="min-w-0">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-neutral-100">Logs del sistema</h2>
      </div>
    </template>

    <!-- Root anti-scroll horizontal -->
    <div class="w-full min-w-0 overflow-x-hidden px-4 sm:px-6 lg:px-8 py-6">
      <div class="max-w-7xl mx-auto min-w-0">
        <div
          class="rounded-2xl border border-slate-200/70 bg-white shadow-sm
                 dark:border-white/10 dark:bg-neutral-950/60"
        >
          <!-- Filtros -->
          <div class="p-4 sm:p-5 border-b border-slate-200/70 dark:border-white/10">
            <!-- En tablet: cards. Grid fluido -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 w-full min-w-0">
              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Desde</label>
                <input
                  v-model="state.from"
                  type="date"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100"
                />
              </div>

              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Hasta</label>
                <input
                  v-model="state.to"
                  type="date"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100"
                />
              </div>

              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tabla</label>
                <select
                  v-model="state.tabla"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option value="">Todas</option>
                  <option v-for="t in props.tablas" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>

              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Acción</label>
                <select
                  v-model="state.accion"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option value="">Todas</option>
                  <option v-for="a in props.acciones" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>

              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Usuario</label>
                <select
                  v-model="state.user_id"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option value="">Todos</option>
                  <option v-for="u in props.usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>

              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">IP</label>
                <input
                  v-model="state.ip"
                  type="text"
                  placeholder="Ej. 192.168"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100 dark:placeholder:text-neutral-500"
                />
              </div>

              <div class="sm:col-span-2 lg:col-span-4 min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
                <input
                  v-model="state.q"
                  type="text"
                  placeholder="Buscar en descripción, tabla, acción, registro, IP..."
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100 dark:placeholder:text-neutral-500"
                />
              </div>

              <div class="min-w-0">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
                <select
                  v-model="state.perPage"
                  class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option :value="10">10</option>
                  <option :value="15">15</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>

              <div class="min-w-0 flex items-end">
                <button
                  type="button"
                  @click="clearFilters"
                  :disabled="!hasActiveFilters"
                  class="w-full rounded-xl px-4 py-2 text-sm font-semibold
                         border border-slate-200 bg-slate-50 text-slate-800
                         hover:bg-slate-100 active:scale-[0.99] transition
                         disabled:opacity-50 disabled:cursor-not-allowed
                         dark:border-white/10 dark:bg-white/5 dark:text-neutral-100 dark:hover:bg-white/10"
                >
                  Limpiar
                </button>
              </div>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div class="text-sm text-slate-600 dark:text-neutral-300">
                Mostrando
                <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.from ?? 0 }}</span>
                a
                <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.to ?? 0 }}</span>
                de
                <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.total }}</span>
              </div>

              <div class="text-xs text-slate-500 dark:text-neutral-400">
                Tip: combina tabla + fechas para auditoría express.
              </div>
            </div>
          </div>

          <!-- TABLA solo xl+ (evita que iPad “parezca a la mitad”) -->
          <div class="hidden xl:block">
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-white/5">
                  <tr class="text-left text-slate-600 dark:text-neutral-300">
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Usuario</th>
                    <th class="px-4 py-3 font-semibold">Acción</th>
                    <th class="px-4 py-3 font-semibold">Tabla</th>
                    <th class="px-4 py-3 font-semibold">Registro</th>
                    <th class="px-4 py-3 font-semibold">IP</th>
                    <th class="px-4 py-3 font-semibold text-right">Detalle</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="row in props.logs.data"
                    :key="row.id"
                    class="border-t border-slate-200/70 hover:bg-slate-50/60 transition
                           dark:border-white/10 dark:hover:bg-white/5"
                  >
                    <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-neutral-100">
                      {{ row.created_at }}
                    </td>

                    <td class="px-4 py-3">
                      <div class="font-semibold text-slate-900 dark:text-neutral-100">
                        {{ row.user?.name ?? 'N/A' }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">
                        ID: {{ row.user_id ?? 'N/A' }}
                      </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                      <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                        :class="badgeForAccion(row.accion)"
                      >
                        {{ row.accion ?? 'N/A' }}
                      </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-neutral-100">
                      {{ row.tabla ?? 'N/A' }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-neutral-100">
                      {{ row.registro_id ?? 'N/A' }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-neutral-100">
                      {{ row.ip_address ?? 'N/A' }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-right">
                      <button
                        type="button"
                        @click="openDetail(row)"
                        class="rounded-xl px-3 py-1.5 text-sm font-semibold
                               bg-slate-900 text-white hover:bg-slate-800 active:scale-[0.99] transition
                               dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white"
                      >
                        Mostrar más
                      </button>
                    </td>
                  </tr>

                  <tr v-if="props.logs.data.length === 0">
                    <td colspan="7" class="px-4 py-10 text-center text-slate-600 dark:text-neutral-300">
                      No hay registros con los filtros actuales.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- CARDS para móvil + tablet + laptop mediana (<= lg/<=xl) -->
          <div class="xl:hidden p-4 sm:p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div
                v-for="row in props.logs.data"
                :key="row.id"
                class="rounded-2xl border border-slate-200/70 bg-white shadow-sm
                       hover:shadow-md transition
                       dark:border-white/10 dark:bg-neutral-950/40"
              >
                <div class="p-4">
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                        {{ row.user?.name ?? 'N/A' }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">
                        {{ row.created_at }} · ID: {{ row.user_id ?? 'N/A' }}
                      </div>
                    </div>

                    <span
                      class="shrink-0 inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold"
                      :class="badgeForAccion(row.accion)"
                    >
                      {{ row.accion ?? 'N/A' }}
                    </span>
                  </div>

                  <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-700 dark:text-neutral-200">
                    <div class="min-w-0"><span class="font-semibold">Tabla:</span> {{ row.tabla ?? 'N/A' }}</div>
                    <div class="min-w-0"><span class="font-semibold">Registro:</span> {{ row.registro_id ?? 'N/A' }}</div>
                    <div class="min-w-0"><span class="font-semibold">IP:</span> {{ row.ip_address ?? 'N/A' }}</div>
                    <div class="min-w-0 truncate"><span class="font-semibold">UA:</span> {{ row.user_agent ?? 'N/A' }}</div>
                  </div>

                  <div class="mt-4">
                    <button
                      type="button"
                      @click="openDetail(row)"
                      class="w-full rounded-xl px-4 py-2 text-sm font-semibold
                             border border-slate-200 bg-slate-50 text-slate-800
                             hover:bg-slate-100 active:scale-[0.99] transition
                             dark:border-white/10 dark:bg-white/5 dark:text-neutral-100 dark:hover:bg-white/10"
                    >
                      Mostrar detalle
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="props.logs.data.length === 0"
              class="mt-3 rounded-2xl border border-slate-200/70 bg-white p-6 text-center text-slate-600
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-300"
            >
              No hay registros con los filtros actuales.
            </div>
          </div>

          <!-- Footer paginación -->
          <div class="p-4 sm:p-5 border-t border-slate-200/70 dark:border-white/10">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div class="text-sm text-slate-600 dark:text-neutral-300">
                Página
                <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.current_page }}</span>
                de
                <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.last_page }}</span>
              </div>

              <nav class="flex flex-wrap gap-2">
                <button
                  v-for="(link, i) in props.logs.links"
                  :key="i"
                  type="button"
                  @click="goTo(link.url)"
                  :disabled="!link.url"
                  class="rounded-xl px-3 py-1.5 text-sm font-semibold border transition
                         border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                         disabled:opacity-50 disabled:cursor-not-allowed
                         dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5"
                  :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
                >
                  {{ formatLabel(link.label) }}
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
