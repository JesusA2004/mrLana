import { reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { swalOk, swalErr } from '@/lib/swal'

type Catalogos = {
  corporativos: { id: number; nombre: string; activo?: boolean }[]
  sucursales: { id: number; nombre: string; codigo: string; corporativo_id: number; activo?: boolean }[]
  empleados: { id: number; nombre: string; sucursal_id: number; activo?: boolean }[]
  conceptos: { id: number; nombre: string; activo?: boolean }[]
  proveedores: { id: number; nombre: string }[]
}

type Plantilla = any | null

/**
 * Hook que maneja el estado y comportamiento del formulario de plantillas.
 * Incluye sincronización de corporativo y sucursal, cálculo de montos y operaciones de guardado/actualización.
 */
export function usePlantillaCreate(catalogos: Catalogos, plantilla: Plantilla = null) {
  const state = reactive({
    nombre: '',
    corporativo_id: '' as number | string,
    sucursal_id: '' as number | string,
    solicitante_id: '' as number | string,
    comprador_corp_id: '' as number | string, // se deriva de sucursal o corporativo
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
      subtotal: number
      iva: number
      total: number
    }>,
  })

  // Listas activas
  const corporativosActive = computed(() =>
    (catalogos.corporativos ?? []).filter((c) => c.activo !== false)
  )

  const sucursalesActive = computed(() =>
    (catalogos.sucursales ?? []).filter((s) => s.activo !== false)
  )

  // Filtra sucursales según corporativo seleccionado
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

  // Copia desde plantilla si la recibimos
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
      sucursal_id: d.sucursal_id ?? '',
      cantidad: Number(d.cantidad ?? 1),
      descripcion: d.descripcion ?? '',
      precio_unitario: Number(d.precio_unitario ?? 0),
      subtotal: Number(d.subtotal ?? 0),
      iva: Number(d.iva ?? 0),
      total: Number(d.total ?? 0),
    }))
  }
  loadFromPlantilla()

  // Ajusta corporativo cuando cambia sucursal
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

  // Resetea sucursal cuando cambia corporativo (si ya no pertenece a ese corporativo)
  watch(
    () => state.corporativo_id,
    (newVal) => {
      if (!newVal) {
        state.sucursal_id       = ''
        state.comprador_corp_id = ''
        return
      }
      const corpId = Number(newVal)
      // si hay sucursal seleccionada pero no pertenece, la limpiamos
      if (state.sucursal_id) {
        const sId = Number(state.sucursal_id)
        const ok  = sucursalesActive.value.some(
          (s) => Number(s.id) === sId && Number(s.corporativo_id) === corpId
        )
        if (!ok) {
          state.sucursal_id = ''
        }
      }
      state.comprador_corp_id = corpId
    }
  )

  // Recalcula montos cada vez que cambian los items
  watch(
    () => state.detalles,
    () => {
      let sub = 0
      let total = 0
      state.detalles.forEach((item) => {
        item.subtotal = item.cantidad * item.precio_unitario
        item.iva      = Number((item.subtotal * 0.16).toFixed(2))
        item.total    = Number((item.subtotal + item.iva).toFixed(2))
        sub  += item.subtotal
        total += item.total
      })
      state.monto_subtotal = Number(sub.toFixed(2))
      state.monto_total    = Number(total.toFixed(2))
    },
    { deep: true, immediate: true }
  )

  // Añade un nuevo item
  function addItem() {
    state.detalles.push({
      sucursal_id: state.sucursal_id || null,
      cantidad: 1,
      descripcion: '',
      precio_unitario: 0,
      subtotal: 0,
      iva: 0,
      total: 0,
    })
  }

  // Elimina item por índice
  function removeItem(index: number) {
    state.detalles.splice(index, 1)
  }

  // Guarda nueva plantilla
  async function save() {
    try {
      await router.post(
        route('plantillas.store'),
        {
          nombre: state.nombre,
          solicitante_id: state.solicitante_id || null,
          sucursal_id: state.sucursal_id || null,
          comprador_corp_id: state.comprador_corp_id || null,
          proveedor_id: state.proveedor_id || null,
          concepto_id: state.concepto_id || null,
          monto_subtotal: state.monto_subtotal,
          monto_total: state.monto_total,
          fecha_solicitud: state.fecha_solicitud || null,
          fecha_autorizacion: state.fecha_autorizacion || null,
          observaciones: state.observaciones || null,
          detalles: state.detalles.map((d) => ({
            sucursal_id: d.sucursal_id || null,
            cantidad: d.cantidad,
            descripcion: d.descripcion,
            precio_unitario: d.precio_unitario,
            subtotal: d.subtotal,
            iva: d.iva,
            total: d.total,
          })),
        },
        { preserveScroll: true }
      )
      await swalOk('La plantilla se ha guardado correctamente.', 'Plantilla creada')
      router.visit(route('plantillas.index'))
    } catch (e: any) {
      await swalErr(e?.message || 'Ocurrió un problema al guardar la plantilla.')
    }
  }

  // Actualiza plantilla existente
  async function update(id: number) {
    try {
      await router.put(
        route('plantillas.update', id),
        {
          nombre: state.nombre,
          solicitante_id: state.solicitante_id || null,
          sucursal_id: state.sucursal_id || null,
          comprador_corp_id: state.comprador_corp_id || null,
          proveedor_id: state.proveedor_id || null,
          concepto_id: state.concepto_id || null,
          monto_subtotal: state.monto_subtotal,
          monto_total: state.monto_total,
          fecha_solicitud: state.fecha_solicitud || null,
          fecha_autorizacion: state.fecha_autorizacion || null,
          observaciones: state.observaciones || null,
          detalles: state.detalles.map((d) => ({
            sucursal_id: d.sucursal_id || null,
            cantidad: d.cantidad,
            descripcion: d.descripcion,
            precio_unitario: d.precio_unitario,
            subtotal: d.subtotal,
            iva: d.iva,
            total: d.total,
          })),
        },
        { preserveScroll: true }
      )
      await swalOk('La plantilla se ha actualizado correctamente.', 'Plantilla actualizada')
      router.visit(route('plantillas.index'))
    } catch (e: any) {
      await swalErr(e?.message || 'Ocurrió un problema al actualizar la plantilla.')
    }
  }

  // Formatea número a moneda MXN
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
  }
}
