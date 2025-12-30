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
  trend30?: Point[]
  financeLine?: Point[]
  byStatus?: Slice[]
  typesPie?: Slice[]
}

function asNumber(v: unknown): number {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

export function useDashboard() {
  const page = usePage()

  // Auth (fuente de verdad)
  const userRole = computed<Role>(() => ((page.props as any)?.auth?.user?.rol ?? 'COLABORADOR') as Role)
  const userName = computed(() => ((page.props as any)?.auth?.user?.name ?? 'Usuario') as string)

  // Datos reales del backend
  const dash = computed<DashboardPayload>(() => ((page.props as any)?.dashboard ?? {}) as DashboardPayload)

  const headline = computed(() => {
    if (dash.value.headline) return dash.value.headline
    if (userRole.value === 'ADMIN') return 'Centro de control'
    if (userRole.value === 'CONTADOR') return 'Panel financiero'
    return 'Mi operación'
  })

  const subheadline = computed(() => dash.value.subheadline ?? '')

  const kpis = computed<KPI[]>(() => (dash.value.kpis ?? []).map(k => ({
    label: String(k.label ?? ''),
    value: String(k.value ?? ''),
    hint: k.hint ? String(k.hint) : undefined,
  })))

  const trend30 = computed<Point[]>(() => (dash.value.trend30 ?? []).map(p => ({
    name: String(p.name ?? ''),
    value: asNumber(p.value),
    value2: p.value2 === undefined ? undefined : asNumber(p.value2),
  })))

  const financeLine = computed<Point[]>(() => (dash.value.financeLine ?? []).map(p => ({
    name: String(p.name ?? ''),
    value: asNumber(p.value),
  })))

  const byStatus = computed<Slice[]>(() => (dash.value.byStatus ?? []).map(s => ({
    name: String(s.name ?? ''),
    value: asNumber(s.value),
  })))

  const typesPie = computed<Slice[]>(() => (dash.value.typesPie ?? []).map(s => ({
    name: String(s.name ?? ''),
    value: asNumber(s.value),
  })))

  return {
    userRole,
    userName,
    headline,
    subheadline,
    kpis,
    trend30,
    financeLine,
    byStatus,
    typesPie,
  }
}
