<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

import '@/css/requisiciones.css'

type RequisicionRow = {
  id: number

  folio_unico?: string
  folio?: string

  status?: string
  fecha_captura?: string
  concepto?: string
  observaciones?: string

  solicitante?: string
  proveedor?: string

  subtotal?: string | number | null
  iva?: string | number | null
  total?: string | number | null
  monto_total?: string | number | null
  moneda?: string | null

  lugar_entrega?: string
  fecha_entrega?: string
  fecha_pago?: string | null

  creada_por?: string
}

type PaginationLink = { url: string | null; label: string; active: boolean }

type OptionItem = { id: number; nombre?: string; nombre_completo?: string }

const props = defineProps<{
  requisiciones: {
    data: RequisicionRow[]
    links: PaginationLink[]
  }
  options?: {
    conceptos?: OptionItem[]
    empleados?: OptionItem[]      // si “solicitante” viene de empleados
    proveedores?: OptionItem[]
    statuses?: string[]
  }
}>()

/* -------------------------
  Helpers
-------------------------- */
const rows = computed(() => props.requisiciones?.data ?? [])

const getFolio = (r: RequisicionRow) => String(r.folio_unico ?? r.folio ?? '').trim()
const getStatus = (r: RequisicionRow) => String(r.status ?? '').trim()
const getConcepto = (r: RequisicionRow) => String(r.concepto ?? '').trim()
const getObs = (r: RequisicionRow) => String(r.observaciones ?? '').trim()
const getSolicitante = (r: RequisicionRow) => String(r.solicitante ?? '').trim()
const getProveedor = (r: RequisicionRow) => String(r.proveedor ?? '').trim()
const getLugar = (r: RequisicionRow) => String(r.lugar_entrega ?? '').trim()
const getCreador = (r: RequisicionRow) => String(r.creada_por ?? '').trim()

const parseNumber = (v: unknown): number | null => {
  if (v === null || v === undefined) return null
  const s = String(v).replace(/[^0-9.-]+/g, '')
  const n = Number(s)
  return Number.isFinite(n) ? n : null
}

