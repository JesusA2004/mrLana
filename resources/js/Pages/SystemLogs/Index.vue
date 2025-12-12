<script setup lang="ts">
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

const state = reactive({
  from: props.filters.from ?? '',
  to: props.filters.to ?? '',
  tabla: props.filters.tabla ?? '',
  accion: props.filters.accion ?? '',
  user_id: props.filters.user_id ?? '',
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

let t: number | undefined

function applyFilters() {
  router.get(
    route('admin.system-logs.index'),
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

function formatLabel(label: string) {
  const raw = label.replace(/&laquo;|&raquo;/g, '').trim().toLowerCase()
  if (raw.includes('previous')) return 'Atrás'
  if (raw.includes('next')) return 'Siguiente'
  if (label.includes('&laquo;')) return 'Atrás'
  if (label.includes('&raquo;')) return 'Siguiente'
  return label.replace(/<[^>]*>/g, '').trim()
}

function openDetail(row: SystemLogRow) {
  const userName = row.user?.name ?? 'N/A'
  const accion = row.accion ?? 'N/A'
  const tabla = row.tabla ?? 'N/A'
  const registro = row.registro_id ?? 'N/A'
  const ip = row.ip_address ?? 'N/A'
  const ua = row.user_agent ?? 'N/A'
  const desc = row.descripcion ?? 'Sin descripción'
  const fecha = row.created_at

  Swal.fire({
    title: 'Detalle del log',
    html: `
      <div style="text-align:left; line-height:1.35">
        <div><b>ID:</b> ${row.id}</div>
        <div><b>Usuario:</b> ${escapeHtml(userName)} (ID: ${row.user_id ?? 'N/A'})</div>
        <div><b>Acción:</b> ${escapeHtml(accion)}</div>
        <div><b>Tabla:</b> ${escapeHtml(tabla)}</div>
        <div><b>Registro:</b> ${escapeHtml(String(registro))}</div>
        <div><b>IP:</b> ${escapeHtml(ip)}</div>
        <div style="margin-top:8px"><b>Fecha:</b> ${escapeHtml(fecha)}</div>
        <div style="margin-top:10px"><b>Descripción:</b><br/>${escapeHtml(desc).replace(/\n/g, '<br/>')}</div>
        <div style="margin-top:10px"><b>User-Agent:</b><br/><span style="font-size:12px">${escapeHtml(ua)}</span></div>
      </div>
    `,
    confirmButtonText: 'Cerrar',
    width: 760,
    background: document.documentElement.classList.contains('dark') ? '#0b0b0c' : '#ffffff',
    color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#111827',
  })
}

function escapeHtml(value: string) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}
</script>

<template>
  <Head title="System Logs" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col gap-1">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-neutral-100">System Logs</h2>
        <p class="text-sm text-slate-600 dark:text-neutral-300">
          Auditoría operativa: trazabilidad por usuario, acción y tabla.
        </p>
      </div>
    </template>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white shadow-sm
               dark:border-neutral-800 dark:bg-neutral-950/60"
      >
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-neutral-800">
          <div class="flex flex-col lg:flex-row lg:items-end gap-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 w-full">
              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Desde</label>
                <input
                  v-model="state.from"
                  type="date"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100"
                />
              </div>

              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Hasta</label>
                <input
                  v-model="state.to"
                  type="date"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100"
                />
              </div>

              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Tabla</label>
                <select
                  v-model="state.tabla"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option value="">Todas</option>
                  <option v-for="t in props.tablas" :key="t" :value="t">{{ t }}</option>
                </select>
              </div>

              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Acción</label>
                <select
                  v-model="state.accion"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option value="">Todas</option>
                  <option v-for="a in props.acciones" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>

              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Usuario</label>
                <select
                  v-model="state.user_id"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option value="">Todos</option>
                  <option v-for="u in props.usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>

              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">IP</label>
                <input
                  v-model="state.ip"
                  type="text"
                  placeholder="Ej. 192.168"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100 dark:placeholder:text-neutral-500"
                />
              </div>

              <div class="sm:col-span-2 lg:col-span-4">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Búsqueda</label>
                <input
                  v-model="state.q"
                  type="text"
                  placeholder="Buscar en descripción, tabla, acción, registro..."
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100 dark:placeholder:text-neutral-500"
                />
              </div>

              <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-600 dark:text-neutral-300">Por página</label>
                <select
                  v-model="state.perPage"
                  class="mt-1 w-full rounded-xl border-slate-200 bg-white text-slate-900
                         focus:border-slate-400 focus:ring-0
                         dark:border-neutral-800 dark:bg-neutral-900/60 dark:text-neutral-100"
                >
                  <option :value="10">10</option>
                  <option :value="15">15</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>

              <div class="lg:col-span-1 flex items-end">
                <button
                  type="button"
                  @click="clearFilters"
                  :disabled="!hasActiveFilters"
                  class="w-full rounded-xl px-4 py-2 text-sm font-medium
                         border border-slate-200 bg-slate-50 text-slate-800
                         hover:bg-slate-100 active:scale-[0.99] transition
                         disabled:opacity-50 disabled:cursor-not-allowed
                         dark:border-neutral-800 dark:bg-neutral-900/50 dark:text-neutral-100 dark:hover:bg-neutral-900"
                >
                  Limpiar
                </button>
              </div>
            </div>
          </div>

          <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Mostrando
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.from ?? 0 }}</span>
              a
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.to ?? 0 }}</span>
              de
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.total }}</span>
            </div>

            <div class="text-xs text-slate-500 dark:text-neutral-400">
              Consejo: filtra por tabla + rango de fechas para acelerar la auditoría.
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-neutral-900/60">
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
                class="border-t border-slate-200 hover:bg-slate-50/70 transition
                       dark:border-neutral-800 dark:hover:bg-neutral-900/40"
              >
                <td class="px-4 py-3 whitespace-nowrap text-slate-900 dark:text-neutral-100">
                  {{ row.created_at }}
                </td>

                <td class="px-4 py-3">
                  <div class="font-medium text-slate-900 dark:text-neutral-100">
                    {{ row.user?.name ?? 'N/A' }}
                  </div>
                  <div class="text-xs text-slate-500 dark:text-neutral-400">
                    ID: {{ row.user_id ?? 'N/A' }}
                  </div>
                </td>

                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                           bg-slate-100 text-slate-800
                           dark:bg-neutral-900 dark:text-neutral-100 dark:border dark:border-neutral-800"
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
                    class="rounded-xl px-3 py-1.5 text-sm font-medium
                           bg-slate-900 text-white hover:opacity-90 active:scale-[0.99] transition
                           dark:bg-neutral-100 dark:text-neutral-950"
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

        <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-neutral-800">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Página <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.current_page }}</span>
              de <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.logs.last_page }}</span>
            </div>

            <nav class="flex flex-wrap gap-2">
              <button
                v-for="(link, i) in props.logs.links"
                :key="i"
                type="button"
                @click="goTo(link.url)"
                :disabled="!link.url"
                class="rounded-xl px-3 py-1.5 text-sm font-medium border transition
                       border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                       disabled:opacity-50 disabled:cursor-not-allowed
                       dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-neutral-100 dark:hover:bg-neutral-900/60"
                :class="link.active ? 'ring-2 ring-slate-300 dark:ring-neutral-700' : ''"
              >
                {{ formatLabel(link.label) }}
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
