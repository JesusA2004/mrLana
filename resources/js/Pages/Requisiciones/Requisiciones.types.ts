export type Id = number

export type PaginationLink = {
  url: string | null
  label: string
  active: boolean
  // El label puede contener HTML; para index se normaliza a texto plano
}

export type RequisicionRow = {
  id: Id
  folio: string
  tipo: 'ANTICIPO' | 'REEMBOLSO'
  status:
    | 'BORRADOR'
    | 'ELIMINADA'
    | 'CAPTURADA'
    | 'PAGO_AUTORIZADO'
    | 'PAGO_RECHAZADO'
    | 'PAGADA'
    | 'POR_COMPROBAR'
    | 'COMPROBACION_ACEPTADA'
    | 'COMPROBACION_RECHAZADA'

  // Montos en string (los enviamos como strings para mantener formato, se convierten en number al usarse)
  monto_subtotal: string
  monto_total: string

  // Nuevas fechas: solicitud y autorización (pueden ser null)
  fecha_solicitud: string | null
  fecha_autorizacion: string | null

  // Relaciones (pueden ser null si no hay datos relacionados)
  comprador: { id: Id; nombre: string } | null
  sucursal: { id: Id; nombre: string } | null
  solicitante: { id: Id; nombre: string } | null
  concepto: { id: Id; nombre: string } | null
  proveedor: { id: Id; nombre: string } | null

  observaciones: string | null
}

export type Paginated<T> = {
  data: T[]
  links: PaginationLink[]
  current_page: number
  last_page: number
  from: number | null
  to: number | null
  total: number
}

export type Catalogos = {
  corporativos: { id: Id; nombre: string; activo?: boolean }[]
  sucursales: { id: Id; nombre: string; codigo: string; corporativo_id: Id; activo?: boolean }[]
  empleados: { id: Id; nombre: string; sucursal_id: Id; puesto?: string; activo?: boolean }[]
  conceptos: { id: Id; nombre: string; activo?: boolean }[]
  proveedores: { id: Id; nombre: string }[]
}

export type RequisicionesFilters = {
  q: string
  tab: 'PENDIENTES' | 'AUTORIZADAS' | 'RECHAZADAS' | 'TODAS'
  status: string
  tipo: string
  comprador_corp_id: string | number
  sucursal_id: string | number
  solicitante_id: string | number
  fecha_from: string
  fecha_to: string
  perPage: number
  sort: string
  dir: 'asc' | 'desc'
}

export type RequisicionesCounts = {
  pendientes: number
  autorizadas: number
  rechazadas: number
  todas: number
}

export type RequisicionesPageProps = {
  requisiciones: Paginated<RequisicionRow>
  filters: RequisicionesFilters
  counts: RequisicionesCounts
  catalogos: Catalogos
}
