// resources/js/Pages/Requisiciones/Pagar.types.ts
export type FileLink = {
  label: string
  url: string
}

export type Beneficiario = {
  nombre?: string | null
  rfc?: string | null
  clabe?: string | null
  banco?: string | null
}

export type PagoRow = {
  id: number
  fecha_pago: string | null
  tipo_pago: string
  monto: number
  referencia?: string | null
  archivo?: FileLink | null
  beneficiario?: Beneficiario | null
}

export type RequisicionPagoData = {
  id: number
  folio: string
  concepto?: string | null
  monto_total: number
  solicitante_nombre?: string | null
  beneficiario?: Beneficiario | null
  status?: string | null
}

export type TipoPagoOption = {
  id: string
  nombre: string
}

export type RequisicionPagoPageProps = {
  requisicion: { data: RequisicionPagoData } | RequisicionPagoData
  pagos: { data: PagoRow[] } | PagoRow[]
  totales?: { pagado: number; pendiente: number }
  tipoPagoOptions: TipoPagoOption[]
  auth?: any
  errors?: any
}
