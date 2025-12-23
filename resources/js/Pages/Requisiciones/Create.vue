<script setup lang="ts">
/**
 * Requisiciones/Create.vue
 * - UX de usuario final (sin capacitación):
 *   1) Datos de la requisición (quién/desde dónde/para qué)
 *   2) Proveedor (obligatorio, con alta inmediata)
 *   3) Detalles (qué se compra y cuánto)
 * - Secciones tipo acordeón (colapsables).
 * - Guía con JS: al completar abre la siguiente, scroll y highlight.
 * - Barra de acción en móvil: sticky (no fixed) para no invadir sidebar.
 */

import { computed, nextTick, reactive, ref, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

type CatalogOption = Record<string, any>

type CreateProps = {
  catalogos: {
    corporativos: CatalogOption[]
    sucursales: CatalogOption[]
    empleados: CatalogOption[]
    conceptos: CatalogOption[]
    proveedores: CatalogOption[]
  }
  defaults?: {
    comprador_corp_id?: number | null
    sucursal_id?: number | null
    solicitante_id?: number | null
  }
}

const props = defineProps<CreateProps>()
const page = usePage<any>()

const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())

/**
 * Flash: proveedor recién creado
 */
const flashNewProveedorId = computed<number | null>(() => {
  const f = page.props?.flash ?? {}
  const direct = page.props?.new_proveedor_id
  const id = (f?.new_proveedor_id ?? direct) as any
  const n = Number(id)
  return Number.isFinite(n) && n > 0 ? n : null
})

/**
 * Catálogos activos
 */
const corporativosActive = computed(() => (props.catalogos?.corporativos ?? []).filter((c) => c.activo !== false))
const sucursalesActive = computed(() => (props.catalogos?.sucursales ?? []).filter((s) => s.activo !== false))
const empleadosActive = computed(() => (props.catalogos?.empleados ?? []).filter((e) => e.activo !== false))
const conceptosActive = computed(() => (props.catalogos?.conceptos ?? []).filter((c) => c.activo !== false))

const proveedoresLocal = ref<CatalogOption[]>(props.catalogos?.proveedores ?? [])
watch(
  () => props.catalogos?.proveedores,
  (v) => (proveedoresLocal.value = v ?? []),
)

/**
 * Form
 */
const form = reactive({
  tipo: 'ANTICIPO' as 'ANTICIPO' | 'REEMBOLSO',
  comprador_corp_id: (props.defaults?.comprador_corp_id ?? null) as number | null,
  sucursal_id: (props.defaults?.sucursal_id ?? null) as number | null,
  solicitante_id: (props.defaults?.solicitante_id ?? null) as number | null,
  concepto_id: null as number | null,
  proveedor_id: null as number | null,
  observaciones: '',
})

type Item = {
  cantidad: number
  descripcion: string
  precio_unitario: number
  sucursal_id: number | null
}

const items = ref<Item[]>([
  { cantidad: 1, descripcion: '', precio_unitario: 0, sucursal_id: form.sucursal_id ?? null },
])

/**
 * Estado UI
 */
const errors = ref<Record<string, string>>({})
const saving = ref(false)

/**
 * Acordeón + guía
 */
type PanelKey = 'datos' | 'proveedor' | 'detalles' | 'revision'
const openPanel = ref<PanelKey>('datos')

const datosRef = ref<HTMLElement | null>(null)
const provRef = ref<HTMLElement | null>(null)
const detRef = ref<HTMLElement | null>(null)

const highlight = ref<PanelKey | null>(null)
let hlTimer: any = null
function pulsePanel(key: PanelKey) {
  highlight.value = key
  clearTimeout(hlTimer)
  hlTimer = setTimeout(() => (highlight.value = null), 700)
}

