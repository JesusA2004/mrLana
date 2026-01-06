import { computed, reactive, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

type ProveedorRow = {
  id: number
  user_duenio_id: number
  nombre_comercial: string
  rfc?: string | null
  email?: string | null
  beneficiario?: string | null
  banco?: string | null
  cuenta?: string | null
  clabe?: string | null
  estatus?: string | null
  created_at?: string | null
  updated_at?: string | null
}

type PagerLink = {
  url: string | null
  label: string
  active: boolean
  cleanLabel?: string
}

type Pagination<T> = {
  data: T[]
  links?: PagerLink[] | any
  meta?: { links?: PagerLink[]; [k: string]: any }
  current_page?: number
  last_page?: number
  [k: string]: any
}

export type ProveedoresIndexProps = {
  filters: {
    q: string
    estatus: string
    sort: 'nombre_comercial' | 'estatus' | 'created_at'
    dir: 'asc' | 'desc'
    perPage: number
  }
  rows: Pagination<ProveedorRow>
  canDelete: boolean
}

function debounce<T extends (...args: any[]) => void>(fn: T, ms = 350) {
  let t: number | undefined
  return (...args: Parameters<T>) => {
    window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), ms)
  }
}

function swalZFix() {
  const style = document.createElement('style')
  style.innerHTML = `.swal2-container{ z-index:20000 !important; }`
  document.head.appendChild(style)
}

function cleanLabel(label: string) {
  return String(label)
    .replaceAll('&laquo;', '«')
    .replaceAll('&raquo;', '»')
    .replaceAll('Previous', 'Atrás')
    .replaceAll('Next', 'Siguiente')
    .replaceAll('…', '...')
}

