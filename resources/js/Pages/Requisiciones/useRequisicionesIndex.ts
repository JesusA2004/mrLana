import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

import type {
  RequisicionesPageProps,
  RequisicionRow,
  RequisicionStatus,
  PaginationLink,
} from './Requisiciones.types'

import { swalNotify } from '@/lib/swal'

function debounce<T extends (...args: any[]) => void>(fn: T, wait = 350) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), wait)
  }
}

function stripHtml(s: string) {
  return String(s ?? '').replace(/<[^>]*>/g, '').trim()
}

function toId(v: any): number {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

function safeDateParse(input: any): Date | null {
  if (!input) return null
  const s = String(input)

  // soporta "YYYY-MM-DD" y "YYYY-MM-DD HH:mm:ss" y ISO
  const isoish = s.includes('T') ? s : s.includes(' ') ? s.replace(' ', 'T') : `${s}T00:00:00`
  const d = new Date(isoish)
  return Number.isNaN(d.getTime()) ? null : d
}

function fmtDateLong(input: any) {
  const d = safeDateParse(input)
  if (!d) return '—'
  try {
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'long', year: 'numeric' }).format(d)
  } catch {
    return String(input ?? '—')
  }
}

function money(v: any) {
  const n = Number(v ?? 0)
  try {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
  } catch {
    return String(v ?? '')
  }
}

