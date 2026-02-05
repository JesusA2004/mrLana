import { reactive, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { swalOk, swalErr } from '@/lib/swal'

type Catalogos = {
  corporativos: { id: number; nombre: string; activo?: boolean }[]
  sucursales: { id: number; nombre: string; codigo: string; corporativo_id: number; activo?: boolean }[]
  empleados: { id: number; nombre: string; sucursal_id: number; activo?: boolean }[]
  conceptos: { id: number; nombre: string; activo?: boolean }[]
  proveedores: { id: number; nombre: string }[]
}

type Plantilla = any | null

export function useRequisicionCreate(catalogos: Catalogos, plantilla: Plantilla = null) {
  const page = usePage<any>()
  const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
  const empleadoId = page.props?.auth?.user?.empleado_id ?? null

  const state = reactive({
    corporativo_id: '' as number | string,
    sucursal_id: '' as number | string,
    solicitante_id: '' as number | string,
    comprador_corp_id: '' as number | string,
    proveedor_id: '' as number | string,
    concepto_id: '' as number | string,
    monto_subtotal: 0,
    monto_total: 0,
    fecha_solicitud: '',
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

  // Cargar desde plantilla (si existe)
  function loadFromPlantilla() {
    if (!plantilla) return
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

  // Si el rol es colaborador, fijar el solicitante al empleado asociado
  if (role.value === 'COLABORADOR' && empleadoId) {
    state.solicitante_id = empleadoId
  }

  const corporativosActive = computed(() =>
    (catalogos.corporativos ?? []).filter((c) => c.activo !== false)
  )

  const sucursalesActive = computed(() =>
    (catalogos.sucursales ?? []).filter((s) => s.activo !== false)
  )

  // filtrar sucursales según corporativo
  const sucursalesFiltered = computed(() => {
    const corpId = Number(state.corporativo_id || 0)
    if (!corpId) return sucursalesActive.value
    return sucursalesActive.value.filter((s) => Number(s.corporativo_id) === corpId)
  })

  const empleadosActive = computed(() =>
    (catalogos.empleados ?? []).filter((e) => e.activo !== false)
  )

  const conceptosActive = computed(() =>
    (catalogos.conceptos ?? []).filter((c) => c.activo !== false)
  )

  const proveedoresList = computed(() => catalogos.proveedores ?? [])

  // Watch para sincronizar corporativo con sucursal
  watch(
    () => state.sucursal_id,
    (newVal) => {
      if (!newVal) return
      const sId = Number(newVal)
      const s   = sucursalesActive.value.find((x) => Number(x.id) === sId)
      if (s) {
        state.corporativo_id    = s.corporativo_id
        state.comprador_corp_id = s.corporativo_id
      }
    }
  )

  // Recalcula montos con IVA opcional
  watch(
    () => state.detalles,
    () => {
      let sub = 0
      let total = 0
      state.detalles.forEach((item) => {
        item.subtotal = item.cantidad * item.precio_unitario
        item.iva      = item.genera_iva ? Number((item.subtotal * 0.16).toFixed(2)) : 0
        item.total    = Number((item.subtotal + item.iva).toFixed(2))
        sub   += item.subtotal
        total += item.total
      })
      state.monto_subtotal = Number(sub.toFixed(2))
      state.monto_total    = Number(total.toFixed(2))
    },
    { deep: true, immediate: true }
  )

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
    try {
      await router.post(
        route('requisiciones.store'),
        {
          // No hay campo tipo
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
            subtotal: d.subtotal,
            iva: d.iva,
            total: d.total,
          })),
        },
        { preserveScroll: true }
      )
      await swalOk('La requisición se ha guardado correctamente.', 'Requisición creada')
      router.visit(route('requisiciones.index'))
    } catch (e: any) {
      await swalErr(e?.message || 'Ocurrió un problema al guardar la requisición.')
    }
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
    money,
    role,
  }
}
