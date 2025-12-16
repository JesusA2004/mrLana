import { router } from '@inertiajs/vue3'
import { computed, reactive, ref, watch, onBeforeUnmount } from 'vue'
import Swal from 'sweetalert2'
import type { ConceptosPageProps, ConceptoRow } from './Conceptos.types'

type FormErrors = Partial<Record<'grupo' | 'nombre', string>>

export function useConceptosIndex(props: ConceptosPageProps) {
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
    style.innerHTML = `.swal2-container.swal2-container-z{ z-index:20000 !important; }`
    document.head.appendChild(style)
  }

  const state = reactive({
    q: props.filters?.q ?? '',
    grupo: props.filters?.grupo ?? '',
    activo: props.filters?.activo ?? '',
    perPage: Number(props.filters?.perPage ?? props.conceptos?.per_page ?? 15),
    sort: (props.filters?.sort ?? 'nombre') as 'id' | 'grupo' | 'nombre',
    dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
  })

  const selectedIds = ref<Set<number>>(new Set())
  const selectedCount = computed(() => selectedIds.value.size)
  const pageIds = computed(() => (props.conceptos?.data ?? []).map((r) => r.id))

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

  function formatLabel(label: string) {
    const t = String(label).replace(/&laquo;|&raquo;|&hellip;/g, '').replace(/<[^>]*>/g, '').trim()
    const low = t.toLowerCase()
    if (low.includes('previous') || low.includes('prev') || low.includes('atrás')) return 'Atrás'
    if (low.includes('next') || low.includes('siguiente')) return 'Siguiente'
    return t || '…'
  }

  const safeLinks = computed(() =>
    (props.conceptos.links ?? []).map((l) => ({ ...l, label: formatLabel(l.label) }))
  )

  function goTo(url: string | null) {
    if (!url) return
    clearSelection()
    router.get(url, {}, { preserveScroll: true, preserveState: true, replace: true })
  }

  let t: any = null
  function debounceVisit() {
    if (t) clearTimeout(t)
    t = setTimeout(() => {
      clearSelection()
      router.get(
        route('conceptos.index'),
        {
          q: state.q || '',
          grupo: state.grupo || '',
          activo: state.activo ?? '',
          perPage: state.perPage,
          sort: state.sort,
          dir: state.dir,
        },
        { preserveScroll: true, preserveState: true, replace: true }
      )
    }, 250)
  }
  watch(() => [state.q, state.grupo, state.activo, state.perPage, state.sort, state.dir], debounceVisit)
  onBeforeUnmount(() => t && clearTimeout(t))

  const hasActiveFilters = computed(() => {
    return (
      !!String(state.q || '').trim() ||
      !!String(state.grupo || '').trim() ||
      String(state.activo ?? '') !== '' ||
      state.dir !== (props.filters?.dir ?? 'asc') ||
      state.sort !== (props.filters?.sort ?? 'nombre')
    )
  })

  function clearFilters() {
    state.q = ''
    state.grupo = ''
    state.activo = ''
    state.perPage = 15
    state.sort = 'nombre'
    state.dir = 'asc'
    clearSelection()
  }

  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.sort = 'nombre'
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  const modalOpen = ref(false)
  const isEdit = ref(false)
  const saving = ref(false)

  const form = reactive({
    id: null as number | null,
    grupo: '',
    nombre: '',
    activo: true,
  })

  const errors = reactive<FormErrors>({})

  function resetErrors() {
    for (const k of Object.keys(errors)) delete (errors as any)[k]
  }

  function validateForm() {
    resetErrors()
    if (!String(form.grupo || '').trim()) errors.grupo = 'El grupo es obligatorio.'
    if (!String(form.nombre || '').trim()) errors.nombre = 'El nombre es obligatorio.'
    return Object.keys(errors).length === 0
  }

  const canSubmit = computed(() => {
    if (saving.value) return false
    return !!String(form.grupo || '').trim() && !!String(form.nombre || '').trim()
  })

  function openCreate() {
    isEdit.value = false
    Object.assign(form, { id: null, grupo: '', nombre: '', activo: true })
    resetErrors()
    modalOpen.value = true
    validateForm()
  }

  function openEdit(row: ConceptoRow) {
    isEdit.value = true
    Object.assign(form, { id: row.id, grupo: row.grupo ?? '', nombre: row.nombre ?? '', activo: !!row.activo })
    resetErrors()
    modalOpen.value = true
    validateForm()
  }

  function closeModal() {
    modalOpen.value = false
  }

  async function submit() {
    if (saving.value) return
    if (!validateForm()) {
      await swalTop({
        icon: 'warning',
        title: 'Faltan campos',
        text: 'Revisa los campos marcados.',
        confirmButtonText: 'Ok',
      })
      return
    }

    saving.value = true
    const payload = {
      grupo: String(form.grupo).trim(),
      nombre: String(form.nombre).trim(),
      activo: !!form.activo,
    }

    const finish = () => (saving.value = false)

    if (!isEdit.value) {
      router.post(route('conceptos.store'), payload, {
        preserveScroll: true,
        onFinish: finish,
        onSuccess: async () => {
          closeModal()
          await swalTop({
            icon: 'success',
            title: 'Concepto creado',
            text: 'Se registró correctamente.',
            timer: 1100,
            showConfirmButton: false,
          })
        },
        onError: async () => {
          await swalTop({
            icon: 'error',
            title: 'No se pudo crear',
            text: 'Revisa validaciones o servidor.',
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
        text: 'No se encontró el ID.',
        confirmButtonText: 'Ok',
      })
      return
    }

    router.put(route('conceptos.update', form.id), payload, {
      preserveScroll: true,
      onFinish: finish,
      onSuccess: async () => {
        closeModal()
        await swalTop({
          icon: 'success',
          title: 'Concepto actualizado',
          text: 'Cambios guardados.',
          timer: 1000,
          showConfirmButton: false,
        })
      },
      onError: async () => {
        await swalTop({
          icon: 'error',
          title: 'No se pudo actualizar',
          text: 'Revisa validaciones o servidor.',
          confirmButtonText: 'Ok',
        })
      },
    })
  }

  async function destroyRow(row: ConceptoRow) {
    const res = await swalTop({
      title: '¿Eliminar concepto?',
      text: `Se eliminará "${row.nombre}".`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!res.isConfirmed) return

    router.delete(route('conceptos.destroy', row.id), {
      preserveScroll: true,
      onSuccess: async () => {
        await swalTop({
          icon: 'success',
          title: 'Concepto eliminado',
          text: 'Se eliminó correctamente.',
          timer: 1000,
          showConfirmButton: false,
        })
      },
      onError: async () => {
        await swalTop({
          icon: 'error',
          title: 'No se pudo eliminar',
          text: 'Puede haber relaciones o un error del servidor.',
          confirmButtonText: 'Ok',
        })
      },
    })
  }

  async function destroySelected() {
    if (selectedIds.value.size === 0) return

    const res = await swalTop({
      title: '¿Eliminar seleccionados?',
      text: `Se eliminarán ${selectedIds.value.size} concepto(s).`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!res.isConfirmed) return

    const ids = Array.from(selectedIds.value)

    router.post(route('conceptos.bulkDestroy'), { ids }, {
      preserveScroll: true,
      onSuccess: async () => {
        clearSelection()
        await swalTop({
          icon: 'success',
          title: 'Eliminación masiva',
          text: 'Se eliminaron correctamente.',
          timer: 1000,
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
  }
}
