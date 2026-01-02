import { computed, reactive, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

import { parseDate } from '@internationalized/date'
import type { WritableComputedOptions } from 'vue'

import { format } from 'date-fns'
import { es } from 'date-fns/locale'

type CatalogOption = Record<string, any>

// Inertia puede mandar string o string[]
type InertiaErrors = Record<string, string | string[]>

function pickFirst(v: unknown): string {
  if (!v) return ''
  if (Array.isArray(v)) return String(v[0] ?? '')
  return String(v)
}

export type CreateProps = {
  // rutas RESUELTAS desde backend (para no depender de Ziggy route())
  routes: {
    index: string
    store: string
    proveedoresStore: string
  }
  catalogos: {
    corporativos: CatalogOption[]
    sucursales: CatalogOption[]
    empleados: CatalogOption[]
    conceptos: CatalogOption[]
    proveedores: CatalogOption[]
  }
  ui?: { rol?: string }
}

type Banner = { type: 'info' | 'success' | 'warn'; title: string; text: string } | null

type OptionUI = {
  id: number
  nombre: string
  puesto?: string
  group?: string
}

export function useRequisicionCreate(props: CreateProps) {
  const page = usePage<any>()

  // ==========================================================
  // 1) ERRORES: TIENEN QUE SER MUTABLES (ref), NO computed
  //    - sync desde page.props.errors
  // ==========================================================
  const errors = ref<Record<string, string>>({})

  watch(
    () => page.props?.errors,
    (raw) => {
      const src = (raw ?? {}) as InertiaErrors
      const out: Record<string, string> = {}
      for (const [k, v] of Object.entries(src)) out[k] = pickFirst(v)
      errors.value = out
    },
    { immediate: true },
  )

  // helpers para errores locales
  function setErr(key: string, msg: string) {
    errors.value = { ...errors.value, [key]: msg }
  }
  function clearErr(key: string) {
    if (!errors.value[key]) return
    const copy = { ...errors.value }
    delete copy[key]
    errors.value = copy
  }

  // ==========================================================
  // 2) STATE BASE
  // ==========================================================
  const saving = ref(false)
  const banner = ref<Banner>(null)
  const savedDraftOnce = ref(false)

  // ==========================================================
  // 3) AUTH / ROL
  // ==========================================================
  const authUser = computed(() => page.props?.auth?.user ?? {})
  const rol = computed(() => String(props.ui?.rol ?? authUser.value?.rol ?? 'COLABORADOR').toUpperCase())
  const isColab = computed(() => rol.value === 'COLABORADOR')
  const isAdminOrContador = computed(() => rol.value === 'ADMIN' || rol.value === 'CONTADOR')

  // ==========================================================
  // 4) CATÁLOGOS
  // ==========================================================
  const corporativosActive = computed(() => (props.catalogos?.corporativos ?? []).filter((c) => c.activo !== false))
  const sucursalesActive = computed(() => (props.catalogos?.sucursales ?? []).filter((s) => s.activo !== false))
  const empleadosActive = computed(() => (props.catalogos?.empleados ?? []).filter((e) => e.activo !== false))
  const conceptosActive = computed(() => (props.catalogos?.conceptos ?? []).filter((c) => c.activo !== false))

  const proveedoresActive = ref<CatalogOption[]>(props.catalogos?.proveedores ?? [])
  watch(
    () => props.catalogos?.proveedores,
    (v) => (proveedoresActive.value = v ?? []),
    { immediate: true },
  )

  // ==========================================================
  // 5) UTILS
  // ==========================================================
  function normStr(v: unknown) {
    return String(v ?? '').trim()
  }

  function money(v: unknown) {
    const n = Number(v ?? 0)
    const safe = Number.isFinite(n) ? n : 0
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(safe)
    } catch {
      return String(v ?? '')
    }
  }

  function digitsOnly(v: string) {
    return String(v || '').replace(/\D+/g, '')
  }

  function clampInt(v: unknown, min = 1, max = 999999) {
    const n = Number(String(v ?? '').replace(/\D+/g, ''))
    if (!Number.isFinite(n)) return min
    return Math.max(min, Math.min(max, Math.trunc(n)))
  }

  function sanitizeMoney(v: string) {
    const raw = String(v ?? '')
      .replace(/[^0-9.]/g, '')
      .replace(/(\..*)\./g, '$1')
    const [a, b] = raw.split('.')
    if (b == null) return a
    return `${a}.${b.slice(0, 2)}`
  }

  function numberFromMoney(v: string) {
    const n = Number(v)
    return Number.isFinite(n) ? n : 0
  }

  // Blindaje numérico
  function allowKeyNumericInteger(e: KeyboardEvent) {
    const k = e.key
    if (k === 'Backspace' || k === 'Delete' || k === 'Tab' || k === 'Enter') return
    if (k === 'ArrowLeft' || k === 'ArrowRight' || k === 'Home' || k === 'End') return
    if (e.ctrlKey || e.metaKey) return
    if (!/^[0-9]$/.test(k)) e.preventDefault()
  }

  function allowKeyNumericDecimal(e: KeyboardEvent) {
    const k = e.key
    if (k === 'Backspace' || k === 'Delete' || k === 'Tab' || k === 'Enter') return
    if (k === 'ArrowLeft' || k === 'ArrowRight' || k === 'Home' || k === 'End') return
    if (e.ctrlKey || e.metaKey) return
    if (!/^[0-9.]$/.test(k)) e.preventDefault()
  }

  function beforeInputInteger(e: InputEvent) {
    const data = (e as any).data as string | null
    if (data == null) return
    if (!/^[0-9]+$/.test(data)) e.preventDefault()
  }

  function beforeInputDecimal(e: InputEvent) {
    const data = (e as any).data as string | null
    if (data == null) return
    if (!/^[0-9.]+$/.test(data)) e.preventDefault()
  }

  function onPasteInteger(e: ClipboardEvent) {
    e.preventDefault()
    const text = e.clipboardData?.getData('text') ?? ''
    const clean = digitsOnly(text)
    document.execCommand('insertText', false, clean)
  }

  function onPasteDecimal(e: ClipboardEvent) {
    e.preventDefault()
    const text = e.clipboardData?.getData('text') ?? ''
    const clean = sanitizeMoney(text)
    document.execCommand('insertText', false, clean)
  }

  // ==========================================================
  // 6) FORM CABECERA
  // ==========================================================
  const form = reactive({
    comprador_corp_id: null as number | null,
    solicitante_id: null as number | null,
    proveedor_id: null as number | null,
    concepto_id: null as number | null,
    sucursal_id: null as number | null,

    lugar_entrega_texto: '',
    fecha_entrega: null as string | null, // YYYY-MM-DD
    observaciones: '',

    recurrente: false,
    recurrencia: {
      frecuencia: 'MENSUAL' as 'SEMANAL' | 'MENSUAL' | 'TRIMESTRAL' | 'ANUAL',
    },
  })

  // ==========================================================
  // 7) EMPLEADOS FOR UI (TIPADO CON id) - ANTES DE WATCHERS QUE LO USEN
  // ==========================================================
  const empleadosForUI = computed<OptionUI[]>(() => {
    if (isColab.value) return []
    const sid = Number(form.sucursal_id ?? 0)
    if (!sid) return []

    return empleadosActive.value
      .filter((e) => Number(e.sucursal_id ?? 0) === sid)
      .map((e) => {
        const nombre = normStr(
          [e.nombre ?? e.name ?? '', e.apellido_paterno ?? '', e.apellido_materno ?? ''].filter(Boolean).join(' '),
        )
        return {
          id: Number(e.id),
          nombre: nombre || 'Sin nombre',
          puesto: normStr(e.puesto ?? e.puesto_nombre ?? '') || undefined,
          group: normStr(e.area?.nombre ?? e.area_nombre ?? e.area ?? e.departamento ?? 'Sin área') || 'Sin área',
        }
      })
  })

    // ==========================================================
    // 8) CALENDAR (FIX TS + FIX toDate) - v-model estable
    // ==========================================================
    // ==========================================================

    type CalendarModel = unknown // lo que sea que el Calendar emita (single, range, etc.)
    const deliveryModel = ref<CalendarModel>(undefined)

    // Convierte lo que emite el Calendar a ISO (YYYY-MM-DD) sin depender de tipos
    function calendarValueToISO(v: any): string {
    if (!v) return ''
    // si es array (por si el componente manda DateValue[])
    const one = Array.isArray(v) ? v[0] : v
    if (!one) return ''

    // DateValue normalmente tiene toString() -> 'YYYY-MM-DD'
    try {
        const s = String(one?.toString?.() ?? one)
        // defensivo: solo regresamos algo tipo 2026-01-16
        return /^\d{4}-\d{2}-\d{2}$/.test(s) ? s : ''
    } catch {
        return ''
    }
    }

    // Setea el modelo del calendar desde ISO del form
    function setCalendarFromISO(iso: string) {
    const s = normStr(iso)
    if (!s) {
        deliveryModel.value = undefined
        return
    }
    try {
        // parseDate devuelve CalendarDate (o equivalente), el Calendar lo acepta
        deliveryModel.value = parseDate(s) as any
    } catch {
        deliveryModel.value = undefined
    }
    }

    // Estado del popover (controlado)
    const deliveryOpen = ref(false)

    // Calendar -> form
    watch(
    () => deliveryModel.value,
    (v) => {
        const iso = calendarValueToISO(v)
        form.fecha_entrega = iso || null

        // Si ya hay fecha válida, cierra el popover
        if (iso) deliveryOpen.value = false
    },
    )

    // form -> Calendar
    watch(
    () => form.fecha_entrega,
    (v) => {
        const iso = normStr(v ?? '')
        if (!iso) {
        deliveryModel.value = undefined
        return
        }
        // evita loop
        if (calendarValueToISO(deliveryModel.value) === iso) return
        setCalendarFromISO(iso)
    },
    { immediate: true },
    )

    // Esto es lo que vas a usar en el template como v-model
    const deliveryDateModel = deliveryModel

    // Label sin toDate() (porque tu build NO lo trae)
    const deliveryLabel = computed(() => {
    const iso = normStr(form.fecha_entrega ?? '')
    if (!iso) return 'Selecciona fecha...'
    const d = new Date(`${iso}T00:00:00`)
    if (Number.isNaN(d.getTime())) return iso
    return format(d, 'PPP', { locale: es })
    })
  
  // ==========================================================
  // 9) COLABORADOR: solicitante + sucursal fijos
  // ==========================================================
  const fixedSolicitante = computed(() => {
    if (!isColab.value) return null
    const empleadoId = Number(authUser.value?.empleado_id ?? 0)
    if (!empleadoId) return null
    return empleadosActive.value.find((x) => Number(x.id) === empleadoId) ?? null
  })

  const fixedSucursal = computed(() => {
    if (!isColab.value) return null
    const emp = fixedSolicitante.value
    const sid = Number(emp?.sucursal_id ?? 0)
    if (!sid) return null
    return sucursalesActive.value.find((s) => Number(s.id) === sid) ?? null
  })

  watch(
    [rol, () => authUser.value?.empleado_id, () => props.catalogos?.empleados, () => props.catalogos?.sucursales],
    () => {
      if (!isColab.value) return
      const emp = fixedSolicitante.value
      if (!emp) return
      form.solicitante_id = Number(emp.id)

      const suc = fixedSucursal.value
      if (suc) form.sucursal_id = Number(suc.id)
    },
    { immediate: true },
  )

  // ==========================================================
  // 10) Sucursal: infiere corporativo + valida solicitante
  // ==========================================================
  watch(
    () => form.sucursal_id,
    (sid) => {
      clearErr('sucursal_id')

      const s = sucursalesActive.value.find((x) => Number(x.id) === Number(sid ?? 0))
      form.lugar_entrega_texto = normStr(s?.nombre) || ''

      const corpId = Number(s?.corporativo_id ?? 0)
      form.comprador_corp_id = corpId || null

      // si admin/cont: cuando cambie sucursal, el solicitante debe pertenecer a esa sucursal
      if (isAdminOrContador.value) {
        const ok = empleadosForUI.value.some((e) => Number(e.id) === Number(form.solicitante_id ?? 0))
        if (!ok) form.solicitante_id = null
      }
    },
    { immediate: true },
  )

  const corporativoName = computed(() => {
    const cid = Number(form.comprador_corp_id ?? 0)
    if (!cid) return ''
    return normStr(corporativosActive.value.find((c) => Number(c.id) === cid)?.nombre)
  })

  const sucursalName = computed(() =>
    normStr(sucursalesActive.value.find((s) => Number(s.id) === Number(form.sucursal_id ?? 0))?.nombre),
  )

  const solicitanteName = computed(() => {
    const emp = empleadosActive.value.find((e) => Number(e.id) === Number(form.solicitante_id ?? 0))
    const n = normStr(emp?.nombre ?? emp?.name ?? '')
    const ap = normStr(emp?.apellido_paterno ?? '')
    const am = normStr(emp?.apellido_materno ?? '')
    return normStr([n, ap, am].filter(Boolean).join(' '))
  })



  // ==========================================================
  // 11) CARRITO
  // ==========================================================
  const draft = reactive({
    cantidad: '1',
    descripcion: '',
    precio_unitario_sin_iva: '0',
    no_genera_iva: false,
  })

  type Item = {
    cantidad: number
    descripcion: string
    precio_unitario_sin_iva: number
    sucursal_id: number | null
    no_genera_iva: boolean
  }

  const items = ref<Item[]>([])
  const IVA_RATE = 0.16

  const computedRows = computed(() => {
    return items.value.map((it) => {
      const qty = Number(it.cantidad) || 0
      const pu = Number(it.precio_unitario_sin_iva) || 0
      const subtotal = qty * pu
      const iva = it.no_genera_iva ? 0 : subtotal * IVA_RATE
      const total = subtotal + iva
      return { ...it, subtotal, iva, total }
    })
  })

  const subtotal = computed(() => computedRows.value.reduce((acc, r) => acc + (Number(r.subtotal) || 0), 0))
  const ivaTotal = computed(() => computedRows.value.reduce((acc, r) => acc + (Number(r.iva) || 0), 0))
  const total = computed(() => subtotal.value + ivaTotal.value)

  function requireCabeceraForCart() {
    if (!form.sucursal_id) {
      setErr('sucursal_id', 'Selecciona sucursal.')
      banner.value = {
        type: 'warn',
        title: 'Falta completar cabecera.',
        text: 'Primero selecciona Sucursal (y con eso se define el Corporativo). Luego ya puedes agregar items.',
      }
      return false
    }
    banner.value = null
    return true
  }

  function onDraftQtyInput(e: Event) {
    const t = e.target as HTMLInputElement
    const clean = digitsOnly(t.value)
    const n = clampInt(clean || '1', 1)
    draft.cantidad = String(n)
  }

  function onDraftPriceInput(e: Event) {
    const t = e.target as HTMLInputElement
    const clean = sanitizeMoney(t.value)
    draft.precio_unitario_sin_iva = clean || '0'
  }

  function validateBeforeAddItem() {
    const e: Record<string, string> = {}

    if (!form.sucursal_id) e.sucursal_id = 'Selecciona sucursal.'

    const qty = clampInt(draft.cantidad, 1)
    if (qty <= 0) e.draft_cantidad = 'Cantidad inválida.'

    if (!normStr(draft.descripcion)) e.draft_descripcion = 'Descripción requerida.'

    const price = numberFromMoney(draft.precio_unitario_sin_iva)
    if (!(price > 0)) e.draft_precio = 'Precio requerido (mayor a 0).'

    errors.value = { ...errors.value, ...e }
    return Object.keys(e).length === 0
  }

  function addItem() {
    clearErr('draft_cantidad')
    clearErr('draft_descripcion')
    clearErr('draft_precio')

    if (!requireCabeceraForCart()) return
    if (!validateBeforeAddItem()) return

    const qty = clampInt(draft.cantidad, 1)
    const price = numberFromMoney(draft.precio_unitario_sin_iva)

    items.value.push({
      cantidad: qty,
      descripcion: normStr(draft.descripcion),
      precio_unitario_sin_iva: price,
      sucursal_id: form.sucursal_id,
      no_genera_iva: !!draft.no_genera_iva,
    })

    draft.cantidad = '1'
    draft.descripcion = ''
    draft.precio_unitario_sin_iva = '0'
    draft.no_genera_iva = false
  }

  function removeItem(idx: number) {
    items.value.splice(idx, 1)
  }

  // edición
  const editIdx = ref<number | null>(null)
  const editDraft = reactive({
    cantidad: '1',
    descripcion: '',
    precio_unitario_sin_iva: '0',
    no_genera_iva: false,
  })

  function startEdit(idx: number) {
    const it = items.value[idx]
    editIdx.value = idx
    editDraft.cantidad = String(clampInt(it.cantidad, 1))
    editDraft.descripcion = it.descripcion
    editDraft.precio_unitario_sin_iva = String(it.precio_unitario_sin_iva ?? 0)
    editDraft.no_genera_iva = !!it.no_genera_iva
  }

  function cancelEdit() {
    editIdx.value = null
  }

  function onEditQtyInput(e: Event) {
    const t = e.target as HTMLInputElement
    const clean = digitsOnly(t.value)
    editDraft.cantidad = String(clampInt(clean || '1', 1))
  }

  function onEditPriceInput(e: Event) {
    const t = e.target as HTMLInputElement
    editDraft.precio_unitario_sin_iva = sanitizeMoney(t.value) || '0'
  }

  function saveEdit() {
    if (editIdx.value == null) return
    const idx = editIdx.value

    const e: Record<string, string> = {}

    const qty = clampInt(editDraft.cantidad, 1)
    if (qty <= 0) e.edit_cantidad = 'Cantidad inválida.'

    if (!normStr(editDraft.descripcion)) e.edit_descripcion = 'Descripción requerida.'

    const price = numberFromMoney(editDraft.precio_unitario_sin_iva)
    if (!(price > 0)) e.edit_precio = 'Precio requerido (mayor a 0).'

    errors.value = { ...errors.value, ...e }
    if (Object.keys(e).length) return

    items.value[idx] = {
      ...items.value[idx],
      cantidad: qty,
      descripcion: normStr(editDraft.descripcion),
      precio_unitario_sin_iva: price,
      no_genera_iva: !!editDraft.no_genera_iva,
    }

    editIdx.value = null
  }

  // ==========================================================
  // 12) SUBMIT (sin Ziggy route)
  // ==========================================================
  function validateAll() {
    const e: Record<string, string> = {}

    if (!form.sucursal_id) e.sucursal_id = 'Selecciona sucursal.'
    if (!form.comprador_corp_id) e.comprador_corp_id = 'Corporativo requerido (se define por sucursal).'
    if (!form.solicitante_id) e.solicitante_id = 'Solicitante requerido.'
    if (!form.proveedor_id) e.proveedor_id = 'Proveedor requerido.'
    if (!form.concepto_id) e.concepto_id = 'Concepto requerido.'
    if (items.value.length === 0) e.items = 'Agrega al menos 1 elemento.'

    errors.value = e
    return Object.keys(e).length === 0
  }

  const canSubmit = computed(() => {
    return (
      !!form.comprador_corp_id &&
      !!form.solicitante_id &&
      !!form.proveedor_id &&
      !!form.concepto_id &&
      !!form.sucursal_id &&
      items.value.length > 0 &&
      !saving.value
    )
  })

  function submit() {
    if (saving.value) return
    banner.value = null

    if (!validateAll()) {
      banner.value = { type: 'warn', title: 'Faltan datos.', text: 'Completa la cabecera y agrega al menos un item.' }
      return
    }

    const status = savedDraftOnce.value ? 'CAPTURADA' : 'BORRADOR'
    saving.value = true

    router.post(
      props.routes.store,
      {
        status,
        comprador_corp_id: Number(form.comprador_corp_id),
        sucursal_id: form.sucursal_id,
        solicitante_id: form.solicitante_id,
        concepto_id: form.concepto_id,
        proveedor_id: form.proveedor_id,

        lugar_entrega_texto: normStr(form.lugar_entrega_texto) || null,
        fecha_entrega: form.fecha_entrega || null,
        observaciones: normStr(form.observaciones),

        monto_subtotal: subtotal.value,
        monto_total: total.value,

        detalles: computedRows.value.map((r0) => ({
          cantidad: Number(r0.cantidad) || 0,
          descripcion: r0.descripcion,
          precio_unitario: Number(r0.precio_unitario_sin_iva) || 0,
          subtotal: Number(r0.subtotal) || 0,
          total: Number(r0.total) || 0,
          sucursal_id: r0.sucursal_id,
          no_genera_iva: !!r0.no_genera_iva,
        })),

        recurrente: form.recurrente,
        recurrencia: form.recurrente
          ? { frecuencia: form.recurrencia.frecuencia, fecha_inicio: new Date().toISOString().slice(0, 10) }
          : null,
      },
      {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
        onError: (e: InertiaErrors) => {
          const out: Record<string, string> = {}
          for (const [k, v] of Object.entries(e ?? {})) out[k] = pickFirst(v)
          errors.value = out
          banner.value = { type: 'warn', title: 'Error de validación.', text: 'Revisa los campos marcados.' }
        },
        onSuccess: () => {
          if (!savedDraftOnce.value) {
            savedDraftOnce.value = true
            banner.value = { type: 'success', title: 'Borrador guardado.', text: 'Si vuelves a enviar, se CAPTURA.' }
          } else {
            banner.value = { type: 'warn', title: 'Requisición capturada.', text: 'Quedó en estatus CAPTURADA.' }
          }
        },
      },
    )
  }

  // ==========================================================
  // 13) MODAL PROVEEDOR (sin Ziggy route)
  // ==========================================================
  const provModalOpen = ref(false)
  const provSaving = ref(false)
  const provErrors = ref<Record<string, string>>({})

  const provForm = reactive({
    nombre_comercial: '',
    razon_social: '',
    direccion: '',
    contacto: '',
    rfc: '',
    telefono: '',
    email: '',
    beneficiario: '',
    banco: '',
    cuenta: '',
    clabe: '',
  })

  function openProveedorModal() {
    provErrors.value = {}
    Object.assign(provForm, {
      nombre_comercial: '',
      razon_social: '',
      direccion: '',
      contacto: '',
      rfc: '',
      telefono: '',
      email: '',
      beneficiario: '',
      banco: '',
      cuenta: '',
      clabe: '',
    })
    provModalOpen.value = true
  }

  function closeProveedorModal() {
    if (provSaving.value) return
    provModalOpen.value = false
  }

  function validateProveedor() {
    const e: Record<string, string> = {}
    if (!normStr(provForm.nombre_comercial)) e.nombre_comercial = 'Nombre comercial requerido.'
    if (!normStr(provForm.razon_social)) e.razon_social = 'Razón social requerida.'
    if (!normStr(provForm.direccion)) e.direccion = 'Dirección requerida.'
    if (!normStr(provForm.contacto)) e.contacto = 'Contacto requerido.'
    if (!normStr(provForm.rfc)) e.rfc = 'RFC requerido.'
    if (!normStr(provForm.banco)) e.banco = 'Banco requerido.'
    if (!normStr(provForm.cuenta)) e.cuenta = 'Número de cuenta requerido.'
    if (!normStr(provForm.clabe)) e.clabe = 'CLABE requerida.'
    provErrors.value = e
    return Object.keys(e).length === 0
  }

  function createProveedor() {
    if (provSaving.value) return
    if (!validateProveedor()) return

    provSaving.value = true

    router.post(
      props.routes.proveedoresStore,
      {
        nombre_comercial: normStr(provForm.nombre_comercial),
        razon_social: normStr(provForm.razon_social),
        direccion: normStr(provForm.direccion),
        contacto: normStr(provForm.contacto),
        rfc: normStr(provForm.rfc).toUpperCase(),
        telefono: digitsOnly(provForm.telefono),
        email: normStr(provForm.email),
        beneficiario: normStr(provForm.beneficiario),
        banco: normStr(provForm.banco),
        cuenta: digitsOnly(provForm.cuenta),
        clabe: digitsOnly(provForm.clabe),
      },
      {
        preserveScroll: true,
        onFinish: () => (provSaving.value = false),
        onError: (e: InertiaErrors) => {
          const out: Record<string, string> = {}
          for (const [k, v] of Object.entries(e ?? {})) out[k] = pickFirst(v)
          provErrors.value = out
        },
        onSuccess: () => {
          router.reload({ only: ['catalogos'] })
          closeProveedorModal()
        },
      },
    )
  }

  return {
    // rol
    rol,
    isColab,
    isAdminOrContador,

    // catálogos
    sucursalesActive,
    conceptosActive,
    proveedoresActive,
    empleadosForUI,

    // form
    form,
    corporativoName,
    sucursalName,
    solicitanteName,

    // calendar
    deliveryDateModel,
    deliveryLabel,
    deliveryOpen,

    // carrito
    draft,
    items,
    computedRows,
    subtotal,
    ivaTotal,
    total,

    // estado
    saving,
    errors,
    banner,
    savedDraftOnce,
    canSubmit,

    // acciones
    submit,
    addItem,
    removeItem,
    startEdit,
    cancelEdit,
    saveEdit,
    editIdx,
    editDraft,
    onDraftQtyInput,
    onDraftPriceInput,
    onEditQtyInput,
    onEditPriceInput,

    // modal proveedor
    provModalOpen,
    provSaving,
    provErrors,
    provForm,
    openProveedorModal,
    closeProveedorModal,
    createProveedor,

    // helpers
    money,

    // blindaje numérico
    allowKeyNumericInteger,
    allowKeyNumericDecimal,
    beforeInputInteger,
    beforeInputDecimal,
    onPasteInteger,
    onPasteDecimal,
  }
}
