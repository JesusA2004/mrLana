import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import type { SucursalesPageProps, SucursalRow } from './Sucursales.types'
import { useSwalTheme } from '@/Utils/swal'

export function useSucursalesIndex(props: SucursalesPageProps) {
  type InertiaErrors = Record<string, string[]>

  const page = usePage()
  const { Swal, toast, swalBaseClasses, ensurePopupDark } = useSwalTheme()

  /**
   * ----------------------------------------------------------
   * Filtros (source of truth)
   * ----------------------------------------------------------
   * Nota:
   * - En query enviamos per_page (Laravel convention).
   * - En state lo mantenemos como perPage (UI convention).
   */
  const state = reactive({
    q: props.filters?.q ?? '',
    corporativo_id: props.filters?.corporativo_id ? Number(props.filters.corporativo_id) : null,
    activo: (props.filters?.activo ?? 'all') as 'all' | '1' | '0',
    perPage: Number(props.filters?.per_page ?? props.filters?.perPage ?? props.sucursales?.meta?.per_page ?? 15),
    sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
    dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
  })

  /**
   * ----------------------------------------------------------
   * Dataset: corporativos para SearchableSelect
   * ----------------------------------------------------------
   */
  const corporativosForSelect = computed(() => {
    const list = props.corporativos ?? []
    return list.filter((c: any) => c && typeof c.id !== 'undefined')
  })

  /**
   * ----------------------------------------------------------
   * Selección (Set) + helpers
   * ----------------------------------------------------------
   */
  const selectedIds = ref<Set<number>>(new Set())

  const selectedCount = computed(() => selectedIds.value.size)

  const pageIds = computed<number[]>(() => (props.sucursales?.data ?? []).map((r: any) => Number(r.id)))

  const isAllSelectedOnPage = computed(() => {
    const ids = pageIds.value
    return ids.length > 0 && ids.every((id) => selectedIds.value.has(id))
  })

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

  /**
   * Proxy para usar AppCheckbox con v-model:checked (array),
   * manteniendo internamente Set (más eficiente).
   */
  const selectedIdsArray = computed<number[]>({
    get() {
      return Array.from(selectedIds.value)
    },
    set(values: number[]) {
      selectedIds.value = new Set(values.map((v) => Number(v)))
    },
  })

  /**
   * ----------------------------------------------------------
   * Paginación: normalización + mobile safe links
   * ----------------------------------------------------------
   */
  type PaginationLink = {
    url: string | null
    label: string
    active?: boolean
  }

  const paginationLinks = computed<PaginationLink[]>(() => {
    const raw = ((props.sucursales as any)?.meta?.links ?? (props.sucursales as any)?.links ?? []) as any[]
    if (!Array.isArray(raw)) return []

    return raw.map((l: any) => {
      const labelRaw = String(l?.label ?? '').toLowerCase()
      let label = String(l?.label ?? '')
      if (labelRaw.includes('previous')) label = 'Atrás'
      if (labelRaw.includes('next')) label = 'Siguiente'
      return { url: l?.url ?? null, label, active: !!l?.active }
    })
  })

  const safeLinks = computed<PaginationLink[]>(() => {
    return (paginationLinks.value ?? [])
      .filter((l) => l && typeof l.label === 'string')
      .map((l) => ({ url: l.url ?? null, label: String(l.label ?? ''), active: !!l.active }))
  })

  const mobileLinks = computed<PaginationLink[]>(() => safeLinks.value)

  function linkLabelShort(label: string): string {
    const clean = String(label)
      .replace(/&laquo;|&raquo;|&hellip;/g, '')
      .replace(/<[^>]*>/g, '')
      .trim()

    const low = clean.toLowerCase()

    if (low.includes('atrás') || low.includes('anterior')) return 'Atrás'
    if (low.includes('siguiente')) return 'Siguiente'
    if (/^\d+$/.test(clean)) return clean
    if (clean.length > 6) return clean.slice(0, 6)
    return clean || '…'
  }

  function goTo(url: string | null) {
    if (!url) return
    router.visit(url, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onFinish: () => nextTick(() => void 0),
    })
  }

  /**
   * ----------------------------------------------------------
   * Debounce Inertia: filtros reactivos
   * ----------------------------------------------------------
   */
  let t: number | null = null

  function debounceVisit() {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => {
      clearSelection()
      router.get(
        route('sucursales.index'),
        {
          q: state.q || '',
          corporativo_id: state.corporativo_id ?? '',
          activo: state.activo ?? 'all',
          per_page: state.perPage,
          sort: state.sort,
          dir: state.dir,
        },
        { preserveScroll: true, preserveState: true, replace: true }
      )
    }, 250)
  }

  watch(
    () => [state.q, state.corporativo_id, state.activo, state.perPage, state.sort, state.dir],
    debounceVisit
  )

  onBeforeUnmount(() => {
    if (t) window.clearTimeout(t)
  })

  const hasActiveFilters = computed(() => {
    return (
      !!String(state.q || '').trim() ||
      state.corporativo_id !== null ||
      String(state.activo ?? 'all') !== 'all' ||
      Number(state.perPage) !== Number(props.filters?.per_page ?? props.filters?.perPage ?? 15) ||
      state.dir !== (props.filters?.dir ?? 'asc') ||
      state.sort !== (props.filters?.sort ?? 'nombre')
    )
  })

  function clearFilters() {
    state.q = ''
    state.corporativo_id = null
    state.activo = 'all'
    state.perPage = 15
    state.sort = 'nombre'
    state.dir = 'asc'
    clearSelection()
  }

  /**
   * ----------------------------------------------------------
   * Sort
   * ----------------------------------------------------------
   */
  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.sort = 'nombre'
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  /**
   * ----------------------------------------------------------
   * Modal create/edit + Validaciones inline
   * ----------------------------------------------------------
   */
  const modalOpen = ref(false)
  const isEdit = ref(false)
  const saving = ref(false)

  const form = reactive({
    id: null as number | null,
    corporativo_id: null as number | null,
    nombre: '',
    codigo: '',
    ciudad: '',
    estado: '',
    direccion: '',
    activo: true,
  })

  const errors = reactive<{ corporativo_id?: string; nombre?: string }>({})

  function resetErrors() {
    errors.corporativo_id = undefined
    errors.nombre = undefined
  }

  function validateForm() {
    resetErrors()
    if (!form.corporativo_id) errors.corporativo_id = 'Selecciona un corporativo.'
    if (!String(form.nombre || '').trim()) errors.nombre = 'El nombre es obligatorio.'
    return !errors.corporativo_id && !errors.nombre
  }

  watch(
    () => [form.corporativo_id, form.nombre],
    () => {
      if (!modalOpen.value) return
      validateForm()
    }
  )

  function openCreate() {
    isEdit.value = false
    Object.assign(form, {
      id: null,
      corporativo_id: null,
      nombre: '',
      codigo: '',
      ciudad: '',
      estado: '',
      direccion: '',
      activo: true,
    })
    resetErrors()
    modalOpen.value = true
    nextTick(() => validateForm())
  }

  function openEdit(row: SucursalRow) {
    isEdit.value = true
    Object.assign(form, {
      id: Number((row as any).id),
      corporativo_id: Number((row as any).corporativo_id ?? (row as any).corporativo?.id ?? null),
      nombre: String((row as any).nombre ?? ''),
      codigo: String((row as any).codigo ?? ''),
      ciudad: String((row as any).ciudad ?? ''),
      estado: String((row as any).estado ?? ''),
      direccion: String((row as any).direccion ?? ''),
      activo: !!(row as any).activo,
    })
    resetErrors()
    modalOpen.value = true
    nextTick(() => validateForm())
  }

  function closeModal() {
    modalOpen.value = false
  }

  function clean(v: unknown) {
    const s = String(v ?? '').trim()
    return s.length ? s : null
  }

  const canSubmit = computed(() => {
    return !!form.corporativo_id && !!String(form.nombre || '').trim() && !saving.value
  })

  function firstError(inertiaErrors: InertiaErrors): string {
    const v = Object.values(inertiaErrors)[0]
    return v?.[0] ?? 'Error de validación.'
  }

  async function submit() {
    if (saving.value) return

    const ok = validateForm()
    if (!ok) {
      await Swal.fire({
        icon: 'warning',
        title: 'Faltan campos',
        text: 'Revisa los campos marcados en el formulario.',
        confirmButtonText: 'Ok',
        customClass: swalBaseClasses(),
        didOpen: ensurePopupDark,
      })
      return
    }

    saving.value = true

    const payload = {
      corporativo_id: Number(form.corporativo_id),
      nombre: String(form.nombre).trim(),
      codigo: clean(form.codigo),
      ciudad: clean(form.ciudad),
      estado: clean(form.estado),
      direccion: clean(form.direccion),
      activo: !!form.activo,
    }

    const finish = () => (saving.value = false)

    if (!isEdit.value) {
      router.post(route('sucursales.store'), payload, {
        preserveScroll: true,
        onFinish: finish,
        onSuccess: () => {
          closeModal()
          toast().fire({ icon: 'success', title: 'Sucursal creada' })
        },
        onError: (e: InertiaErrors) => {
          Swal.fire({
            icon: 'error',
            title: 'No se pudo crear',
            text: firstError(e),
            confirmButtonText: 'OK',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
          })
        },
      })
      return
    }

    if (!form.id) {
      finish()
      await Swal.fire({
        icon: 'error',
        title: 'Error interno',
        text: 'No se encontró el ID de la sucursal.',
        confirmButtonText: 'OK',
        customClass: swalBaseClasses(),
        didOpen: ensurePopupDark,
      })
      return
    }

    router.put(route('sucursales.update', form.id), payload, {
      preserveScroll: true,
      onFinish: finish,
      onSuccess: () => {
        closeModal()
        toast().fire({ icon: 'success', title: 'Sucursal actualizada' })
      },
      onError: (e: InertiaErrors) => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo actualizar',
          text: firstError(e),
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  async function destroyRow(row: SucursalRow) {
    const res = await Swal.fire({
      icon: 'warning',
      title: 'Eliminar sucursal',
      text: `Se eliminará "${String((row as any).nombre ?? '')}".`,
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })
    if (!res.isConfirmed) return

    router.delete(route('sucursales.destroy', Number((row as any).id)), {
      preserveScroll: true,
      onSuccess: () => {
        toast().fire({ icon: 'success', title: 'Sucursal eliminada' })
        // si estaba seleccionada, la removemos
        const next = new Set(selectedIds.value)
        next.delete(Number((row as any).id))
        selectedIds.value = next
      },
      onError: () => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo eliminar',
          text: 'Puede haber restricciones o un error del servidor.',
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  async function destroySelected() {
    if (selectedIds.value.size === 0) return

    const ids = Array.from(selectedIds.value)

    const res = await Swal.fire({
      icon: 'warning',
      title: 'Eliminar seleccionadas',
      html: `<div class="text-sm">Se eliminarán <b>${ids.length}</b> sucursales. Esta acción no se puede deshacer.</div>`,
      showCancelButton: true,
      confirmButtonText: `Eliminar (${ids.length})`,
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })
    if (!res.isConfirmed) return

    router.post(
      route('sucursales.bulkDestroy'),
      { ids },
      {
        preserveScroll: true,
        onSuccess: () => {
          clearSelection()
          toast().fire({ icon: 'success', title: 'Eliminación completada' })
        },
        onError: () => {
          Swal.fire({
            icon: 'error',
            title: 'No se pudo eliminar',
            text: 'Revisa permisos, relaciones o el endpoint bulk.',
            confirmButtonText: 'OK',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
          })
        },
      }
    )
  }

  /**
   * Flash messages (si existen) -> toast
   */
  watch(
    () => (page.props as any)?.flash,
    (f: any) => {
      const msg = f?.success || f?.message
      if (msg) toast().fire({ icon: 'success', title: String(msg) })
    },
    { deep: true }
  )

  /**
   * Por seguridad, si cambia el dataset, limpiamos selección
   * (evita que el usuario borre "sin querer" IDs de otra página).
   */
  watch(
    () => props.sucursales?.data,
    () => clearSelection(),
    { deep: true }
  )

  /**
   * Inicialización ligera
   */
  onMounted(() => {
    // aquí podrías meter métricas o telemetry si luego quieres
  })

  return {
    // filtros
    state,
    corporativosForSelect,
    hasActiveFilters,
    clearFilters,
    sortLabel,
    toggleSort,

    // selección
    selectedIds,
    selectedIdsArray,
    selectedCount,
    isAllSelectedOnPage,
    toggleAllOnPage,
    clearSelection,

    // paginación
    paginationLinks,
    safeLinks,
    mobileLinks,
    linkLabelShort,
    goTo,

    // modal/form
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

    // acciones
    destroyRow,
    destroySelected,
  }
}
