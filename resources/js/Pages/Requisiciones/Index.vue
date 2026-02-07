<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import DatePickerShadcn from '@/Components/ui/DatePickerShadcn.vue'

import ICON_PDF from '@/img/pdf.png'
import ICON_EXCEL from '@/img/excel.png'
import { toQS, downloadFile } from '@/Utils/exports'

import type { RequisicionesPageProps, RequisicionRow } from './Requisiciones.types'
import { useRequisicionesIndex } from './useRequisicionesIndex'

import {
  Plus,
  Search,
  Banknote,
  FileText,
  Printer,
  Trash2,
  ArrowUpDown,
  X,
} from 'lucide-vue-next'

const props = defineProps<RequisicionesPageProps>()

const {
  role,
  isColaborador,
  canDelete,
  canPay,
  canComprobar,

  state,
  rows,
  meta,
  safePagerLinks,

  corporativosActive,
  sucursalesFiltered,
  empleadosActive,
  statusOptions,

  inputBase,
  hasActiveFilters,
  clearFilters,

  sortLabel,
  toggleSort,

  selectedCount,
  isAllSelectedOnPage,
  toggleRow,
  toggleAllOnPage,
  clearSelection,
  destroySelected,

  goTo,
  goShow,
  goCreate,
  goPay,
  goComprobar,
  printReq,
  destroyRow,

  statusPill,
  fmtDateLong,
  money,
  displayName,
} = useRequisicionesIndex(props)

const exportPdfUrl = computed(() => route('requisiciones.export.pdf') + toQS(state as any))
const exportExcelUrl = computed(() => route('requisiciones.export.excel') + toQS(state as any))

const pageSummary = computed(() => {
  const from = meta.value?.from ?? null
  const to = meta.value?.to ?? null
  const total = meta.value?.total ?? null
  if (!from || !to || !total) return ''
  return `Mostrando ${from}–${to} de ${total}`
})

const corpPicked = computed(() => Number(state.comprador_corp_id || 0) > 0)

function pillText(s: any) {
  const v = String(s || '').toUpperCase()
  if (v === 'BORRADOR') return 'Borrador'
  if (v === 'ELIMINADA') return 'Eliminada'
  if (v === 'CAPTURADA') return 'Capturada'
  if (v === 'PAGO_AUTORIZADO') return 'Pago autorizado'
  if (v === 'PAGO_RECHAZADO') return 'Pago rechazado'
  if (v === 'PAGADA') return 'Pagada'
  if (v === 'POR_COMPROBAR') return 'Por comprobar'
  if (v === 'COMPROBACION_ACEPTADA') return 'Comprobación aceptada'
  if (v === 'COMPROBACION_RECHAZADA') return 'Comprobación rechazada'
  return v || '—'
}

function rowDisabled(r: RequisicionRow) {
  return String(r.status).toUpperCase() === 'ELIMINADA'
}
</script>

