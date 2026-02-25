import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

type Role = 'ADMIN' | 'CONTADOR' | 'COLABORADOR'

export type KPI = { label: string; value: string; hint?: string }
export type Point = { name: string; value: number; value2?: number }
export type Slice = { name: string; value: number }

type DashboardPayload = {
  headline?: string
  subheadline?: string
  userName?: string
  userRole?: Role
  kpis?: KPI[]

  // NUEVAS llaves (las que ya estás mandando desde controllers corregidos)
  activityDaily?: Point[]
  amountsDaily?: Point[]
  statusMix?: Slice[]
  comprobantesMix?: Slice[]

  // Compat (por si algún Vue viejo aún las usa)
  trend30?: Point[]
  financeLine?: Point[]
  byStatus?: Slice[]
  typesPie?: Slice[]
}

function toNumber(v: unknown): number {
  if (typeof v === 'number') return Number.isFinite(v) ? v : 0

  if (typeof v === 'string') {
    // Soporta: "$ 1,234.50", "1,234.50", "  123 ", "0"
    const cleaned = v.replace(/[$,\s]/g, '')
    const n = Number(cleaned)
    return Number.isFinite(n) ? n : 0
  }

  return 0
}

function safePoints(arr: unknown): Point[] {
  if (!Array.isArray(arr)) return []
  return (arr as any[]).map((p) => ({
    name: String(p?.name ?? ''),
    value: toNumber(p?.value),
    value2: p?.value2 === undefined ? undefined : toNumber(p?.value2),
  }))
}

function safeSlices(arr: unknown): Slice[] {
  if (!Array.isArray(arr)) return []
  return (arr as any[]).map((s) => ({
    name: String(s?.name ?? ''),
    value: toNumber(s?.value),
  }))
}

export function useDashboard() {
  const page = usePage()
  const props = computed(() => (page.props as any) ?? {})

  // Auth (fuente de verdad)
  const userRole = computed<Role>(() => (props.value?.auth?.user?.rol ?? 'COLABORADOR') as Role)
  const userName = computed(() => (props.value?.auth?.user?.name ?? 'Usuario') as string)

  // Datos reales del backend
  const dash = computed<DashboardPayload>(() => (props.value?.dashboard ?? {}) as DashboardPayload)

  const headline = computed(() => {
    if (dash.value.headline) return String(dash.value.headline)
    if (userRole.value === 'ADMIN') return 'Centro de control'
    if (userRole.value === 'CONTADOR') return 'Panel financiero'
    return 'Mi operación'
  })

  const subheadline = computed(() => String(dash.value.subheadline ?? ''))

  const kpis = computed<KPI[]>(() =>
    (dash.value.kpis ?? []).map((k) => ({
      label: String(k?.label ?? ''),
      value: String(k?.value ?? ''),
      hint: k?.hint ? String(k.hint) : undefined,
    })),
  )

  /**
   * =========
   * SERIES (NUEVAS)
   * =========
   * Estas son las que tus controllers ya están devolviendo:
   * - activityDaily
   * - amountsDaily
   * - statusMix
   * - comprobantesMix
   *
   * IMPORTANTÍSIMO: van "safe" para evitar NaN en Unovis.
   */
  const activityDaily = computed<Point[]>(() => safePoints(dash.value.activityDaily))
  const amountsDaily = computed<Point[]>(() => safePoints(dash.value.amountsDaily))
  const statusMix = computed<Slice[]>(() => safeSlices(dash.value.statusMix))
  const comprobantesMix = computed<Slice[]>(() => safeSlices(dash.value.comprobantesMix))

  /**
   * =========
   * COMPAT (VIEJAS)
   * =========
   * Si tu AdminDashboard.vue viejo todavía llama a trend30/financeLine/byStatus/typesPie,
   * aquí lo mantenemos pero lo "mapeamos" a lo nuevo cuando sea posible.
   */
  const trend30 = computed<Point[]>(() => {
    // prioridad: si viene trend30 desde backend, úsalo
    if (Array.isArray(dash.value.trend30)) return safePoints(dash.value.trend30)
    // fallback: usa activityDaily (mismo shape)
    return activityDaily.value
  })

  const financeLine = computed<Point[]>(() => {
    if (Array.isArray(dash.value.financeLine)) return safePoints(dash.value.financeLine)
    // fallback: usa amountsDaily
    return amountsDaily.value
  })

  const byStatus = computed<Slice[]>(() => {
    if (Array.isArray(dash.value.byStatus)) return safeSlices(dash.value.byStatus)
    // fallback: usa statusMix
    return statusMix.value
  })

  const typesPie = computed<Slice[]>(() => {
    if (Array.isArray(dash.value.typesPie)) return safeSlices(dash.value.typesPie)
    // fallback: usa comprobantesMix
    return comprobantesMix.value
  })

  /**
   * =========
   * EXPORTS (Ziggy)
   * =========
   * Tus rutas reales:
   * dashboard.export.pdf   -> /exports/dashboard/{role}/pdf
   * dashboard.export.excel -> /exports/dashboard/{role}/excel
   */
  const exportPdfUrl = computed(() => {
    try {
      // @ts-ignore - Ziggy global
      return route('dashboard.export.pdf', { role: userRole.value })
    } catch {
      return ''
    }
  })

  const exportExcelUrl = computed(() => {
    try {
      // @ts-ignore - Ziggy global
      return route('dashboard.export.excel', { role: userRole.value })
    } catch {
      return ''
    }
  })

  const canExport = computed(() => Boolean(exportPdfUrl.value) && Boolean(exportExcelUrl.value))

  function openExport(url: string) {
    if (!url) return
    window.open(url, '_blank', 'noopener,noreferrer')
  }

  function exportPdf() {
    openExport(exportPdfUrl.value)
  }

  function exportExcel() {
    openExport(exportExcelUrl.value)
  }

  return {
    userRole,
    userName,

    headline,
    subheadline,

    kpis,

    // nuevas
    activityDaily,
    amountsDaily,
    statusMix,
    comprobantesMix,

    // compat
    trend30,
    financeLine,
    byStatus,
    typesPie,

    // exports
    canExport,
    exportPdfUrl,
    exportExcelUrl,
    exportPdf,
    exportExcel,
  }
}
