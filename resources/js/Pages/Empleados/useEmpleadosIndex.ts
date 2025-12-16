import { router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch, onBeforeUnmount, nextTick } from 'vue'
import Swal from 'sweetalert2'
import type { EmpleadosPageProps, EmpleadoRow } from './Empleados.types'

type FormErrors = Partial<Record<
  | 'corporativo_id'
  | 'sucursal_id'
  | 'area_id'
  | 'nombre'
  | 'apellido_paterno'
  | 'email'
  | 'user_name'
  | 'user_email'
  | 'user_password'
  | 'user_rol'
, string>>

export function useEmpleadosIndex(props: EmpleadosPageProps) {
  /* --------------------------------------------------------------------------
   * SWEETALERT2 SIEMPRE ARRIBA DEL MODAL
   * -------------------------------------------------------------------------- */
  function swalBaseClasses() {
    return {
      container: 'swal2-container-z',
      popup:
        'rounded-3xl shadow-2xl border border-white/10 ' +
        'bg-white text-slate-900 ' +
        'dark:bg-neutral-900 dark:text-neutral-100',
      title: 'text-slate-900 dark:text-neutral-100',
      htmlContainer: 'text-slate-600 dark:text-neutral-200 !m-0',
      confirmButton:
        'rounded-2xl px-4 py-2 font-semibold bg-slate-900 text-white hover:bg-slate-800 ' +
        'dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white transition active:scale-[0.98]',
      cancelButton:
        'rounded-2xl px-4 py-2 font-semibold bg-slate-100 text-slate-900 hover:bg-slate-200 ' +
        'dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition active:scale-[0.98]',
    }
  }

  async function swalTop(opts: Parameters<typeof Swal.fire>[0]) {
    return Swal.fire({
      ...opts,
      target: document.body,
      heightAuto: false,
      customClass: { ...(opts as any)?.customClass, ...swalBaseClasses() },
      didOpen: () => {
        const el = document.querySelector('.swal2-container') as HTMLElement | null
        if (el) el.style.zIndex = '20000'
      },
    })
  }

  if (!(window as any).__swalZInjected) {
    ;(window as any).__swalZInjected = true
    const style = document.createElement('style')
    style.innerHTML = `
      .swal2-container.swal2-container-z{ z-index:20000 !important; }
    `
    document.head.appendChild(style)
  }

  /* --------------------------------------------------------------------------
   * STATE FILTROS (TIEMPO REAL + DEBOUNCE)
   * -------------------------------------------------------------------------- */
  const state = reactive({
    q: props.filters?.q ?? '',
    corporativo_id: props.filters?.corporativo_id ?? '',
    sucursal_id: props.filters?.sucursal_id ?? '',
    area_id: props.filters?.area_id ?? '',
    activo: props.filters?.activo ?? '',
    perPage: Number(props.filters?.perPage ?? props.empleados?.per_page ?? 15),
    sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
    dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
  })

  /* --------------------------------------------------------------------------
   * SELECCIÓN + BULK DELETE (NO SE TOCA)
   * -------------------------------------------------------------------------- */
  const selectedIds = ref<Set<number>>(new Set())
  const selectedCount = computed(() => selectedIds.value.size)

  const pageIds = computed(() => (props.empleados?.data ?? []).map((r) => r.id))

  const isAllSelectedOnPage = computed(() => {
    const ids = pageIds.value
    if (ids.length === 0) return false
    return ids.every((id) => selectedIds.value.has(id))
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
   * PAGINACIÓN EN ESPAÑOL
   * -------------------------------------------------------------------------- */
  function formatLabel(label: string) {
    const t = String(label)
      .replace(/&laquo;|&raquo;|&hellip;/g, '')
      .replace(/<[^>]*>/g, '')
      .trim()

    const low = t.toLowerCase()
    if (low.includes('previous') || low.includes('prev') || low.includes('atrás')) return 'Atrás'
    if (low.includes('next') || low.includes('siguiente')) return 'Siguiente'
    return t || '…'
  }

  const safeLinks = computed(() =>
    (props.empleados.links ?? [])
      .filter((l): l is Exclude<typeof l, null> => !!l && typeof l === 'object')
      .map((l) => ({ ...l, label: formatLabel(l.label) }))
  )

  function goTo(url: string | null) {
    if (!url) return
    clearSelection()
    router.get(url, {}, { preserveScroll: true, preserveState: true, replace: true })
  }

  /* --------------------------------------------------------------------------
   * DEBOUNCE VISIT (REALTIME)
   * -------------------------------------------------------------------------- */
  let t: any = null
  function debounceVisit() {
    if (t) clearTimeout(t)
    t = setTimeout(() => {
      clearSelection()
      router.get(
        route('empleados.index'),
        {
          q: state.q || '',
          corporativo_id: state.corporativo_id || '',
          sucursal_id: state.sucursal_id || '',
          area_id: state.area_id || '',
          activo: state.activo ?? '',
          perPage: state.perPage,
          sort: state.sort,
          dir: state.dir,
        },
        { preserveScroll: true, preserveState: true, replace: true }
      )
    }, 250)
  }
  watch(() => [state.q, state.corporativo_id, state.sucursal_id, state.area_id, state.activo, state.perPage, state.sort, state.dir], debounceVisit)
  onBeforeUnmount(() => t && clearTimeout(t))

  const hasActiveFilters = computed(() => {
    return (
      !!String(state.q || '').trim() ||
      !!String(state.corporativo_id || '').trim() ||
      !!String(state.sucursal_id || '').trim() ||
      !!String(state.area_id || '').trim() ||
      String(state.activo ?? '') !== '' ||
      Number(state.perPage) !== Number(props.filters?.perPage ?? 15) ||
      state.dir !== (props.filters?.dir ?? 'asc') ||
      state.sort !== (props.filters?.sort ?? 'nombre')
    )
  })

  function clearFilters() {
    state.q = ''
    state.corporativo_id = ''
    state.sucursal_id = ''
    state.area_id = ''
    state.activo = ''
    state.perPage = 15
    state.sort = 'nombre'
    state.dir = 'asc'
    clearSelection()
  }

  /* Sort A-Z en tabla */
  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.sort = 'nombre'
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  /* --------------------------------------------------------------------------
   * DATA PARA SELECTS + AGRUPACIÓN
   * -------------------------------------------------------------------------- */
  const corporativosActive = computed(() => (props.corporativos ?? []).filter((c) => c.activo !== false))

  const sucursalesActive = computed(() => (props.sucursales ?? []).filter((s) => s.activo !== false))
  const areasActive = computed(() => (props.areas ?? []).filter((a) => a.activo !== false))

  const sucursalesByCorp = computed(() => {
    const corpId = Number(state.corporativo_id || 0)
    const list = sucursalesActive.value
    const filtered = corpId ? list.filter((s) => Number(s.corporativo_id) === corpId) : list
    return [...filtered].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
  })

  const areasByCorp = computed(() => {
    const corpId = Number(state.corporativo_id || 0)
    const list = areasActive.value
    const filtered = corpId ? list.filter((a) => Number(a.corporativo_id) === corpId) : list
    return [...filtered].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
  })

  /* --------------------------------------------------------------------------
   * MODAL (IMPORTANTE: DECLARAR ANTES DE WATCHERS QUE LO USEN)
   * -------------------------------------------------------------------------- */
  const modalOpen = ref(false)
  const isEdit = ref(false)
  const saving = ref(false)

  const form = reactive({
    id: null as number | null,

    // para filtrar selects dentro del modal
    corporativo_id: '' as '' | number,

    // obligatorios
    sucursal_id: '' as '' | number,
    area_id: '' as '' | number,

    // empleado
    nombre: '',
    apellido_paterno: '',
    apellido_materno: '',
    email: '',
    telefono: '',
    puesto: '',
    activo: true,

    // user (se crea/actualiza aquí)
    user_name: '',
    user_email: '',
    user_password: '',
    user_rol: 'COLABORADOR' as 'ADMIN' | 'CONTADOR' | 'COLABORADOR',
    user_activo: true,
  })

  const errors = reactive<FormErrors>({})

  function resetErrors() {
    for (const k of Object.keys(errors)) delete (errors as any)[k]
  }

  function clean(v: unknown) {
    const s = String(v ?? '').trim()
    return s.length ? s : null
  }

  const modalSucursales = computed(() => {
    const corpId = Number(form.corporativo_id || 0)
    const list = sucursalesActive.value
    const filtered = corpId ? list.filter((s) => Number(s.corporativo_id) === corpId) : list
    return [...filtered].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
  })

  const modalAreas = computed(() => {
    const corpId = Number(form.corporativo_id || 0)
    const list = areasActive.value
    const filtered = corpId ? list.filter((a) => Number(a.corporativo_id) === corpId) : list
    return [...filtered].sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'))
  })

  function validateForm() {
    resetErrors()

    // negocio: empleado NO puede estar sin sucursal (y por ende corporativo viene desde sucursal)
    if (!String(form.sucursal_id || '').trim()) errors.sucursal_id = 'Selecciona una sucursal.'

    if (!String(form.nombre || '').trim()) errors.nombre = 'El nombre es obligatorio.'
    if (!String(form.apellido_paterno || '').trim()) errors.apellido_paterno = 'El apellido paterno es obligatorio.'

    // User: obligatorio siempre (porque aquí se administra users)
    if (!String(form.user_name || '').trim()) errors.user_name = 'El nombre de usuario es obligatorio.'
    if (!String(form.user_email || '').trim()) errors.user_email = 'El email de usuario es obligatorio.'
    if (!String(form.user_rol || '').trim()) errors.user_rol = 'Selecciona un rol.'

    // password: obligatorio al crear; opcional al editar
    if (!isEdit.value && !String(form.user_password || '').trim()) errors.user_password = 'La contraseña es obligatoria.'

    return Object.keys(errors).length === 0
  }

  // validación viva (solo cuando modal está abierto)
  watch(
    () => [form.sucursal_id, form.nombre, form.apellido_paterno, form.user_name, form.user_email, form.user_password, form.user_rol],
    () => {
      if (!modalOpen.value) return
      validateForm()
    }
  )

  // cuando cambia corporativo en modal, limpia sucursal/área si ya no pertenecen
  watch(
    () => form.corporativo_id,
    () => {
      if (!modalOpen.value) return
      const sOk = modalSucursales.value.some((s) => String(s.id) === String(form.sucursal_id))
      if (!sOk) form.sucursal_id = ''
      const aOk = modalAreas.value.some((a) => String(a.id) === String(form.area_id))
      if (!aOk) form.area_id = ''
    }
  )

  const canSubmit = computed(() => {
    if (saving.value) return false
    const ok =
      !!String(form.sucursal_id || '').trim() &&
      !!String(form.nombre || '').trim() &&
      !!String(form.apellido_paterno || '').trim() &&
      !!String(form.user_name || '').trim() &&
      !!String(form.user_email || '').trim() &&
      !!String(form.user_rol || '').trim() &&
      (isEdit.value ? true : !!String(form.user_password || '').trim())
    return ok
  })

  function openCreate() {
    isEdit.value = false
    Object.assign(form, {
      id: null,
      corporativo_id: '',
      sucursal_id: '',
      area_id: '',
      nombre: '',
      apellido_paterno: '',
      apellido_materno: '',
      email: '',
      telefono: '',
      puesto: '',
      activo: true,

      user_name: '',
      user_email: '',
      user_password: '',
      user_rol: 'COLABORADOR',
      user_activo: true,
    })
    resetErrors()
    modalOpen.value = true
    nextTick(() => validateForm())
  }

  function openEdit(row: EmpleadoRow) {
    isEdit.value = true

    // infer corporativo desde sucursal relacionada
    const inferredCorpId = row.sucursal?.corporativo?.id ?? row.sucursal?.corporativo_id ?? ''

    Object.assign(form, {
      id: row.id,
      corporativo_id: inferredCorpId ? Number(inferredCorpId) : '',
      sucursal_id: Number(row.sucursal_id),
      area_id: row.area_id ? Number(row.area_id) : '',
      nombre: row.nombre ?? '',
      apellido_paterno: row.apellido_paterno ?? '',
      apellido_materno: row.apellido_materno ?? '',
      email: row.email ?? '',
      telefono: row.telefono ?? '',
      puesto: row.puesto ?? '',
      activo: !!row.activo,

      user_name: row.user?.name ?? `${row.nombre ?? ''} ${row.apellido_paterno ?? ''}`.trim(),
      user_email: row.user?.email ?? (row.email ?? ''),
      user_password: '',
      user_rol: row.user?.rol ?? 'COLABORADOR',
      user_activo: row.user?.activo ?? true,
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
      await swalTop({
        icon: 'warning',
        title: 'Faltan campos',
        text: 'Revisa los campos marcados en el formulario.',
        confirmButtonText: 'Ok',
      })
      return
    }

    saving.value = true

    const payload: any = {
      // empleado
      sucursal_id: Number(form.sucursal_id),
      area_id: form.area_id ? Number(form.area_id) : null,
      nombre: String(form.nombre).trim(),
      apellido_paterno: String(form.apellido_paterno).trim(),
      apellido_materno: clean(form.apellido_materno),
      email: clean(form.email),
      telefono: clean(form.telefono),
      puesto: clean(form.puesto),
      activo: !!form.activo,

      // user (se crea/actualiza aquí mismo)
      user_name: String(form.user_name).trim(),
      user_email: String(form.user_email).trim(),
      user_rol: form.user_rol,
      user_activo: !!form.user_activo,
    }

    // password solo si viene
    if (String(form.user_password || '').trim()) {
      payload.user_password = String(form.user_password).trim()
    }

    const finish = () => (saving.value = false)

    if (!isEdit.value) {
      router.post(route('empleados.store'), payload, {
        preserveScroll: true,
        onFinish: finish,
        onSuccess: async () => {
          closeModal()
          await swalTop({
            icon: 'success',
            title: 'Empleado creado',
            text: 'Empleado + usuario registrados correctamente.',
            timer: 1200,
            showConfirmButton: false,
          })
        },
        onError: async () => {
          await swalTop({
            icon: 'error',
            title: 'No se pudo crear',
            text: 'Revisa validaciones o el servidor.',
            confirmButtonText: 'Ok',
          })
        },
      })
      return
    }

    if (!form.id) {
      finish()
      await swalTop({
        icon: 'error',
        title: 'Error interno',
        text: 'No se encontró el ID del empleado.',
        confirmButtonText: 'Ok',
      })
      return
    }

    router.put(route('empleados.update', form.id), payload, {
      preserveScroll: true,
      onFinish: finish,
      onSuccess: async () => {
        closeModal()
        await swalTop({
          icon: 'success',
          title: 'Empleado actualizado',
          text: 'Cambios guardados.',
          timer: 1100,
          showConfirmButton: false,
        })
      },
      onError: async () => {
        await swalTop({
          icon: 'error',
          title: 'No se pudo actualizar',
          text: 'Revisa validaciones o el servidor.',
          confirmButtonText: 'Ok',
        })
      },
    })
  }

  async function destroyRow(row: EmpleadoRow) {
    const res = await swalTop({
      title: '¿Eliminar empleado?',
      text: `Se eliminará "${row.nombre} ${row.apellido_paterno}".`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!res.isConfirmed) return

    router.delete(route('empleados.destroy', row.id), {
      preserveScroll: true,
      onSuccess: async () => {
        await swalTop({
          icon: 'success',
          title: 'Empleado eliminado',
          text: 'Se eliminó correctamente.',
          timer: 1100,
          showConfirmButton: false,
        })
      },
      onError: async () => {
        await swalTop({
          icon: 'error',
          title: 'No se pudo eliminar',
          text: 'Puede haber restricciones o un error del servidor.',
          confirmButtonText: 'Ok',
        })
      },
    })
  }

  async function destroySelected() {
    if (selectedIds.value.size === 0) return

    const res = await swalTop({
      title: '¿Eliminar seleccionados?',
      text: `Se eliminarán ${selectedIds.value.size} empleado(s).`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!res.isConfirmed) return

    const ids = Array.from(selectedIds.value)

    router.post(route('empleados.bulkDestroy'), { ids }, {
      preserveScroll: true,
      onSuccess: async () => {
        clearSelection()
        await swalTop({
          icon: 'success',
          title: 'Eliminación masiva',
          text: 'Se eliminaron correctamente.',
          timer: 1100,
          showConfirmButton: false,
        })
      },
      onError: async () => {
        await swalTop({
          icon: 'error',
          title: 'No se pudo eliminar',
          text: 'Revisa permisos, relaciones o el endpoint bulk.',
          confirmButtonText: 'Ok',
        })
      },
    })
  }

  return {
    state,
    safeLinks,
    goTo,
    hasActiveFilters,
    clearFilters,
    sortLabel,
    toggleSort,

    corporativosActive,
    sucursalesByCorp,
    areasByCorp,

    modalSucursales,
    modalAreas,

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

    selectedIds,
    selectedCount,
    isAllSelectedOnPage,
    toggleRow,
    toggleAllOnPage,
    clearSelection,
    destroySelected,
  }
}
