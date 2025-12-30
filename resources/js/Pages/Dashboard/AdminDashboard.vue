<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

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
}

const props = defineProps<{ dashboard?: DashboardPayload }>()

const userName = computed(() => props.dashboard?.userName ?? 'Usuario')
const userRole = computed(() => props.dashboard?.userRole ?? 'ADMIN')
const headline = computed(() => props.dashboard?.headline ?? 'Centro de control')
const subheadline = computed(() => props.dashboard?.subheadline ?? 'Visión ejecutiva de operación y gasto.')

const kpis = computed<KPI[]>(() => {
  const v = props.dashboard?.kpis
  if (v?.length) return v

  // Fallback seguro si aún no llega data real
  return [
    { label: 'Requisiciones (mes)', value: '0', hint: 'Capturadas por la organización.' },
    { label: 'Pendientes', value: '0', hint: 'Borrador / capturada.' },
    { label: 'Por comprobar', value: '0', hint: 'Pendiente de evidencia.' },
    { label: 'Monto del mes', value: '$0.00', hint: 'Total capturado.' },
  ]
})

const activityDaily = computed<Point[]>(() => props.dashboard?.activityDaily ?? [])
const amountsDaily = computed<Point[]>(() => props.dashboard?.amountsDaily ?? [])

const x = (d: any) => d.name

const exportPdf = () => downloadFile(route('dashboards.export.pdf', { role: 'ADMIN' }))
const exportExcel = () => downloadFile(route('dashboards.export.excel', { role: 'ADMIN' }))
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <!-- Alto fijo: sin scroll vertical en pantalla -->
    <div class="h-[calc(100vh-6rem)] px-4 py-4 sm:px-6 lg:px-8 overflow-hidden">
      <div class="h-full flex flex-col gap-4">

        <!-- KPIs en una fila, compactos -->
        <div class="grid gap-3 grid-cols-2 lg:grid-cols-3">
          <Card
            v-for="k in kpis"
            :key="k.label"
            class="rounded-2xl border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-zinc-950/30 backdrop-blur"
          >
            <CardHeader class="py-3">
              <CardDescription class="text-[11px] leading-none">{{ k.label }}</CardDescription>
              <CardTitle class="text-xl leading-tight">{{ k.value }}</CardTitle>
            </CardHeader>
            <CardContent class="pb-3 pt-0">
              <p v-if="k.hint" class="text-[11px] text-slate-600 dark:text-zinc-300 line-clamp-1">
                {{ k.hint }}
              </p>
            </CardContent>
          </Card>

          <Card
            class="rounded-2xl border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-zinc-950/30 backdrop-blur"
          >
            <CardHeader class="py-3">
              <CardDescription class="text-[11px] leading-none">PDF</CardDescription>
              <CardTitle class="text-xl leading-tight">PDF2</CardTitle>
            </CardHeader>
            <CardContent class="pb-3 pt-0">
              <p class="text-[11px] text-slate-600 dark:text-zinc-300 line-clamp-1">
                PDF3
              </p>
            </CardContent>
          </Card>

          <Card
            class="rounded-2xl border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-zinc-950/30 backdrop-blur"
          >
            <CardHeader class="py-3">
              <CardDescription class="text-[11px] leading-none">excel</CardDescription>
              <CardTitle class="text-xl leading-tight">excel2</CardTitle>
            </CardHeader>
            <CardContent class="pb-3 pt-0">
              <p class="text-[11px] text-slate-600 dark:text-zinc-300 line-clamp-1">
                excel3
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Charts: 2 tarjetas (todo cabe) -->
        <div class="grid gap-3 lg:grid-cols-2 flex-1 min-h-0">
          <!-- Actividad -->
          <Card class="rounded-2xl border-slate-200/70 dark:border-white/10 overflow-hidden min-h-0">
            <CardHeader class="py-3">
              <CardTitle class="text-base">Actividad diaria</CardTitle>
              <CardDescription class="text-xs">Últimos 30 días (conteo de requisiciones).</CardDescription>
            </CardHeader>

            <CardContent class="pt-0 h-full min-h-0">
              <div
                class="h-full min-h-0 w-full relative z-0 overflow-hidden
                       rounded-2xl border border-slate-200/70 dark:border-white/10
                       bg-white/60 dark:bg-zinc-950/30 p-3"
              >
                <VisXYContainer :data="activityDaily" class="h-full w-full">
                  <VisAxis type="x" :x="x" />
                  <VisAxis type="y" />
                  <VisArea :x="x" :y="(d:any) => d.value" :opacity="0.25" />
                  <VisLine :x="x" :y="(d:any) => d.value" :stroke-width="2" />
                  <VisTooltip />
                </VisXYContainer>

                <div v-if="!activityDaily.length" class="h-full grid place-items-center text-xs text-slate-500 dark:text-zinc-400">
                  Sin datos todavía.
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Montos -->
          <Card class="rounded-2xl border-slate-200/70 dark:border-white/10 overflow-hidden min-h-0">
            <CardHeader class="py-3">
              <CardTitle class="text-base">Montos</CardTitle>
              <CardDescription class="text-xs">Últimos 30 días (monto total diario).</CardDescription>
            </CardHeader>

            <CardContent class="pt-0 h-full min-h-0">
              <div
                class="h-full min-h-0 w-full relative z-0 overflow-hidden
                       rounded-2xl border border-slate-200/70 dark:border-white/10
                       bg-white/60 dark:bg-zinc-950/30 p-3"
              >
                <VisXYContainer :data="amountsDaily" class="h-full w-full">
                  <VisAxis type="x" :x="x" />
                  <VisAxis type="y" />
                  <VisArea :x="x" :y="(d:any) => d.value" :opacity="0.20" />
                  <VisLine :x="x" :y="(d:any) => d.value" :stroke-width="2" />
                  <VisTooltip />
                </VisXYContainer>

                <div v-if="!amountsDaily.length" class="h-full grid place-items-center text-xs text-slate-500 dark:text-zinc-400">
                  Sin datos todavía.
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