export function useProveedoresIndex(props: ProveedoresIndexProps) {
  // =========================
  // UI base (igual que Reqs)
  // =========================
  const inputBase =
    'w-full px-4 py-3 text-sm font-semibold border transition outline-none ' +
    'border-slate-200 bg-white text-slate-800 ' +
    'hover:bg-slate-50 hover:border-slate-300 ' +
    'focus:border-slate-900 focus:ring-2 focus:ring-slate-200/70 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 ' +
    'dark:hover:bg-neutral-950/60 dark:hover:border-white/20 ' +
    'dark:focus:border-white/30 dark:focus:ring-white/10'

  const selectBase =
    'w-full px-4 py-3 text-sm font-semibold border transition outline-none ' +
    'border-slate-200 bg-white text-slate-800 ' +
    'hover:bg-slate-50 hover:border-slate-300 ' +
    'focus:border-slate-900 focus:ring-2 focus:ring-slate-200/70 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 ' +
    'dark:hover:bg-neutral-950/60 dark:hover:border-white/20 ' +
    'dark:focus:border-white/30 dark:focus:ring-white/10'

  // =========================
  // Filtros (realtime)
  // =========================
  const state = reactive({
    q: props.filters?.q ?? '',
    estatus: props.filters?.estatus ?? '',
    sort: props.filters?.sort ?? 'created_at',
    dir: props.filters?.dir ?? 'desc',
    perPage: props.filters?.perPage ?? 10,
  })

  const hasActiveFilters = computed(() => {
    return Boolean(state.q?.trim()) || Boolean(state.estatus) || state.perPage !== (props.filters?.perPage ?? 10)
  })

  function clearFilters() {
    state.q = ''
    state.estatus = ''
    state.sort = 'created_at'
    state.dir = 'desc'
    state.perPage = props.filters?.perPage ?? 10
  }

  const sortLabel = computed(() => {
    const col = state.sort
    const dir = state.dir
    const name =
      col === 'created_at' ? 'Fecha' : col === 'nombre_comercial' ? 'Nombre' : col === 'estatus' ? 'Estatus' : 'Fecha'
    return `${name} (${dir.toUpperCase()})`
  })

  function toggleSort() {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  function setSort(col: 'nombre_comercial' | 'estatus' | 'created_at') {
    if (state.sort === col) state.dir = state.dir === 'asc' ? 'desc' : 'asc'
    else {
      state.sort = col
      state.dir = 'asc'
    }
  }

  const reload = debounce(() => {
    router.get(route('proveedores.index'), state, {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      only: ['rows', 'filters', 'canDelete'],
      onSuccess: () => clearSelection(),
    })
  }, 350)

  watch(
    () => [state.q, state.estatus, state.perPage, state.sort, state.dir],
    () => reload()
  )

  // =========================
  // Datos
  // =========================
  const rows = computed(() => props.rows?.data ?? [])

  // =========================
  // Selección
  // =========================
  const selectedIds = ref<Set<number>>(new Set())

  const selectedCount = computed(() => selectedIds.value.size)

  const pageIds = computed(() => rows.value.map(r => r.id))

  const isAllSelectedOnPage = computed(() => {
    const ids = pageIds.value
    if (!ids.length) return false
    return ids.every(id => selectedIds.value.has(id))
  })

  function toggleRow(id: number, checked?: boolean) {
    const next = new Set(selectedIds.value)
    const isChecked = checked ?? !next.has(id)
    if (isChecked) next.add(id)
    else next.delete(id)
    selectedIds.value = next
  }

  function toggleAllOnPage(checked: boolean) {
    const next = new Set(selectedIds.value)
    for (const id of pageIds.value) {
      if (checked) next.add(id)
      else next.delete(id)
    }
    selectedIds.value = next
  }

  function clearSelection() {
    selectedIds.value = new Set()
  }

  // =========================
  // Paginación (safe)
  // =========================
  const safePagerLinks = computed<PagerLink[]>(() => {
    const a = (props.rows as any)?.links
    const b = (props.rows as any)?.meta?.links
    const links = Array.isArray(a) ? a : Array.isArray(b) ? b : []
    return links
      .filter(Boolean)
      .map((l: PagerLink) => ({
        ...l,
        cleanLabel: cleanLabel(l.label),
      }))
  })

  function goTo(url: string) {
    router.visit(url, { preserveScroll: true, preserveState: true })
  }

  // =========================
  // Modal create/edit
  // =========================
  const modalOpen = ref(false)
  const editing = ref<ProveedorRow | null>(null)

  const form = useForm({
    nombre_comercial: '',
    rfc: '',
    email: '',
    beneficiario: '',
    banco: '',
    cuenta: '',
    clabe: '',
    estatus: 'ACTIVO',
  })

  function openCreate() {
    editing.value = null
    form.reset()
    form.clearErrors()
    form.estatus = 'ACTIVO'
    modalOpen.value = true
  }

  function openEdit(row: ProveedorRow) {
    editing.value = row
    form.reset()
    form.clearErrors()

    form.nombre_comercial = row.nombre_comercial ?? ''
    form.rfc = row.rfc ?? ''
    form.email = row.email ?? ''
    form.beneficiario = row.beneficiario ?? ''
    form.banco = row.banco ?? ''
    form.cuenta = row.cuenta ?? ''
    form.clabe = row.clabe ?? ''
    form.estatus = (row.estatus ?? 'ACTIVO') as any

    modalOpen.value = true
  }

  function closeModal() {
    modalOpen.value = false
  }

  function submit() {
    if (editing.value) {
      form.put(route('proveedores.update', editing.value.id), {
        preserveScroll: true,
        onSuccess: () => {
          closeModal()
          form.reset()
        },
      })
      return
    }

    form.post(route('proveedores.store'), {
      preserveScroll: true,
      onSuccess: () => {
        closeModal()
        form.reset()
      },
    })
  }

  // =========================
  // Eliminar
  // =========================
  async function confirmDeleteOne(id: number) {
    swalZFix()
    const res = await Swal.fire({
      title: 'Eliminar proveedor',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
    })
    if (!res.isConfirmed) return

    router.delete(route('proveedores.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        // si estaba seleccionado, límpialo
        const next = new Set(selectedIds.value)
        next.delete(id)
        selectedIds.value = next
      },
    })
  }

  async function destroySelected() {
    if (selectedCount.value < 1) return

    swalZFix()
    const res = await Swal.fire({
      title: 'Eliminar proveedores',
      text: `Se eliminarán ${selectedCount.value} registros. Esta acción no se puede deshacer.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
    })
    if (!res.isConfirmed) return

    router.post(
      route('proveedores.bulkDestroy'),
      { ids: Array.from(selectedIds.value) },
      {
        preserveScroll: true,
        onSuccess: () => clearSelection(),
      }
    )
  }

  // Helpers UI
  const statusPill = (estatus?: string | null) => {
    const v = String(estatus ?? '').toUpperCase()
    if (v === 'INACTIVO') {
      return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100'
    }
    if (v === 'ACTIVO') {
      return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    }
    return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100'
  }

  const headline = computed(() => {
    if (state.estatus) return `Mostrando proveedores con estatus ${state.estatus}.`
    if (state.q?.trim()) return 'Mostrando resultados de tu búsqueda.'
    return 'Mostrando todos los proveedores.'
  })

  return {
    // permisos
    canDelete: props.canDelete,

    // data + filtros
    state,
    rows,
    inputBase,
    selectBase,
    hasActiveFilters,
    clearFilters,
    sortLabel,
    toggleSort,
    setSort,
    headline,

    // selección
    selectedIds,
    selectedCount,
    isAllSelectedOnPage,
    toggleRow,
    toggleAllOnPage,
    clearSelection,
    destroySelected,

    // navegación/pager
    safePagerLinks,
    goTo,

    // modal + form
    modalOpen,
    editing,
    form,
    openCreate,
    openEdit,
    closeModal,
    submit,

    // delete one
    confirmDeleteOne,

    // helpers UI
    statusPill,
  }
}
