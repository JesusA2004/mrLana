import { router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch, onBeforeUnmount } from 'vue'
import Swal from 'sweetalert2'
import type { AreasPageProps, AreaRow } from './Areas.types'

export function useAreasIndex(props: AreasPageProps) {
  /* --------------------------------------------------------------------------
   * Filtros (source of truth)
   * -------------------------------------------------------------------------- */
  const state = reactive({
    q: props.filters?.q ?? '',
    corporativo_id: props.filters?.corporativo_id ?? '',
    activo: props.filters?.activo ?? '',
    perPage: Number(props.filters?.perPage ?? props.areas?.per_page ?? 15),
    sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
    dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
  })

  /* --------------------------------------------------------------------------
   * Bulk selection
   * -------------------------------------------------------------------------- */
  const selectedIds = ref<Set<number>>(new Set())
  const selectedCount = computed(() => selectedIds.value.size)

  const pageIds = computed(() => (props.areas?.data ?? []).map((r) => r.id))

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
   * SweetAlert2 arriba del modal SIEMPRE
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
      allowOutsideClick: true,
      customClass: { ...(opts as any)?.customClass, ...swalBaseClasses() },
      didOpen: () => {
        const el = document.querySelector('.swal2-container') as HTMLElement | null
        if (el) el.style.zIndex = '20000'
      },
    })
  }

  const injected = (window as any).__swalZInjected
  if (!injected) {
    ;(window as any).__swalZInjected = true
    const style = document.createElement('style')
    style.innerHTML = `.swal2-container.swal2-container-z{ z-index:20000 !important; }`
    document.head.appendChild(style)
  }

  /* --------------------------------------------------------------------------
   * Debounce Inertia (filtros en tiempo real)
   * - Limpia selección al cambiar dataset (evita borrar fantasmas)
   * -------------------------------------------------------------------------- */
  let t: any = null
  function debounceVisit() {
    if (t) clearTimeout(t)
    t = setTimeout(() => {
      clearSelection()

      router.get(
        route('areas.index'),
        {
          q: state.q || '',
          corporativo_id: state.corporativo_id || '',
          activo: state.activo ?? '',
          perPage: state.perPage,
          sort: state.sort,
          dir: state.dir,
        },
        { preserveScroll: true, preserveState: true, replace: true }
      )
    }, 250)
  }

  watch(() => [state.q, state.corporativo_id, state.activo, state.perPage, state.sort, state.dir], debounceVisit)
  onBeforeUnmount(() => t && clearTimeout(t))

  /* --------------------------------------------------------------------------
   * Paginación (español)
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
    (props.areas.links ?? [])
      .filter((l): l is Exclude<typeof l, null> => !!l && typeof l === 'object')
      .map((l) => ({ ...l, label: formatLabel(l.label) }))
  )

  function goTo(url: string | null) {
    if (!url) return
    clearSelection()
    router.get(url, {}, { preserveScroll: true, preserveState: true, replace: true })
  }

  const hasActiveFilters = computed(() => {
    return (
      !!String(state.q || '').trim() ||
      !!String(state.corporativo_id || '').trim() ||
      String(state.activo ?? '') !== '' ||
      Number(state.perPage) !== Number(props.filters?.perPage ?? 15) ||
      state.dir !== (props.filters?.dir ?? 'asc') ||
      state.sort !== (props.filters?.sort ?? 'nombre')
    )
  })

  function clearFilters() {
    state.q = ''
    state.corporativo_id = ''
    state.activo = ''
    state.perPage = 15
    state.sort = 'nombre'
    state.dir = 'asc'
    clearSelection()
  }

  /* Sort A-Z / Z-A real */
  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.sort = 'nombre'
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  /* --------------------------------------------------------------------------
   * Combobox corporativos (filtros)
   * -------------------------------------------------------------------------- */
  const corpOpen = ref(false)
  const corpQuery = ref('')
  const corpButtonRef = ref<HTMLElement | null>(null)

  const corporativosActive = computed(() => (props.corporativos ?? []).filter((c) => c.activo !== false))

  const corporativosFiltered = computed(() => {
    const q = corpQuery.value.trim().toLowerCase()
    const list = corporativosActive.value
    if (!q) return list
    return list.filter((c) => {
      const n = String(c.nombre ?? '').toLowerCase()
      const code = String(c.codigo ?? '').toLowerCase()
      return n.includes(q) || code.includes(q)
    })
  })

  const selectedCorp = computed(() => {
    const id = Number(state.corporativo_id || 0)
    if (!id) return null
    return (props.corporativos ?? []).find((c) => c.id === id) ?? null
  })

  function selectCorp(id: number | '') {
    state.corporativo_id = id === '' ? '' : Number(id)
    corpOpen.value = false
    corpQuery.value = ''
  }

  function closeCorpDropdownOnOutside(e: MouseEvent) {
    if (!corpOpen.value) return
    const target = e.target as HTMLElement
    const btn = corpButtonRef.value
    if (!btn) return
    const panel = document.getElementById('corp-dropdown-panel')
    if (btn.contains(target)) return
    if (panel && panel.contains(target)) return
    corpOpen.value = false
  }
  document.addEventListener('mousedown', closeCorpDropdownOnOutside)
  onBeforeUnmount(() => document.removeEventListener('mousedown', closeCorpDropdownOnOutside))

  /* --------------------------------------------------------------------------
   * Modal create/edit + validación inline
   * -------------------------------------------------------------------------- */
  const modalOpen = ref(false)
  const isEdit = ref(false)
  const saving = ref(false)

  const form = reactive({
    id: null as number | null,
    corporativo_id: null as number | null,
    nombre: '',
    activo: true,
  })

  const errors = reactive<{ nombre?: string }>({})

  function resetErrors() {
    errors.nombre = undefined
  }

  function validateForm() {
    resetErrors()
    if (!String(form.nombre || '').trim()) errors.nombre = 'El nombre es obligatorio.'
    return !errors.nombre
  }

  watch(
    () => [form.nombre],
    () => {
      if (!modalOpen.value) return
      validateForm()
    }
  )

  const canSubmit = computed(() => !!String(form.nombre || '').trim() && !saving.value)

  function openCreate() {
    isEdit.value = false
    Object.assign(form, { id: null, corporativo_id: null, nombre: '', activo: true })
    resetErrors()
    modalOpen.value = true
    validateForm()
  }

  function openEdit(row: AreaRow) {
    isEdit.value = true
    Object.assign(form, {
      id: row.id,
      corporativo_id: row.corporativo_id ?? null,
      nombre: row.nombre ?? '',
      activo: !!row.activo,
    })
    resetErrors()
    modalOpen.value = true
    validateForm()
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
    const payload = {
      corporativo_id: form.corporativo_id,
      nombre: String(form.nombre).trim(),
      activo: !!form.activo,
    }

    const finish = () => (saving.value = false)

    if (!isEdit.value) {
      router.post(route('areas.store'), payload, {
        preserveScroll: true,
        onFinish: finish,
        onSuccess: async () => {
          closeModal()
          await swalTop({ icon: 'success', title: 'Área creada', text: 'Listo.', timer: 1000, showConfirmButton: false })
        },
        onError: async () => {
          await swalTop({ icon: 'error', title: 'No se pudo crear', text: 'Revisa validaciones o el servidor.', confirmButtonText: 'Ok' })
        },
      })
      return
    }

    if (!form.id) {
      finish()
      await swalTop({ icon: 'error', title: 'Error interno', text: 'No se encontró el ID del área.', confirmButtonText: 'Ok' })
      return
    }

    router.put(route('areas.update', form.id), payload, {
      preserveScroll: true,
      onFinish: finish,
      onSuccess: async () => {
        closeModal()
        await swalTop({ icon: 'success', title: 'Área actualizada', text: 'Cambios guardados.', timer: 950, showConfirmButton: false })
      },
      onError: async () => {
        await swalTop({ icon: 'error', title: 'No se pudo actualizar', text: 'Revisa validaciones o el servidor.', confirmButtonText: 'Ok' })
      },
    })
  }

  async function destroyRow(row: AreaRow) {
    const res = await swalTop({
      title: '¿Eliminar área?',
      text: `Se eliminará "${row.nombre}".`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!res.isConfirmed) return

    router.delete(route('areas.destroy', row.id), {
      preserveScroll: true,
      onSuccess: async () => {
        await swalTop({ icon: 'success', title: 'Área eliminada', text: 'Se eliminó correctamente.', timer: 950, showConfirmButton: false })
      },
      onError: async () => {
        await swalTop({ icon: 'error', title: 'No se pudo eliminar', text: 'Puede haber restricciones o un error del servidor.', confirmButtonText: 'Ok' })
      },
    })
  }

  async function destroySelected() {
    if (selectedIds.value.size === 0) return

    const res = await swalTop({
      title: '¿Eliminar seleccionadas?',
      text: `Se eliminarán ${selectedIds.value.size} áreas.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!res.isConfirmed) return

    const ids = Array.from(selectedIds.value)

    router.post(route('areas.bulkDestroy'), { ids }, {
      preserveScroll: true,
      onSuccess: async () => {
        clearSelection()
        await swalTop({ icon: 'success', title: 'Eliminación masiva', text: 'Se eliminaron correctamente.', timer: 1000, showConfirmButton: false })
      },
      onError: async () => {
        await swalTop({ icon: 'error', title: 'No se pudo eliminar', text: 'Revisa permisos, relaciones o el endpoint bulk.', confirmButtonText: 'Ok' })
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

    corpOpen,
    corpQuery,
    corpButtonRef,
    corporativosFiltered,
    selectedCorp,
    selectCorp,

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