function goPanel(key: PanelKey, doScroll = true) {
  openPanel.value = key
  nextTick(() => {
    pulsePanel(key)
    if (!doScroll) return
    const el = key === 'datos' ? datosRef.value : key === 'proveedor' ? provRef.value : detRef.value
    el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

/**
 * Reglas de completitud (simple, claro)
 */
const datosOk = computed(() => !!form.comprador_corp_id && !!form.sucursal_id && !!form.solicitante_id && !!form.concepto_id)
const proveedorOk = computed(() => !!form.proveedor_id)
const detallesOk = computed(() => {
  const hasAny = items.value.some((it) => String(it.descripcion || '').trim().length > 0)
  const allValid = items.value.every((it) => {
    const descOk = String(it.descripcion || '').trim().length > 0
    const qtyOk = (Number(it.cantidad) || 0) > 0
    const priceOk = (Number(it.precio_unitario) || 0) >= 0
    return descOk && qtyOk && priceOk
  })
  return hasAny && allValid
})

const step = computed(() => (datosOk.value ? (proveedorOk.value ? (detallesOk.value ? 4 : 3) : 2) : 1))
const stepPct = computed(() => (step.value / 4) * 100)

/**
 * Métricas no redundantes
 */
const subtotal = computed(() => items.value.reduce((acc, it) => acc + (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0), 0))
const total = computed(() => subtotal.value)
const partidas = computed(() => items.value.length)
const obsCount = computed(() => String(form.observaciones || '').length)
const canSubmit = computed(() => datosOk.value && proveedorOk.value && detallesOk.value && !saving.value)

function money(v: any) {
  const n = Number(v ?? 0)
  try {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
  } catch {
    return String(v ?? '')
  }
}

/**
 * Items
 */
function addItem() {
  items.value.push({ cantidad: 1, descripcion: '', precio_unitario: 0, sucursal_id: form.sucursal_id ?? null })
  nextTick(() => pulsePanel('detalles'))
}
function removeItem(idx: number) {
  if (items.value.length === 1) return
  items.value.splice(idx, 1)
}

/**
 * Validación cliente
 */
function validateAll() {
  const e: Record<string, string> = {}

  if (!form.comprador_corp_id) e.comprador_corp_id = 'Selecciona corporativo.'
  if (!form.sucursal_id) e.sucursal_id = 'Selecciona sucursal que absorbe.'
  if (!form.solicitante_id) e.solicitante_id = 'Selecciona solicitante.'
  if (!form.concepto_id) e.concepto_id = 'Selecciona concepto.'
  if (!form.proveedor_id) e.proveedor_id = 'Selecciona proveedor (obligatorio).'

  if (!items.value.some((it) => String(it.descripcion || '').trim().length > 0)) e.items = 'Agrega al menos 1 detalle.'

  items.value.forEach((it, i) => {
    if (!String(it.descripcion || '').trim()) e[`items.${i}.descripcion`] = 'Descripción requerida.'
    if ((Number(it.cantidad) || 0) <= 0) e[`items.${i}.cantidad`] = 'Cantidad inválida.'
    if ((Number(it.precio_unitario) || 0) < 0) e[`items.${i}.precio_unitario`] = 'Precio inválido.'
  })

  errors.value = e
  return Object.keys(e).length === 0
}

function firstInvalidPanel(): PanelKey {
  if (!datosOk.value) return 'datos'
  if (!proveedorOk.value) return 'proveedor'
  if (!detallesOk.value) return 'detalles'
  return 'revision'
}

/**
 * Submit
 */
function submit() {
  if (saving.value) return

  if (!validateAll()) {
    goPanel(firstInvalidPanel(), true)
    return
  }

  saving.value = true
  router.post(
    route('requisiciones.store'),
    {
      ...form,
      monto_subtotal: subtotal.value,
      monto_total: total.value,
      detalles: items.value.map((it) => ({
        cantidad: Number(it.cantidad) || 0,
        descripcion: it.descripcion,
        precio_unitario: Number(it.precio_unitario) || 0,
        subtotal: (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0),
        total: (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0),
        sucursal_id: it.sucursal_id,
      })),
    },
    {
      preserveScroll: true,
      onFinish: () => (saving.value = false),
      onError: (e) => (errors.value = { ...errors.value, ...(e as any) }),
    },
  )
}

/**
 * UX: hereda sucursal en items que estén null
 */
watch(
  () => form.sucursal_id,
  (v) => {
    items.value = items.value.map((it) => (it.sucursal_id == null ? { ...it, sucursal_id: v ?? null } : it))
  },
)

/**
 * Guía: abre siguiente panel cuando el actual queda OK
 */
watch(
  () => datosOk.value,
  async (ok) => {
    if (!ok) return
    if (openPanel.value === 'datos') {
      await nextTick()
      goPanel('proveedor', true)
    }
  },
)
watch(
  () => proveedorOk.value,
  async (ok) => {
    if (!ok) return
    if (openPanel.value === 'proveedor') {
      await nextTick()
      goPanel('detalles', true)
    }
  },
)
watch(
  () => detallesOk.value,
  async (ok) => {
    if (!ok) return
    if (openPanel.value === 'detalles') {
      await nextTick()
      openPanel.value = 'revision'
      pulsePanel('revision')
    }
  },
)

/**
 * Base input
 */
const inputBase =
  'mt-1 w-full rounded-2xl px-4 py-3 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200/80 bg-white/80 text-slate-900 placeholder:text-slate-400 focus:ring-slate-300 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'

/**
 * Modal proveedor (tabs)
 */
const provModalOpen = ref(false)
const provTab = ref<'generales' | 'bancarios'>('generales')
const provSaving = ref(false)
const provErrors = ref<Record<string, string>>({})

const provForm = reactive({
  nombre_comercial: '',
  razon_social: '',
  rfc: '',
  direccion: '',
  contacto: '',
  telefono: '',
  email: '',
  beneficiario: '',
  banco: '',
  cuenta: '',
  clabe: '',
})

function openProveedorModal() {
  provErrors.value = {}
  provTab.value = 'generales'
  Object.assign(provForm, {
    nombre_comercial: '',
    razon_social: '',
    rfc: '',
    direccion: '',
    contacto: '',
    telefono: '',
    email: '',
    beneficiario: '',
    banco: '',
    cuenta: '',
    clabe: '',
  })
  provModalOpen.value = true
}

function closeProveedorModal() {
  if (provSaving.value) return
  provModalOpen.value = false
}

function digitsOnly(v: string) {
  return String(v || '').replace(/\D+/g, '')
}

function validateProveedor() {
  const e: Record<string, string> = {}
  if (!String(provForm.nombre_comercial || '').trim()) e.nombre_comercial = 'Nombre comercial requerido.'

  const email = String(provForm.email || '').trim()
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) e.email = 'Email inválido.'

  const rfc = String(provForm.rfc || '').trim()
  if (rfc && rfc.length < 10) e.rfc = 'RFC muy corto.'

  const tel = digitsOnly(provForm.telefono)
  if (provForm.telefono && tel.length < 7) e.telefono = 'Teléfono inválido.'

  const clabe = digitsOnly(provForm.clabe)
  if (provForm.clabe && clabe.length !== 18) e.clabe = 'CLABE debe tener 18 dígitos.'

  provErrors.value = e

  // Si falla, manda al tab correcto
  if (e.nombre_comercial || e.rfc || e.telefono || e.email) provTab.value = 'generales'
  if (e.clabe) provTab.value = 'bancarios'

  return Object.keys(e).length === 0
}

function createProveedor() {
  if (provSaving.value) return
  if (!validateProveedor()) return

  provSaving.value = true

  router.post(
    route('proveedores.store'),
    {
      ...provForm,
      telefono: digitsOnly(provForm.telefono),
      cuenta: digitsOnly(provForm.cuenta),
      clabe: digitsOnly(provForm.clabe),
      rfc: String(provForm.rfc || '').trim().toUpperCase(),
    },
    {
      preserveScroll: true,
      onFinish: () => (provSaving.value = false),
      onError: (e) => (provErrors.value = { ...(e as any) }),
      onSuccess: () => {
        router.reload({
          only: ['catalogos', 'flash', 'new_proveedor_id', 'success'],
          onSuccess: async () => {
            const id = flashNewProveedorId.value
            if (id) form.proveedor_id = id
            closeProveedorModal()
            await nextTick()
            goPanel('proveedor', true)
          },
        })
      },
    },
  )
}

watch(
  () => flashNewProveedorId.value,
  (id) => {
    if (!id) return
    form.proveedor_id = id
  },
)
</script>

<template>
  <Head title="Nueva requisición" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100 truncate">
          Nueva requisición
        </h2>

        <div class="hidden sm:flex items-center gap-2">
          <span
            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-extrabold border
                   border-slate-200/70 bg-white/70 text-slate-700
                   dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-200"
          >
            {{ role }}
          </span>

          <span
            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-extrabold border"
            :class="step === 4
              ? 'border-emerald-200 bg-emerald-500/10 text-emerald-700 dark:border-emerald-500/20 dark:text-emerald-200'
              : 'border-sky-200 bg-sky-500/10 text-sky-700 dark:border-sky-500/20 dark:text-sky-200'"
          >
            {{ step === 4 ? 'Listo para enviar' : 'En captura' }}
          </span>
        </div>
      </div>
    </template>

    <div class="page-bg">
      <div class="w-full min-w-0">
        <div class="w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
          <!-- TOP: guía + progreso + acciones -->
          <div class="card-glass mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-900/50 backdrop-blur shadow-sm p-5 sm:p-6">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
              <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-neutral-100">
                  Registra tu requisición
                </h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                  Completa la información básica, elige proveedor y captura lo que se compra. El sistema te va guiando solo.
                </p>

                <div class="mt-4">
                  <div class="flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-neutral-300">
                    <span :class="step >= 1 ? 'text-slate-900 dark:text-neutral-100' : ''">Datos</span>
                    <span :class="step >= 2 ? 'text-slate-900 dark:text-neutral-100' : ''">Proveedor</span>
                    <span :class="step >= 3 ? 'text-slate-900 dark:text-neutral-100' : ''">Detalles</span>
                    <span :class="step >= 4 ? 'text-slate-900 dark:text-neutral-100' : ''">Revisión</span>
                  </div>
                  <div class="mt-2 h-2 rounded-full bg-slate-200/70 dark:bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full progress-bar" :style="{ width: `${stepPct}%` }"></div>
                  </div>
                </div>
              </div>

              <div class="flex gap-2">
                <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.index'))">
                  Volver
                </SecondaryButton>
              </div>
            </div>

            <!-- KPIs: solo lo que importa -->
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div class="mini-kpi">
                <div class="mini-kpi__label">Items</div>
                <div class="mini-kpi__value">{{ partidas }}</div>
              </div>
              <div class="mini-kpi">
                <div class="mini-kpi__label">Total estimado</div>
                <div class="mini-kpi__value">{{ money(total) }}</div>
              </div>
              <div class="mini-kpi">
                <div class="mini-kpi__label">Estatus</div>
                <div class="mini-kpi__value">{{ step === 4 ? 'Listo' : step === 3 ? 'Detalles' : step === 2 ? 'Proveedor' : 'Datos' }}</div>
              </div>
            </div>
          </div>

          <!-- MAIN GRID -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- LEFT -->
            <div class="lg:col-span-8 grid gap-4">
              <!-- PANEL: DATOS -->
              <section
                ref="datosRef"
                class="panel"
                :class="{ 'ring-2 ring-emerald-400/30': highlight === 'datos' }"
              >
                <button class="panel-head" type="button" @click="openPanel = openPanel === 'datos' ? 'proveedor' : 'datos'">
                  <div class="min-w-0">
                    <div class="panel-title">
                      1) Datos de la requisición
                      <span class="panel-badge" :class="datosOk ? 'badge-ok' : 'badge-pend'">
                        {{ datosOk ? 'OK' : 'Pendiente' }}
                      </span>
                    </div>
                    <div class="panel-subtitle">
                      Selecciona desde qué corporativo/sucursal se compra, quién solicita y el concepto.
                    </div>
                  </div>
                  <div class="panel-cta">
                    <span class="chev" :class="{ 'chev-open': openPanel === 'datos' }">⌄</span>
                  </div>
                </button>

                <transition name="acc">
                  <div v-show="openPanel === 'datos'" class="panel-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo</label>
                        <select
                          v-model="form.tipo"
                          class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border transition
                                 border-slate-200/80 bg-white/80 text-slate-900 hover:bg-white
                                 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                 dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10"
                        >
                          <option value="ANTICIPO">Anticipo</option>
                          <option value="REEMBOLSO">Reembolso</option>
                        </select>
                      </div>

                      <div>
                        <SearchableSelect
                          v-model="form.comprador_corp_id"
                          :options="corporativosActive"
                          label="Corporativo comprador"
                          placeholder="Selecciona..."
                          searchPlaceholder="Buscar corporativo..."
                          :allowNull="true"
                          nullLabel="Selecciona..."
                          rounded="3xl"
                          zIndexClass="z-[999]"
                        />
                        <p v-if="errors.comprador_corp_id" class="mt-1 text-xs text-rose-500">{{ errors.comprador_corp_id }}</p>
                      </div>

                      <div>
                        <SearchableSelect
                          v-model="form.sucursal_id"
                          :options="sucursalesActive"
                          label="Sucursal que absorbe"
                          placeholder="Selecciona..."
                          searchPlaceholder="Buscar sucursal..."
                          :allowNull="true"
                          nullLabel="Selecciona..."
                          rounded="3xl"
                          zIndexClass="z-[999]"
                          labelKey="nombre"
                          secondaryKey="codigo"
                        />
                        <p v-if="errors.sucursal_id" class="mt-1 text-xs text-rose-500">{{ errors.sucursal_id }}</p>
                      </div>

                      <div>
                        <SearchableSelect
                          v-model="form.solicitante_id"
                          :options="empleadosActive"
                          label="Solicitante"
                          placeholder="Selecciona..."
                          searchPlaceholder="Buscar empleado..."
                          :allowNull="true"
                          nullLabel="Selecciona..."
                          rounded="3xl"
                          zIndexClass="z-[999]"
                          labelKey="nombre"
                          secondaryKey="puesto"
                        />
                        <p v-if="errors.solicitante_id" class="mt-1 text-xs text-rose-500">{{ errors.solicitante_id }}</p>
                      </div>

                      <div class="sm:col-span-2">
                        <SearchableSelect
                          v-model="form.concepto_id"
                          :options="conceptosActive"
                          label="Concepto"
                          placeholder="Selecciona..."
                          searchPlaceholder="Buscar concepto..."
                          :allowNull="true"
                          nullLabel="Selecciona..."
                          rounded="3xl"
                          zIndexClass="z-[999]"
                          labelKey="nombre"
                          secondaryKey="id"
                        />
                        <p v-if="errors.concepto_id" class="mt-1 text-xs text-rose-500">{{ errors.concepto_id }}</p>
                      </div>

                      <div class="sm:col-span-2">
                        <div class="flex items-center justify-between">
                          <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Observaciones</label>
                          <span class="text-[11px] font-semibold text-slate-500 dark:text-neutral-400">{{ obsCount }}/400</span>
                        </div>
                        <textarea
                          v-model="form.observaciones"
                          rows="3"
                          :class="inputBase"
                          maxlength="400"
                          placeholder="Justificación breve: por qué se requiere, urgencia y respaldo."
                        ></textarea>
                      </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                      <button
                        type="button"
                        class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]"
                        @click="goPanel('proveedor', true)"
                      >
                        Continuar a proveedor
                      </button>
                    </div>
                  </div>
                </transition>
              </section>

              <!-- PANEL: PROVEEDOR -->
              <section
                ref="provRef"
                class="panel"
                :class="{ 'ring-2 ring-emerald-400/30': highlight === 'proveedor' }"
              >
                <button class="panel-head" type="button" @click="openPanel = openPanel === 'proveedor' ? 'detalles' : 'proveedor'">
                  <div class="min-w-0">
                    <div class="panel-title">
                      2) Proveedor
                      <span class="panel-badge" :class="proveedorOk ? 'badge-ok' : 'badge-warn'">
                        {{ proveedorOk ? 'OK' : 'Obligatorio' }}
                      </span>
                    </div>
                    <div class="panel-subtitle">
                      Elige el proveedor correcto para que pagos y comprobación salgan limpios.
                    </div>
                  </div>
                  <div class="panel-cta">
                    <span class="chev" :class="{ 'chev-open': openPanel === 'proveedor' }">⌄</span>
                  </div>
                </button>

                <transition name="acc">
                  <div v-show="openPanel === 'proveedor'" class="panel-body">
                    <div class="flex items-end gap-2">
                      <div class="flex-1 min-w-0">
                        <SearchableSelect
                          v-model="form.proveedor_id"
                          :options="proveedoresLocal"
                          label="Proveedor (obligatorio)"
                          placeholder="Selecciona..."
                          searchPlaceholder="Buscar proveedor..."
                          :allowNull="true"
                          nullLabel="Selecciona..."
                          rounded="3xl"
                          zIndexClass="z-[999]"
                          labelKey="nombre_comercial"
                          secondaryKey="rfc"
                        />
                        <p v-if="errors.proveedor_id" class="mt-1 text-xs text-rose-500">{{ errors.proveedor_id }}</p>
                      </div>

                      <button
                        type="button"
                        @click="openProveedorModal"
                        class="shrink-0 rounded-2xl px-4 py-3 text-sm font-extrabold border transition active:scale-[0.99]
                               border-slate-200 bg-white/80 text-slate-800 hover:bg-slate-50
                               dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40"
                        title="Registrar proveedor"
                      >
                        +
                      </button>
                    </div>

                    <div class="mt-4 flex justify-end">
                      <button
                        type="button"
                        class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]"
                        @click="goPanel('detalles', true)"
                      >
                        Continuar a detalles
                      </button>
                    </div>
                  </div>
                </transition>
              </section>

              <!-- PANEL: DETALLES -->
              <section
                ref="detRef"
                class="panel"
                :class="{ 'ring-2 ring-emerald-400/30': highlight === 'detalles' }"
              >
                <button class="panel-head" type="button" @click="openPanel = openPanel === 'detalles' ? 'revision' : 'detalles'">
                  <div class="min-w-0">
                    <div class="panel-title">
                      3) Detalles de compra
                      <span class="panel-badge" :class="detallesOk ? 'badge-ok' : 'badge-pend'">
                        {{ detallesOk ? 'OK' : 'Pendiente' }}
                      </span>
                    </div>
                    <div class="panel-subtitle">
                      Captura lo que se compra. Puedes asignar sucursal por partida si aplica.
                    </div>
                  </div>

                  <div class="panel-cta">
                    <button
                      type="button"
                      class="btn-dark rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]"
                      @click.stop="addItem"
                    >
                      + Agregar
                    </button>
                    <span class="chev ml-2" :class="{ 'chev-open': openPanel === 'detalles' }">⌄</span>
                  </div>
                </button>

                <transition name="acc">
                  <div v-show="openPanel === 'detalles'" class="panel-body">
                    <p v-if="errors.items" class="mb-2 text-xs text-rose-500">{{ errors.items }}</p>

                    <!-- Desktop table -->
                    <div class="hidden lg:block overflow-x-auto rounded-2xl border border-slate-200/70 dark:border-white/10">
                      <table class="w-full min-w-[920px] text-sm">
                        <thead class="bg-slate-50/70 dark:bg-neutral-950/40">
                          <tr class="text-left text-slate-600 dark:text-neutral-300">
                            <th class="px-4 py-3 font-semibold w-[110px]">Cantidad</th>
                            <th class="px-4 py-3 font-semibold">Descripción</th>
                            <th class="px-4 py-3 font-semibold w-[240px]">Sucursal</th>
                            <th class="px-4 py-3 font-semibold w-[160px] text-right">P. Unit</th>
                            <th class="px-4 py-3 font-semibold w-[160px] text-right">Importe</th>
                            <th class="px-4 py-3 font-semibold w-[110px] text-right">Acción</th>
                          </tr>
                        </thead>

                        <transition-group name="row" tag="tbody">
                          <tr
                            v-for="(it, idx) in items"
                            :key="`it-${idx}`"
                            class="border-t border-slate-200/70 dark:border-white/10 hover:bg-white/40 dark:hover:bg-neutral-950/20 transition"
                          >
                            <td class="px-4 py-3 align-top">
                              <input
                                v-model.number="it.cantidad"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-2xl px-3 py-2 border transition
                                       border-slate-200/80 bg-white/80 hover:bg-white
                                       focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                       dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10"
                              />
                              <p v-if="errors[`items.${idx}.cantidad`]" class="mt-1 text-xs text-rose-500">
                                {{ errors[`items.${idx}.cantidad`] }}
                              </p>
                            </td>

                            <td class="px-4 py-3 align-top">
                              <input
                                v-model="it.descripcion"
                                type="text"
                                placeholder="Ej: Licencia, cafetería, envío..."
                                class="w-full rounded-2xl px-3 py-2 border transition
                                       border-slate-200/80 bg-white/80 hover:bg-white
                                       focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                       dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10"
                              />
                              <p v-if="errors[`items.${idx}.descripcion`]" class="mt-1 text-xs text-rose-500">
                                {{ errors[`items.${idx}.descripcion`] }}
                              </p>
                            </td>

                            <td class="px-4 py-3 align-top">
                              <SearchableSelect
                                v-model="it.sucursal_id"
                                :options="sucursalesActive"
                                placeholder="Usar sucursal general"
                                searchPlaceholder="Buscar sucursal..."
                                :allowNull="true"
                                nullLabel="Usar sucursal general"
                                rounded="2xl"
                                zIndexClass="z-[999]"
                                labelKey="nombre"
                                secondaryKey="codigo"
                              />
                            </td>

                            <td class="px-4 py-3 align-top text-right">
                              <input
                                v-model.number="it.precio_unitario"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-2xl px-3 py-2 border transition text-right
                                       border-slate-200/80 bg-white/80 hover:bg-white
                                       focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                       dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10"
                              />
                              <p v-if="errors[`items.${idx}.precio_unitario`]" class="mt-1 text-xs text-rose-500 text-left">
                                {{ errors[`items.${idx}.precio_unitario`] }}
                              </p>
                            </td>

                            <td class="px-4 py-3 align-top text-right font-extrabold text-slate-900 dark:text-neutral-100">
                              {{ money((Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0)) }}
                            </td>

                            <td class="px-4 py-3 align-top text-right">
                              <button
                                type="button"
                                @click="removeItem(idx)"
                                class="btn-danger rounded-2xl px-3 py-2 text-xs font-extrabold transition active:scale-[0.99]"
                                :disabled="items.length === 1"
                              >
                                Quitar
                              </button>
                            </td>
                          </tr>
                        </transition-group>
                      </table>
                    </div>

                    <!-- Mobile cards -->
                    <div class="mt-3 grid gap-3 lg:hidden">
                      <transition-group name="row" tag="div" class="grid gap-3">
                        <div
                          v-for="(it, idx) in items"
                          :key="`mit-${idx}`"
                          class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/80 dark:bg-neutral-950/20 p-4"
                        >
                          <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                              <div class="text-xs font-extrabold text-slate-900 dark:text-neutral-100">Partida {{ idx + 1 }}</div>
                              <div class="mt-1 text-xs text-slate-600 dark:text-neutral-300">
                                Importe:
                                <span class="font-extrabold text-slate-900 dark:text-neutral-100">
                                  {{ money((Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0)) }}
                                </span>
                              </div>
                            </div>

                            <button
                              type="button"
                              @click="removeItem(idx)"
                              class="btn-danger rounded-2xl px-3 py-2 text-xs font-extrabold transition active:scale-[0.99]"
                              :disabled="items.length === 1"
                            >
                              Quitar
                            </button>
                          </div>

                          <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Cantidad</label>
                              <input v-model.number="it.cantidad" type="number" step="0.01" min="0" class="w-full rounded-2xl px-3 py-2 border transition
                                border-slate-200/80 bg-white/80 hover:bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10" />
                              <p v-if="errors[`items.${idx}.cantidad`]" class="mt-1 text-xs text-rose-500">{{ errors[`items.${idx}.cantidad`] }}</p>
                            </div>

                            <div>
                              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Precio unitario</label>
                              <input v-model.number="it.precio_unitario" type="number" step="0.01" min="0" class="w-full rounded-2xl px-3 py-2 border transition text-right
                                border-slate-200/80 bg-white/80 hover:bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10" />
                              <p v-if="errors[`items.${idx}.precio_unitario`]" class="mt-1 text-xs text-rose-500">{{ errors[`items.${idx}.precio_unitario`] }}</p>
                            </div>

                            <div class="sm:col-span-2">
                              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Descripción</label>
                              <input v-model="it.descripcion" type="text" placeholder="Ej: Licencia, cafetería, envío..." class="w-full rounded-2xl px-3 py-2 border transition
                                border-slate-200/80 bg-white/80 hover:bg-white focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-100 dark:hover:bg-neutral-950/40 dark:focus:ring-white/10" />
                              <p v-if="errors[`items.${idx}.descripcion`]" class="mt-1 text-xs text-rose-500">{{ errors[`items.${idx}.descripcion`] }}</p>
                            </div>

                            <div class="sm:col-span-2">
                              <SearchableSelect
                                v-model="it.sucursal_id"
                                :options="sucursalesActive"
                                label="Sucursal"
                                placeholder="Usar sucursal general"
                                searchPlaceholder="Buscar sucursal..."
                                :allowNull="true"
                                nullLabel="Usar sucursal general"
                                rounded="2xl"
                                zIndexClass="z-[999]"
                                labelKey="nombre"
                                secondaryKey="codigo"
                              />
                            </div>
                          </div>
                        </div>
                      </transition-group>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                      <div class="text-xs text-slate-600 dark:text-neutral-300">
                        Total estimado: <span class="font-extrabold text-slate-900 dark:text-neutral-100">{{ money(total) }}</span>
                      </div>
                      <button type="button" class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]" @click="openPanel = 'revision'; pulsePanel('revision')">
                        Revisar
                      </button>
                    </div>
                  </div>
                </transition>
              </section>

              <!-- PANEL: REVISIÓN -->
              <section class="panel" :class="{ 'ring-2 ring-emerald-400/30': highlight === 'revision' }">
                <button class="panel-head" type="button" @click="openPanel = openPanel === 'revision' ? 'detalles' : 'revision'">
                  <div class="min-w-0">
                    <div class="panel-title">
                      4) Revisión y envío
                      <span class="panel-badge" :class="canSubmit ? 'badge-ok' : 'badge-pend'">
                        {{ canSubmit ? 'Listo' : 'Pendiente' }}
                      </span>
                    </div>
                    <div class="panel-subtitle">
                      Si algo falta, el sistema te regresa al bloque correcto. Cero drama, cero retrabajo.
                    </div>
                  </div>
                  <div class="panel-cta">
                    <span class="chev" :class="{ 'chev-open': openPanel === 'revision' }">⌄</span>
                  </div>
                </button>

                <transition name="acc">
                  <div v-show="openPanel === 'revision'" class="panel-body">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div class="summary-card">
                        <div class="summary-label">Partidas</div>
                        <div class="summary-value">{{ partidas }}</div>
                      </div>
                      <div class="summary-card">
                        <div class="summary-label">Subtotal</div>
                        <div class="summary-value">{{ money(subtotal) }}</div>
                      </div>
                      <div class="summary-card">
                        <div class="summary-label">Total</div>
                        <div class="summary-value">{{ money(total) }}</div>
                      </div>
                    </div>

                    <div v-if="Object.keys(errors || {}).length" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50/70 text-rose-700 px-4 py-3 text-sm dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200">
                      Hay campos pendientes. Te llevamos al bloque correcto al intentar enviar.
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                      <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.index'))">Cancelar</SecondaryButton>
                      <button
                        type="button"
                        @click="submit"
                        :disabled="!canSubmit"
                        class="btn-primary rounded-2xl px-5 py-3 text-sm font-extrabold disabled:opacity-60 disabled:cursor-not-allowed transition active:scale-[0.99]"
                      >
                        {{ saving ? 'Guardando...' : 'Enviar requisición' }}
                      </button>
                    </div>
                  </div>
                </transition>
              </section>
            </div>

            <!-- RIGHT: resumen sticky (desktop) -->
            <div class="lg:col-span-4 hidden lg:block">
              <div class="card-glass sticky top-6 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-900/50 backdrop-blur shadow-sm p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Resumen</div>
                    <div class="mt-1 text-xs text-slate-600 dark:text-neutral-300">
                      {{ step === 4 ? 'Listo para envío' : step === 3 ? 'Completa detalles' : step === 2 ? 'Selecciona proveedor' : 'Completa datos' }}
                    </div>
                  </div>

                  <span class="text-[11px] font-extrabold px-2 py-1 rounded-full border"
                    :class="canSubmit
                      ? 'border-emerald-200 bg-emerald-500/10 text-emerald-700 dark:border-emerald-500/20 dark:text-emerald-200'
                      : 'border-slate-200 bg-white/60 text-slate-700 dark:border-white/10 dark:bg-neutral-950/30 dark:text-neutral-200'"
                  >
                    {{ canSubmit ? 'Listo' : 'En proceso' }}
                  </span>
                </div>

                <div class="mt-4 grid gap-3">
                  <div class="summary-card">
                    <div class="summary-label">Total estimado</div>
                    <div class="summary-value">{{ money(total) }}</div>
                  </div>

                  <div class="grid grid-cols-2 gap-3">
                    <div class="summary-mini">
                      <div class="summary-mini__label">Partidas</div>
                      <div class="summary-mini__value">{{ partidas }}</div>
                    </div>
                    <div class="summary-mini">
                      <div class="summary-mini__label">Progreso</div>
                      <div class="summary-mini__value">{{ step }}/4</div>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-2">
                    <button type="button" class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]" @click="goPanel('datos', true)">
                      Datos
                    </button>
                    <button type="button" class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]" @click="goPanel('proveedor', true)">
                      Proveedor
                    </button>
                    <button type="button" class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]" @click="goPanel('detalles', true)">
                      Detalles
                    </button>
                    <button type="button" class="btn-soft rounded-2xl px-4 py-2 text-sm font-extrabold transition active:scale-[0.99]" @click="openPanel = 'revision'; pulsePanel('revision')">
                      Revisar
                    </button>
                  </div>

                  <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="btn-primary rounded-2xl px-5 py-3 text-sm font-extrabold disabled:opacity-60 disabled:cursor-not-allowed transition active:scale-[0.99]"
                  >
                    {{ saving ? 'Guardando...' : 'Enviar requisición' }}
                  </button>

                  <div class="mt-1 text-[11px] text-slate-500 dark:text-neutral-400">
                    Si el monto es alto, una observación clara acelera aprobación.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile action bar: sticky (no invade sidebar) -->
          <div class="lg:hidden sticky bottom-0 z-[30] mt-6 pb-3">
            <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-lg p-3">
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                  <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300 truncate">Total estimado</div>
                  <div class="text-base font-extrabold text-slate-900 dark:text-neutral-100 truncate">{{ money(total) }}</div>
                  <div class="text-[11px] text-slate-500 dark:text-neutral-400">{{ partidas }} partidas · Paso {{ step }}/4</div>
                </div>

                <button
                  type="button"
                  @click="submit"
                  :disabled="!canSubmit"
                  class="btn-primary rounded-2xl px-5 py-3 text-sm font-extrabold disabled:opacity-60 disabled:cursor-not-allowed transition active:scale-[0.99]"
                >
                  {{ saving ? 'Guardando...' : 'Enviar' }}
                </button>
              </div>
            </div>
          </div>

          <!-- MODAL: proveedor -->
          <transition name="fadeUp">
            <div v-if="provModalOpen" class="fixed inset-0 z-[120]">
              <div class="absolute inset-0 bg-black/40" @click="closeProveedorModal"></div>

              <div class="absolute inset-0 flex items-end sm:items-center justify-center p-3 sm:p-6">
                <div class="w-full max-w-2xl rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950 shadow-xl overflow-hidden">
                  <div class="p-4 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <div class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Registrar proveedor</div>
                        <div class="text-xs text-slate-600 dark:text-neutral-300">Captura lo esencial. Lo bancario es opcional.</div>
                      </div>

                      <button
                        type="button"
                        @click="closeProveedorModal"
                        class="rounded-2xl px-3 py-2 text-sm font-extrabold border transition active:scale-[0.99]
                               border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                               dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-950/40"
                      >
                        Cerrar
                      </button>
                    </div>

                    <!-- Tabs -->
                    <div class="mt-4 flex gap-2">
                      <button type="button" class="tab" :class="provTab === 'generales' ? 'tab-on' : 'tab-off'" @click="provTab = 'generales'">
                        Generales
                      </button>
                      <button type="button" class="tab" :class="provTab === 'bancarios' ? 'tab-on' : 'tab-off'" @click="provTab = 'bancarios'">
                        Bancarios
                      </button>
                    </div>

                    <transition name="acc">
                      <div v-show="provTab === 'generales'" class="mt-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                          <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre comercial *</label>
                            <input v-model="provForm.nombre_comercial" type="text" :class="inputBase" placeholder="Ej: Papelería Central" />
                            <p v-if="provErrors.nombre_comercial" class="mt-1 text-xs text-rose-500">{{ provErrors.nombre_comercial }}</p>
                          </div>

                          <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Razón social</label>
                            <input v-model="provForm.razon_social" type="text" :class="inputBase" placeholder="Ej: Papelería Central S.A. de C.V." />
                          </div>

                          <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">RFC</label>
                            <input v-model="provForm.rfc" type="text" :class="inputBase" placeholder="XAXX010101000" />
                            <p v-if="provErrors.rfc" class="mt-1 text-xs text-rose-500">{{ provErrors.rfc }}</p>
                          </div>

                          <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Contacto</label>
                            <input v-model="provForm.contacto" type="text" :class="inputBase" placeholder="Ej: Juan Pérez" />
                          </div>

                          <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Teléfono</label>
                            <input v-model="provForm.telefono" type="text" :class="inputBase" placeholder="7771234567" />
                            <p v-if="provErrors.telefono" class="mt-1 text-xs text-rose-500">{{ provErrors.telefono }}</p>
                          </div>

                          <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Email</label>
                            <input v-model="provForm.email" type="email" :class="inputBase" placeholder="contacto@proveedor.com" />
                            <p v-if="provErrors.email" class="mt-1 text-xs text-rose-500">{{ provErrors.email }}</p>
                          </div>

                          <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Dirección</label>
                            <input v-model="provForm.direccion" type="text" :class="inputBase" placeholder="Calle, número, colonia, municipio, estado" />
                          </div>
                        </div>
                      </div>
                    </transition>

                    <transition name="acc">
                      <div v-show="provTab === 'bancarios'" class="mt-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                          <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Beneficiario</label>
                            <input v-model="provForm.beneficiario" type="text" :class="inputBase" placeholder="Nombre del beneficiario" />
                          </div>

                          <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Banco</label>
                            <input v-model="provForm.banco" type="text" :class="inputBase" placeholder="Ej: BBVA" />
                          </div>

                          <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Cuenta</label>
                            <input v-model="provForm.cuenta" type="text" :class="inputBase" placeholder="Solo números si aplica" />
                          </div>

                          <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">CLABE</label>
                            <input v-model="provForm.clabe" type="text" :class="inputBase" placeholder="18 dígitos" />
                            <p v-if="provErrors.clabe" class="mt-1 text-xs text-rose-500">{{ provErrors.clabe }}</p>
                          </div>
                        </div>
                      </div>
                    </transition>

                    <div class="mt-6 flex items-center justify-end gap-2">
                      <SecondaryButton class="rounded-2xl" @click="closeProveedorModal">Cancelar</SecondaryButton>

                      <button
                        type="button"
                        @click="createProveedor"
                        :disabled="provSaving"
                        class="btn-primary rounded-2xl px-5 py-3 text-sm font-extrabold disabled:opacity-60 disabled:cursor-not-allowed transition active:scale-[0.99]"
                      >
                        {{ provSaving ? 'Creando...' : 'Crear y usar' }}
                      </button>
                    </div>

                    <div class="mt-3 text-[11px] text-slate-500 dark:text-neutral-400">
                      Si tu ruta no es <span class="font-semibold">proveedores.store</span>, ajusta el route() del modal.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.page-bg {
  position: relative;
  min-height: 100%;
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(16, 185, 129, 0.10), transparent 60%),
    radial-gradient(900px 520px at 90% 20%, rgba(59, 130, 246, 0.10), transparent 55%),
    radial-gradient(900px 520px at 50% 90%, rgba(99, 102, 241, 0.10), transparent 55%),
    linear-gradient(to bottom, rgba(248, 250, 252, 1), rgba(241, 245, 249, 1));
}
:global(html.dark) .page-bg {
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(16, 185, 129, 0.14), transparent 60%),
    radial-gradient(900px 520px at 90% 20%, rgba(59, 130, 246, 0.16), transparent 55%),
    radial-gradient(900px 520px at 50% 90%, rgba(99, 102, 241, 0.14), transparent 55%),
    linear-gradient(to bottom, rgba(10, 10, 10, 1), rgba(14, 14, 16, 1));
}

