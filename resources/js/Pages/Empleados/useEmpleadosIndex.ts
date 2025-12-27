// useEmpleadosIndex.ts
import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import type { EmpleadosPageProps, EmpleadoRow } from './Empleados.types'
import { useSwalTheme } from '@/Utils/swal'

/**
 * ======================================================
 * useEmpleadosIndex
 * ------------------------------------------------------
 * - Filtros realtime (debounce)
 * - Selects: SOLO corporativos/sucursales/áreas ACTIVAS
 *   + sucursales activas SOLO si su corporativo está activo
 * - Dependencias: corporativo -> habilita/filtra sucursales/áreas
 *   + NO se permite abrir sucursal/área si no hay corporativo
 * - Modal sin redundancias:
 *   - user_name NO se captura (se deriva de nombre completo)
 *   - email ÚNICO (user_email) -> se usa para empleado y usuario
 *   - password NO se captura (backend genera y manda por email)
 * - Acciones:
 *   - Baja lógica: DELETE empleados.destroy
 *   - Reactivar:  PATCH empleados.activate
 *   - Bulk baja:  POST empleados.bulkDestroy
 * - SweetAlert: tema centralizado con useSwalTheme (sin hacks)
 * ======================================================
 */

type InertiaErrors = Record<string, string[]>

type FormErrors = Partial<
  Record<
    | 'corporativo_id'
    | 'sucursal_id'
    | 'area_id'
    | 'nombre'
    | 'apellido_paterno'
    | 'user_email'
    | 'user_rol',
    string
  >
>

function clean(v: unknown) {
  const s = String(v ?? '').trim()
  return s.length ? s : null
}

function firstError(e: InertiaErrors): string {
  const v = Object.values(e ?? {})[0]
  return v?.[0] ?? 'Error de validación.'
}

function fullNameFromParts(p: {
  nombre?: string
  apellido_paterno?: string
  apellido_materno?: string | null
}) {
  return `${p.nombre ?? ''} ${p.apellido_paterno ?? ''}${p.apellido_materno ? ` ${p.apellido_materno}` : ''}`.trim()
}

