import { reactive, computed, watch, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { swalOk, swalErr, swalLoading, swalClose } from '@/lib/swal'

type Catalogos = {
  corporativos: { id: number; nombre: string; activo?: boolean }[]
  sucursales:   { id: number; nombre: string; codigo: string; corporativo_id: number; activo?: boolean }[]
  empleados:    { id: number; nombre: string; sucursal_id: number; activo?: boolean }[]
  conceptos:    { id: number; nombre: string; activo?: boolean }[]
  proveedores:  { id: number; nombre: string }[]
}

type Plantilla = any | null

type InertiaErrors = Record<string, string | string[]>

function firstErrorMessage(errors: InertiaErrors | undefined | null): string | null {
  if (!errors) return null
  const v = Object.values(errors)[0]
  if (!v) return null
  return Array.isArray(v) ? (v[0] ?? null) : v
}

export function usePlantillaCreate(catalogos: Catalogos, plantilla: Plantilla = null) {
  const page = usePage<any>()
  const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
  const empleadoId = page.props?.auth?.user?.empleado_id ?? null

  const saving = ref(false)
  const showError = ref(false)

  // Para mostrar errores por campo en el template
  const errors = ref<InertiaErrors>({})

  const state = reactive({
    nombre: '',
    corporativo_id: '' as number | string,
    sucursal_id: '' as number | string,
    solicitante_id: '' as number | string,
    comprador_corp_id: '' as number | string,
    proveedor_id: '' as number | string,
    concepto_id: '' as number | string,
    monto_subtotal: 0,
    monto_total: 0,
    // Guardamos fecha como 'YYYY-MM-DD' (DatePickerShadcn ya emite eso)
    fecha_solicitud: '' as string,
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

  // Load inicial si es edición
  if (plantilla) {
    state.nombre = plantilla.nombre ?? ''
    state.sucursal_id = plantilla.sucursal_id ?? ''
    state.solicitante_id = plantilla.solicitante_id ?? ''
    state.comprador_corp_id = plantilla.comprador_corp_id ?? ''
    state.corporativo_id = plantilla.comprador_corp_id ?? ''
    state.proveedor_id = plantilla.proveedor_id ?? ''
    state.concepto_id = plantilla.concepto_id ?? ''
    state.monto_subtotal = Number(plantilla.monto_subtotal ?? 0)
    state.monto_total = Number(plantilla.monto_total ?? 0)
    state.fecha_solicitud = plantilla.fecha_solicitud ?? ''
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

  // Auto-solicitante para colaboradores y NO editable
  if (role.value === 'COLABORADOR' && empleadoId) {
    state.solicitante_id = empleadoId
  }

  // Catálogos activos
  const corporativosActive = computed(() => (catalogos.corporativos ?? []).filter(c => c.activo !== false))
  const sucursalesActive = computed(() => (catalogos.sucursales ?? []).filter(s => s.activo !== false))
  const empleadosActive = computed(() => (catalogos.empleados ?? []).filter(e => e.activo !== false))
  const conceptosActive = computed(() => (catalogos.conceptos ?? []).filter(c => c.activo !== false))
  const proveedoresList = computed(() => (catalogos.proveedores ?? []))

  const sucursalesFiltered = computed(() => {
    const corpId = Number(state.corporativo_id || 0)
    if (!corpId) return sucursalesActive.value
    return sucursalesActive.value.filter(s => Number(s.corporativo_id) === corpId)
  })

  // Sync sucursal -> corporativo
  watch(() => state.sucursal_id, (newVal) => {
    if (!newVal) return
    const sId = Number(newVal)
    const s = sucursalesActive.value.find(x => Number(x.id) === sId)
    if (s) {
      state.corporativo_id = s.corporativo_id
      state.comprador_corp_id = s.corporativo_id
    }
  })

  // Sync corporativo -> comprador_corp_id y valida sucursal
  watch(() => state.corporativo_id, (newVal) => {
    if (!newVal) {
      state.sucursal_id = ''
      state.comprador_corp_id = ''
      return
    }
    const corpId = Number(newVal)
    if (state.sucursal_id) {
      const sId = Number(state.sucursal_id)
      const ok = sucursalesActive.value.some(s => Number(s.id) === sId && Number(s.corporativo_id) === corpId)
      if (!ok) state.sucursal_id = ''
    }
    state.comprador_corp_id = corpId
  })

  // Cálculo
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

  function fieldError(key: string): string | null {
    const v = errors.value?.[key]
    if (!v) return null
    return Array.isArray(v) ? (v[0] ?? null) : v
  }

  function makePayload() {
    return {
      nombre: state.nombre,
      solicitante_id: state.solicitante_id || null,
      sucursal_id: state.sucursal_id || null,
      comprador_corp_id: state.comprador_corp_id || null,
      proveedor_id: state.proveedor_id || null,
      concepto_id: state.concepto_id || null,
      monto_subtotal: state.monto_subtotal,
      monto_total: state.monto_total,
      fecha_solicitud: state.fecha_solicitud || null,
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
  }

  function save() {
    // NO bloquear envío: si está vacío, dejamos que Laravel valide y nos regrese el 422.
    showError.value = state.detalles.length === 0
    errors.value = {}

    swalLoading('Guardando plantilla...')
    saving.value = true

    router.post(route('plantillas.store'), makePayload(), {
      preserveScroll: true,
      onError: (e: InertiaErrors) => {
        errors.value = e || {}
        const msg = firstErrorMessage(e) || 'Error al guardar la plantilla'
        swalErr(msg)
      },
      onSuccess: () => {
        errors.value = {}
        swalOk('Plantilla guardada correctamente.', 'Listo')
        router.visit(route('plantillas.index'))
      },
      onFinish: () => {
        saving.value = false
        swalClose()
      },
    })
  }

  function update(id: number) {
    showError.value = state.detalles.length === 0
    errors.value = {}

    swalLoading('Actualizando plantilla...')
    saving.value = true

    router.put(route('plantillas.update', id), makePayload(), {
      preserveScroll: true,
      onError: (e: InertiaErrors) => {
        errors.value = e || {}
        const msg = firstErrorMessage(e) || 'Error al actualizar la plantilla'
        swalErr(msg)
      },
      onSuccess: () => {
        errors.value = {}
        swalOk('Plantilla actualizada correctamente.', 'Listo')
        router.visit(route('plantillas.index'))
      },
      onFinish: () => {
        saving.value = false
        swalClose()
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
    saving,
    showError,
    fieldError,
  }
}