.card-glass {
  transition: transform 160ms ease, box-shadow 160ms ease, border-color 160ms ease;
}
.card-glass:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.06);
}
:global(html.dark) .card-glass:hover {
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.35);
}

.progress-bar {
  background: linear-gradient(90deg, rgba(16, 185, 129, 1), rgba(59, 130, 246, 1));
  transition: width 220ms ease;
}

.panel {
  border-radius: 24px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  background: rgba(255, 255, 255, 0.70);
  backdrop-filter: blur(10px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.04);
  overflow: hidden;
}
:global(html.dark) .panel {
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(10,10,10,0.18);
  box-shadow: 0 16px 50px rgba(0,0,0,0.35);
}
.panel-head {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 18px 18px;
  text-align: left;
}
.panel-body {
  padding: 0 18px 18px 18px;
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 900;
  color: rgba(15, 23, 42, 1);
}
:global(html.dark) .panel-title {
  color: rgba(245,245,245,1);
}
.panel-subtitle {
  margin-top: 4px;
  font-size: 12px;
  color: rgba(100,116,139,1);
}
:global(html.dark) .panel-subtitle {
  color: rgba(163,163,163,1);
}

.panel-cta {
  display: flex;
  align-items: center;
  gap: 10px;
}
.chev {
  display: inline-block;
  transform: rotate(0deg);
  transition: transform 180ms ease;
  font-weight: 900;
  opacity: 0.8;
}
.chev-open {
  transform: rotate(180deg);
}

