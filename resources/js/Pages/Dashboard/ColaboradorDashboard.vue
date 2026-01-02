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
    type Point = { name: string; value: number }

    type DashboardPayload = {
        userName?: string
        userRole?: 'ADMIN' | 'CONTADOR' | 'COLABORADOR'
        headline?: string
        subheadline?: string
        kpis?: KPI[]
        activityDaily?: Point[]
        amountsDaily?: Point[]
    }

    const props = defineProps<{ dashboard?: DashboardPayload }>()

    const headline = computed(() => props.dashboard?.headline ?? 'Mi operación')
    const subheadline = computed(() => props.dashboard?.subheadline ?? 'Tu ritmo de captura y montos del periodo.')

    const kpis = computed<KPI[]>(() => {
        const v = props.dashboard?.kpis
        if (v?.length) return v

        return [
            { label: 'Mis requisiciones (mes)', value: '0', hint: 'Volumen del periodo.' },
            { label: 'Pendientes', value: '0', hint: 'Borrador / capturada.' },
            { label: 'Por comprobar', value: '0', hint: 'Pendiente de evidencia.' },
            { label: 'Monto del mes', value: '$0.00', hint: 'Total capturado por ti.' },
        ]
    })

    function placeholderDays(n = 14): Point[] {
        const out: Point[] = []
        for (let i = n; i >= 1; i--) out.push({ name: `D-${i}`, value: 0 })
        return out
    }

    const activityDaily = computed<Point[]>(() => {
        const v = props.dashboard?.activityDaily ?? []
        return v.length ? v : placeholderDays(14)
    })

    const amountsDaily = computed<Point[]>(() => {
        const v = props.dashboard?.amountsDaily ?? []
        return v.length ? v : placeholderDays(14)
    })

    const hasRealActivity = computed(() => (props.dashboard?.activityDaily?.length ?? 0) > 0)
    const hasRealAmounts = computed(() => (props.dashboard?.amountsDaily?.length ?? 0) > 0)

    const x = (d: any) => d.name

    const exporting = ref<'pdf' | 'excel' | null>(null)

    const exportPdfUrl = computed(() => route('dashboards.export.pdf', { role: 'COLABORADOR' }))
    const exportExcelUrl = computed(() => route('dashboards.export.excel', { role: 'COLABORADOR' }))

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

            <!-- Headline (sin “panel ejecutivo”, solo copy ligero) -->
            <div class="mt-3">
                <div class="text-base font-semibold text-slate-900 dark:text-white">
                    {{ headline }}
                </div>
                <div class="text-xs text-slate-600 dark:text-zinc-300">
                    {{ subheadline }}
                </div>
            </div>

            <!-- Charts -->
            <div class="mt-3 grid gap-3 lg:grid-cols-2">
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
                                <div class="chart-overlay-sub">Aquí verás tu ritmo de captura día a día.</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="dash-card card-fx min-h-0">
                    <CardHeader class="py-3">
                        <CardTitle class="text-base">Montos</CardTitle>
                        <CardDescription class="text-xs">
                            Monto total diario (tendencia personal).
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
