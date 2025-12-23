export type Id = number

export type PaginationLink = {
  url: string | null
  label: string
  active: boolean
}

export type RequisicionRow = {
  id: Id
  folio: string
  tipo: 'ANTICIPO' | 'REEMBOLSO'
  status:
    | 'BORRADOR'
    | 'CAPTURADA'
    | 'PAGADA'
    | 'POR_COMPROBAR'
    | 'COMPROBADA'
    | 'ACEPTADA'
    | 'RECHAZADA'

  monto_total: string
  monto_subtotal: string

  fecha_captura: string | null
  fecha_pago: string | null

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
  corporativos: { id: Id; nombre: string }[]
  sucursales: { id: Id; nombre: string; corporativo_id: Id }[]
  empleados: { id: Id; nombre: string; sucursal_id: Id }[]
  conceptos: { id: Id; nombre: string }[]
  proveedores: { id: Id; nombre: string }[]
}

export type RequisicionesFilters = {
  q: string
  tab: 'PENDIENTES' | 'APROBADAS' | 'RECHAZADAS' | 'TODAS'
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
  aprobadas: number
  rechazadas: number
  todas: number
}

export type RequisicionesPageProps = {
  requisiciones: Paginated<RequisicionRow>
  filters: RequisicionesFilters
  counts: RequisicionesCounts
  catalogos: Catalogos
}