export function useEmpleadosIndex(props: EmpleadosPageProps) {
  const page = usePage()
  const { Swal, toast, swalBaseClasses, ensurePopupDark } = useSwalTheme()

  /* --------------------------------------------------------------------------
   * FILTROS (source of truth)
   * -------------------------------------------------------------------------- */
  const state = reactive({
    q: props.filters?.q ?? '',
    corporativo_id: props.filters?.corporativo_id ? Number(props.filters.corporativo_id) : null,
    sucursal_id: props.filters?.sucursal_id ? Number(props.filters.sucursal_id) : null,
    area_id: props.filters?.area_id ? Number(props.filters.area_id) : null,
    activo: (props.filters?.activo ?? '1') as 'all' | '1' | '0',
    perPage: Number((props.filters as any)?.per_page ?? props.filters?.perPage ?? (props.empleados as any)?.per_page ?? 15),
    sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
    dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
  })

  const canPickSucursalFilter = computed(() => !!state.corporativo_id)
  const canPickAreaFilter = computed(() => !!state.corporativo_id)

  /* --------------------------------------------------------------------------
   * DATASETS (SOLO ACTIVOS)
   * -------------------------------------------------------------------------- */
  const corporativosActive = computed(() => (props.corporativos ?? []).filter((c: any) => c && c.activo !== false))
  const corporativosActiveIds = computed(() => new Set(corporativosActive.value.map((c: any) => Number(c.id))))

  const sucursalesActive = computed(() => {
    const list = (props.sucursales ?? []).filter((s: any) => s && s.activo !== false)
    return list.filter((s: any) => {
      const corpId = Number(s.corporativo_id ?? s.corporativo?.id ?? 0)
      return corporativosActiveIds.value.has(corpId)
    })
  })

  const areasActive = computed(() => (props.areas ?? []).filter((a: any) => a && a.activo !== false))

  // IMPORTANT: si no hay corporativo, NO mostramos sucursales/áreas (evita "todas")
  const sucursalesByCorp = computed(() => {
    const corpId = state.corporativo_id ? Number(state.corporativo_id) : 0
    if (!corpId) return []
    const list = sucursalesActive.value as any[]
    const filtered = list.filter((s) => Number(s.corporativo_id ?? s.corporativo?.id) === corpId)
    return [...filtered].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
  })

  const areasByCorp = computed(() => {
    const corpId = state.corporativo_id ? Number(state.corporativo_id) : 0
    if (!corpId) return []
    const list = areasActive.value as any[]
    const filtered = list.filter((a) => Number(a.corporativo_id) === corpId)
    return [...filtered].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
  })

  // Dependencias filtros: si cambias corporativo -> resetea sucursal/área si ya no aplican
  watch(
    () => state.corporativo_id,
    () => {
      if (!state.corporativo_id) {
        state.sucursal_id = null
        state.area_id = null
        return
      }

      const sOk =
        state.sucursal_id === null
          ? true
          : sucursalesByCorp.value.some((s: any) => Number(s.id) === Number(state.sucursal_id))
      if (!sOk) state.sucursal_id = null

      const aOk =
        state.area_id === null
          ? true
          : areasByCorp.value.some((a: any) => Number(a.id) === Number(state.area_id))
      if (!aOk) state.area_id = null
    }
  )

  /* --------------------------------------------------------------------------
   * SELECCIÓN + BULK
   * -------------------------------------------------------------------------- */
  const selectedIds = ref<Set<number>>(new Set())
  const selectedCount = computed(() => selectedIds.value.size)
  const pageIds = computed<number[]>(() => (props.empleados?.data ?? []).map((r: any) => Number(r.id)))

  const isAllSelectedOnPage = computed(() => {
    const ids = pageIds.value
    return ids.length > 0 && ids.every((id) => selectedIds.value.has(id))
  })

  function toggleRow(id: number, checked: boolean) {
    const next = new Set(selectedIds.value)
    if (checked) next.add(id)
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

  /* --------------------------------------------------------------------------
   * PAGINACIÓN (labels ES)
   * -------------------------------------------------------------------------- */
  function formatLabel(label: string) {
    const t = String(label)
      .replace(/&laquo;|&raquo;|&hellip;/g, '')
      .replace(/<[^>]*>/g, '')
      .trim()

    const low = t.toLowerCase()
    if (low.includes('previous') || low.includes('prev') || low.includes('atrás') || low.includes('anterior')) return 'Atrás'
    if (low.includes('next') || low.includes('siguiente')) return 'Siguiente'
    return t || '…'
  }

  const safeLinks = computed(() => (props.empleados?.links ?? []).map((l: any) => ({ ...l, label: formatLabel(l.label) })))

  function goTo(url: string | null) {
    if (!url) return
    clearSelection()
    router.visit(url, { preserveState: true, preserveScroll: true, replace: true })
  }

  /* --------------------------------------------------------------------------
   * DEBOUNCE VISIT
   * -------------------------------------------------------------------------- */
  let t: number | null = null

  function debounceVisit() {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => {
      clearSelection()
      router.get(
        route('empleados.index'),
        {
          q: state.q || '',
          corporativo_id: state.corporativo_id ?? '',
          sucursal_id: state.sucursal_id ?? '',
          area_id: state.area_id ?? '',
          activo: state.activo ?? 'all',
          per_page: state.perPage,
          sort: state.sort,
          dir: state.dir,
        },
        { preserveScroll: true, preserveState: true, replace: true }
      )
    }, 250)
  }

  watch(() => [state.q, state.corporativo_id, state.sucursal_id, state.area_id, state.activo, state.perPage, state.sort, state.dir], debounceVisit)

  onBeforeUnmount(() => {
    if (t) window.clearTimeout(t)
  })

  const hasActiveFilters = computed(() => {
    return (
      !!String(state.q || '').trim() ||
      state.corporativo_id !== null ||
      state.sucursal_id !== null ||
      state.area_id !== null ||
      String(state.activo ?? '1') !== '1' ||
      Number(state.perPage) !== 15 ||
      state.dir !== 'asc' ||
      state.sort !== 'nombre'
    )
  })

  function clearFilters() {
    state.q = ''
    state.corporativo_id = null
    state.sucursal_id = null
    state.area_id = null
    state.activo = '1'
    state.perPage = 15
    state.sort = 'nombre'
    state.dir = 'asc'
    clearSelection()
  }

  /* Sort A-Z */
  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.sort = 'nombre'
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  /* --------------------------------------------------------------------------
   * MODAL create/edit (sin redundancias)
   * - corporativo obligatorio antes de sucursal/área
   * - email único (user_email)
   * - password NO se pide (backend)
   * -------------------------------------------------------------------------- */
  const modalOpen = ref(false)
  const isEdit = ref(false)
  const saving = ref(false)

  const form = reactive({
    id: null as number | null,

    corporativo_id: null as number | null,
    sucursal_id: null as number | null,
    area_id: null as number | null,

    // empleado
    nombre: '',
    apellido_paterno: '',
    apellido_materno: '',
    telefono: '',
    puesto: '',
    activo: true,

    // acceso
    user_email: '',
    user_rol: 'COLABORADOR' as 'ADMIN' | 'CONTADOR' | 'COLABORADOR',
    user_activo: true,
  })

  const canPickSucursalModal = computed(() => !!form.corporativo_id)
  const canPickAreaModal = computed(() => !!form.corporativo_id)

  const errors = reactive<FormErrors>({})

  function resetErrors() {
    for (const k of Object.keys(errors)) delete (errors as any)[k]
  }

  const modalSucursales = computed(() => {
    const corpId = form.corporativo_id ? Number(form.corporativo_id) : 0
    if (!corpId) return []
    const list = sucursalesActive.value as any[]
    const filtered = list.filter((s) => Number(s.corporativo_id ?? s.corporativo?.id) === corpId)
    return [...filtered].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
  })

  const modalAreas = computed(() => {
    const corpId = form.corporativo_id ? Number(form.corporativo_id) : 0
    if (!corpId) return []
    const list = areasActive.value as any[]
    const filtered = list.filter((a) => Number(a.corporativo_id) === corpId)
    return [...filtered].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
  })

  // Dependencias modal: corporativo invalida sucursal/área
  watch(
    () => form.corporativo_id,
    () => {
      if (!modalOpen.value) return

      if (!form.corporativo_id) {
        form.sucursal_id = null
        form.area_id = null
        return
      }

      const sOk =
        form.sucursal_id === null ? true : modalSucursales.value.some((s: any) => Number(s.id) === Number(form.sucursal_id))
      if (!sOk) form.sucursal_id = null

      const aOk =
        form.area_id === null ? true : modalAreas.value.some((a: any) => Number(a.id) === Number(form.area_id))
      if (!aOk) form.area_id = null
    }
  )

  function validateForm() {
    resetErrors()

    if (!form.corporativo_id) errors.corporativo_id = 'Selecciona un corporativo.'
    if (!form.sucursal_id) errors.sucursal_id = 'Selecciona una sucursal.'
    if (!String(form.nombre || '').trim()) errors.nombre = 'El nombre es obligatorio.'
    if (!String(form.apellido_paterno || '').trim()) errors.apellido_paterno = 'El apellido paterno es obligatorio.'

    // email único
    if (!String(form.user_email || '').trim()) errors.user_email = 'El email es obligatorio.'
    if (!String(form.user_rol || '').trim()) errors.user_rol = 'Selecciona un rol.'

    return Object.keys(errors).length === 0
  }

  watch(
    () => [form.corporativo_id, form.sucursal_id, form.area_id, form.nombre, form.apellido_paterno, form.user_email, form.user_rol],
    () => {
      if (!modalOpen.value) return
      validateForm()
    }
  )

  const canSubmit = computed(() => {
    if (saving.value) return false
    return (
      !!form.corporativo_id &&
      !!form.sucursal_id &&
      !!String(form.nombre || '').trim() &&
      !!String(form.apellido_paterno || '').trim() &&
      !!String(form.user_email || '').trim() &&
      !!String(form.user_rol || '').trim()
    )
  })

  function openCreate() {
    isEdit.value = false
    Object.assign(form, {
      id: null,
      corporativo_id: null,
      sucursal_id: null,
      area_id: null,

      nombre: '',
      apellido_paterno: '',
      apellido_materno: '',
      telefono: '',
      puesto: '',
      activo: true,

      user_email: '',
      user_rol: 'COLABORADOR',
      user_activo: true,
    })
    resetErrors()
    modalOpen.value = true
    nextTick(() => validateForm())
  }

  function openEdit(row: EmpleadoRow) {
    isEdit.value = true

    const inferredCorpId =
      (row as any)?.sucursal?.corporativo?.id ??
      (row as any)?.sucursal?.corporativo_id ??
      (row as any)?.corporativo_id ??
      null

    Object.assign(form, {
      id: Number((row as any).id),

      corporativo_id: inferredCorpId ? Number(inferredCorpId) : null,
      sucursal_id: (row as any).sucursal_id ? Number((row as any).sucursal_id) : null,
      area_id: (row as any).area_id ? Number((row as any).area_id) : null,

      nombre: String((row as any).nombre ?? ''),
      apellido_paterno: String((row as any).apellido_paterno ?? ''),
      apellido_materno: String((row as any).apellido_materno ?? ''),
      telefono: String((row as any).telefono ?? ''),
      puesto: String((row as any).puesto ?? ''),
      activo: Boolean((row as any).activo),

      // email único
      user_email: String((row as any)?.user?.email ?? (row as any)?.email ?? ''),
      user_rol: ((row as any)?.user?.rol ?? 'COLABORADOR') as any,
      user_activo: Boolean((row as any)?.user?.activo ?? true),
    })

    resetErrors()
    modalOpen.value = true
    nextTick(() => validateForm())
  }

  function closeModal() {
    modalOpen.value = false
  }

  async function submit() {
    if (saving.value) return

    const ok = validateForm()
    if (!ok) {
      await Swal.fire({
        icon: 'warning',
        title: 'Faltan campos',
        text: 'Revisa los campos marcados en el formulario.',
        confirmButtonText: 'OK',
        customClass: swalBaseClasses(),
        didOpen: ensurePopupDark,
      })
      return
    }

    saving.value = true

    const payload: any = {
      sucursal_id: Number(form.sucursal_id),
      area_id: form.area_id ? Number(form.area_id) : null,

      nombre: String(form.nombre).trim(),
      apellido_paterno: String(form.apellido_paterno).trim(),
      apellido_materno: clean(form.apellido_materno),
      telefono: clean(form.telefono),
      puesto: clean(form.puesto),
      activo: !!form.activo,

      // derivado, no se captura
      user_name: fullNameFromParts(form),

      // email único
      user_email: String(form.user_email).trim(),
      user_rol: form.user_rol,
      user_activo: !!form.user_activo,
    }

    const finish = () => (saving.value = false)

    if (!isEdit.value) {
      router.post(route('empleados.store'), payload, {
        preserveScroll: true,
        onFinish: finish,
        onSuccess: () => {
          closeModal()
          toast().fire({ icon: 'success', title: 'Empleado creado (password enviado por correo)' })
          clearSelection()
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
        text: 'No se encontró el ID del empleado.',
        confirmButtonText: 'OK',
        customClass: swalBaseClasses(),
        didOpen: ensurePopupDark,
      })
      return
    }

    router.put(route('empleados.update', form.id), payload, {
      preserveScroll: true,
      onFinish: finish,
      onSuccess: () => {
        closeModal()
        toast().fire({ icon: 'success', title: 'Empleado actualizado' })
        clearSelection()
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

  /* --------------------------------------------------------------------------
   * ACCIONES: baja lógica / activar
   * -------------------------------------------------------------------------- */
  async function confirmDeactivate(row: EmpleadoRow) {
    const id = Number((row as any).id)
    const name = fullNameFromParts(row)

    if (!(row as any).activo) {
      await confirmActivate(row)
      return
    }

    const res = await Swal.fire({
      icon: 'warning',
      title: 'Dar de baja empleado',
      text: `¿Deseas dar de baja a "${name}"?`,
      showCancelButton: true,
      confirmButtonText: 'Dar de baja',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })
    if (!res.isConfirmed) return

    router.delete(route('empleados.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        const next = new Set(selectedIds.value)
        next.delete(id)
        selectedIds.value = next
        toast().fire({ icon: 'success', title: 'Empleado dado de baja' })
      },
      onError: () => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo dar de baja',
          text: 'Revisa relaciones, permisos o el servidor.',
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  async function confirmActivate(row: EmpleadoRow) {
    const id = Number((row as any).id)
    const name = fullNameFromParts(row)

    const res = await Swal.fire({
      icon: 'question',
      title: 'Activar empleado',
      text: `Se activará "${name}".`,
      showCancelButton: true,
      confirmButtonText: 'Activar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })
    if (!res.isConfirmed) return

    router.patch(route('empleados.activate', id), {}, {
      preserveScroll: true,
      onSuccess: () => toast().fire({ icon: 'success', title: 'Empleado activado' }),
      onError: () => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo activar',
          text: 'La sucursal o el corporativo podría estar dado de baja, o faltan permisos.',
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  async function confirmBulkDeactivate() {
    if (selectedIds.value.size === 0) return

    const ids = Array.from(selectedIds.value)

    const res = await Swal.fire({
      icon: 'warning',
      title: 'Dar de baja seleccionados',
      html: `<div class="text-sm">Se darán de baja <b>${ids.length}</b> empleado(s).</div>`,
      showCancelButton: true,
      confirmButtonText: `Dar de baja (${ids.length})`,
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })
    if (!res.isConfirmed) return

    router.post(route('empleados.bulkDestroy'), { ids }, {
      preserveScroll: true,
      onSuccess: () => {
        clearSelection()
        toast().fire({ icon: 'success', title: 'Baja masiva aplicada' })
      },
      onError: () => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo procesar',
          text: 'Revisa permisos o el endpoint bulk.',
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  /* Flash messages -> toast */
  watch(
    () => (page.props as any)?.flash,
    (f: any) => {
      const msg = f?.success || f?.message
      if (msg) toast().fire({ icon: 'success', title: String(msg) })
    },
    { deep: true }
  )

  watch(
    () => props.empleados?.data,
    () => clearSelection(),
    { deep: true }
  )

  return {
    // filtros/paginación/sort
    state,
    safeLinks,
    goTo,
    hasActiveFilters,
    clearFilters,
    sortLabel,
    toggleSort,

    // dependencias filtros
    canPickSucursalFilter,
    canPickAreaFilter,

    // datasets
    corporativosActive,
    sucursalesByCorp,
    areasByCorp,

    // modal datasets + dependencias
    modalSucursales,
    modalAreas,
    canPickSucursalModal,
    canPickAreaModal,

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
    confirmDeactivate,
    confirmActivate,
    confirmBulkDeactivate,

    // selección
    selectedIds,
    selectedCount,
    isAllSelectedOnPage,
    toggleRow,
    toggleAllOnPage,
    clearSelection,
  }
}