const moneyText = (v: unknown) => {
  const n = parseNumber(v)
  if (n === null) return '—'
  return n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const parseDate = (v?: string | null) => {
  if (!v) return null
  const d = new Date(v)
  return Number.isNaN(d.getTime()) ? null : d
}

const dayTs = (v?: string | null) => {
  const d = parseDate(v)
  if (!d) return null
  return new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime()
}

/* -------------------------
  Status list
-------------------------- */
const statuses = computed(() => {
  const server = props.options?.statuses?.filter(Boolean) ?? []
  return server.length ? server : ['BORRADOR', 'CAPTURADA', 'APROBADA', 'RECHAZADA', 'PAGADA']
})

/* -------------------------
  Datalists (autocomplete)
  - Si no escribes nada, el input muestra lista completa
  - Mientras escribes, el navegador filtra sugerencias
-------------------------- */
const uniq = (arr: string[]) =>
  Array.from(new Set(arr.map(s => s.trim()).filter(Boolean))).sort((a, b) => a.localeCompare(b, 'es'))

const conceptosList = computed(() => {
  const fromOptions = (props.options?.conceptos ?? []).map(x => x.nombre ?? '').filter(Boolean)
  const fromRows = rows.value.map(r => getConcepto(r)).filter(Boolean)
  return uniq([...fromOptions, ...fromRows])
})

const proveedoresList = computed(() => {
  const fromOptions = (props.options?.proveedores ?? []).map(x => x.nombre ?? '').filter(Boolean)
  const fromRows = rows.value.map(r => getProveedor(r)).filter(Boolean)
  return uniq([...fromOptions, ...fromRows])
})

const solicitantesList = computed(() => {
  const fromOptions = (props.options?.empleados ?? []).map(x => x.nombre_completo ?? x.nombre ?? '').filter(Boolean)
  const fromRows = rows.value.map(r => getSolicitante(r)).filter(Boolean)
  return uniq([...fromOptions, ...fromRows])
})

/* -------------------------
  Filtros (realtime frontend)
-------------------------- */
const filters = reactive({
  q: '',
  status: '',

  concepto: '',
  solicitante: '',
  proveedor: '',
  lugar_entrega: '',
  creada_por: '',

  captura_ini: '',
  captura_fin: '',
  entrega_ini: '',
  entrega_fin: '',
  pago_ini: '',
  pago_fin: '',

  monto_min: '',
  monto_max: '',
})

const matchText = (hay: string, needle: string) =>
  hay.toLowerCase().includes(needle.trim().toLowerCase())

const inDateRange = (val: string | undefined | null, ini: string, fin: string) => {
  if (!ini && !fin) return true
  const dd = dayTs(val)
  if (dd === null) return false
  const ss = ini ? dayTs(ini) : null
  const ee = fin ? dayTs(fin) : null
  if (ss !== null && dd < ss) return false
  if (ee !== null && dd > ee) return false
  return true
}

const inMoneyRange = (totalNum: number, min: string, max: string) => {
  const mn = parseNumber(min)
  const mx = parseNumber(max)
  if (mn !== null && totalNum < mn) return false
  if (mx !== null && totalNum > mx) return false
  return true
}

const filtered = computed(() => {
  const q = filters.q.trim().toLowerCase()

  return rows.value.filter((r) => {
    const folio = getFolio(r)
    const concepto = getConcepto(r)
    const obs = getObs(r)
    const solicitante = getSolicitante(r)
    const proveedor = getProveedor(r)
    const lugar = getLugar(r)
    const creador = getCreador(r)
    const status = getStatus(r)

    const subtotalN = parseNumber(r.subtotal) ?? 0
    const ivaN = parseNumber(r.iva) ?? 0
    const totalN = parseNumber(r.total ?? r.monto_total) ?? (subtotalN + ivaN)

    // global search
    if (q) {
      const blob = `${folio} ${concepto} ${obs} ${solicitante} ${proveedor} ${lugar} ${creador} ${status}`.toLowerCase()
      if (!blob.includes(q)) return false
    }

    if (filters.status && status !== filters.status) return false

    if (filters.concepto && !matchText(concepto, filters.concepto)) return false
    if (filters.solicitante && !matchText(solicitante, filters.solicitante)) return false
    if (filters.proveedor && !matchText(proveedor, filters.proveedor)) return false
    if (filters.lugar_entrega && !matchText(lugar, filters.lugar_entrega)) return false
    if (filters.creada_por && !matchText(creador, filters.creada_por)) return false

    if (!inDateRange(r.fecha_captura, filters.captura_ini, filters.captura_fin)) return false
    if (!inDateRange(r.fecha_entrega, filters.entrega_ini, filters.entrega_fin)) return false
    if (!inDateRange(r.fecha_pago ?? null, filters.pago_ini, filters.pago_fin)) return false

    if (!inMoneyRange(totalN, filters.monto_min, filters.monto_max)) return false

    return true
  })
})

const clearFilters = () => {
  Object.assign(filters, {
    q: '',
    status: '',
    concepto: '',
    solicitante: '',
    proveedor: '',
    lugar_entrega: '',
    creada_por: '',
    captura_ini: '',
    captura_fin: '',
    entrega_ini: '',
    entrega_fin: '',
    pago_ini: '',
    pago_fin: '',
    monto_min: '',
    monto_max: '',
  })
}

/* -------------------------
  Panel flotante (NO overlay, NO bloquea fondo)
-------------------------- */
const panelOpen = ref(false)
const panelEdit = ref(false)
const currentId = ref<number | null>(null)

const form = reactive({
  folio_unico: '',
  status: 'CAPTURADA',
  fecha_captura: new Date().toISOString().slice(0, 10),

  concepto: '',
  observaciones: '',

  solicitante: '',
  proveedor: '',

  subtotal: '',
  iva: '',
  total: '',
  moneda: 'MXN',

  lugar_entrega: '',
  fecha_entrega: '',
  fecha_pago: '',

  creada_por: '',
})

const resetForm = () => {
  Object.assign(form, {
    folio_unico: '',
    status: 'CAPTURADA',
    fecha_captura: new Date().toISOString().slice(0, 10),
    concepto: '',
    observaciones: '',
    solicitante: '',
    proveedor: '',
    subtotal: '',
    iva: '',
    total: '',
    moneda: 'MXN',
    lugar_entrega: '',
    fecha_entrega: '',
    fecha_pago: '',
    creada_por: '',
  })
}

const openCreate = () => {
  resetForm()
  panelOpen.value = true
  panelEdit.value = false
  currentId.value = null
}

const openEdit = (r: RequisicionRow) => {
  resetForm()
  panelOpen.value = true
  panelEdit.value = true
  currentId.value = r.id

  form.folio_unico = getFolio(r)
  form.status = getStatus(r) || 'CAPTURADA'
  form.fecha_captura = (r.fecha_captura ?? new Date().toISOString().slice(0, 10)).slice(0, 10)

  form.concepto = getConcepto(r)
  form.observaciones = getObs(r)

  form.solicitante = getSolicitante(r)
  form.proveedor = getProveedor(r)

  form.subtotal = String(r.subtotal ?? '')
  form.iva = String(r.iva ?? '')
  form.total = String(r.total ?? r.monto_total ?? '')
  form.moneda = String(r.moneda ?? 'MXN')

  form.lugar_entrega = getLugar(r)
  form.fecha_entrega = String(r.fecha_entrega ?? '').slice(0, 10)
  form.fecha_pago = String(r.fecha_pago ?? '').slice(0, 10)

  form.creada_por = getCreador(r)
}

const closePanel = () => {
  panelOpen.value = false
  panelEdit.value = false
  currentId.value = null
}

const onKey = (e: KeyboardEvent) => {
  if (!panelOpen.value) return
  if (e.key === 'Escape') closePanel()
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

/* -------------------------
  Acciones (conecta tus rutas)
-------------------------- */
const submit = () => {
  // Conecta aquí tus rutas reales:
  // if (panelEdit.value && currentId.value) {
  //   router.put(route('requisiciones.update', currentId.value), form, { preserveScroll: true })
  // } else {
  //   router.post(route('requisiciones.store'), form, { preserveScroll: true })
  // }
  // closePanel()

  // Placeholder sin romperte el flujo:
  closePanel()
}

const destroyRow = (r: RequisicionRow) => {
  // router.delete(route('requisiciones.destroy', r.id), { preserveScroll: true })
  alert(`Eliminar: ${getFolio(r) || `#${r.id}`}`)
}

const viewRow = (r: RequisicionRow) => {
  alert(`Ver: ${getFolio(r) || `#${r.id}`}`)
}

/* -------------------------
  Paginación (español)
-------------------------- */
const normalizeLabel = (htmlLabel: string) => {
  const txt = htmlLabel
    .replace(/&laquo;|«/g, '')
    .replace(/&raquo;|»/g, '')
    .replace(/Previous/i, 'Atrás')
    .replace(/Next/i, 'Siguiente')
    .trim()

  if (!txt) return '—'
  return txt
}

const linksEs = computed(() =>
  (props.requisiciones?.links ?? []).map(l => ({
    ...l,
    label: normalizeLabel(l.label),
  }))
)

const go = (url: string | null) => {
  if (!url) return
  router.visit(url, { preserveScroll: true, preserveState: true })
}

/* -------------------------
  UI (Tailwind) - dark neutro (sin negro puro)
-------------------------- */
const pageCard =
  'rounded-2xl border shadow-sm ' +
  'border-slate-200/80 bg-white/90 backdrop-blur ' +
  'dark:border-zinc-700/50 dark:bg-zinc-900/25'

const input =
  'w-full rounded-xl border px-3 py-2 text-sm outline-none transition ' +
  'border-slate-200 bg-white text-slate-900 shadow-sm ' +
  'focus:ring-2 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-zinc-700/60 dark:bg-zinc-900/35 dark:text-zinc-100 dark:focus:ring-zinc-700/50 dark:focus:border-zinc-600'

const btnSoft =
  'rounded-xl border px-3 py-2 text-sm font-medium transition active:scale-[0.99] ' +
  'border-slate-200 bg-white text-slate-900 hover:bg-slate-50 shadow-sm ' +
  'dark:border-zinc-700/60 dark:bg-zinc-900/30 dark:text-zinc-100 dark:hover:bg-zinc-800/30'

const btnPrimary =
  'rounded-xl px-4 py-2 text-sm font-semibold transition active:scale-[0.99] shadow-sm ' +
  'bg-slate-900 text-white hover:opacity-90 ' +
  'dark:bg-zinc-100 dark:text-zinc-900'

const pill =
  'inline-flex items-center rounded-full border px-2 py-1 text-xs font-semibold ' +
  'border-slate-200 bg-slate-50 text-slate-700 ' +
  'dark:border-zinc-700/60 dark:bg-zinc-900/40 dark:text-zinc-200'

const tableWrap =
  'overflow-x-auto rounded-2xl border ' +
  'border-slate-200/80 bg-white/90 ' +
  'dark:border-zinc-700/50 dark:bg-zinc-900/20'

const th =
  'px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide ' +
  'text-slate-600 bg-slate-50/80 ' +
  'dark:text-zinc-300 dark:bg-zinc-900/30'

const td =
  'px-4 py-3 align-top text-sm text-slate-800 dark:text-zinc-100'

const tr =
  'border-t border-slate-100 hover:bg-slate-50/60 transition ' +
  'dark:border-zinc-800/60 dark:hover:bg-zinc-800/20'
</script>

<template>
  <Head title="Requisiciones" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-xl font-semibold text-slate-900 dark:text-zinc-100">Requisiciones</h2>
          <p class="mt-1 text-sm text-slate-600 dark:text-zinc-300">Gestión simple y rápida</p>
        </div>
      </div>
    </template>

    <div class="py-4">
      <div class="w-full space-y-2">

        <!-- FILTROS + CTA -->
        <section :class="pageCard" class="p-4 sm:p-5 req-fade-in">
          <div class="flex items-start justify-between gap-1">
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-zinc-100">Filtros</h3>
            </div>

            <button type="button" :class="btnSoft" class="req-hover-lift" @click="clearFilters">
              Limpiar
            </button>
          </div>

          <div class="mt-4 grid gap-3 lg:grid-cols-12">
            <!-- Buscar global -->
            <div class="lg:col-span-4">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Buscar</label>
              <input
                v-model="filters.q"
                :class="input"
                class="mt-1"
                placeholder="Folio, proveedor, observaciones..."
              />
            </div>

            <!-- Status -->
            <div class="lg:col-span-2">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Status</label>
              <select v-model="filters.status" :class="input" class="mt-1">
                <option value="">Todos</option>
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>

            <!-- Concepto (autocomplete) -->
            <div class="lg:col-span-2">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Concepto</label>
              <input v-model="filters.concepto" :class="input" class="mt-1" list="dl-conceptos" placeholder="Ej. Viáticos" />
              <datalist id="dl-conceptos">
                <option v-for="c in conceptosList" :key="c" :value="c" />
              </datalist>
            </div>

            <!-- Solicitante (autocomplete) -->
            <div class="lg:col-span-2">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Solicitante</label>
              <input v-model="filters.solicitante" :class="input" class="mt-1" list="dl-solicitantes" placeholder="Nombre..." />
              <datalist id="dl-solicitantes">
                <option v-for="e in solicitantesList" :key="e" :value="e" />
              </datalist>
            </div>

            <!-- Proveedor (autocomplete) -->
            <div class="lg:col-span-2">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Proveedor</label>
              <input v-model="filters.proveedor" :class="input" class="mt-1" list="dl-proveedores" placeholder="Nombre..." />
              <datalist id="dl-proveedores">
                <option v-for="p in proveedoresList" :key="p" :value="p" />
              </datalist>
            </div>

            <!-- Lugar entrega -->
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Lugar entrega</label>
              <input v-model="filters.lugar_entrega" :class="input" class="mt-1" placeholder="Corporativo, Huamantla..." />
            </div>

            <!-- Creada por -->
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Creada por</label>
              <input v-model="filters.creada_por" :class="input" class="mt-1" placeholder="Usuario..." />
            </div>

            <!-- Monto min/max -->
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Monto mín.</label>
              <input v-model="filters.monto_min" :class="input" class="mt-1" placeholder="0" inputmode="decimal" />
            </div>

            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Monto máx.</label>
              <input v-model="filters.monto_max" :class="input" class="mt-1" placeholder="999999" inputmode="decimal" />
            </div>

            <!-- Fechas captura -->
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Captura: inicio</label>
              <input v-model="filters.captura_ini" type="date" :class="input" class="mt-1" />
            </div>
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Captura: fin</label>
              <input v-model="filters.captura_fin" type="date" :class="input" class="mt-1" />
            </div>

            <!-- Fechas entrega -->
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Entrega: inicio</label>
              <input v-model="filters.entrega_ini" type="date" :class="input" class="mt-1" />
            </div>
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Entrega: fin</label>
              <input v-model="filters.entrega_fin" type="date" :class="input" class="mt-1" />
            </div>

            <!-- Fechas pago -->
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Pago: inicio</label>
              <input v-model="filters.pago_ini" type="date" :class="input" class="mt-1" />
            </div>
            <div class="lg:col-span-3">
              <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Pago: fin</label>
              <input v-model="filters.pago_fin" type="date" :class="input" class="mt-1" />
            </div>

            <!-- Footer filtros -->
            <div class="lg:col-span-12 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between pt-2">
              <div class="text-xs text-slate-500 dark:text-zinc-400">
                Mostrando <span class="font-semibold">{{ filtered.length }}</span> de
                <span class="font-semibold">{{ rows.length }}</span> en esta página.
              </div>

              <button type="button" :class="btnPrimary" class="req-hover-lift" @click="openCreate">
                <span class="inline-flex items-center gap-2">
                  <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  Nueva requisición
                </span>
              </button>
            </div>
          </div>
        </section>

        <!-- TABLA -->
        <section :class="tableWrap" class="req-fade-in">
          <table class="w-full min-w-[1450px]">
            <thead>
              <tr>
                <th :class="th">Folio</th>
                <th :class="th">Status</th>
                <th :class="th">Fecha captura</th>
                <th :class="th">Concepto</th>
                <th :class="th">Observaciones</th>
                <th :class="th">Solicitante</th>
                <th :class="th">Proveedor</th>
                <th :class="th">Monto</th>
                <th :class="th">Lugar entrega</th>
                <th :class="th">Fecha entrega</th>
                <th :class="th">Fecha pago</th>
                <th :class="th">Creada por</th>
                <th :class="th" class="text-right">Acciones</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="r in filtered" :key="r.id" :class="tr" class="req-row">
                <td :class="td" class="font-semibold">
                  {{ getFolio(r) || `#${r.id}` }}
                </td>

                <td :class="td">
                  <span :class="pill" class="req-pill">{{ getStatus(r) || '—' }}</span>
                </td>

                <td :class="td">{{ r.fecha_captura || '—' }}</td>
                <td :class="td">{{ getConcepto(r) || '—' }}</td>

                <td :class="td" class="max-w-[380px]">
                  <div class="line-clamp-3 text-slate-600 dark:text-zinc-300">
                    {{ getObs(r) || '—' }}
                  </div>
                </td>

                <td :class="td">{{ getSolicitante(r) || '—' }}</td>
                <td :class="td">{{ getProveedor(r) || '—' }}</td>

                <td :class="td" class="text-sm">
                  <div class="text-slate-600 dark:text-zinc-300">Subtotal: {{ moneyText(r.subtotal) }}</div>
                  <div class="text-slate-600 dark:text-zinc-300">IVA: {{ moneyText(r.iva) }}</div>
                  <div class="font-semibold">
                    Total: {{ moneyText(r.total ?? r.monto_total) }} {{ (r.moneda ?? '') }}
                  </div>
                </td>

                <td :class="td">{{ getLugar(r) || '—' }}</td>
                <td :class="td">{{ r.fecha_entrega || '—' }}</td>
                <td :class="td">{{ r.fecha_pago || '—' }}</td>
                <td :class="td">{{ getCreador(r) || '—' }}</td>

                <td :class="td" class="text-right">
                  <div class="inline-flex items-center gap-2">
                    <button type="button" :class="btnSoft" class="px-2 py-1.5 req-icon-btn" @click="viewRow(r)" title="Ver">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2"/>
                      </svg>
                    </button>

                    <button type="button" :class="btnSoft" class="px-2 py-1.5 req-icon-btn" @click="openEdit(r)" title="Editar">
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z" stroke="currentColor" stroke-width="2"/>
                      </svg>
                    </button>

                    <button
                      type="button"
                      class="rounded-xl border px-2 py-1.5 text-sm font-medium transition active:scale-[0.99] shadow-sm req-icon-btn
                             border-red-200 bg-white text-red-700 hover:bg-red-50
                             dark:border-red-900/40 dark:bg-zinc-900/25 dark:text-red-300 dark:hover:bg-red-900/15"
                      @click="destroyRow(r)"
                      title="Eliminar"
                    >
                      <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M6 6l1 16h10l1-16" stroke="currentColor" stroke-width="2"/>
                        <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filtered.length === 0">
                <td colspan="13" class="px-6 py-14 text-center text-slate-500 dark:text-zinc-400">
                  No hay resultados.
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <!-- PAGINACIÓN (ES) -->
        <div class="flex flex-wrap items-center gap-2 req-fade-in">
          <button
            v-for="(l, i) in linksEs"
            :key="i"
            type="button"
            :disabled="!l.url"
            @click="go(l.url)"
            class="rounded-xl border px-3 py-1.5 text-sm transition req-hover-lift"
            :class="[
              l.active
                ? 'border-slate-900 bg-slate-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                : 'border-slate-200 bg-white text-slate-900 hover:bg-slate-50 dark:border-zinc-700/60 dark:bg-zinc-900/25 dark:text-zinc-100 dark:hover:bg-zinc-800/25',
              !l.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
          >
            {{ l.label }}
          </button>
        </div>

        <!-- PANEL FLOTANTE (SIN OVERLAY, NO BLOQUEA FONDO) -->
        <div
          v-if="panelOpen"
          class="fixed right-4 top-24 z-[9999] w-[min(520px,calc(100vw-2rem))] req-panel"
        >
          <div class="rounded-2xl border shadow-2xl req-panel-card
                      border-slate-200/80 bg-white/95
                      dark:border-zinc-700/60 dark:bg-zinc-900/35">
            <div class="flex items-center justify-between border-b px-5 py-3 border-slate-100 dark:border-zinc-800/60">
              <h3 class="text-base font-semibold text-slate-900 dark:text-zinc-100">
                {{ panelEdit ? 'Editar requisición' : 'Nueva requisición' }}
              </h3>

              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-xl border shadow-sm transition active:scale-[0.99] req-hover-lift
                       border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                       dark:border-zinc-700/60 dark:bg-zinc-900/35 dark:text-zinc-200 dark:hover:bg-zinc-800/30"
                @click="closePanel"
                aria-label="Cerrar"
              >
                ✕
              </button>
            </div>

            <div class="grid gap-3 p-5 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Folio</label>
                <input v-model="form.folio_unico" :class="input" class="mt-1" placeholder="Ej. 25917 / RQ-0001" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Status</label>
                <select v-model="form.status" :class="input" class="mt-1">
                  <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Fecha captura</label>
                <input v-model="form.fecha_captura" type="date" :class="input" class="mt-1" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Concepto</label>
                <input v-model="form.concepto" :class="input" class="mt-1" list="dl-conceptos" placeholder="Viáticos / Insumos..." />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Lugar entrega</label>
                <input v-model="form.lugar_entrega" :class="input" class="mt-1" placeholder="Corporativo / Huamantla..." />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Solicitante</label>
                <input v-model="form.solicitante" :class="input" class="mt-1" list="dl-solicitantes" placeholder="Nombre..." />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Proveedor</label>
                <input v-model="form.proveedor" :class="input" class="mt-1" list="dl-proveedores" placeholder="Nombre..." />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Subtotal</label>
                <input v-model="form.subtotal" :class="input" class="mt-1" placeholder="0.00" inputmode="decimal" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">IVA</label>
                <input v-model="form.iva" :class="input" class="mt-1" placeholder="0.00" inputmode="decimal" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Total</label>
                <input v-model="form.total" :class="input" class="mt-1" placeholder="0.00" inputmode="decimal" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Moneda</label>
                <input v-model="form.moneda" :class="input" class="mt-1" placeholder="MXN" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Fecha entrega</label>
                <input v-model="form.fecha_entrega" type="date" :class="input" class="mt-1" />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Fecha pago</label>
                <input v-model="form.fecha_pago" type="date" :class="input" class="mt-1" />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Observaciones</label>
                <textarea v-model="form.observaciones" rows="3" :class="input" class="mt-1" placeholder="Detalle / justificación..." />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-700 dark:text-zinc-300">Creada por</label>
                <input v-model="form.creada_por" :class="input" class="mt-1" placeholder="Usuario..." />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t px-5 py-4 border-slate-100 dark:border-zinc-800/60">
              <button type="button" :class="btnSoft" class="req-hover-lift" @click="closePanel">Cancelar</button>
              <button type="button" :class="btnPrimary" class="req-hover-lift" @click="submit">
                {{ panelEdit ? 'Guardar cambios' : 'Registrar' }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
