import { reactive, computed, watch, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { swalOk, swalErr, swalLoading, swalClose } from '@/lib/swal'

type Catalogos = {
  corporativos: { id: number; nombre: string; activo?: boolean }[]
  sucursales: { id: number; nombre: string; codigo: string; corporativo_id: number; activo?: boolean }[]
  empleados: { id: number; nombre: string; sucursal_id: number; activo?: boolean }[]
  conceptos: { id: number; nombre: string; activo?: boolean }[]
  proveedores: { id: number; razon_social: string; rfc: string; clabe: string; banco: string; status: string }[]
}

type Plantilla = any | null
type InertiaErrors = Record<string, string | string[]>

function firstErrorMessage(errors: InertiaErrors | undefined | null): string | null {
  if (!errors) return null
  const v = Object.values(errors)[0]
  if (!v) return null
  return Array.isArray(v) ? (v[0] ?? null) : v
}

export function useRequisicionCreate(catalogos: Catalogos, plantilla: Plantilla = null) {
  const page = usePage<any>()
  const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
  const empleadoId = page.props?.auth?.user?.empleado_id ?? null

  const saving = ref(false)
  const showError = ref(false)
  const errors = ref<InertiaErrors>({})

  const state = reactive({
    corporativo_id: '' as number | string,
    sucursal_id: '' as number | string,
    solicitante_id: '' as number | string,
    comprador_corp_id: '' as number | string,
    proveedor_id: '' as number | string,
    concepto_id: '' as number | string,
    monto_subtotal: 0,
    monto_total: 0,
    fecha_solicitud: '' as string,
    fecha_autorizacion: '' as string,
    observaciones: '',
    detalles: [] as Array<{
      sucursal_id: number | string | null
      cantidad: number
      descripcion: string
      precio_unitario: number
      genera_iva: boolean
      subtotal: number
      iva: number
      total: number
    }>,
  })

  function fieldError(key: string): string | null {
    const v = (errors.value as any)?.[key]
    if (!v) return null
    return Array.isArray(v) ? (v[0] ?? null) : v
  }

  function loadFromPlantilla() {
    if (!plantilla) return
    state.corporativo_id = plantilla.comprador_corp_id ?? ''
    state.sucursal_id = plantilla.sucursal_id ?? ''
    state.solicitante_id = plantilla.solicitante_id ?? ''
    state.comprador_corp_id = plantilla.comprador_corp_id ?? ''
    state.proveedor_id = plantilla.proveedor_id ?? ''
    state.concepto_id = plantilla.concepto_id ?? ''
    state.monto_subtotal = Number(plantilla.monto_subtotal ?? 0)
    state.monto_total = Number(plantilla.monto_total ?? 0)
    state.fecha_solicitud = plantilla.fecha_solicitud ?? ''
    state.fecha_autorizacion = plantilla.fecha_autorizacion ?? ''
    state.observaciones = plantilla.observaciones ?? ''
    state.detalles = (plantilla.detalles ?? []).map((d: any) => ({
      sucursal_id: d.sucursal_id ?? '',
      cantidad: Number(d.cantidad ?? 1),
      descripcion: d.descripcion ?? '',
      precio_unitario: Number(d.precio_unitario ?? 0),
      genera_iva: Boolean(d.genera_iva ?? true),
      subtotal: Number(d.subtotal ?? 0),
      iva: Number(d.iva ?? 0),
      total: Number(d.total ?? 0),
    }))
  }
  loadFromPlantilla()

  if (role.value === 'COLABORADOR' && empleadoId) {
    state.solicitante_id = empleadoId
  }

  const corporativosActive = computed(() => (catalogos.corporativos ?? []).filter((c) => c.activo !== false))
  const sucursalesActive = computed(() => (catalogos.sucursales ?? []).filter((s) => s.activo !== false))
  const empleadosActive = computed(() => (catalogos.empleados ?? []).filter((e) => e.activo !== false))
  const conceptosActive = computed(() => (catalogos.conceptos ?? []).filter((c) => c.activo !== false))
  const proveedoresList = computed(() => (catalogos.proveedores ?? []).filter((p) => String(p.status ?? '').toUpperCase() === 'ACTIVO'))

  const sucursalesFiltered = computed(() => {
    const corpId = Number(state.corporativo_id || 0)
    if (!corpId) return []
    return sucursalesActive.value.filter((s) => Number(s.corporativo_id) === corpId)
  })

  const selectedProveedor = computed(() => {
    const id = Number(state.proveedor_id || 0)
    if (!id) return null
    return proveedoresList.value.find((p) => Number(p.id) === id) ?? null
  })

  watch(
    () => state.corporativo_id,
    (newVal) => {
      errors.value = {}
      const corpId = Number(newVal || 0)
      state.comprador_corp_id = corpId ? corpId : ''
      if (!corpId) {
        state.sucursal_id = ''
        state.detalles.forEach((d) => (d.sucursal_id = null))
        return
      }
      const sid = Number(state.sucursal_id || 0)
      if (sid) {
        const s = sucursalesActive.value.find((x) => Number(x.id) === sid)
        if (s && Number(s.corporativo_id) !== corpId) {
          state.sucursal_id = ''
          state.detalles.forEach((d) => (d.sucursal_id = null))
        }
      }
    }
  )

  watch(
    () => state.sucursal_id,
    (newVal) => {
      errors.value = {}
      if (!newVal) return
      const sId = Number(newVal)
      const s = sucursalesActive.value.find((x) => Number(x.id) === sId)
      if (s) {
        state.corporativo_id = s.corporativo_id
        state.comprador_corp_id = s.corporativo_id
        state.detalles.forEach((d) => {
          if (!d.sucursal_id) d.sucursal_id = sId
        })
      }
    }
  )

  watch(
    () => state.detalles,
    () => {
      let sub = 0
      let total = 0
      state.detalles.forEach((item) => {
        item.subtotal = Number((item.cantidad * item.precio_unitario).toFixed(2))
        item.iva = item.genera_iva ? Number((item.subtotal * 0.16).toFixed(2)) : 0
        item.total = Number((item.subtotal + item.iva).toFixed(2))
        sub += item.subtotal
        total += item.total
      })
      state.monto_subtotal = Number(sub.toFixed(2))
      state.monto_total = Number(total.toFixed(2))
    },
    { deep: true, immediate: true }
  )

  function addItem() {
    errors.value = {}
    showError.value = false
    state.detalles.push({
      sucursal_id: state.sucursal_id || null,
      cantidad: 1,
      descripcion: '',
      precio_unitario: 0,
      genera_iva: true,
      subtotal: 0,
      iva: 0,
      total: 0,
    })
  }

  function removeItem(index: number) {
    errors.value = {}
    state.detalles.splice(index, 1)
  }

  function money(v: any) {
    const n = Number(v ?? 0)
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
    } catch {
      return String(v ?? '')
    }
  }

  function validateClient(): string | null {
    if (!state.corporativo_id) return 'Selecciona un comprador (corporativo).'
    if (!state.sucursal_id) return 'Selecciona una sucursal.'
    if (!state.solicitante_id) return 'Selecciona un solicitante.'
    if (!state.concepto_id) return 'Selecciona un concepto.'
    if (!state.fecha_solicitud) return 'Selecciona la fecha de solicitud.'
    if (!state.detalles.length) return 'Agrega al menos un item.'

    for (let i = 0; i < state.detalles.length; i++) {
      const d = state.detalles[i]
      if (!d.descripcion || String(d.descripcion).trim().length < 2) return `El item #${i + 1} requiere descripción.`
      if (!(Number(d.cantidad) > 0)) return `El item #${i + 1} requiere cantidad mayor a 0.`
    }
    return null
  }

  function makePayload(accion: 'BORRADOR' | 'ENVIAR') {
    return {
      accion,
      solicitante_id: state.solicitante_id,
      sucursal_id: state.sucursal_id || null,
      comprador_corp_id: state.comprador_corp_id || null,
      proveedor_id: state.proveedor_id || null,
      concepto_id: state.concepto_id || null,
      monto_subtotal: state.monto_subtotal,
      monto_total: state.monto_total,
      fecha_solicitud: state.fecha_solicitud || null,
      fecha_autorizacion: role.value !== 'COLABORADOR' ? state.fecha_autorizacion || null : null,
      observaciones: state.observaciones || null,
      detalles: state.detalles.map((d) => ({
        sucursal_id: d.sucursal_id || null,
        cantidad: d.cantidad,
        descripcion: d.descripcion,
        precio_unitario: d.precio_unitario,
        genera_iva: d.genera_iva,
      })),
    }
  }

  function submit(accion: 'BORRADOR' | 'ENVIAR') {
    const msg = validateClient()
    if (msg) {
      showError.value = true
      swalErr(msg)
      return
    }

    errors.value = {}
    showError.value = false

    swalLoading(accion === 'ENVIAR' ? 'Enviando requisición y correo…' : 'Guardando borrador…')
    saving.value = true

    router.post(route('requisiciones.store'), makePayload(accion), {
      preserveScroll: true,
      onError: (e: InertiaErrors) => {
        errors.value = e || {}
        const raw = firstErrorMessage(e)
        const safe = raw && raw.includes('validation.') ? 'Revisa los campos obligatorios.' : raw
        swalErr(safe || 'No se pudo guardar la requisición.')
      },
      onSuccess: () => {
        errors.value = {}
        swalOk(accion === 'ENVIAR' ? 'Requisición enviada correctamente.' : 'Borrador guardado correctamente.', 'Listo')
        router.visit(route('requisiciones.index'))
      },
      onFinish: () => {
        saving.value = false
        swalClose()
      },
    })
  }

  function saveDraft() {
    submit('BORRADOR')
  }

  function sendRequi() {
    submit('ENVIAR')
  }

  return {
    state,
    items: computed(() => state.detalles),
    corporativosActive,
    sucursalesFiltered,
    empleadosActive,
    conceptosActive,
    proveedoresList,
    selectedProveedor,
    addItem,
    removeItem,
    saveDraft,
    sendRequi,
    money,
    role,
    saving,
    showError,
    fieldError,
  }
}
