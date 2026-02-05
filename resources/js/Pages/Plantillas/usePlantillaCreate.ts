import { reactive, computed, watch, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { swalOk, swalErr } from '@/lib/swal'

type Catalogos = {
  corporativos: { id: number; nombre: string; activo?: boolean }[]
  sucursales:   { id: number; nombre: string; codigo: string; corporativo_id: number; activo?: boolean }[]
  empleados:    { id: number; nombre: string; sucursal_id: number; activo?: boolean }[]
  conceptos:    { id: number; nombre: string; activo?: boolean }[]
  proveedores:  { id: number; nombre: string }[]
}

type Plantilla = any | null

export function usePlantillaCreate(catalogos: Catalogos, plantilla: Plantilla = null) {
  const page = usePage<any>()
  const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
  const empleadoId = page.props?.auth?.user?.empleado_id ?? null

  // Estado de la plantilla
  const state = reactive({
    nombre: '',
    corporativo_id: '' as number | string,
    sucursal_id:    '' as number | string,
    solicitante_id: '' as number | string,
    comprador_corp_id: '' as number | string,
    proveedor_id:   '' as number | string,
    concepto_id:    '' as number | string,
    monto_subtotal: 0,
    monto_total:    0,
    fecha_solicitud:    '',
    fecha_autorizacion: '',
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

  // Muestra mensaje si intentan guardar sin items
  const showError = ref(false)

  function loadFromPlantilla() {
    if (!plantilla) return
    state.nombre            = plantilla.nombre ?? ''
    state.corporativo_id    = plantilla.comprador_corp_id ?? ''
    state.sucursal_id       = plantilla.sucursal_id ?? ''
    state.solicitante_id    = plantilla.solicitante_id ?? ''
    state.comprador_corp_id = plantilla.comprador_corp_id ?? ''
    state.proveedor_id      = plantilla.proveedor_id ?? ''
    state.concepto_id       = plantilla.concepto_id ?? ''
    state.monto_subtotal    = Number(plantilla.monto_subtotal ?? 0)
    state.monto_total       = Number(plantilla.monto_total ?? 0)
    state.fecha_solicitud   = plantilla.fecha_solicitud ?? ''
    state.fecha_autorizacion= plantilla.fecha_autorizacion ?? ''
    state.observaciones     = plantilla.observaciones ?? ''
    state.detalles = (plantilla.detalles ?? []).map((d: any) => ({
      sucursal_id:   d.sucursal_id ?? '',
      cantidad:      Number(d.cantidad ?? 1),
      descripcion:   d.descripcion ?? '',
      precio_unitario:Number(d.precio_unitario ?? 0),
      genera_iva:    Boolean(d.genera_iva ?? true),
      subtotal:      Number(d.subtotal ?? 0),
      iva:           Number(d.iva ?? 0),
      total:         Number(d.total ?? 0),
    }))
  }
  loadFromPlantilla()

  // Auto-solicitante para colaboradores
  if (role.value === 'COLABORADOR' && empleadoId) {
    state.solicitante_id = empleadoId
  }

  // Listas activas
  const corporativosActive = computed(() => (catalogos.corporativos ?? []).filter(c => c.activo !== false))
  const sucursalesActive   = computed(() => (catalogos.sucursales   ?? []).filter(s => s.activo !== false))
  const empleadosActive    = computed(() => (catalogos.empleados    ?? []).filter(e => e.activo !== false))
  const conceptosActive    = computed(() => (catalogos.conceptos    ?? []).filter(c => c.activo !== false))
  const proveedoresList    = computed(() => catalogos.proveedores ?? [])

  // Sucursales según corporativo
  const sucursalesFiltered = computed(() => {
    const corpId = Number(state.corporativo_id || 0)
    if (!corpId) return sucursalesActive.value
    return sucursalesActive.value.filter(s => Number(s.corporativo_id) === corpId)
  })

  // Sincroniza corporativo con sucursal
  watch(() => state.sucursal_id, (newVal) => {
    if (!newVal) return
    const sId = Number(newVal)
    const s   = sucursalesActive.value.find(x => Number(x.id) === sId)
    if (s) {
      state.corporativo_id    = s.corporativo_id
      state.comprador_corp_id = s.corporativo_id
    }
  })

  // Ajusta corporativo y limpia sucursal si no coincide
  watch(() => state.corporativo_id, (newVal) => {
    if (!newVal) {
      state.sucursal_id       = ''
      state.comprador_corp_id = ''
      return
    }
    const corpId = Number(newVal)
    if (state.sucursal_id) {
      const sId = Number(state.sucursal_id)
      const ok  = sucursalesActive.value.some(s => Number(s.id) === sId && Number(s.corporativo_id) === corpId)
      if (!ok) {
        state.sucursal_id = ''
      }
    }
    state.comprador_corp_id = corpId
  })

  // Calcula subtotal, iva y total por item
  watch(() => state.detalles, () => {
    let sub = 0
    let total = 0
    state.detalles.forEach(item => {
      item.subtotal = item.cantidad * item.precio_unitario
      item.iva      = item.genera_iva ? Number((item.subtotal * 0.16).toFixed(2)) : 0
      item.total    = Number((item.subtotal + item.iva).toFixed(2))
      sub   += item.subtotal
      total += item.total
    })
    state.monto_subtotal = Number(sub.toFixed(2))
    state.monto_total    = Number(total.toFixed(2))
  }, { deep: true, immediate: true })

  function addItem() {
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
    state.detalles.splice(index, 1)
  }

  async function save() {
  // Validación previa: se exige al menos un item
  if (state.detalles.length === 0) {
    showError.value = true
    return
  }
  showError.value = false

  // Armar el payload completo conforme a PlantillaStoreRequest
  const payload = {
    nombre: state.nombre,
    solicitante_id: state.solicitante_id || null,
    sucursal_id: state.sucursal_id || null,
    comprador_corp_id: state.comprador_corp_id || null,
    proveedor_id: state.proveedor_id || null,
    concepto_id: state.concepto_id || null,
    monto_subtotal: state.monto_subtotal,
    monto_total: state.monto_total,
    fecha_solicitud: state.fecha_solicitud || null,
    fecha_autorizacion: role.value !== 'COLABORADOR' ? state.fecha_autorizacion || null : null,
    observaciones: state.observaciones || null,
    detalles: state.detalles.map(d => ({
      sucursal_id: d.sucursal_id || null,
      cantidad: d.cantidad,
      descripcion: d.descripcion,
      precio_unitario: d.precio_unitario,
      genera_iva: d.genera_iva,
      subtotal: d.subtotal,
      iva: d.iva,
      total: d.total,
    })),
  }

  await router.post(route('plantillas.store'), payload, {
    preserveScroll: true,
    onError: (errors) => {
      // Mostrar el primer mensaje de error recibido del backend
      const firstError = (errors && Object.values(errors)[0]) as string
      swalErr(firstError || 'Error al guardar la plantilla')
    },
    onSuccess: () => {
      showError.value = false
      // Puedes mostrar un swalOk aquí si lo prefieres, o dejarlo en el controlador
    },
  })
}

async function update(id: number) {
  if (state.detalles.length === 0) {
    showError.value = true
    return
  }
  showError.value = false

  const payload = {
    nombre: state.nombre,
    solicitante_id: state.solicitante_id || null,
    sucursal_id: state.sucursal_id || null,
    comprador_corp_id: state.comprador_corp_id || null,
    proveedor_id: state.proveedor_id || null,
    concepto_id: state.concepto_id || null,
    monto_subtotal: state.monto_subtotal,
    monto_total: state.monto_total,
    fecha_solicitud: state.fecha_solicitud || null,
    fecha_autorizacion: role.value !== 'COLABORADOR' ? state.fecha_autorizacion || null : null,
    observaciones: state.observaciones || null,
    detalles: state.detalles.map(d => ({
      sucursal_id: d.sucursal_id || null,
      cantidad: d.cantidad,
      descripcion: d.descripcion,
      precio_unitario: d.precio_unitario,
      genera_iva: d.genera_iva,
      subtotal: d.subtotal,
      iva: d.iva,
      total: d.total,
    })),
  }

  await router.put(route('plantillas.update', id), payload, {
    preserveScroll: true,
    onError: (errors) => {
      const firstError = (errors && Object.values(errors)[0]) as string
      swalErr(firstError || 'Error al actualizar la plantilla')
    },
    onSuccess: () => {
      showError.value = false
    },
  })
}

  function money(v: any) {
    const n = Number(v ?? 0)
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
    } catch {
      return String(v ?? '')
    }
  }

  return {
    state,
    items: computed(() => state.detalles),
    corporativosActive,
    sucursalesFiltered,
    empleadosActive,
    conceptosActive,
    proveedoresList,
    addItem,
    removeItem,
    save,
    update,
    money,
    role,
    showError,
  }
}
