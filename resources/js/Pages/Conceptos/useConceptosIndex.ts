import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import type { ConceptosPageProps, ConceptoRow } from './Conceptos.types'
import { useSwalTheme } from '@/Utils/swal'

export function useConceptosIndex(props: ConceptosPageProps) {
    type InertiaErrors = Record<string, string[]>

    const page = usePage()
    const { Swal, toast, swalBaseClasses, ensurePopupDark } = useSwalTheme()

    /**
     * ----------------------------------------------------------
     * Filtros (source of truth)
     * - Default: Activos ('1')
     * - Soporta: 'all' | '1' | '0'
     * ----------------------------------------------------------
     */
    const state = reactive({
        q: props.filters?.q ?? '',
        activo: (props.filters?.activo ?? '1') as 'all' | '1' | '0',
        perPage: Number(
            (props.filters as any)?.per_page ??
            props.filters?.perPage ??
            (props.conceptos as any)?.meta?.per_page ??
            (props.conceptos as any)?.per_page ??
            15
        ),
        sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
        dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
    })

    /**
     * ----------------------------------------------------------
     * Selección (Set) + proxies
     * ----------------------------------------------------------
     */
    const selectedIds = ref<Set<number>>(new Set())
    const selectedCount = computed(() => selectedIds.value.size)

    const pageIds = computed<number[]>(() => (props.conceptos?.data ?? []).map((r: any) => Number(r.id)))

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

    /**
     * ----------------------------------------------------------
     * Paginación: normalización + mobile links
     * ----------------------------------------------------------
     */
    type PaginationLink = {
        url: string | null
        label: string
        active: boolean
    }

    type RawLink = {
        url?: string | null
        label?: string | null
        active?: boolean | null
    }

    const paginationLinks = computed<PaginationLink[]>(() => {
        const raw =
        (((props.conceptos as any)?.meta?.links ?? (props.conceptos as any)?.links ?? []) as RawLink[]) || []
        if (!Array.isArray(raw)) return []

        return raw.map((l) => {
            const labelRaw = String(l?.label ?? '').toLowerCase()
            let label = String(l?.label ?? '')

            if (labelRaw.includes('previous')) label = 'Atrás'
            if (labelRaw.includes('next')) label = 'Siguiente'

            return {
                url: l?.url ?? null,
                label,
                active: Boolean(l?.active),
            }
        })
    })

    const safeLinks = computed(() => paginationLinks.value)
    const mobileLinks = computed<PaginationLink[]>(() => paginationLinks.value ?? [])

    function linkLabelShort(label: string): string {
        const clean = String(label).replace(/&laquo;|&raquo;|&hellip;/g, '').replace(/<[^>]*>/g, '').trim()

        const low = clean.toLowerCase()
        if (low.includes('atrás') || low.includes('anterior')) return 'Atrás'
        if (low.includes('siguiente')) return 'Siguiente'
        if (/^\d+$/.test(clean)) return clean
        if (clean.length > 6) return clean.slice(0, 6)
        return clean || '…'
    }

    function goTo(url: string | null) {
        if (!url) return
        clearSelection()
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
                route('conceptos.index'),{
                    q: state.q || '',
                    activo: state.activo ?? '1',
                    per_page: state.perPage, // snake (estándar)
                    sort: state.sort,
                    dir: state.dir,
                },
                { preserveScroll: true, preserveState: true, replace: true }
            )
        }, 250)
    }

    watch(() => [state.q, state.activo, state.perPage, state.sort, state.dir], debounceVisit)

    onBeforeUnmount(() => {
        if (t) window.clearTimeout(t)
    })

    const hasActiveFilters = computed(() => {
        return (
            !!String(state.q || '').trim() ||
            String(state.activo ?? '1') !== '1' ||
            Number(state.perPage) !== Number((props.filters as any)?.per_page ?? props.filters?.perPage ?? 15) ||
            state.dir !== (props.filters?.dir ?? 'asc') ||
            state.sort !== (props.filters?.sort ?? 'nombre')
        )
    })

    function clearFilters() {
        state.q = ''
        state.activo = '1' // baseline enterprise
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

    function openCreate() {
        isEdit.value = false
        Object.assign(form, {
            id: null,
            nombre: '',
            activo: true,
        })
        resetErrors()
        modalOpen.value = true
        nextTick(() => validateForm())
    }

    function openEdit(row: ConceptoRow) {
        isEdit.value = true
        Object.assign(form, {
            id: Number((row as any).id),
            nombre: String((row as any).nombre ?? ''),
            activo: !!(row as any).activo,
        })
        resetErrors()
        modalOpen.value = true
        nextTick(() => validateForm())
    }

    function closeModal() {
        modalOpen.value = false
    }

    const canSubmit = computed(() => {
        return !!String(form.nombre || '').trim() && !errors.nombre && !saving.value
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
                confirmButtonText: 'OK',
                customClass: swalBaseClasses(),
                didOpen: ensurePopupDark,
            })
        return
        }

        saving.value = true

        const payload = {
            nombre: String(form.nombre).trim(),
            activo: true, // catálogo: crear/editar siempre activo; baja/activar son acciones aparte
        }

        const finish = () => (saving.value = false)

        if (!isEdit.value) {
            router.post(route('conceptos.store'), payload, {
                preserveScroll: true,
                onFinish: finish,
                onSuccess: () => {
                closeModal()
                toast().fire({ icon: 'success', title: 'Concepto creado' })
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
                text: 'No se encontró el ID del concepto.',
                confirmButtonText: 'OK',
                customClass: swalBaseClasses(),
                didOpen: ensurePopupDark,
            })
            return
        }

        router.put(route('conceptos.update', form.id), payload,{
            preserveScroll: true,
            onFinish: finish,
            onSuccess: () => {
                closeModal()
                toast().fire({ icon: 'success', title: 'Concepto actualizado' })
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

    // Baja logica / activación
    async function destroyRow(row: ConceptoRow) {
        const name = String((row as any).nombre ?? '')
        const id = Number((row as any).id)
        const active = !!(row as any).activo

        // Ya está inactivo -> ofrecer activar
        if (!active) {
        const res = await Swal.fire({
            icon: 'question',
            title: 'Concepto inactivo',
            text: `“${name}” está dado de baja. ¿Deseas reactivarlo?`,
            showCancelButton: true,
            confirmButtonText: 'Activar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return
        await confirmActivate(row)
        return
        }

        // normal: dar de baja
        const res = await Swal.fire({
            icon: 'warning',
            title: 'Eliminar concepto',
            text: `¿Estas seguro? Se eliminara “${name}”.`,
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return

        router.delete(route('conceptos.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
            toast().fire({ icon: 'success', title: 'Concepto dado de baja' })
            const next = new Set(selectedIds.value)
            next.delete(id)
            selectedIds.value = next
        },
        onError: () => {
            Swal.fire({
            icon: 'error',
            title: 'No se pudo dar de baja',
            text: 'Puede haber restricciones o una explicación desde servidor.',
            confirmButtonText: 'OK',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
            })
        },
        })
    }

    async function confirmActivate(row: any) {
        const name = String(row?.nombre ?? '')
        const id = Number(row?.id)

        const res = await Swal.fire({
            icon: 'question',
            title: 'Activar concepto',
            text: `Se activará “${name}”.`,
            showCancelButton: true,
            confirmButtonText: 'Activar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return

        router.patch(route('conceptos.activate', id), {}, {
        preserveScroll: true,
        onSuccess: () => toast().fire({ icon: 'success', title: 'Concepto activado' }),
        onError: () =>
            Swal.fire({
            icon: 'error',
            title: 'No se pudo activar',
            text: 'Revisa permisos o el endpoint de activación.',
            confirmButtonText: 'OK',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
            }),
        })
    }

    async function destroySelected() {
        if (selectedIds.value.size === 0) return

        const ids = Array.from(selectedIds.value)

        const res = await Swal.fire({
            icon: 'warning',
            title: 'Dar de baja seleccionados',
            html: `<div class="text-sm">Se darán de baja <b>${ids.length}</b> concepto(s).</div>`,
            showCancelButton: true,
            confirmButtonText: `Dar de baja (${ids.length})`,
            cancelButtonText: 'Cancelar',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return

        Swal.fire({
            title: 'Procesando...',
            html: `<div class="text-sm">Actualizando <b>${ids.length}</b> registros</div>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: swalBaseClasses(),
            didOpen: () => {
                ensurePopupDark()
                Swal.showLoading()
            },
        })

        router.post(route('conceptos.bulkDestroy'), { ids }, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.close()
                clearSelection()
                toast().fire({ icon: 'success', title: `Actualizados ${ids.length}` })
            },
            onError: () => {
                Swal.close()
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

    /**
     * Flash messages -> toast (opcional)
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
     * Si cambia el dataset, limpiamos selección
     */
    watch(
        () => props.conceptos?.data,
        () => clearSelection(),
        { deep: true }
    )

    onMounted(() => {
        // no-op
    })

    return {
        // filtros / sort
        state,
        hasActiveFilters,
        clearFilters,
        sortLabel,
        toggleSort,

        // selección
        selectedIds,
        selectedCount,
        isAllSelectedOnPage,
        toggleRow,
        toggleAllOnPage,
        clearSelection,

        // paginación
        paginationLinks,
        mobileLinks,
        linkLabelShort,
        goTo,
        safeLinks,

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
        confirmActivate,
        destroySelected,
    }

}