.panel-badge {
  font-size: 11px;
  font-weight: 900;
  padding: 3px 10px;
  border-radius: 999px;
  border: 1px solid rgba(226,232,240,0.9);
  background: rgba(255,255,255,0.6);
}
:global(html.dark) .panel-badge {
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(10,10,10,0.25);
}
.badge-ok {
  color: rgba(5, 150, 105, 1);
  border-color: rgba(16,185,129,0.25);
  background: rgba(16,185,129,0.08);
}
.badge-pend {
  color: rgba(100,116,139,1);
}
.badge-warn {
  color: rgba(180,83,9,1);
  border-color: rgba(245,158,11,0.25);
  background: rgba(245,158,11,0.08);
}

.btn-primary {
  background: linear-gradient(135deg, rgba(16, 185, 129, 1), rgba(59, 130, 246, 1));
  color: white;
  box-shadow: 0 10px 30px rgba(16, 185, 129, 0.18);
}
.btn-primary:hover {
  filter: brightness(1.02);
  box-shadow: 0 12px 34px rgba(59, 130, 246, 0.18);
}

.btn-dark {
  background: linear-gradient(135deg, rgba(15, 23, 42, 1), rgba(30, 41, 59, 1));
  color: white;
}
:global(html.dark) .btn-dark {
  background: linear-gradient(135deg, rgba(245, 245, 245, 1), rgba(229, 231, 235, 1));
  color: rgba(10, 10, 10, 1);
}

