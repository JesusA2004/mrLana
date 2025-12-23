import { router } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import Swal from 'sweetalert2'
import type { SucursalesPageProps, SucursalRow } from './Sucursales.types'

export function useSucursalesIndex(props: SucursalesPageProps) {
    /* --------------------------------------------------------------------------
     * Filtros (source of truth)
     * -------------------------------------------------------------------------- */
    const state = reactive({
        q: props.filters?.q ?? '',
        // SearchableSelect: null = “Todos”
        corporativo_id: (props.filters?.corporativo_id ? Number(props.filters.corporativo_id) : null) as number | null,
        activo: props.filters?.activo ?? '',
        perPage: Number(props.filters?.perPage ?? props.sucursales?.per_page ?? 15),
        sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
        dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
    })

    /* --------------------------------------------------------------------------
     * Dataset: corporativos para SearchableSelect
     * -------------------------------------------------------------------------- */
    const corporativosForSelect = computed(() => {
        const list = props.corporativos ?? []
        return list.filter((c) => c && (c as any).id) // sanity
    })

    /* --------------------------------------------------------------------------
     * SweetAlert2 (siempre arriba del modal)
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

    // Inyecta regla global UNA vez
    const injected = (window as any).__swalZInjected
    if (!injected) {
        ;(window as any).__swalZInjected = true
        const style = document.createElement('style')
        style.innerHTML = `.swal2-container.swal2-container-z{ z-index:20000 !important; }`
        document.head.appendChild(style)
    }

    /* --------------------------------------------------------------------------
     * Paginación: labels en español y sin HTML raro
     * -------------------------------------------------------------------------- */
    function formatLabel(label: string) {
        const t = String(label)
            .replace(/&laquo;|&raquo;|&hellip;/g, '')
            .replace(/<[^>]*>/g, '')
            .trim()

        const low = t.toLowerCase()

        if (low.includes('previous') || low.includes('prev') || low.includes('atrás') || low.includes('anterior')) return 'Atrás'
        if (low.includes('next') || low.includes('siguiente')) return 'Siguiente'

        // números tal cual
        if (/^\d+$/.test(t)) return t

        return t || '…'
    }

    const safeLinks = computed(() =>
        (props.sucursales?.links ?? [])
            .filter((l): l is Exclude<typeof l, null> => !!l && typeof l === 'object')
            .map((l) => ({ ...l, label: formatLabel((l as any).label) }))
    )

    /* --------------------------------------------------------------------------
     * Selección + Bulk delete
     * -------------------------------------------------------------------------- */
    const selectedIds = ref<Set<number>>(new Set())
    const selectedCount = computed(() => selectedIds.value.size)

    const pageIds = computed(() => (props.sucursales?.data ?? []).map((r) => r.id))

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

    function goTo(url: string | null) {
        if (!url) return
        clearSelection()
        router.get(url, {}, { preserveScroll: true, preserveState: true, replace: true })
    }

    /* --------------------------------------------------------------------------
     * Debounce Inertia: filtros reactivos
     * -------------------------------------------------------------------------- */
    let t: any = null

    function debounceVisit() {
        if (t) clearTimeout(t)
        t = setTimeout(() => {
            clearSelection()

            router.get(
                route('sucursales.index'),
                {
                    q: state.q || '',
                    corporativo_id: state.corporativo_id ?? '',
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

    const hasActiveFilters = computed(() => {
        return (
            !!String(state.q || '').trim() ||
            state.corporativo_id !== null ||
            String(state.activo ?? '') !== '' ||
            Number(state.perPage) !== Number(props.filters?.perPage ?? 15) ||
            state.dir !== (props.filters?.dir ?? 'asc') ||
            state.sort !== (props.filters?.sort ?? 'nombre')
        )
    })

    function clearFilters() {
        state.q = ''
        state.corporativo_id = null
        state.activo = ''
        state.perPage = 15
        state.sort = 'nombre'
        state.dir = 'asc'
        clearSelection()
    }

    /* Sort */
    const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
    function toggleSort() {
        state.sort = 'nombre'
        state.dir = state.dir === 'asc' ? 'desc' : 'asc'
    }

    /* --------------------------------------------------------------------------
     * Modal create/edit + Validaciones inline
     * -------------------------------------------------------------------------- */
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
            id: row.id,
            corporativo_id: Number((row as any).corporativo_id),
            nombre: (row as any).nombre ?? '',
            codigo: (row as any).codigo ?? '',
            ciudad: (row as any).ciudad ?? '',
            estado: (row as any).estado ?? '',
            direccion: (row as any).direccion ?? '',
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
                onSuccess: async () => {
                    closeModal()
                    await swalTop({
                        icon: 'success',
                        title: 'Sucursal creada',
                        text: 'Listo. Ya está disponible en el catálogo.',
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
                text: 'No se encontró el ID de la sucursal.',
                confirmButtonText: 'Ok',
            })
            return
        }

        router.put(route('sucursales.update', form.id), payload, {
            preserveScroll: true,
            onFinish: finish,
            onSuccess: async () => {
                closeModal()
                await swalTop({
                    icon: 'success',
                    title: 'Sucursal actualizada',
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

    async function destroyRow(row: SucursalRow) {
        const res = await swalTop({
            title: '¿Eliminar sucursal?',
            text: `Se eliminará "${(row as any).nombre}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        })
        if (!res.isConfirmed) return

        router.delete(route('sucursales.destroy', (row as any).id), {
            preserveScroll: true,
            onSuccess: async () => {
                await swalTop({
                    icon: 'success',
                    title: 'Sucursal eliminada',
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
            title: '¿Eliminar seleccionadas?',
            text: `Se eliminarán ${selectedIds.value.size} sucursales.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        })
        if (!res.isConfirmed) return

        const ids = Array.from(selectedIds.value)

        router.post(route('sucursales.bulkDestroy'), { ids }, {
            preserveScroll: true,
            onSuccess: async () => {
                clearSelection()
                await swalTop({
                    icon: 'success',
                    title: 'Eliminación completada',
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

        corporativosForSelect,

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
