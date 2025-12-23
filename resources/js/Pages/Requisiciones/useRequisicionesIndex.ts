import { computed, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

import type { RequisicionesPageProps, RequisicionRow } from './Requisiciones.types'

type SaveForm = {
  id?: number
  folio: string
  tipo: 'ANTICIPO' | 'REEMBOLSO'
  status:
    | 'BORRADOR'
    | 'CAPTURADA'
    | 'PAGADA'
    | 'POR_COMPROBAR'
    | 'COMPROBADA'
    | 'ACEPTADA'
    | 'RECHAZADA'
  comprador_corp_id: number | ''
  sucursal_id: number | ''
  solicitante_id: number | ''
  proveedor_id: number | ''
  concepto_id: number | ''
  monto_subtotal: string
  monto_total: string
  fecha_captura: string
  fecha_pago: string
  observaciones: string
}

function debounce<T extends (...args: any[]) => void>(fn: T, wait = 350) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), wait)
  }
}

export function useRequisicionesIndex(props: RequisicionesPageProps) {
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

  const selectedIds = ref<Set<number>>(new Set())

  const safeLinks = computed(() => props.requisiciones.links ?? [])
  const rows = computed(() => props.requisiciones.data ?? [])

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

  const goTo = (url: string | null) => {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
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

  // Sorting simple
  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  // Selection
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

  function destroySelected() {
    const ids = Array.from(selectedIds.value)
    if (ids.length === 0) return

    router.post(route('requisiciones.bulkDestroy'), { ids }, {
      preserveScroll: true,
      onSuccess: () => selectedIds.value.clear(),
    })
  }

  // Modal CRUD
  const modalOpen = ref(false)
  const isEdit = ref(false)
  const saving = ref(false)

  const form = reactive<SaveForm>({
    folio: '',
    tipo: 'ANTICIPO',
    status: 'CAPTURADA',
    comprador_corp_id: '',
    sucursal_id: '',
    solicitante_id: '',
    proveedor_id: '',
    concepto_id: '',
    monto_subtotal: '0.00',
    monto_total: '0.00',
    fecha_captura: '',
    fecha_pago: '',
    observaciones: '',
  })

  const errors = reactive<Record<string, string>>({})

  function resetErrors() {
    Object.keys(errors).forEach((k) => delete errors[k])
  }

  function openCreate() {
    isEdit.value = false
    modalOpen.value = true
    resetErrors()

    form.id = undefined
    form.folio = ''
    form.tipo = 'ANTICIPO'
    form.status = 'CAPTURADA'
    form.comprador_corp_id = ''
    form.sucursal_id = ''
    form.solicitante_id = ''
    form.proveedor_id = ''
    form.concepto_id = ''
    form.monto_subtotal = '0.00'
    form.monto_total = '0.00'
    form.fecha_captura = new Date().toISOString().slice(0, 16)
    form.fecha_pago = ''
    form.observaciones = ''
  }

  function openEdit(row: RequisicionRow) {
    isEdit.value = true
    modalOpen.value = true
    resetErrors()

    form.id = row.id
    form.folio = row.folio
    form.tipo = row.tipo
    form.status = row.status
    form.comprador_corp_id = row.comprador?.id ?? ''
    form.sucursal_id = row.sucursal?.id ?? ''
    form.solicitante_id = row.solicitante?.id ?? ''
    form.proveedor_id = row.proveedor?.id ?? ''
    form.concepto_id = row.concepto?.id ?? ''
    form.monto_subtotal = row.monto_subtotal ?? '0.00'
    form.monto_total = row.monto_total ?? '0.00'
    form.fecha_captura = (row.fecha_captura ?? '').slice(0, 16)
    form.fecha_pago = row.fecha_pago ?? ''
    form.observaciones = row.observaciones ?? ''
  }

  function closeModal() {
    modalOpen.value = false
    saving.value = false
    resetErrors()
  }

  const canSubmit = computed(() => {
    return (
      String(form.folio).trim().length > 0 &&
      String(form.monto_total).trim().length > 0 &&
      String(form.fecha_captura).trim().length > 0 &&
      form.comprador_corp_id !== '' &&
      form.sucursal_id !== '' &&
      form.solicitante_id !== '' &&
      form.concepto_id !== ''
    )
  })

  function submit() {
    if (!canSubmit.value) return

    saving.value = true
    resetErrors()

    const payload = {
      folio: form.folio,
      tipo: form.tipo,
      status: form.status,
      comprador_corp_id: form.comprador_corp_id,
      sucursal_id: form.sucursal_id,
      solicitante_id: form.solicitante_id,
      proveedor_id: form.proveedor_id || null,
      concepto_id: form.concepto_id,
      monto_subtotal: form.monto_subtotal,
      monto_total: form.monto_total,
      fecha_captura: form.fecha_captura,
      fecha_pago: form.fecha_pago || null,
      observaciones: form.observaciones || null,
    }

    if (!isEdit.value) {
      router.post(route('requisiciones.store'), payload, {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
        onSuccess: () => closeModal(),
        onError: (e) => Object.assign(errors, e),
      })
      return
    }

    router.put(route('requisiciones.update', form.id), payload, {
      preserveScroll: true,
      onFinish: () => (saving.value = false),
      onSuccess: () => closeModal(),
      onError: (e) => Object.assign(errors, e),
    })
  }

  function destroyRow(row: RequisicionRow) {
    router.delete(route('requisiciones.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => selectedIds.value.delete(row.id),
    })
  }

  function setTab(tab: RequisicionesPageProps['filters']['tab']) {
    state.tab = tab
  }

  return {
    state,
    safeLinks,
    rows,

    hasActiveFilters,
    clearFilters,
    sortLabel,
    toggleSort,
    goTo,

    selectedIds,
    selectedCount,
    isAllSelectedOnPage,
    toggleRow,
    toggleAllOnPage,
    clearSelection,
    destroySelected,

    modalOpen,
    isEdit,
    saving,
    form,
    errors,
    canSubmit,
    openCreate,
    openEdit,
    closeModal,
    submit,
    destroyRow,

    setTab,
  }
}