.btn-soft {
  border: 1px solid rgba(226,232,240,0.9);
  background: rgba(255,255,255,0.65);
  color: rgba(15,23,42,1);
}
:global(html.dark) .btn-soft {
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(10,10,10,0.20);
  color: rgba(245,245,245,1);
}

.btn-danger {
  background: rgba(255, 255, 255, 0.7);
  color: rgba(190, 18, 60, 1);
  border: 1px solid rgba(254, 205, 211, 1);
}
.btn-danger:hover {
  background: rgba(255, 241, 242, 1);
}
:global(html.dark) .btn-danger {
  background: rgba(10, 10, 10, 0.3);
  color: rgba(253, 164, 175, 1);
  border: 1px solid rgba(190, 18, 60, 0.25);
}
:global(html.dark) .btn-danger:hover {
  background: rgba(190, 18, 60, 0.12);
}

.mini-kpi {
  border: 1px solid rgba(226, 232, 240, 0.8);
  background: rgba(255, 255, 255, 0.65);
  border-radius: 16px;
  padding: 12px 14px;
}
:global(html.dark) .mini-kpi {
  border: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(10, 10, 10, 0.20);
}
.mini-kpi__label {
  font-size: 11px;
  font-weight: 800;
  color: rgba(100, 116, 139, 1);
}
:global(html.dark) .mini-kpi__label {
  color: rgba(163, 163, 163, 1);
}
.mini-kpi__value {
  margin-top: 2px;
  font-size: 16px;
  font-weight: 900;
  color: rgba(15, 23, 42, 1);
}
:global(html.dark) .mini-kpi__value {
  color: rgba(245, 245, 245, 1);
}