<template>
  <Head title="Requisiciones" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100 truncate">
          Requisiciones
        </h2>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <!-- HERO -->
      <div
        class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-4 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
      >
        <div class="min-w-0">
          <div class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-neutral-100 truncate">
            Control y seguimiento de requisiciones
          </div>
          <div class="text-sm text-slate-500 dark:text-neutral-300 truncate">
            {{ pageSummary || (isColaborador ? 'Mostrando tus requisiciones.' : 'Mostrando requisiciones del sistema.') }}
          </div>
        </div>

        <button
          type="button"
          @click="goCreate"
          class="inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-2.5 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 shadow-sm hover:shadow transition active:scale-[0.98] w-full sm:w-auto"
        >
          <Plus class="h-4 w-4" />
          Nueva requisición
        </button>
      </div>

      <!-- FILTROS -->
      <div
        class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4 sm:p-5"
      >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
          <!-- Corporativo -->
          <div class="lg:col-span-3 min-w-0 relative z-[30]">
            <SearchableSelect
              v-model="state.comprador_corp_id"
              :options="corporativosActive"
              label="Corporativo"
              placeholder="Todos"
              searchPlaceholder="Buscar corporativo..."
              :allowNull="true"
              nullLabel="Todos"
              rounded="2xl"
              zIndexClass="z-40"
              labelKey="nombre"
              valueKey="id"
            />
          </div>

          <!-- Sucursal (depende de corporativo) -->
          <div class="lg:col-span-3 min-w-0 relative z-[30]">
            <template v-if="corpPicked">
              <SearchableSelect
                v-model="state.sucursal_id"
                :options="sucursalesFiltered"
                label="Sucursal"
                placeholder="Todas"
                searchPlaceholder="Buscar sucursal..."
                :allowNull="true"
                nullLabel="Todas"
                rounded="2xl"
                zIndexClass="z-40"
                labelKey="nombre"
                secondaryKey="codigo"
                valueKey="id"
              />
            </template>

            <template v-else>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Sucursal</label>
              <div
                class="mt-1 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 px-4 py-3"
              >
                <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100">Todas</div>
                <div class="text-[12px] text-slate-500 dark:text-neutral-400">
                  Elige un corporativo para filtrar sucursales.
                </div>
              </div>
            </template>
          </div>

          <!-- Solicitante -->
          <div class="lg:col-span-3 min-w-0 relative z-[30]">
            <template v-if="!isColaborador">
              <SearchableSelect
                v-model="state.solicitante_id"
                :options="empleadosActive"
                label="Solicitante"
                placeholder="Todos"
                searchPlaceholder="Buscar empleado..."
                :allowNull="true"
                nullLabel="Todos"
                rounded="2xl"
                zIndexClass="z-40"
                labelKey="nombre"
                secondaryKey="puesto"
                valueKey="id"
              />
            </template>

            <template v-else>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Solicitante</label>
              <div
                class="mt-1 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 px-4 py-3"
              >
                <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100">Mis requisiciones</div>
                <div class="text-[12px] text-slate-500 dark:text-neutral-400">Rol: {{ role }}</div>
              </div>
            </template>
          </div>

          <!-- Estatus (único sistema: sin tabs) -->
          <div class="lg:col-span-3 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select v-model="state.status" :class="inputBase" class="!py-3">
              <option v-for="s in statusOptions" :key="s.id" :value="s.id">{{ s.nombre }}</option>
            </select>
          </div>

          <!-- Fechas -->
          <div class="lg:col-span-4 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha solicitada (rango)</label>
            <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
              <DatePickerShadcn v-model="state.fecha_from" placeholder="Desde" />
              <DatePickerShadcn v-model="state.fecha_to" placeholder="Hasta" />
            </div>
          </div>

          <!-- Buscar -->
          <div class="lg:col-span-5 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Buscar</label>
            <div class="relative">
              <Search class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                v-model="state.q"
                type="text"
                placeholder="Folio, proveedor, concepto, observaciones..."
                :class="inputBase"
                class="pl-11"
              />
              <button
                v-if="state.q"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 inline-flex items-center justify-center h-8 w-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-neutral-900 dark:hover:bg-white/10 transition"
                @click="state.q = ''"
                title="Limpiar búsqueda"
              >
                <X class="h-4 w-4 text-slate-600 dark:text-neutral-200" />
              </button>
            </div>
          </div>

          <!-- Por página -->
          <div class="lg:col-span-3 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
            <select v-model="state.perPage" :class="inputBase" class="!py-3">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="20">20</option>
              <option :value="50">50</option>
            </select>
          </div>

          <!-- Acciones de filtros -->
          <div class="lg:col-span-12 flex flex-wrap items-center gap-3 pt-2">
            <button
              type="button"
              @click="downloadFile(exportExcelUrl)"
              class="inline-flex items-center gap-3 rounded-2xl px-4 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 hover:shadow-sm transition active:scale-[0.99] dark:border-white/10 dark:bg-neutral-950/40 dark:hover:bg-white/10"
              title="Exportar Excel"
            >
              <span class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/10">
                <img :src="ICON_EXCEL" alt="Excel" class="h-6 w-6" />
              </span>
              <div class="leading-tight">
                <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Excel</div>
                <div class="text-[12px] text-slate-500 dark:text-neutral-300">Descargar</div>
              </div>
            </button>

            <button
              type="button"
              @click="downloadFile(exportPdfUrl)"
              class="inline-flex items-center gap-3 rounded-2xl px-4 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 hover:shadow-sm transition active:scale-[0.99] dark:border-white/10 dark:bg-neutral-950/40 dark:hover:bg-white/10"
              title="Exportar PDF"
            >
              <span class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/10">
                <img :src="ICON_PDF" alt="PDF" class="h-6 w-6" />
              </span>
              <div class="leading-tight">
                <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">PDF</div>
                <div class="text-[12px] text-slate-500 dark:text-neutral-300">Descargar</div>
              </div>
            </button>

            <button
              type="button"
              @click="toggleSort"
              class="ml-auto inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-xs font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition active:scale-[0.99]"
              title="Cambiar orden"
            >
              <ArrowUpDown class="h-4 w-4" />
              Orden: {{ sortLabel }}
            </button>

            <button
              v-if="hasActiveFilters"
              type="button"
              @click="clearFilters"
              class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-xs font-semibold border border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition active:scale-[0.99]"
            >
              Limpiar filtros
            </button>

            <template v-if="canDelete && selectedCount > 0">
              <button
                type="button"
                @click="destroySelected"
                class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-xs font-extrabold bg-rose-600 text-white hover:bg-rose-700 transition active:scale-[0.99]"
              >
                Eliminar seleccionadas ({{ selectedCount }})
              </button>

              <button
                type="button"
                @click="clearSelection"
                class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-xs font-semibold border border-slate-200 bg-white hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition active:scale-[0.99]"
              >
                Quitar selección
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- DESKTOP -->
      <div class="hidden lg:block">
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-[1200px] w-full">
              <thead class="bg-slate-50/80 dark:bg-neutral-950/40">
                <tr class="text-left text-[12px] font-extrabold text-slate-600 dark:text-neutral-300">
                  <th class="px-4 py-3 w-[44px]">
                    <input
                      type="checkbox"
                      :checked="isAllSelectedOnPage"
                      @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"
                      class="h-4 w-4 rounded border-slate-300 dark:border-white/20"
                    />
                  </th>
                  <th class="px-4 py-3">Folio</th>
                  <th class="px-4 py-3">Fecha solicitada</th>
                  <th class="px-4 py-3">Fecha entregada</th>
                  <th class="px-4 py-3">Solicitante</th>
                  <th class="px-4 py-3">Corporativo</th>
                  <th class="px-4 py-3">Sucursal</th>
                  <th class="px-4 py-3">Concepto</th>
                  <th class="px-4 py-3">Proveedor</th>
                  <th class="px-4 py-3 text-right">Total</th>
                  <th class="px-4 py-3">Estatus</th>
                  <th class="px-4 py-3">Observaciones</th>
                  <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="r in rows"
                  :key="r.id"
                  class="border-t border-slate-200/70 dark:border-white/10 hover:bg-slate-50/70 dark:hover:bg-white/5 transition"
                  :class="rowDisabled(r) ? 'opacity-60' : ''"
                >
                  <td class="px-4 py-3 align-top">
                    <input
                      type="checkbox"
                      :disabled="rowDisabled(r)"
                      @change="toggleRow(r.id, ($event.target as HTMLInputElement).checked)"
                      class="h-4 w-4 rounded border-slate-300 dark:border-white/20"
                    />
                  </td>

                  <td class="px-4 py-3 align-top">
                    <div class="font-extrabold text-slate-900 dark:text-neutral-100">{{ r.folio }}</div>
                    <div class="text-[12px] text-slate-500 dark:text-neutral-400">ID: {{ r.id }}</div>
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    {{ fmtDateLong(r.fecha_solicitud) }}
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    {{ fmtDateLong((r as any).fecha_pago) }}
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    {{ displayName(r.solicitante) }}
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    {{ displayName(r.comprador) }}
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    <div class="font-semibold">{{ displayName(r.sucursal) }}</div>
                    <div class="text-[12px] text-slate-500 dark:text-neutral-400">{{ (r.sucursal as any)?.codigo || '' }}</div>
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    {{ displayName(r.concepto) }}
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-800 dark:text-neutral-100">
                    {{ displayName(r.proveedor) }}
                  </td>

                  <td class="px-4 py-3 align-top text-right">
                    <div class="font-extrabold text-slate-900 dark:text-neutral-100">
                      {{ money(r.monto_total) }}
                    </div>
                    <div class="text-[12px] text-slate-500 dark:text-neutral-400">
                      Sub: {{ money(r.monto_subtotal) }}
                    </div>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <span
                      class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-extrabold"
                      :class="statusPill(r.status)"
                    >
                      <span class="h-2 w-2 rounded-full bg-emerald-500/70"></span>
                      {{ pillText(r.status) }}
                    </span>
                  </td>

                  <td class="px-4 py-3 align-top text-sm text-slate-700 dark:text-neutral-200">
                    <span class="line-clamp-2">{{ r.observaciones || '—' }}</span>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <div class="flex items-center justify-end gap-2">
                      <button
                        type="button"
                        class="actionBtn"
                        title="Ver detalles"
                        @click="goShow(r.id)"
                      >
                        <Search class="h-4 w-4" />
                      </button>

                      <button
                        type="button"
                        class="actionBtn"
                        title="Pagar"
                        :disabled="!canPay || rowDisabled(r)"
                        @click="goPay(r.id)"
                      >
                        <Banknote class="h-4 w-4" />
                      </button>

                      <button
                        type="button"
                        class="actionBtn"
                        title="Comprobación"
                        :disabled="!canComprobar || rowDisabled(r)"
                        @click="goComprobar(r.id)"
                      >
                        <FileText class="h-4 w-4" />
                      </button>

                      <button
                        type="button"
                        class="actionBtn"
                        title="Imprimir"
                        @click="printReq(r.id)"
                      >
                        <Printer class="h-4 w-4" />
                      </button>

                      <button
                        v-if="canDelete"
                        type="button"
                        class="actionBtnDanger"
                        title="Eliminar (lógico)"
                        :disabled="rowDisabled(r)"
                        @click="destroyRow(r)"
                      >
                        <Trash2 class="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="rows.length === 0">
                  <td colspan="13" class="px-6 py-10 text-center text-sm text-slate-500 dark:text-neutral-400">
                    No hay requisiciones con los filtros actuales.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination desktop -->
          <div class="px-4 sm:px-6 py-4 border-t border-slate-200/70 dark:border-white/10 flex items-center justify-between gap-3">
            <div class="text-sm text-slate-600 dark:text-neutral-300">{{ pageSummary }}</div>

            <div class="flex items-center gap-2 flex-wrap justify-end">
              <button
                v-for="l in safePagerLinks"
                :key="l.label + String(l.url)"
                type="button"
                @click="goTo(l.url)"
                :disabled="!l.url"
                class="pagerBtn"
                :class="l.active ? 'pagerBtnActive' : ''"
              >
                {{ l.cleanLabel === 'Previous' ? 'Atrás' : l.cleanLabel === 'Next' ? 'Siguiente' : l.cleanLabel }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- MOVIL -->
      <div class="lg:hidden space-y-3">
        <div
          v-for="r in rows"
          :key="r.id"
          class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4"
          :class="rowDisabled(r) ? 'opacity-60' : ''"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate">{{ r.folio }}</div>
              <div class="text-[12px] text-slate-500 dark:text-neutral-400">ID: {{ r.id }}</div>
            </div>

            <span
              class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-extrabold shrink-0"
              :class="statusPill(r.status)"
            >
              <span class="h-2 w-2 rounded-full bg-emerald-500/70"></span>
              {{ pillText(r.status) }}
            </span>
          </div>

          <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
            <div>
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Solicitante</div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ displayName(r.solicitante) }}</div>
            </div>

            <div>
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Total</div>
              <div class="font-extrabold text-slate-900 dark:text-neutral-100">{{ money(r.monto_total) }}</div>
            </div>

            <div>
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Corporativo</div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ displayName(r.comprador) }}</div>
            </div>

            <div>
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Sucursal</div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ displayName(r.sucursal) }}</div>
            </div>

            <div>
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Concepto</div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ displayName(r.concepto) }}</div>
            </div>

            <div>
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Proveedor</div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ displayName(r.proveedor) }}</div>
            </div>

            <div class="col-span-2">
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Fechas</div>
              <div class="text-slate-800 dark:text-neutral-100">
                <span class="font-semibold">Solicitada:</span> {{ fmtDateLong(r.fecha_solicitud) }}
              </div>
              <div class="text-slate-800 dark:text-neutral-100">
                <span class="font-semibold">Entregada:</span> {{ fmtDateLong((r as any).fecha_pago) }}
              </div>
            </div>

            <div class="col-span-2">
              <div class="text-[12px] font-bold text-slate-500 dark:text-neutral-400">Observaciones</div>
              <div class="text-slate-800 dark:text-neutral-100">{{ r.observaciones || '—' }}</div>
            </div>
          </div>

          <div class="mt-4 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                :disabled="rowDisabled(r)"
                @change="toggleRow(r.id, ($event.target as HTMLInputElement).checked)"
                class="h-4 w-4 rounded border-slate-300 dark:border-white/20"
              />
              <span class="text-[12px] text-slate-600 dark:text-neutral-300">Seleccionar</span>
            </div>

            <div class="flex items-center gap-2">
              <button class="actionBtn" title="Ver" @click="goShow(r.id)"><Search class="h-4 w-4" /></button>
              <button class="actionBtn" title="Pagar" :disabled="!canPay || rowDisabled(r)" @click="goPay(r.id)"><Banknote class="h-4 w-4" /></button>
              <button class="actionBtn" title="Comprobación" :disabled="!canComprobar || rowDisabled(r)" @click="goComprobar(r.id)"><FileText class="h-4 w-4" /></button>
              <button class="actionBtn" title="Imprimir" @click="printReq(r.id)"><Printer class="h-4 w-4" /></button>
              <button v-if="canDelete" class="actionBtnDanger" title="Eliminar" :disabled="rowDisabled(r)" @click="destroyRow(r)"><Trash2 class="h-4 w-4" /></button>
            </div>
          </div>
        </div>

        <div v-if="rows.length === 0" class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-6 text-center text-sm text-slate-500 dark:text-neutral-400">
          No hay requisiciones con los filtros actuales.
        </div>

        <!-- Pagination móvil -->
        <div v-if="safePagerLinks.length" class="flex items-center justify-center gap-2 flex-wrap py-2">
          <button
            v-for="l in safePagerLinks"
            :key="l.label + String(l.url)"
            type="button"
            @click="goTo(l.url)"
            :disabled="!l.url"
            class="pagerBtn"
            :class="l.active ? 'pagerBtnActive' : ''"
          >
            {{ l.cleanLabel === 'Previous' ? 'Atrás' : l.cleanLabel === 'Next' ? 'Siguiente' : l.cleanLabel }}
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.actionBtn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  width: 36px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(255, 255, 255, 0.9);
  transition: transform 140ms ease, box-shadow 140ms ease, background 140ms ease, border-color 140ms ease;
}
.actionBtn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.08);
  background: rgba(248, 250, 252, 1);
}
.actionBtn:disabled {
  opacity: 0.45;
  pointer-events: none;
}
.actionBtnDanger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  width: 36px;
  border-radius: 14px;
  border: 1px solid rgba(244, 63, 94, 0.35);
  background: rgba(255, 241, 242, 0.9);
  transition: transform 140ms ease, box-shadow 140ms ease, background 140ms ease, border-color 140ms ease;
}
.actionBtnDanger:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.08);
  background: rgba(255, 228, 230, 1);
}
.actionBtnDanger:disabled {
  opacity: 0.45;
  pointer-events: none;
}

.pagerBtn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 38px;
  padding: 0 12px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(255, 255, 255, 0.9);
  font-weight: 800;
  font-size: 12px;
  color: rgba(15, 23, 42, 0.85);
  transition: transform 140ms ease, box-shadow 140ms ease, background 140ms ease, border-color 140ms ease;
}
.pagerBtn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.08);
  background: rgba(248, 250, 252, 1);
}
.pagerBtn:disabled {
  opacity: 0.45;
  pointer-events: none;
}
.pagerBtnActive {
  background: rgba(15, 23, 42, 0.95);
  color: white;
  border-color: rgba(15, 23, 42, 0.95);
}
</style>
