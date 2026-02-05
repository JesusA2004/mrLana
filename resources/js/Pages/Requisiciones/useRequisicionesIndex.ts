// resources/js/Pages/Requisiciones/useRequisicionesIndex.ts

import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { RequisicionesPageProps, RequisicionRow } from './Requisiciones.types'

/**
 * Pequeño helper para diferir la ejecución de búsquedas cuando el usuario escribe o mueve filtros.
 */
function debounce<T extends (...args: any[]) => void>(fn: T, wait = 350) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), wait)
  }
}

/**
 * Normaliza los links de paginación retornados por Laravel (pueden venir en distintos formatos).
 */
function normalizeLinks(raw: any): any[] {
  if (Array.isArray(raw)) return raw
  if (raw && Array.isArray(raw.links)) return raw.links

  if (raw && typeof raw === 'object') {
    const values = Object.values(raw)
    if (Array.isArray(values) && values.every((v) => v && typeof v === 'object')) return values
  }

  return []
}

/**
 * Formatea una fecha JS a ISO (yyyy-mm-dd) para inputs de tipo date.
 */
function iso(d: Date) {
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

/**
 * Hook principal para el índice de requisiciones.
 */
export function useRequisicionesIndex(props: RequisicionesPageProps) {
  const page = usePage<any>()

  // Rol del usuario, según props de Inertia
  const userRole = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())

  // Permisos derivados del rol
  const canDelete = computed(() => ['ADMIN','CONTADOR'].includes(userRole.value))
  const canPay    = computed(() => userRole.value === 'CONTADOR')
  // Todos pueden subir comprobantes, pero un contador/admin también
  const canUploadComprobantes = computed(() => ['ADMIN','CONTADOR','COLABORADOR'].includes(userRole.value))

  // Estado reactivo con filtros y ordenamiento
  const state = reactive({
    q: props.filters.q ?? '',
    tab: (props.filters.tab ?? 'TODAS') as RequisicionesPageProps['filters']['tab'],
    status: props.filters.status ?? '',
    tipo: props.filters.tipo ?? '',
    comprador_corp_id: props.filters.comprador_corp_id ?? '',
    sucursal_id: props.filters.sucursal_id ?? '',
    solicitante_id: props.filters.solicitante_id ?? '',
    fecha_from: props.filters.fecha_from ?? '',
    fecha_to: props.filters.fecha_to ?? '',
    perPage: Number(props.filters.perPage ?? 15),
    sort: props.filters.sort ?? 'id',
    dir: (props.filters.dir ?? 'desc') as 'asc' | 'desc',
  })

  // Lista de filas de requisiciones (se transforma a RequisicionRow)
  const rows = computed<RequisicionRow[]>(() => (props.requisiciones?.data ?? []) as RequisicionRow[])
  const safeLinks = computed(() => props.requisiciones?.links ?? [])
  const safePagerLinks = computed(() => {
    const links = normalizeLinks(safeLinks.value)
    return links
      .filter((l: any) => l && typeof l.label === 'string')
      .map((l: any) => ({
        ...l,
        cleanLabel: String(l.label).replace(/<[^>]*>/g, '').trim(),
      }))
  })

  // Tabs definidas según los grupos de estados:
  const tabs = computed(() => [
    { key: 'PENDIENTES', label: 'Pendientes', count: props.counts?.pendientes ?? 0 },
    { key: 'AUTORIZADAS', label: 'Autorizadas', count: props.counts?.autorizadas ?? 0 },
    { key: 'RECHAZADAS', label: 'Rechazadas', count: props.counts?.rechazadas ?? 0 },
    { key: 'TODAS', label: 'Todas', count: props.counts?.todas ?? 0 },
  ])

  // Catálogos filtrados a sólo activos (útiles para selects)
  const corporativosActive = computed(() => (props.catalogos?.corporativos ?? []).filter((c) => c.activo !== false))
  const sucursalesActive   = computed(() => (props.catalogos?.sucursales ?? []).filter((s) => s.activo !== false))
  const empleadosActive    = computed(() => (props.catalogos?.empleados ?? []).filter((e) => e.activo !== false))

  // Clases base para inputs y selects (Tailwind con modo dark)
  const selectBase =
    'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
    'border-slate-200 bg-white text-slate-900 focus:ring-slate-200 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10'

  const inputBase =
    'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
    'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'

  // Determina si hay filtros activos para mostrar el botón “Limpiar”
  const hasActiveFilters = computed(() => {
    return Boolean(
      state.q ||
        state.status ||
        state.tipo ||
        state.comprador_corp_id ||
        state.sucursal_id ||
        state.solicitante_id ||
        state.fecha_from ||
        state.fecha_to ||
        (state.tab && state.tab !== 'TODAS')
    )
  })

  /**
   * Obtiene los parámetros de búsqueda a enviar a la ruta index.
   * Se omiten valores vacíos para que la URL permanezca limpia.
   */
  function params() {
    return {
      q: state.q || undefined,
      tab: state.tab || undefined,
      status: state.status || undefined,
      tipo: state.tipo || undefined,
      comprador_corp_id: state.comprador_corp_id || undefined,
      sucursal_id: state.sucursal_id || undefined,
      solicitante_id: state.solicitante_id || undefined,
      fecha_from: state.fecha_from || undefined,
      fecha_to: state.fecha_to || undefined,
      perPage: state.perPage || undefined,
      sort: state.sort || undefined,
      dir: state.dir || undefined,
    }
  }

  /**
   * Realiza la navegación a una URL de paginación conservando scroll y estado.
   */
  function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
  }

  // Selección múltiple de filas
  const selectedIds = ref<Set<number>>(new Set())

  // Ejecuta la búsqueda cuando los filtros cambian, usando debounce para no enviar demasiadas peticiones
  const runSearch = debounce(() => {
    router.get(route('requisiciones.index'), params(), {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    })
    selectedIds.value.clear()
  }, 350)

  // Observa cambios en cualquier filtro y ejecuta la búsqueda
  watch(
    () => [
      state.q,
      state.tab,
      state.status,
      state.tipo,
      state.comprador_corp_id,
      state.sucursal_id,
      state.solicitante_id,
      state.fecha_from,
      state.fecha_to,
      state.perPage,
      state.sort,
      state.dir,
    ],
    () => runSearch()
  )

  function clearFilters() {
    state.q = ''
    state.tab = 'TODAS'
    state.status = ''
    state.tipo = ''
    state.comprador_corp_id = ''
    state.sucursal_id = ''
    state.solicitante_id = ''
    state.fecha_from = ''
    state.fecha_to = ''
    state.perPage = 15
    state.sort = 'id'
    state.dir = 'desc'
  }

  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  const selectedCount = computed(() => selectedIds.value.size)
  const isAllSelectedOnPage = computed(() => {
    const data = rows.value
    if (data.length === 0) return false
    return data.every((r) => selectedIds.value.has(r.id))
  })

  function toggleRow(id: number, checked: boolean) {
    const s = selectedIds.value
    if (checked) s.add(id)
    else s.delete(id)
  }

  function toggleAllOnPage(checked: boolean) {
    const s = selectedIds.value
    if (!checked) {
      rows.value.forEach((r) => s.delete(r.id))
      return
    }
    rows.value.forEach((r) => s.add(r.id))
  }

  function clearSelection() {
    selectedIds.value.clear()
  }

  /**
   * Elimina en bloque las requisiciones seleccionadas (solo admin/contador).
   * Envía el arreglo de IDs y luego limpia la selección al terminar.
   */
  function destroySelected() {
    const ids = Array.from(selectedIds.value)
    if (ids.length === 0) return
    router.delete(route('requisiciones.bulkDestroy'), {
      data: { ids },
      preserveScroll: true,
      onSuccess: () => selectedIds.value.clear(),
    })
  }

  function setTab(tab: RequisicionesPageProps['filters']['tab']) {
    state.tab = tab
  }

  /**
   * Formatea un número como moneda MXN.
   */
  function money(v: any) {
    const n = Number(v ?? 0)
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
    } catch {
      return String(v ?? '')
    }
  }

  /**
   * Determina las clases CSS para cada estado. Ajustado a los nuevos estados.
   */
  function statusPill(status: string) {
    const s = String(status || '').toUpperCase()
    if (s === 'BORRADOR')              return 'bg-zinc-500/10 text-zinc-700 border-zinc-300/50 dark:text-zinc-200 dark:border-white/10'
    if (s === 'ELIMINADA')             return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    if (s === 'CAPTURADA')             return 'bg-slate-500/10 text-slate-700 border-slate-300/50 dark:text-slate-200 dark:border-white/10'
    if (s === 'PAGO_AUTORIZADO')       return 'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:text-sky-200'
    if (s === 'PAGO_RECHAZADO')        return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    if (s === 'PAGADA')               return 'bg-cyan-600/10 text-cyan-700 border-cyan-600/20 dark:text-cyan-200'
    if (s === 'POR_COMPROBAR')         return 'bg-amber-500/10 text-amber-800 border-amber-500/20 dark:text-amber-200'
    if (s === 'COMPROBACION_ACEPTADA') return 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:text-emerald-200'
    if (s === 'COMPROBACION_RECHAZADA')return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    return 'bg-slate-500/10 text-slate-700 border-slate-300/50 dark:text-slate-200 dark:border-white/10'
  }

  // Navegación a las vistas Show, Create, Pay, Comprobar, Print
  function goShow(id: number) {
    router.visit(route('requisiciones.show', id))
  }

  function goCreate() {
    router.visit(route('requisiciones.registrar'))
  }

  function goPay(id: number) {
    router.visit(route('requisiciones.pagar', id))
  }

  function goComprobar(id: number) {
    router.visit(route('requisiciones.comprobar', id))
  }

  function printReq(id: number) {
    const url = route('requisiciones.print', id)
    const w = window.open(url, '_blank', 'noopener,noreferrer')
    w?.focus()
  }

  /**
   * Elimina (marca como eliminada) una requisición individual. Se confirma con alert.
   */
  function destroyRow(row: RequisicionRow) {
    if (!canDelete.value) return
    if (!confirm(`¿Eliminar requisición ${row.folio}? Esta acción no se puede deshacer.`)) return
    router.delete(route('requisiciones.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => selectedIds.value.delete(row.id),
    })
  }

  // Manejo del popover de fechas
  const dateOpen = ref(false)
  const dateAnchorRef = ref<HTMLElement | null>(null)
  const datePanelRef = ref<HTMLElement | null>(null)
  const tempFrom = ref<string>('')
  const tempTo = ref<string>('')

  const dateLabel = computed(() => {
    const from = state.fecha_from || ''
    const to = state.fecha_to || ''
    if (!from && !to) return 'Rango de fechas'
    if (from && !to) return `Desde ${from}`
    if (!from && to) return `Hasta ${to}`
    return `${from} → ${to}`
  })

  function openDate() {
    tempFrom.value = state.fecha_from || ''
    tempTo.value = state.fecha_to || ''
    dateOpen.value = true
  }

  function closeDate() {
    dateOpen.value = false
  }

  function applyDate() {
    state.fecha_from = tempFrom.value
    state.fecha_to = tempTo.value
    closeDate()
  }

  function clearDate() {
    tempFrom.value = ''
    tempTo.value = ''
    state.fecha_from = ''
    state.fecha_to = ''
    closeDate()
  }

  // Presets para rangos de fechas comunes
  function presetToday() {
    const v = iso(new Date())
    tempFrom.value = v
    tempTo.value = v
  }

  function presetLast7() {
    const end = new Date()
    const start = new Date()
    start.setDate(start.getDate() - 6)
    tempFrom.value = iso(start)
    tempTo.value = iso(end)
  }

  function presetThisMonth() {
    const now = new Date()
    const start = new Date(now.getFullYear(), now.getMonth(), 1)
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0)
    tempFrom.value = iso(start)
    tempTo.value = iso(end)
  }

  /**
   * Cierra el popover si se hace clic fuera de él o se presiona Escape.
   */
  function onDocClick(e: MouseEvent) {
    if (!dateOpen.value) return
    const t = e.target as Node
    if (dateAnchorRef.value?.contains(t)) return
    if (datePanelRef.value?.contains(t)) return
    closeDate()
  }

  function onEsc(e: KeyboardEvent) {
    if (e.key !== 'Escape') return
    if (!dateOpen.value) return
    closeDate()
  }

  onMounted(() => {
    document.addEventListener('click', onDocClick)
    document.addEventListener('keydown', onEsc)
  })

  onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick)
    document.removeEventListener('keydown', onEsc)
  })

  return {
    canDelete,
    canPay,
    canUploadComprobantes,

    state,
    rows,
    safeLinks,
    safePagerLinks,

    tabs,
    corporativosActive,
    sucursalesActive,
    empleadosActive,

    inputBase,
    selectBase,

    hasActiveFilters,
    clearFilters,
    sortLabel,
    toggleSort,

    selectedIds,
    selectedCount,
    isAllSelectedOnPage,
    toggleRow,
    toggleAllOnPage,
    clearSelection,
    destroySelected,

    goTo,
    goShow,
    goCreate,
    goPay,
    goComprobar,
    printReq,

    money,
    statusPill,

    destroyRow,

    setTab,

    dateOpen,
    dateAnchorRef,
    datePanelRef,
    tempFrom,
    tempTo,
    dateLabel,
    openDate,
    closeDate,
    applyDate,
    clearDate,
    presetToday,
    presetLast7,
    presetThisMonth,
  }
}
