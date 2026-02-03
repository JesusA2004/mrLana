// resources/js/Pages/Empleados/useEmpleadosIndex.ts
import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import type {
    EmpleadosPageProps,
    EmpleadoRow,
    PaginationLink,
    CorporativoLite,
    SucursalLite,
    AreaLite,
} from './Empleados.types'
import { useSwalTheme } from '@/Utils/swal'

type InertiaErrors = Record<string, string>

type FormErrors = Partial<
    Record<
        | 'corporativo_id'
        | 'sucursal_id'
        | 'area_id'
        | 'nombre'
        | 'apellido_paterno'
        | 'user_email'
        | 'user_rol'
        | 'user_password'
        | 'user_password_confirmation',
        string
    >
>

function firstError(e: InertiaErrors): string {
    const v = Object.values(e ?? {})[0]
    if (!v) return 'Error de validación.'
    return String(v)
}

function normStr(v: unknown) {
    return String(v ?? '').trim()
}

function isEmail(v: string) {
    const s = normStr(v)
    if (!s) return false
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s)
}

function fullNameFromParts(p: { nombre?: string; apellido_paterno?: string; apellido_materno?: string }) {
    return `${p.nombre ?? ''} ${p.apellido_paterno ?? ''}${p.apellido_materno ? ` ${p.apellido_materno}` : ''}`.trim()
}

function formatLabel(label: string) {
    const t = String(label).replace(/&laquo;|&raquo;|&hellip;/g, '').replace(/<[^>]*>/g, '').trim()
    const low = t.toLowerCase()
    if (low.includes('previous') || low.includes('prev') || low.includes('anterior') || low.includes('atrás')) return 'Atrás'
    if (low.includes('next') || low.includes('siguiente')) return 'Siguiente'
    return t || '...'
}

// Normalizo selects (a veces llega '' en vez de null)
function toNullNumber(v: unknown): number | null {
    if (v === '' || v === undefined || v === null) return null
    const n = Number(v)
    return Number.isFinite(n) ? n : null
}

type Opt = { id: number; nombre?: string; activo?: boolean; [k: string]: any }
function withStatusLabel<T extends Opt>(list: T[], key = 'nombre') {
    return (list ?? []).map((o) => ({
        ...o,
        __label: o?.activo === false ? `${o?.[key] ?? '—'} (Baja)` : (o?.[key] ?? '—'),
    }))
}

function isInactive(list: Opt[], id: number | null) {
    if (!id) return false
    const found = (list ?? []).find((x) => Number(x.id) === Number(id))
    return found?.activo === false
}

function keepSelected<T extends Opt>(list: T[], selectedId: number | null) {
    if (!selectedId) return list ?? []
    const sel = (list ?? []).find((x) => Number(x.id) === Number(selectedId))
    if (!sel) return list ?? []
    const exists = (list ?? []).some((x) => Number(x.id) === Number(sel.id))
    return exists ? (list ?? []) : [sel, ...(list ?? [])]
}