export function useRequisicionesIndex(props: RequisicionesPageProps) {
  const page = usePage<any>()
  const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
  const empleadoId = computed(() => page.props?.auth?.user?.empleado_id ?? null)

  const canDelete = computed(() => ['ADMIN', 'CONTADOR'].includes(role.value))
  const canPay = computed(() => role.value === 'CONTADOR')
  const canComprobar = computed(() => ['ADMIN', 'CONTADOR', 'COLABORADOR'].includes(role.value))

  const isColaborador = computed(() => role.value === 'COLABORADOR')

  const state = reactive({
    q: props.filters?.q ?? '',
    status: props.filters?.status ?? '',
    comprador_corp_id: props.filters?.comprador_corp_id ?? '',
    sucursal_id: props.filters?.sucursal_id ?? '',
    solicitante_id: props.filters?.solicitante_id ?? '',
    fecha_from: props.filters?.fecha_from ?? '',
    fecha_to: props.filters?.fecha_to ?? '',
    perPage: Number(props.filters?.perPage ?? 10),
    sort: props.filters?.sort ?? 'id',
    dir: (props.filters?.dir ?? 'desc') as 'asc' | 'desc',
  })

  // Forzar solicitante en colaborador (y el filtro se vuelve “Mi data”)
  if (isColaborador.value && empleadoId.value) {
    state.solicitante_id = empleadoId.value
  }

  const rows = computed<RequisicionRow[]>(() => (props.requisiciones?.data ?? []) as RequisicionRow[])

  const meta = computed(() => props.requisiciones?.meta ?? {})
  const pagerLinks = computed<PaginationLink[]>(() => {
    const mLinks = (meta.value?.links ?? []) as PaginationLink[]
    if (mLinks?.length) return mLinks
    const top = (props.requisiciones?.links ?? []) as any
    return Array.isArray(top) ? (top as PaginationLink[]) : []
  })

  const safePagerLinks = computed(() =>
    (pagerLinks.value ?? []).map((l) => ({ ...l, cleanLabel: stripHtml(l.label) }))
  )

  const corporativosActive = computed(() =>
    (props.catalogos?.corporativos ?? []).filter((c) => c.activo !== false)
  )

  const sucursalesActive = computed(() =>
    (props.catalogos?.sucursales ?? []).filter((s) => s.activo !== false)
  )

  const empleadosActive = computed(() =>
    (props.catalogos?.empleados ?? []).filter((e) => e.activo !== false)
  )

  // Regla: NO mostrar sucursales si no hay corporativo seleccionado
  const sucursalesFiltered = computed(() => {
    const corpId = toId(state.comprador_corp_id)
    if (!corpId) return []
    return sucursalesActive.value.filter((s) => toId(s.corporativo_id) === corpId)
  })

  // Si cambia corporativo -> reset sucursal
  watch(
    () => state.comprador_corp_id,
    () => {
      state.sucursal_id = ''
    }
  )

  const statusOptions = computed(() => {
    const items: Array<{ id: string; nombre: string }> = [
      { id: '', nombre: 'Todos' },
      { id: 'BORRADOR', nombre: 'Borrador' },
      { id: 'CAPTURADA', nombre: 'Capturada' },
      { id: 'PAGO_AUTORIZADO', nombre: 'Pago autorizado' },
      { id: 'PAGO_RECHAZADO', nombre: 'Pago rechazado' },
      { id: 'PAGADA', nombre: 'Pagada' },
      { id: 'POR_COMPROBAR', nombre: 'Por comprobar' },
      { id: 'COMPROBACION_ACEPTADA', nombre: 'Comprobación aceptada' },
      { id: 'COMPROBACION_RECHAZADA', nombre: 'Comprobación rechazada' },
      { id: 'ELIMINADA', nombre: 'Eliminada' },
    ]
    return items
  })

  const inputBase =
    'mt-1 w-full rounded-2xl px-4 py-3 text-sm border transition focus:outline-none focus:ring-2 ' +
    'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'

  const hasActiveFilters = computed(() => {
    return Boolean(
      state.q ||
        state.status ||
        state.comprador_corp_id ||
        state.sucursal_id ||
        (!isColaborador.value && state.solicitante_id) ||
        state.fecha_from ||
        state.fecha_to ||
        state.perPage !== 10 ||
        state.sort !== 'id' ||
        state.dir !== 'desc'
    )
  })

  function params() {
    return {
      q: state.q || undefined,
      status: state.status || undefined,
      comprador_corp_id: state.comprador_corp_id || undefined,
      sucursal_id: state.sucursal_id || undefined,
      solicitante_id: isColaborador.value ? empleadoId.value : state.solicitante_id || undefined,
      fecha_from: state.fecha_from || undefined,
      fecha_to: state.fecha_to || undefined,
      perPage: state.perPage || undefined,
      sort: state.sort || undefined,
      dir: state.dir || undefined,
    }
  }

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
    state.status = ''
    state.comprador_corp_id = ''
    state.sucursal_id = ''
    if (!isColaborador.value) state.solicitante_id = ''
    state.fecha_from = ''
    state.fecha_to = ''
    state.perPage = 10
    state.sort = 'id'
    state.dir = 'desc'
  }

  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
  }

  // Selección múltiple
  const selectedIds = ref<Set<number>>(new Set())
  const selectedCount = computed(() => selectedIds.value.size)

  const isAllSelectedOnPage = computed(() => {
    const data = rows.value
    if (!data.length) return false
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

  async function confirmDanger(title: string, text: string) {
    const res = await Swal.fire({
      title,
      text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      focusCancel: true,
    })
    return res.isConfirmed
  }

  async function destroySelected() {
    if (!canDelete.value) return
    const ids = Array.from(selectedIds.value)
    if (!ids.length) return

    const ok = await confirmDanger('Eliminar requisiciones', `Se marcarán como eliminadas (${ids.length}).`)
    if (!ok) return

    router.delete(route('requisiciones.bulkDestroy'), {
      data: { ids },
      preserveScroll: true,
      onSuccess: () => {
        selectedIds.value.clear()
        swalNotify('Las requisiciones seleccionadas se marcaron como eliminadas.', 'ok')
      },
    })
  }

  function statusPill(status: RequisicionStatus | string) {
    const s = String(status || '').toUpperCase()
    if (s === 'BORRADOR') return 'bg-zinc-500/10 text-zinc-700 border-zinc-300/50 dark:text-zinc-200 dark:border-white/10'
    if (s === 'ELIMINADA') return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    if (s === 'CAPTURADA') return 'bg-slate-500/10 text-slate-700 border-slate-300/50 dark:text-slate-200 dark:border-white/10'
    if (s === 'PAGO_AUTORIZADO') return 'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:text-sky-200'
    if (s === 'PAGO_RECHAZADO') return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    if (s === 'PAGADA') return 'bg-cyan-600/10 text-cyan-700 border-cyan-600/20 dark:text-cyan-200'
    if (s === 'POR_COMPROBAR') return 'bg-amber-500/10 text-amber-800 border-amber-500/20 dark:text-amber-200'
    if (s === 'COMPROBACION_ACEPTADA') return 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:text-emerald-200'
    if (s === 'COMPROBACION_RECHAZADA') return 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'
    return 'bg-slate-500/10 text-slate-700 border-slate-300/50 dark:text-slate-200 dark:border-white/10'
  }

  // Rutas
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

  function printReq(_id: number) {
    // solo ícono por ahora (sin lógica)
  }

  async function destroyRow(row: RequisicionRow) {
    if (!canDelete.value) return
    const ok = await confirmDanger('Eliminar requisición', `Se marcará como eliminada: ${row.folio}`)
    if (!ok) return

    router.delete(route('requisiciones.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => {
        selectedIds.value.delete(row.id)
        swalNotify('La requisición se marcó como eliminada.', 'ok')
      },
    })
  }

  // Cerrar popovers raros si tu SearchableSelect usa overlay externo (no tocamos tu componente)
  function onEsc(_e: KeyboardEvent) {}
  onMounted(() => document.addEventListener('keydown', onEsc))
  onBeforeUnmount(() => document.removeEventListener('keydown', onEsc))

  function displayName(x: any) {
    if (!x) return '—'
    return x.nombre ?? x.razon_social ?? x.name ?? '—'
  }

  return {
    role,
    isColaborador,
    canDelete,
    canPay,
    canComprobar,

    state,
    rows,
    meta,
    safePagerLinks,

    corporativosActive,
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
    printReq,
    destroyRow,

    statusPill,
    fmtDateLong,
    money,
    displayName,
  }
}
