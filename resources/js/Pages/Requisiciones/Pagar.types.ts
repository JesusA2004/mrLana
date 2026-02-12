export type Money = number | string | null | undefined

export interface PagoRow {
  id: number
  fecha_pago: string | null
  tipo_pago: string
  monto: number
  archivo: null | { label: string; url: string }
  beneficiario?: {
    nombre?: string | null
    cuenta?: string | null
    clabe?: string | null
    banco?: string | null
  }
}

export interface RequisicionPagoLite {
  id: number
  folio: string
  concepto: string | null
  monto_total: number
  solicitante_nombre: string
  status: string
  beneficiario: {
    nombre: string
    cuenta: string | null
    clabe: string | null
    banco: string | null
  }
}

export interface RequisicionPagoPageProps {
  requisicion: { data: RequisicionPagoLite } | RequisicionPagoLite
  pagos: { data: PagoRow[] } | PagoRow[]
  totales: { pagado: number; pendiente: number }
  tipoPagoOptions: { id: string; nombre: string }[]
}