.summary-card {
  border: 1px solid rgba(226, 232, 240, 0.8);
  background: rgba(255, 255, 255, 0.65);
  border-radius: 18px;
  padding: 14px;
}
:global(html.dark) .summary-card {
  border: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(10, 10, 10, 0.20);
}
.summary-label {
  font-size: 11px;
  font-weight: 800;
  color: rgba(100, 116, 139, 1);
}
:global(html.dark) .summary-label {
  color: rgba(163, 163, 163, 1);
}
.summary-value {
  margin-top: 2px;
  font-size: 18px;
  font-weight: 900;
  color: rgba(15, 23, 42, 1);
}
:global(html.dark) .summary-value {
  color: rgba(245, 245, 245, 1);
}

.summary-mini {
  border: 1px solid rgba(226, 232, 240, 0.8);
  background: rgba(255, 255, 255, 0.55);
  border-radius: 16px;
  padding: 12px 14px;
}
:global(html.dark) .summary-mini {
  border: 1px solid rgba(255, 255, 255, 0.10);
  background: rgba(10, 10, 10, 0.16);
}
.summary-mini__label {
  font-size: 11px;
  font-weight: 800;
  color: rgba(100, 116, 139, 1);
}
:global(html.dark) .summary-mini__label {
  color: rgba(163, 163, 163, 1);
}
.summary-mini__value {
  margin-top: 2px;
  font-size: 14px;
  font-weight: 900;
  color: rgba(15, 23, 42, 1);
}
:global(html.dark) .summary-mini__value {
  color: rgba(245, 245, 245, 1);
}

