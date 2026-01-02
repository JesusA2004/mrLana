<script setup lang="ts">
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import { Head } from '@inertiajs/vue3'
    import { computed, ref } from 'vue'

    // CSS (ubicación: resources/js/css/dashboard.css)
    import '@/css/dashboard.css'

    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card'

    import { VisXYContainer, VisLine, VisArea, VisAxis, VisTooltip } from '@unovis/vue'

    import ICON_PDF from '@/img/pdf.png'
    import ICON_EXCEL from '@/img/excel.png'
    import { downloadFile } from '@/Utils/exports'

    type KPI = { label: string; value: string | number; hint?: string }
    type Point = { name: string; value: number; value2?: number }

    type DashboardPayload = {
        userName?: string
        userRole?: 'ADMIN' | 'CONTADOR' | 'COLABORADOR'
        headline?: string
        subheadline?: string
        kpis?: KPI[]
        activityDaily?: Point[]
        amountsDaily?: Point[]
        statusMix?: Point[]
        comprobantesMix?: Point[]
    }

    const props = defineProps<{ dashboard?: DashboardPayload }>()

    const headline = computed(() => props.dashboard?.headline ?? 'Dashboard')
    const subheadline = computed(() => props.dashboard?.subheadline ?? 'Visión ejecutiva del sistema: rápida, clara, accionable.')

    const kpis = computed<KPI[]>(() => {
        const v = props.dashboard?.kpis
        if (v?.length) return v

        return [
            { label: 'Corporativos activos', value: '0', hint: 'Base operativa vigente.' },
            { label: 'Sucursales activas', value: '0', hint: 'Cobertura actual.' },
            { label: 'Empleados activos', value: '0', hint: 'Usuarios operando.' },
            { label: 'Monto del mes', value: '$0.00', hint: 'Total capturado en el periodo.' },
        ]
    })

    // -----------------------------
    // Placeholders para ejes X/Y
    // -----------------------------
    function placeholderDays(n = 14): Point[] {
        const out: Point[] = []
        for (let i = n; i >= 1; i--) {
            out.push({ name: `D-${i}`, value: 0 })
        }
        return out
    }

    function placeholderStatus(): Point[] {
        return [
            { name: 'BORRADOR', value: 0 },
            { name: 'CAPTURADA', value: 0 },
            { name: 'PAGADA', value: 0 },
            { name: 'POR_COMPROBAR', value: 0 },
            { name: 'COMPROBADA', value: 0 },
            { name: 'ACEPTADA', value: 0 },
            { name: 'RECHAZADA', value: 0 },
        ]
    }

    function placeholderComprobantes(): Point[] {
        return [
            { name: 'FACTURA', value: 0 },
            { name: 'TICKET', value: 0 },
            { name: 'NOTA', value: 0 },
            { name: 'OTRO', value: 0 },
        ]
    }

    const activityDaily = computed<Point[]>(() => {
        const v = props.dashboard?.activityDaily ?? []
        return v.length ? v : placeholderDays(14)
    })

    const amountsDaily = computed<Point[]>(() => {
        const v = props.dashboard?.amountsDaily ?? []
        return v.length ? v : placeholderDays(14)
    })

    const statusMix = computed<Point[]>(() => {
        const v = props.dashboard?.statusMix ?? []
        return v.length ? v : placeholderStatus()
    })

    const comprobantesMix = computed<Point[]>(() => {
        const v = props.dashboard?.comprobantesMix ?? []
        return v.length ? v : placeholderComprobantes()
    })

    const hasRealActivity = computed(() => (props.dashboard?.activityDaily?.length ?? 0) > 0)
    const hasRealAmounts = computed(() => (props.dashboard?.amountsDaily?.length ?? 0) > 0)
    const hasRealStatus = computed(() => (props.dashboard?.statusMix?.length ?? 0) > 0)
    const hasRealComprobantes = computed(() => (props.dashboard?.comprobantesMix?.length ?? 0) > 0)

    const x = (d: any) => d.name

    // Export
    const exporting = ref<'pdf' | 'excel' | null>(null)

    const userRole = computed(() => props.dashboard?.userRole ?? 'ADMIN')
    const exportPdfUrl = computed(() => route('dashboards.export.pdf', { role: userRole.value }))
    const exportExcelUrl = computed(() => route('dashboards.export.excel', { role: userRole.value }))

    const exportPdf = async () => {
        exporting.value = 'pdf'
        try {
            await downloadFile(exportPdfUrl.value)
        } finally {
            exporting.value = null
        }
    }

    const exportExcel = async () => {
        exporting.value = 'excel'
        try {
            await downloadFile(exportExcelUrl.value)
        } finally {
            exporting.value = null
        }
    }
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="dash-shell px-4 py-4 sm:px-6 lg:px-8 overflow-y-auto">
            <div class="dash-bg" aria-hidden="true"></div>
            <!-- 6 cuadritos en una fila (PDF + Excel + 4 KPIs) -->
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
                
                <!-- 4 KPIs -->
                <Card
                    v-for="k in kpis"
                    :key="k.label"
                    class="dash-card dash-mini card-fx"
                >
                    <CardHeader class="py-3">
                        <CardDescription class="text-[11px] leading-none">
                            {{ k.label }}
                        </CardDescription>

                        <CardTitle class="text-xl leading-tight tracking-tight">
                            {{ k.value }}
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="pb-3 pt-0">
                        <p v-if="k.hint" class="text-[11px] text-muted line-clamp-1">
                            {{ k.hint }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Export PDF -->
                <Card class="dash-card dash-mini card-fx">
                    <CardContent class="p-3">
                        <button
                            type="button"
                            :disabled="exporting !== null"
                            @click="exportPdf"
                            @keydown.enter.prevent="exportPdf"
                            @keydown.space.prevent="exportPdf"
                            aria-label="Exportar dashboard a PDF"
                        >
                            <span class="export-mini-glow" aria-hidden="true"></span>

                            <span class="export-mini-top">
                                <span class="export-pill">PDF</span>
                            </span>

                            <div class="export-mini-mid">
                                <span class="export-mini-icon">
                                    <img :src="ICON_PDF" alt="" class="h-5 w-5" />
                                </span>

                                <div class="min-w-0">
                                    <div class="export-mini-title">Exportar</div>
                                    <div class="export-mini-sub">Informe ejecutivo</div>
                                </div>

                                <span class="export-mini-right">
                                    <span v-if="exporting === 'pdf'" class="spinner" aria-hidden="true"></span>
                                    <span v-else class="export-arrow" aria-hidden="true">→</span>
                                </span>
                            </div>

                            <div class="export-mini-foot">
                                Incluye métricas y gráficas actuales
                            </div>
                        </button>
                    </CardContent>
                </Card>

                <!-- Export Excel -->
                <Card class="dash-card dash-mini card-fx">
                    <CardContent class="p-3">
                        <button
                            type="button"
                            :disabled="exporting !== null"
                            @click="exportExcel"
                            @keydown.enter.prevent="exportExcel"
                            @keydown.space.prevent="exportExcel"
                            aria-label="Exportar dashboard a Excel"
                        >
                            <span class="export-mini-glow" aria-hidden="true"></span>

                            <span class="export-mini-top">
                                <span class="export-pill">Excel</span>
                            </span>

                            <div class="export-mini-mid">
                                <span class="export-mini-icon">
                                    <img :src="ICON_EXCEL" alt="" class="h-5 w-5" />
                                </span>

                                <div class="min-w-0">
                                    <div class="export-mini-title">Exportar</div>
                                    <div class="export-mini-sub">Pivots y control</div>
                                </div>

                                <span class="export-mini-right">
                                    <span v-if="exporting === 'excel'" class="spinner" aria-hidden="true"></span>
                                    <span v-else class="export-arrow" aria-hidden="true">→</span>
                                </span>
                            </div>

                            <div class="export-mini-foot">
                                Descarga datos para análisis avanzado
                            </div>
                        </button>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts -->
            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                <!-- 1) Actividad -->
                <Card class="dash-card card-fx min-h-0">
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">Actividad diaria</CardTitle>
                        <CardDescription class="text-xs">
                            Conteo de requisiciones por día (últimos días).
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="pt-0 min-h-0">
                        <div class="chart-wrap chart-fx">
                            <VisXYContainer :data="activityDaily" class="h-full w-full">
                                <VisAxis type="x" :x="x" />
                                <VisAxis type="y" />
                                <VisArea :x="x" :y="(d:any) => d.value" :opacity="0.22" />
                                <VisLine :x="x" :y="(d:any) => d.value" :stroke-width="2" />
                                <VisTooltip />
                            </VisXYContainer>

                            <div v-if="!hasRealActivity" class="chart-overlay">
                                <div class="chart-overlay-title">Sin datos todavía</div>
                                <div class="chart-overlay-sub">Aquí verás el ritmo de captura día a día.</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 2) Montos -->
                <Card class="dash-card card-fx min-h-0">
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">Montos</CardTitle>
                        <CardDescription class="text-xs">
                            Monto total diario (tendencia de gasto).
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="pt-0 min-h-0">
                        <div class="chart-wrap chart-fx">
                            <VisXYContainer :data="amountsDaily" class="h-full w-full">
                                <VisAxis type="x" :x="x" />
                                <VisAxis type="y" />
                                <VisArea :x="x" :y="(d:any) => d.value" :opacity="0.18" />
                                <VisLine :x="x" :y="(d:any) => d.value" :stroke-width="2" />
                                <VisTooltip />
                            </VisXYContainer>

                            <div v-if="!hasRealAmounts" class="chart-overlay">
                                <div class="chart-overlay-title">Sin datos todavía</div>
                                <div class="chart-overlay-sub">Aquí se ve la tendencia de montos por día.</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 3) Requisiciones por estatus -->
                <Card class="dash-card card-fx min-h-0">
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">Estatus de requisiciones</CardTitle>
                        <CardDescription class="text-xs">
                            Distribución por estatus (pipeline operativo).
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="pt-0 min-h-0">
                        <div class="chart-wrap chart-fx">
                            <VisXYContainer :data="statusMix" class="h-full w-full">
                                <VisAxis type="x" :x="x" />
                                <VisAxis type="y" />
                                <VisArea :x="x" :y="(d:any) => d.value" :opacity="0.18" />
                                <VisLine :x="x" :y="(d:any) => d.value" :stroke-width="2" />
                                <VisTooltip />
                            </VisXYContainer>

                            <div v-if="!hasRealStatus" class="chart-overlay">
                                <div class="chart-overlay-title">Sin datos todavía</div>
                                <div class="chart-overlay-sub">Pipeline: de borrador a aceptada.</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- 4) Comprobantes por tipo -->
                <Card class="dash-card card-fx min-h-0">
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">Comprobantes</CardTitle>
                        <CardDescription class="text-xs">
                            Conteo por tipo de documento (factura, ticket, nota, otro).
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="pt-0 min-h-0">
                        <div class="chart-wrap chart-fx">
                            <VisXYContainer :data="comprobantesMix" class="h-full w-full">
                                <VisAxis type="x" :x="x" />
                                <VisAxis type="y" />
                                <VisArea :x="x" :y="(d:any) => d.value" :opacity="0.18" />
                                <VisLine :x="x" :y="(d:any) => d.value" :stroke-width="2" />
                                <VisTooltip />
                            </VisXYContainer>

                            <div v-if="!hasRealComprobantes" class="chart-overlay">
                                <div class="chart-overlay-title">Sin datos todavía</div>
                                <div class="chart-overlay-sub">Mix de evidencia y calidad documental.</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
