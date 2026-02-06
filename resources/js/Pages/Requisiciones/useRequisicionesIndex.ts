import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { RequisicionesPageProps, RequisicionRow } from './Requisiciones.types'

function debounce<T extends (...args: any[]) => void>(fn: T, wait = 350) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), wait)
  }
}

function normalizeLinks(raw: any): any[] {
  if (!raw) return []
  if (Array.isArray(raw)) return raw
  if (raw && Array.isArray(raw.links)) return raw.links
  if (raw && typeof raw === 'object') {
    const values = Object.values(raw)
    if (Array.isArray(values) && values.every((v) => v && typeof v === 'object')) return values as any[]
  }
  return []
}

function iso(d: Date) {
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

/** Extrae filas y links aunque props venga con formas distintas */
function extractPaginated(anyPaginated: any): { data: any[]; links: any[] } {
  if (!anyPaginated) return { data: [], links: [] }

  // Caso ideal: { data: [...], links: [...], meta: {...} }
  if (Array.isArray(anyPaginated.data)) {
    return { data: anyPaginated.data, links: normalizeLinks(anyPaginated.links ?? anyPaginated.meta?.links ?? []) }
  }

  // Caso: { data: { data: [...], links: [...]} }
  if (anyPaginated.data && Array.isArray(anyPaginated.data.data)) {
    return { data: anyPaginated.data.data, links: normalizeLinks(anyPaginated.data.links ?? anyPaginated.data.meta?.links ?? []) }
  }

  // Caso raro: directamente array
  if (Array.isArray(anyPaginated)) {
    return { data: anyPaginated, links: [] }
  }

  return { data: [], links: normalizeLinks(anyPaginated.links ?? anyPaginated.meta?.links ?? []) }
}

export function useRequisicionesIndex(props: RequisicionesPageProps) {
  const page = usePage<any>()
  const userRole = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
  const empleadoId = computed(() => page.props?.auth?.user?.empleado_id ?? null)

  const canDelete = computed(() => ['ADMIN', 'CONTADOR'].includes(userRole.value))
  const canPay = computed(() => ['ADMIN', 'CONTADOR'].includes(userRole.value))
  const canUploadComprobantes = computed(() => ['ADMIN', 'CONTADOR', 'COLABORADOR'].includes(userRole.value))

  const state = reactive({
    q: props.filters?.q ?? '',
    tab: (props.filters?.tab ?? 'ACTIVAS') as any,

    status: props.filters?.status ?? '',
    comprador_corp_id: props.filters?.comprador_corp_id ?? '',
    sucursal_id: props.filters?.sucursal_id ?? '',
    solicitante_id: props.filters?.solicitante_id ?? '',

    fecha_from: props.filters?.fecha_from ?? '',
    fecha_to: props.filters?.fecha_to ?? '',

    perPage: Number(props.filters?.perPage ?? 15),
    sort: props.filters?.sort ?? 'id',
    dir: (props.filters?.dir ?? 'desc') as 'asc' | 'desc',
  })

  // Si es colaborador, forzamos solicitante a su empleado (si existe)
  if (userRole.value === 'COLABORADOR' && empleadoId.value) {
    state.solicitante_id = empleadoId.value
  }

  const pag = computed(() => extractPaginated((props as any).requisiciones))
  const rows = computed<RequisicionRow[]>(() => (pag.value.data ?? []) as RequisicionRow[])

  const safePagerLinks = computed(() => {
    const links = normalizeLinks(pag.value.links)
    return links
      .filter((l: any) => l && typeof l.label === 'string')
      .map((l: any) => ({
        ...l,
        cleanLabel: String(l.label).replace(/<[^>]*>/g, '').trim(),
      }))
  })

  // Tabs (ajustados a tus estatus reales)
  const tabs = computed(() => {
    const counts = (props as any)?.counts ?? {}
    const base = [
      { key: 'ACTIVAS', label: 'Activas', count: counts.activas ?? counts.todas ?? 0, enabled: true },
      { key: 'PAGO', label: 'Pago', count: counts.pago ?? 0, enabled: true },
      { key: 'COMPROBACION', label: 'Comprobación', count: counts.comprobacion ?? 0, enabled: true },
      { key: 'TODAS', label: 'Todas', count: counts.todas ?? 0, enabled: true },
    ]
    // eliminadas solo admin/conta
    if (canDelete.value) {
      base.splice(3, 0, { key: 'ELIMINADAS', label: 'Eliminadas', count: counts.eliminadas ?? 0, enabled: true })
    }
    return base
  })

  const corporativosActive = computed(() => (props.catalogos?.corporativos ?? []).filter((c: any) => c.activo !== false))
  const sucursalesActive = computed(() => (props.catalogos?.sucursales ?? []).filter((s: any) => s.activo !== false))
  const empleadosActive = computed(() => (props.catalogos?.empleados ?? []).filter((e: any) => e.activo !== false))

  const sucursalesFiltered = computed(() => {
    const corpId = Number(state.comprador_corp_id || 0)
    if (!corpId) return []
    return sucursalesActive.value.filter((s: any) => Number(s.corporativo_id) === corpId)
  })

  // Si cambia corporativo, limpia sucursal si ya no aplica
  watch(
    () => state.comprador_corp_id,
    () => {
      if (!state.comprador_corp_id) {
        state.sucursal_id = ''
        return
      }
      const sid = Number(state.sucursal_id || 0)
      if (!sid) return
      const s = sucursalesActive.value.find((x: any) => Number(x.id) === sid)
      if (s && Number(s.corporativo_id) !== Number(state.comprador_corp_id)) {
        state.sucursal_id = ''
      }
    }
  )

  const inputBase =
    'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
    'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'

  const hasActiveFilters = computed(() => {
    return Boolean(
      state.q ||
        state.status ||
        state.comprador_corp_id ||
        state.sucursal_id ||
        (userRole.value !== 'COLABORADOR' && state.solicitante_id) ||
        state.fecha_from ||
        state.fecha_to ||
        (state.tab && state.tab !== 'ACTIVAS')
    )
  })

  function params() {
    return {
      q: state.q || undefined,
      tab: state.tab || undefined,
      status: state.status || undefined,
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

  function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
  }

  const selectedIds = ref<Set<number>>(new Set())

  const runSearch = debounce(() => {
    router.get(route('requisiciones.index'), params(), {
      preserveScroll: true,
      preserveState: true,
      replace: true,
    })
    selectedIds.value.clear()
  }, 350)

  watch(
    () => [
      state.q,
      state.tab,
      state.status,
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
    state.tab = 'ACTIVAS'
    state.status = ''
    state.comprador_corp_id = ''
    state.sucursal_id = ''
    if (userRole.value !== 'COLABORADOR') state.solicitante_id = ''
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
    return data.every((r: any) => selectedIds.value.has(r.id))
  })

  function toggleRow(id: number, checked: boolean) {
    const s = selectedIds.value
    if (checked) s.add(id)
    else s.delete(id)
  }

  function toggleAllOnPage(checked: boolean) {
    const s = selectedIds.value
    if (!checked) {
      rows.value.forEach((r: any) => s.delete(r.id))
      return
    }
    rows.value.forEach((r: any) => s.add(r.id))
  }

  function clearSelection() {
    selectedIds.value.clear()
  }

  function destroySelected() {
    const ids = Array.from(selectedIds.value)
    if (ids.length === 0) return
    router.delete(route('requisiciones.bulkDestroy'), {
      data: { ids },
      preserveScroll: true,
      onSuccess: () => selectedIds.value.clear(),
    })
  }

  function money(v: any) {
    const n = Number(v ?? 0)
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
    } catch {
      return String(v ?? '')
    }
  }

  const statusOptions = computed(() => {
    const base = [
      { value: 'BORRADOR', label: 'Borrador' },
      { value: 'CAPTURADA', label: 'Capturada' },
      { value: 'PAGO_AUTORIZADO', label: 'Pago autorizado' },
      { value: 'PAGO_RECHAZADO', label: 'Pago rechazado' },
      { value: 'PAGADA', label: 'Pagada' },
      { value: 'POR_COMPROBAR', label: 'Por comprobar' },
      { value: 'COMPROBACION_ACEPTADA', label: 'Comprobación aceptada' },
      { value: 'COMPROBACION_RECHAZADA', label: 'Comprobación rechazada' },
    ] as Array<{ value: string; label: string }>

    if (canDelete.value) base.push({ value: 'ELIMINADA', label: 'Eliminada' })
    return base
  })

  function statusLabel(status: string) {
    const s = String(status || '').toUpperCase()
    const map: Record<string, string> = {
      BORRADOR: 'Borrador',
      ELIMINADA: 'Eliminada',
      CAPTURADA: 'Capturada',
      PAGO_AUTORIZADO: 'Pago autorizado',
      PAGO_RECHAZADO: 'Pago rechazado',
      PAGADA: 'Pagada',
      POR_COMPROBAR: 'Por comprobar',
      COMPROBACION_ACEPTADA: 'Comprobación aceptada',
      COMPROBACION_RECHAZADA: 'Comprobación rechazada',
    }
    return map[s] ?? s
  }

  function statusPill(status: string) {
    const s = String(status || '').toUpperCase()
    if (s === 'BORRADOR')               return 'bg-zinc-500/10 text-zinc-700 border-zinc-300/50 dark:text-zinc-200 dark:border-white/10'
    if (s === 'ELIMINADA')              return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    if (s === 'CAPTURADA')              return 'bg-slate-500/10 text-slate-700 border-slate-300/50 dark:text-slate-200 dark:border-white/10'
    if (s === 'PAGO_AUTORIZADO')        return 'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:text-sky-200'
    if (s === 'PAGO_RECHAZADO')         return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    if (s === 'PAGADA')                 return 'bg-cyan-600/10 text-cyan-700 border-cyan-600/20 dark:text-cyan-200'
    if (s === 'POR_COMPROBAR')          return 'bg-amber-500/10 text-amber-800 border-amber-500/20 dark:text-amber-200'
    if (s === 'COMPROBACION_ACEPTADA')  return 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:text-emerald-200'
    if (s === 'COMPROBACION_RECHAZADA') return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    return 'bg-slate-500/10 text-slate-700 border-slate-300/50 dark:text-slate-200 dark:border-white/10'
  }

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

  function destroyRow(row: any) {
    if (!canDelete.value) return
    const folio = row?.folio ?? `#${row?.id}`
    if (!confirm(`¿Eliminar requisición ${folio}?`)) return
    router.delete(route('requisiciones.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => selectedIds.value.delete(row.id),
    })
  }

  // Popover fechas (usa DatePickerShadcn en el componente)
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
    state.fecha_from = tempFrom.value || ''
    state.fecha_to = tempTo.value || ''
    closeDate()
  }

  function clearDate() {
    tempFrom.value = ''
    tempTo.value = ''
    state.fecha_from = ''
    state.fecha_to = ''
    closeDate()
  }

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
    safePagerLinks,
    tabs,

    corporativosActive,
    sucursalesActive,
    sucursalesFiltered,
    empleadosActive,
    statusOptions,

    inputBase,
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
    money,
    statusPill,
    statusLabel,
    destroyRow,

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