.tab {
  border-radius: 999px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 900;
  transition: all 160ms ease;
}
.tab-on {
  background: rgba(59,130,246,0.12);
  border: 1px solid rgba(59,130,246,0.25);
  color: rgba(29,78,216,1);
}
:global(html.dark) .tab-on {
  background: rgba(59,130,246,0.18);
  border: 1px solid rgba(59,130,246,0.25);
  color: rgba(191,219,254,1);
}
.tab-off {
  background: rgba(255,255,255,0.6);
  border: 1px solid rgba(226,232,240,0.9);
  color: rgba(51,65,85,1);
}
:global(html.dark) .tab-off {
  background: rgba(10,10,10,0.18);
  border: 1px solid rgba(255,255,255,0.10);
  color: rgba(229,231,235,1);
}

.acc-enter-active,
.acc-leave-active {
  transition: all 200ms ease;
}
.acc-enter-from,
.acc-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

.fadeUp-enter-active,
.fadeUp-leave-active {
  transition: all 160ms ease;
}
.fadeUp-enter-from,
.fadeUp-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

.row-enter-active,
.row-leave-active {
  transition: all 180ms ease;
}
.row-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.row-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

:global(html.dark select option) {
  background: #0a0a0a;
  color: #f5f5f5;
}
</style>
