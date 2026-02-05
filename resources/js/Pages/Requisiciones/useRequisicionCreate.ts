// resources/js/Pages/Requisiciones/useRequisicionCreate.ts

import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { swalOk, swalErr } from '@/lib/swal' // Importa funciones de SweetAlert de tu helper

type Catalogos = {
  corporativos: { id: number; nombre: string }[]
  sucursales: { id: number; nombre: string; codigo: string; corporativo_id: number }[]
  empleados: { id: number; nombre: string; sucursal_id: number }[]
  conceptos: { id: number; nombre: string }[]
  proveedores: { id: number; nombre: string }[]
}

type Plantilla = any | null

/**
 * Hook de creación de requisiciones.
 * Maneja el estado del formulario, carrito de items, cargas desde plantillas y guardado.
 */
export function useRequisicionCreate(catalogos: Catalogos, plantilla: Plantilla = null) {
  const state = reactive({
    folio: '',
    tipo: 'ANTICIPO' as 'ANTICIPO' | 'REEMBOLSO',
    status: 'BORRADOR' as 'BORRADOR',
    solicitante_id: '' as string | number,
    sucursal_id: '' as string | number,
    comprador_corp_id: '' as string | number,
    proveedor_id: '' as string | number,
    concepto_id: '' as string | number,
    monto_subtotal: 0,
    monto_total: 0,
    fecha_solicitud: '',
    fecha_autorizacion: '',
    observaciones: '',
    detalles: [] as Array<{
      id?: number
      sucursal_id: string | number | null
      cantidad: number
      descripcion: string
      precio_unitario: number
      subtotal: number
      iva: number
      total: number
    }>,
  })

  const items = computed(() => state.detalles)

  /**
   * Copia los campos de una plantilla (si se proporciona) al estado actual.
   */
  function loadFromPlantilla() {
    if (!plantilla) return
    state.folio = ''
    state.sucursal_id = plantilla.sucursal_id ?? ''
    state.comprador_corp_id = plantilla.comprador_corp_id ?? ''
    state.solicitante_id = plantilla.solicitante_id ?? ''
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
      subtotal: Number(d.subtotal ?? 0),
      iva: Number(d.iva ?? 0),
      total: Number(d.total ?? 0),
    }))
  }

  // Llama a la inicialización al cargar el hook
  loadFromPlantilla()

  // Actualiza totales cuando los items cambian
  watch(
    () => state.detalles,
    () => {
      let sub = 0
      let total = 0
      state.detalles.forEach((item) => {
        item.subtotal = item.cantidad * item.precio_unitario
        item.iva = Number((item.subtotal * 0.16).toFixed(2))
        item.total = Number((item.subtotal + item.iva).toFixed(2))
        sub += item.subtotal
        total += item.total
      })
      state.monto_subtotal = Number(sub.toFixed(2))
      state.monto_total = Number(total.toFixed(2))
    },
    { deep: true, immediate: true }
  )

  // Ajusta el corporativo cuando cambia la sucursal
  watch(
    () => state.sucursal_id,
    (newVal) => {
      const sId = Number(newVal || 0)
      const s = catalogos.sucursales.find((x) => x.id === sId)
      if (s) {
        state.comprador_corp_id = s.corporativo_id
      }
    }
  )

  function addItem() {
    state.detalles.push({
      sucursal_id: state.sucursal_id || '',
      cantidad: 1,
      descripcion: '',
      precio_unitario: 0,
      subtotal: 0,
      iva: 0,
      total: 0,
    })
  }

  function removeItem(index: number) {
    state.detalles.splice(index, 1)
  }

  /**
   * Envía la requisición al backend.
   * Usa swalOk y swalErr para notificar al usuario.
   */
  async function save() {
    try {
      await router.post(
        route('requisiciones.store'),
        {
          folio: state.folio,
          tipo: state.tipo,
          status: state.status,
          solicitante_id: state.solicitante_id,
          sucursal_id: state.sucursal_id,
          comprador_corp_id: state.comprador_corp_id,
          proveedor_id: state.proveedor_id || null,
          concepto_id: state.concepto_id,
          monto_subtotal: state.monto_subtotal,
          monto_total: state.monto_total,
          fecha_solicitud: state.fecha_solicitud,
          fecha_autorizacion: state.fecha_autorizacion || null,
          observaciones: state.observaciones,
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
      // Notifica éxito
      await swalOk('La requisición se ha guardado correctamente.', 'Requisición creada')
      router.visit(route('requisiciones.index'))
    } catch (e: any) {
      // Notifica error
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
    items,
    addItem,
    removeItem,
    save,
    money,
  }
}