export function useEmpleadosIndex(props: EmpleadosPageProps) {
    // Yo centralizo aquí el tema de SweetAlert para tenerlo consistente en todo el módulo
    const { Swal, toast, swalBaseClasses, ensurePopupDark, ensureSwalOnTop } = useSwalTheme()

    // Rol del usuario actual (para permisos simples sin tabla de permisos)
    const page = usePage()
    const currentRole = computed(() => String((page.props as any)?.auth?.user?.rol ?? ''))
    const canEditPassword = computed(() => currentRole.value === 'ADMIN')

    /**
     * ---------------------------------------------------------
     * FILTROS (INDEX)
     * ---------------------------------------------------------
     */
    const state = reactive({
        q: props.filters?.q ?? '',
        corporativo_id: toNullNumber(props.filters?.corporativo_id ?? null),
        sucursal_id: toNullNumber(props.filters?.sucursal_id ?? null),
        area_id: toNullNumber(props.filters?.area_id ?? null),
        activo: (props.filters?.activo ?? '1') as 'all' | '1' | '0',
        perPage: Number((props.filters as any)?.per_page ?? (props.filters as any)?.perPage ?? 15),
        sort: (props.filters?.sort ?? 'nombre') as 'nombre' | 'id',
        dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
    })

    const modalOpen = ref(false)

    // Index: sucursal/área solo si ya elegí corporativo (evito combinaciones inválidas)
    const canPickSucursalFilter = computed(() => !!state.corporativo_id)
    const canPickAreaFilter = computed(() => !!state.corporativo_id)

    /**
     * ---------------------------------------------------------
     * CATÁLOGOS (Index)
     * ---------------------------------------------------------
     */
    const corporativosAll = computed<CorporativoLite[]>(() => {
        return [...(props.corporativos ?? [])].sort((a, b) => String(a.nombre ?? '').localeCompare(String(b.nombre ?? ''), 'es'))
    })

    const sucursalesAll = computed<SucursalLite[]>(() => {
        return [...(props.sucursales ?? [])].sort((a, b) => String(a.nombre ?? '').localeCompare(String(b.nombre ?? ''), 'es'))
    })

    const areasAll = computed<AreaLite[]>(() => {
        return [...(props.areas ?? [])].sort((a, b) => String(a.nombre ?? '').localeCompare(String(b.nombre ?? ''), 'es'))
    })

    // Si corporativo = null, reseteo sucursal/área (para no mandar filtros huérfanos)
    watch(
        () => state.corporativo_id,
        (nv) => {
        state.corporativo_id = toNullNumber(nv)
        if (!state.corporativo_id) {
            state.sucursal_id = null
            state.area_id = null
        } else {
            const corpId = Number(state.corporativo_id)
            if (state.sucursal_id) {
            const ok = sucursalesAll.value.some(
                (s: any) => Number(s.id) === Number(state.sucursal_id) && Number(s.corporativo_id ?? s.corporativo?.id) === corpId
            )
            if (!ok) state.sucursal_id = null
            }
            if (state.area_id) {
            const ok = areasAll.value.some((a: any) => Number(a.id) === Number(state.area_id) && Number(a.corporativo_id ?? 0) === corpId)
            if (!ok) state.area_id = null
            }
        }
        },
        { flush: 'post' }
    )

    const corporativosForSelect = computed(() => corporativosAll.value)
    const sucursalesForSelect = computed(() => {
        if (!state.corporativo_id) return []
        const corpId = Number(state.corporativo_id)
        return sucursalesAll.value.filter((s: any) => Number(s.corporativo_id ?? s.corporativo?.id) === corpId)
    })
    const areasForSelect = computed(() => {
        if (!state.corporativo_id) return []
        const corpId = Number(state.corporativo_id)
        return areasAll.value.filter((a: any) => Number(a.corporativo_id ?? 0) === corpId)
    })

    const corporativosLabeled = computed(() => withStatusLabel(corporativosForSelect.value as any[], 'nombre'))
    const sucursalesLabeled = computed(() => withStatusLabel(sucursalesForSelect.value as any[], 'nombre'))
    const areasLabeled = computed(() => withStatusLabel(areasForSelect.value as any[], 'nombre'))

    // SELECCIÓN MASIVA
    const selectedIds = ref<Set<number>>(new Set())
    const selectedCount = computed(() => selectedIds.value.size)
    const pageIds = computed<number[]>(() => (props.empleados?.data ?? []).map((r: any) => Number(r.id)))
    const isAllSelectedOnPage = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selectedIds.value.has(id)))

    const selectedIdsArray = computed<number[]>({
        get() {
        return Array.from(selectedIds.value)
        },
        set(values: number[]) {
        selectedIds.value = new Set((values ?? []).map((v) => Number(v)))
        },
    })

    function toggleAllOnPage(checked: boolean) {
        const next = new Set(selectedIds.value)
        for (const id of pageIds.value) checked ? next.add(id) : next.delete(id)
        selectedIds.value = next
    }

    function clearSelection() {
        selectedIds.value = new Set()
    }

    /**
     * ---------------------------------------------------------
     * PAGINACIÓN
     * ---------------------------------------------------------
     */
    const paginationLinks = computed<PaginationLink[]>(() => {
        const links = (props.empleados as any)?.links ?? (props.empleados as any)?.meta?.links ?? []
        return (links ?? []).map((l: any) => ({
        url: l?.url ?? null,
        label: formatLabel(l?.label ?? ''),
        active: Boolean(l?.active),
        }))
    })

    const mobileLinks = computed<PaginationLink[]>(() => (paginationLinks.value ?? []).filter((l) => typeof l.label === 'string'))

    function linkLabelShort(label: string): string {
        const clean = String(label).replace(/&laquo;|&raquo;|&hellip;/g, '').replace(/<[^>]*>/g, '').trim()
        const low = clean.toLowerCase()
        if (low.includes('previous') || low.includes('prev') || low.includes('anterior') || low.includes('atrás')) return 'Atrás'
        if (low.includes('next') || low.includes('siguiente')) return 'Siguiente'
        if (/^\d+$/.test(clean)) return clean
        return clean || '…'
    }

    function goTo(url: string | null) {
        if (!url) return
        clearSelection()
        router.visit(url, { preserveState: true, preserveScroll: true, replace: true })
    }

    /**
     * ---------------------------------------------------------
     * NAVEGACIÓN POR FILTROS
     * - Debounce para no spamear peticiones al teclear
     * - Si el modal está abierto, NO navego
     * ---------------------------------------------------------
     */
    let t: number | null = null

    function buildQuery() {
        return {
        q: normStr(state.q),
        corporativo_id: state.corporativo_id ?? '',
        sucursal_id: state.sucursal_id ?? '',
        area_id: state.area_id ?? '',
        activo: state.activo ?? 'all',
        per_page: Number(state.perPage) || 15,
        sort: state.sort,
        dir: state.dir,
        }
    }

    function visitNow() {
        if (modalOpen.value) return
        clearSelection()
        router.get(route('empleados.index'), buildQuery(), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        })
    }

    function debounceVisit() {
        if (modalOpen.value) return
        if (t) window.clearTimeout(t)

        t = window.setTimeout(() => {
        visitNow()
        }, 300)
    }

    watch(
        () => [state.q, state.corporativo_id, state.sucursal_id, state.area_id, state.activo, state.perPage, state.sort, state.dir],
        debounceVisit,
        { flush: 'post' }
    )

    onBeforeUnmount(() => {
        if (t) window.clearTimeout(t)
    })

    const hasActiveFilters = computed(() => {
        return (
        normStr(state.q).length > 0 ||
        state.corporativo_id !== null ||
        state.sucursal_id !== null ||
        state.area_id !== null ||
        String(state.activo ?? '1') !== '1' ||
        Number(state.perPage) !== 15 ||
        state.sort !== 'nombre' ||
        state.dir !== 'asc'
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
        // Yo fuerzo el refresh inmediato para que el usuario lo vea al instante
        visitNow()
    }

    const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
    function toggleSort() {
        // Yo dejo el sort fijo por "nombre completo" y solo alterno dirección
        state.sort = 'nombre'
        state.dir = state.dir === 'asc' ? 'desc' : 'asc'
        // Importantísimo: esto lo hago "immediate" para que el botón sí se sienta (sin esperar debounce)
        visitNow()
    }

    // MODAL + FORM
    const isEdit = ref(false)
    const saving = ref(false)
    const savingText = computed(() => (isEdit.value ? 'Espere, se está actualizando el empleado...' : 'Espere, se está dando de alta el empleado...'))

    const form = reactive({
        id: null as number | null,
        corporativo_id: null as number | null,
        sucursal_id: null as number | null,
        area_id: null as number | null,
        nombre: '',
        apellido_paterno: '',
        apellido_materno: '',
        telefono: '',
        puesto: '',
        activo: true,
        user_email: '',
        user_rol: 'COLABORADOR' as 'ADMIN' | 'CONTADOR' | 'COLABORADOR',
        user_activo: true,
        // Solo admin usa esto al EDITAR (si lo dejo vacío, no cambia)
        user_password: '',
        user_password_confirmation: '',
    })

    const errors = reactive<FormErrors>({})

    function setError<K extends keyof FormErrors>(k: K, v?: string) {
        const current = (errors as any)[k]
        if (!v) {
        if (current) delete (errors as any)[k]
        return
        }
        if (current !== v) (errors as any)[k] = v
    }

    function resetErrors() {
        for (const k of Object.keys(errors)) delete (errors as any)[k]
    }

    const modalSucursales = computed<SucursalLite[]>(() => {
        const corpId = form.corporativo_id ? Number(form.corporativo_id) : 0
        if (!corpId) return []
        return sucursalesAll.value.filter((s: any) => Number(s.corporativo_id ?? s.corporativo?.id) === corpId)
    })

    const modalAreas = computed<AreaLite[]>(() => {
        const corpId = form.corporativo_id ? Number(form.corporativo_id) : 0
        if (!corpId) return []
        return areasAll.value.filter((a: any) => Number(a.corporativo_id ?? 0) === corpId)
    })

    const modalCorporativos = computed(() => withStatusLabel(corporativosAll.value as any[], 'nombre'))

    const modalSucursalesSafe = computed(() => {
        const base = (modalSucursales.value as any[]) ?? []
        const activeOnly = base.filter((x) => x?.activo !== false)
        const merged = keepSelected(activeOnly, form.sucursal_id)
        return withStatusLabel(merged, 'nombre')
    })

    const modalAreasSafe = computed(() => {
        const base = (modalAreas.value as any[]) ?? []
        const activeOnly = base.filter((x) => x?.activo !== false)
        const merged = keepSelected(activeOnly, form.area_id)
        return withStatusLabel(merged, 'nombre')
    })

    const modalHasInactiveSelection = computed(() => {
        const corpBad = isInactive(corporativosAll.value as any[], form.corporativo_id)
        const sucBad = isInactive(modalSucursales.value as any[], form.sucursal_id)
        const areaBad = isInactive(modalAreas.value as any[], form.area_id)
        return corpBad || sucBad || areaBad
    })

    watch(
        () => form.corporativo_id,
        () => {
            if (!modalOpen.value) return
            form.corporativo_id = toNullNumber(form.corporativo_id)
            if (!form.corporativo_id) {
                form.sucursal_id = null
                form.area_id = null
                return
            }
            const corpId = Number(form.corporativo_id)
            if (
                form.sucursal_id &&
                !modalSucursales.value.some((s: any) => Number(s.id) === Number(form.sucursal_id) && Number(s.corporativo_id ?? s.corporativo?.id) === corpId)
            ) {
                form.sucursal_id = null
            }
            if (form.area_id && !modalAreas.value.some((a: any) => Number(a.id) === Number(form.area_id) && Number(a.corporativo_id ?? 0) === corpId)) {
                form.area_id = null
            }
        },
        { flush: 'post' }
    )

    function validatePasswordsIfNeeded() {
        // Solo admin y solo si estoy en edición
        if (!isEdit.value || !canEditPassword.value) {
            setError('user_password', undefined)
            setError('user_password_confirmation', undefined)
            return
        }

        const pass = normStr(form.user_password)
        const pass2 = normStr(form.user_password_confirmation)
        // Si ambos vacíos, no valido nada (no cambia password)
        if (!pass && !pass2) {
            setError('user_password', undefined)
            setError('user_password_confirmation', undefined)
            return
        }
        // Si uno se llenó, exijo ambos
        if (pass.length < 8) setError('user_password', 'La contraseña debe tener al menos 8 caracteres.')
        else setError('user_password', undefined)

        if (!pass2) setError('user_password_confirmation', 'Confirma la contraseña.')
        else if (pass !== pass2) setError('user_password_confirmation', 'Las contraseñas no coinciden.')
        else setError('user_password_confirmation', undefined)
    }

    function validateAll() {
        setError('corporativo_id', form.corporativo_id ? undefined : 'Selecciona un corporativo.')
        setError('sucursal_id', form.sucursal_id ? undefined : 'Selecciona una sucursal.')
        setError('nombre', normStr(form.nombre) ? undefined : 'El nombre es obligatorio.')
        setError('apellido_paterno', normStr(form.apellido_paterno) ? undefined : 'El apellido paterno es obligatorio.')
        setError('user_email', isEmail(form.user_email) ? undefined : 'Ingresa un email válido.')
        setError('user_rol', normStr(form.user_rol) ? undefined : 'Selecciona un rol.')
        validatePasswordsIfNeeded()
        return Object.keys(errors).length === 0
    }

    watch(
        () => [
            modalOpen.value,
            form.corporativo_id,
            form.sucursal_id,
            form.nombre,
            form.apellido_paterno,
            form.user_email,
            form.user_rol,
            form.user_password,
            form.user_password_confirmation,
        ],
        () => {
            if (!modalOpen.value) return
            validateAll()
        },
        { flush: 'post' }
    )

    const canSubmit = computed(() => {
        if (saving.value) return false
        if (!form.corporativo_id) return false
        if (!form.sucursal_id) return false
        if (!normStr(form.nombre)) return false
        if (!normStr(form.apellido_paterno)) return false
        if (!isEmail(form.user_email)) return false
        if (!normStr(form.user_rol)) return false
        // Si admin capturó password, exijo que sea válido
        if (isEdit.value && canEditPassword.value) {
            const pass = normStr(form.user_password)
            const pass2 = normStr(form.user_password_confirmation)
            if (pass || pass2) {
                if (pass.length < 8) return false
                if (pass !== pass2) return false
            }
        }
        return true
    })

    const canSubmitFinal = computed(() => !!canSubmit.value && !modalHasInactiveSelection.value)

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
            user_password: '',
            user_password_confirmation: '',
        })
        resetErrors()
        modalOpen.value = true
        nextTick(() => validateAll())
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
            user_email: String((row as any)?.user?.email ?? (row as any)?.email ?? ''),
            user_rol: ((row as any)?.user?.rol ?? 'COLABORADOR') as any,
            user_activo: Boolean((row as any)?.user?.activo ?? true),
            // Importante: al abrir edición nunca precargo password
            user_password: '',
            user_password_confirmation: '',
        })
        resetErrors()
        modalOpen.value = true
        nextTick(() => validateAll())
    }

    function closeModal(force = false) {
        if (saving.value && !force) return
        modalOpen.value = false
    }

    async function submit() {
        if (saving.value) return

        if (!validateAll()) {
            await Swal.fire({
                icon: 'warning',
                title: 'Faltan campos',
                text: 'Revisa los campos marcados.',
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
            nombre: normStr(form.nombre),
            apellido_paterno: normStr(form.apellido_paterno),
            apellido_materno: normStr(form.apellido_materno) || null,
            telefono: normStr(form.telefono) || null,
            puesto: normStr(form.puesto) || null,
            activo: !!form.activo,
            user_name: fullNameFromParts(form),
            user_email: normStr(form.user_email),
            user_rol: form.user_rol,
            user_activo: !!form.user_activo,
        }

        // Solo admin y solo si capturé password: lo mando
        if (isEdit.value && canEditPassword.value) {
            const pass = normStr(form.user_password)
            const pass2 = normStr(form.user_password_confirmation)
            if (pass && pass2 && pass === pass2) {
                payload.user_password = pass
                payload.user_password_confirmation = pass2
            }
        }

        const finish = () => (saving.value = false)

        if (!isEdit.value) {
        router.post(route('empleados.store'), payload, {
            preserveScroll: true,
            onFinish: finish,
            onSuccess: () => {
                closeModal(true)
                toast().fire({ icon: 'success', title: 'Empleado creado' })
                clearSelection()
            },
            onError: (e: InertiaErrors) => {
                ensureSwalOnTop()
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo registrar',
                    text: firstError(e),
                    confirmButtonText: 'OK',
                    customClass: swalBaseClasses(),
                    didOpen: ensurePopupDark,
                })
            },
        })
        return
        }

        router.put(route('empleados.update', form.id), payload, {
            preserveScroll: true,
            onFinish: finish,
            onSuccess: () => {
                closeModal(true)
                toast().fire({ icon: 'success', title: 'Empleado actualizado' })
                clearSelection()
            },
            onError: (e: InertiaErrors) => {
                ensureSwalOnTop()
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

    async function confirmDeactivate(row: EmpleadoRow) {
        const id = Number((row as any).id)
        if (!(row as any).activo) return confirmActivate(row)

        const res = await Swal.fire({
            icon: 'warning',
            title: 'Eliminación',
            text: `¿Eliminar a "${fullNameFromParts(row as any)}"?`,
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
        })
        if (!res.isConfirmed) return

        router.delete(route('empleados.destroy', id), {
            preserveScroll: true,
            onSuccess: () => toast().fire({ icon: 'success', title: 'Empleado eliminado' }),
            onError: (e: InertiaErrors) => {
                ensureSwalOnTop()
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo eliminar',
                    text: firstError(e),
                    confirmButtonText: 'OK',
                    customClass: swalBaseClasses(),
                    didOpen: ensurePopupDark,
                })
            },
        })
    }

    async function confirmActivate(row: EmpleadoRow) {
        const id = Number((row as any).id)

        const res = await Swal.fire({
            icon: 'question',
            title: 'Activar',
            text: `¿Activar a "${fullNameFromParts(row as any)}"?`,
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
            onError: (e: InertiaErrors) => {
                ensureSwalOnTop()
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo activar',
                    text: firstError(e),
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
            title: 'Eliminación masiva',
            text: `Se eliminaran ${ids.length} empleado(s).`,
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
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
            toast().fire({ icon: 'success', title: 'Eliminación aplicada' })
        },
        onError: (e: InertiaErrors) => {
            ensureSwalOnTop()
            Swal.fire({
            icon: 'error',
            title: 'No se pudo procesar',
            text: firstError(e),
            confirmButtonText: 'OK',
            customClass: swalBaseClasses(),
            didOpen: ensurePopupDark,
            })
        },
        })
    }

    // Si el backend manda flash success, lo convierto a toast
    watch(
        () => (page.props as any)?.flash,
        (f: any) => {
        const msg = f?.success || f?.message
        if (msg) toast().fire({ icon: 'success', title: String(msg) })
        },
        { deep: true }
    )

    // helpers display
    function fullName(r: any) {
        const a = `${r.nombre ?? ''} ${r.apellido_paterno ?? ''}${r.apellido_materno ? ` ${r.apellido_materno}` : ''}`
        return a.trim() || '—'
    }
    function corpName(row: any) {
        return row?.sucursal?.corporativo?.nombre ?? row?.corporativo?.nombre ?? row?.corporativo_nombre ?? '—'
    }
    function sucursalName(row: any) {
        return row?.sucursal?.nombre ?? row?.sucursal_nombre ?? '—'
    }
    function areaName(row: any) {
        return row?.area?.nombre ?? row?.area_nombre ?? '—'
    }
    function emailValue(row: any) {
        return row?.user?.email ?? row?.email ?? '—'
    }
    function badgeText(active: boolean) {
        return active ? 'Activo' : 'Inactivo'
    }

    const from = computed(() => (props.empleados as any)?.from ?? (props.empleados as any)?.meta?.from ?? 0)
    const to = computed(() => (props.empleados as any)?.to ?? (props.empleados as any)?.meta?.to ?? 0)
    const total = computed(() => (props.empleados as any)?.total ?? (props.empleados as any)?.meta?.total ?? 0)

    return {
        state,
        hasActiveFilters,
        clearFilters,
        sortLabel,
        toggleSort,

        corporativosLabeled,
        sucursalesLabeled,
        areasLabeled,
        canPickSucursalFilter,
        canPickAreaFilter,

        selectedIdsArray,
        selectedCount,
        isAllSelectedOnPage,
        toggleAllOnPage,
        clearSelection,
        confirmBulkDeactivate,

        paginationLinks,
        mobileLinks,
        linkLabelShort,
        goTo,

        modalOpen,
        isEdit,
        saving,
        savingText,
        form,
        errors,
        canSubmitFinal,
        canEditPassword,
        openCreate,
        openEdit,
        closeModal,
        submit,

        modalCorporativos,
        modalSucursalesSafe,
        modalAreasSafe,
        modalHasInactiveSelection,

        confirmDeactivate,
        confirmActivate,

        fullName,
        corpName,
        sucursalName,
        areaName,
        emailValue,
        badgeText,
        from,
        to,
        total,
    }
}
