/**
 * ============================================================================
 * useAreasIndex.ts
 * ----------------------------------------------------------------------------
 * Áreas (Index composable) - patrón MR-Lana / Sucursales
 * - Filtros reactivos + debounce Inertia
 * - Paginación en español (Atrás / Siguiente)
 * - Bulk selection por página + limpieza al cambiar dataset
 * - Modal Create/Edit con validación inline
 * - SweetAlert2: tema (dark) + z-index blindado vía useSwalTheme()
 * - Regla de negocio encadenada:
 *    * Si el corporativo está en baja => NO crear/actualizar/activar áreas.
 * ============================================================================
 */

import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import type { AreasPageProps, AreaRow } from './Areas.types'
import { useSwalTheme } from '@/Utils/swal'

export function useAreasIndex(props: AreasPageProps) {
    type InertiaErrors = Record<string, string[]>

    const page = usePage()
    const { Swal, toast, swalBaseClasses, ensurePopupDark } = useSwalTheme()

    /**
     * ----------------------------------------------------------
     * Filtros (source of truth)
     * ----------------------------------------------------------
     */
    const state = reactive({
        q: props.filters?.q ?? '',
        corporativo_id: props.filters?.corporativo_id ? Number(props.filters.corporativo_id) : null,

        // Default: Activas
        activo: (props.filters?.activo ?? '1') as 'all' | '1' | '0',

        perPage: Number(
            props.filters?.per_page ??
            props.filters?.perPage ??
            (props.areas as any)?.meta?.per_page ??
            (props.areas as any)?.per_page ??
            15
        ),
        sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
        dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
    })

    /**
     * ----------------------------------------------------------
     * Corporativos (SearchableSelect)
     * - Para filtros: all
     * - Para modal: solo activos (negocio)
     * ----------------------------------------------------------
     */
    const corporativosAll = computed(() => {
        const list = (props.corporativos ?? []) as any[]
        return list.filter((c) => c && typeof c.id !== 'undefined')
    })

    const corporativosActive = computed(() => corporativosAll.value.filter((c) => c.activo !== false))

    function corporativoIsActiveById(id: number | null): boolean {
        if (!id) return true
        const c = corporativosAll.value.find((x) => Number(x.id) === Number(id))
        return c ? c.activo !== false : true
    }

    /**
     * ----------------------------------------------------------
     * Selección (Set) + proxies
     * ----------------------------------------------------------
     */
    const selectedIds = ref<Set<number>>(new Set())
    const selectedCount = computed(() => selectedIds.value.size)

    const pageIds = computed<number[]>(() => (props.areas?.data ?? []).map((r: any) => Number(r.id)))

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
     * Paginación: normalización
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
        const raw = (((props.areas as any)?.meta?.links ?? (props.areas as any)?.links ?? []) as RawLink[]) || []
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
            route('areas.index'),
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

    watch(() => [state.q, state.corporativo_id, state.activo, state.perPage, state.sort, state.dir], debounceVisit)

    onBeforeUnmount(() => {
        if (t) window.clearTimeout(t)
    })

    const hasActiveFilters = computed(() => {
        const basePerPage = Number(props.filters?.per_page ?? props.filters?.perPage ?? 15)
        const baseSort = props.filters?.sort ?? 'nombre'
        const baseDir = props.filters?.dir ?? 'asc'

        return (
            !!String(state.q || '').trim() ||
            state.corporativo_id !== null ||

            // ✅ baseline ahora es '1'
            String(state.activo ?? '1') !== '1' ||

            Number(state.perPage) !== basePerPage ||
            state.dir !== baseDir ||
            state.sort !== baseSort
        )
    })

    function clearFilters() {
        state.q = ''
        state.corporativo_id = null

        // Al limpiar: Activas
        state.activo = '1'

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
     * Modal create/edit + Validaciones inline + Regla encadenada
     * ----------------------------------------------------------
     */
    const modalOpen = ref(false)
    const isEdit = ref(false)
    const saving = ref(false)

    const form = reactive({
        id: null as number | null,
        corporativo_id: null as number | null,
        nombre: '',
        activo: true,
    })

    const errors = reactive<{ corporativo_id?: string; nombre?: string }>({})

    function resetErrors() {
        errors.corporativo_id = undefined
        errors.nombre = undefined
    }

    function validateForm() {
        resetErrors()

        if (!String(form.nombre || '').trim()) errors.nombre = 'El nombre es obligatorio.'

        // Negocio: corporativo en baja => bloquea
        if (form.corporativo_id && !corporativoIsActiveById(form.corporativo_id)) {
        errors.corporativo_id = 'No puedes asignar un área a un corporativo en baja.'
        }

        return !errors.corporativo_id && !errors.nombre
    }

    watch(
        () => [form.corporativo_id, form.nombre, form.activo],
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
        activo: true,
        })
        resetErrors()
        modalOpen.value = true
        nextTick(() => validateForm())
    }

    function openEdit(row: AreaRow) {
        isEdit.value = true
        Object.assign(form, {
        id: Number((row as any).id),
        corporativo_id: Number((row as any).corporativo_id ?? (row as any).corporativo?.id ?? null),
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
        return !!String(form.nombre || '').trim() && !errors.corporativo_id && !saving.value
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

        // Negocio: corporativo en baja => bloquear
        if (form.corporativo_id && !corporativoIsActiveById(form.corporativo_id)) {
        await Swal.fire({
            icon: 'info',
            title: 'Acción no disponible',
            text: 'No puedes dar de alta/actualizar un área bajo un corporativo en baja.',
            confirmButtonText: 'Entendido',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        return
        }

        saving.value = true

        const payload = {
            corporativo_id: form.corporativo_id, // null permitido si tu backend lo permite
            nombre: String(form.nombre).trim(),
            activo: !!form.activo,
        }

        const finish = () => (saving.value = false)

        if (!isEdit.value) {
        router.post(route('areas.store'), payload, {
            preserveScroll: true,
            onFinish: finish,
            onSuccess: () => {
            closeModal()
            toast().fire({ icon: 'success', title: 'Área creada' })
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
                text: 'No se encontró el ID del área.',
                confirmButtonText: 'OK',
                customClass: swalBaseClasses(),
                didOpen: ensurePopupDark,
            })
            return
        }

        router.put(route('areas.update', form.id), payload, {
            preserveScroll: true,
            onFinish: finish,
            onSuccess: () => {
                closeModal()
                toast().fire({ icon: 'success', title: 'Área actualizada' })
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

    /**
     * ----------------------------------------------------------
     * Acciones: delete / activate
     * ----------------------------------------------------------
     */
    async function destroyRow(row: AreaRow) {
        const name = String((row as any).nombre ?? '')
        const id = Number((row as any).id)

        const res = await Swal.fire({
            icon: 'warning',
            title: 'Eliminar área',
            text: `Se eliminará "${name}".`,
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return

        router.delete(route('areas.destroy', id), {
        preserveScroll: true,
            onSuccess: () => {
                toast().fire({ icon: 'success', title: 'Área eliminada' })
                const next = new Set(selectedIds.value)
                next.delete(id)
                selectedIds.value = next
            },
            onError: () => {
                Swal.fire({
                icon: 'error',
                title: 'No se pudo eliminar',
                text: 'Puede haber restricciones o una explicación desde servidor.',
                confirmButtonText: 'OK',
                customClass: swalBaseClasses(),
                didOpen: ensurePopupDark,
                })
            },
        })
    }

    async function confirmActivate(row: any) {
        // Bloqueo por corporativo en baja (encadenado)
        if (row?.corporativo?.activo === false || row?.corporativo_activo === false) {
        await Swal.fire({
            icon: 'info',
            title: 'Acción no disponible',
            text: 'No puedes activar esta área porque su corporativo está dado de baja. Activa el corporativo primero.',
            confirmButtonText: 'Entendido',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        return
        }

        const res = await Swal.fire({
            icon: 'question',
            title: 'Activar área',
            text: `Se activará "${String(row?.nombre ?? '')}".`,
            showCancelButton: true,
            confirmButtonText: 'Activar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return
            router.patch(route('areas.activate', Number(row.id)), {}, {
            preserveScroll: true,
            onSuccess: () => toast().fire({ icon: 'success', title: 'Área activada' }),
            onError: () => Swal.fire({
                icon: 'error',
                title: 'No se pudo activar',
                text: 'Revisa permisos o el estatus del corporativo.',
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
            title: 'Eliminar seleccionadas',
            html: `<div class="text-sm">Se eliminarán <b>${ids.length}</b> áreas. Esta acción no se puede deshacer.</div>`,
            showCancelButton: true,
            confirmButtonText: `Eliminar (${ids.length})`,
            cancelButtonText: 'Cancelar',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return

        Swal.fire({
            title: 'Eliminando...',
            html: `<div class="text-sm">Procesando <b>${ids.length}</b> registros</div>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: swalBaseClasses(),
            didOpen: () => {
                ensurePopupDark()
                Swal.showLoading()
            },
        })

        let ok = 0
        let fail = 0

        for (const id of ids) {
            await new Promise<void>((resolve) => {
                router.delete(route('areas.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    ok++
                    resolve()
                },
                onError: () => {
                    fail++
                    resolve()
                },
                })
            })
        }

        Swal.close()
        clearSelection()

        if (fail === 0) toast().fire({ icon: 'success', title: `Eliminadas ${ok}` })
        else toast().fire({ icon: 'warning', title: `Eliminadas ${ok}, fallaron ${fail}` })
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
        () => props.areas?.data,
        () => clearSelection(),
        { deep: true }
    )

    onMounted(() => {
        // no-op
    })

    return {
        // filtros / sort
        state,
        corporativosAll,
        corporativosActive,
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
