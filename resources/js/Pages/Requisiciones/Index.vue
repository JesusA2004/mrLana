<!-- resources/js/Pages/Requisiciones/Index.vue -->
<script setup lang="ts">
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DatePickerShadcn from '@/Components/ui/DatePickerShadcn.vue'

import ICON_PDF from '@/img/pdf.png'
import ICON_EXCEL from '@/img/excel.png'
import { toQS, downloadFile } from '@/Utils/exports'

import type { RequisicionesPageProps } from './Requisiciones.types'
import { useRequisicionesIndex } from './useRequisicionesIndex'

import {
  Search,
  Banknote,
  FileCheck2,
  Printer,
  Trash2,
  Plus,
  Filter,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const props = defineProps<RequisicionesPageProps>()

const page = usePage<any>()
const userRole = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase() as 'ADMIN'|'CONTADOR'|'COLABORADOR')
const isColaborador = computed(() => userRole.value === 'COLABORADOR')

const {
  canDelete,
  canPay,
  canUploadComprobantes,

  state,
  rows,
  safePagerLinks,
  tabs,

  corporativosActive,
  sucursalesActive,
  sucursalesFiltered,
  empleadosActive,
  statusOptions,

  inputBase,
  hasActiveFilters,
  clearFilters,

  sortLabel,
  toggleSort,

  selectedIds,
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
  money,
  statusPill,
  statusLabel,
  destroyRow,

  // fechas (popover)
  dateOpen,
  dateAnchorRef,
  datePanelRef,
  tempFrom,
  tempTo,
  dateLabel,
  openDate,
  closeDate,
  applyDate,
  clearDate,
  presetToday,
  presetLast7,
  presetThisMonth,
} = useRequisicionesIndex(props)

const exportPdfUrl   = computed(() => route('requisiciones.export.pdf')   + toQS(state))
const exportExcelUrl = computed(() => route('requisiciones.export.excel') + toQS(state))

const headline = computed(() => {
  if (isColaborador.value) return 'Mostrando tus requisiciones.'
  if (state.solicitante_id) return 'Mostrando requisiciones del solicitante seleccionado.'
  return 'Mostrando requisiciones de todos los usuarios.'
})

const showSolicitante = computed(() => !isColaborador.value)
</script>

<template>
  <Head title="Requisiciones" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100 truncate">
          Requisiciones
        </h2>

        <button type="button" @click="goCreate"
          class="hidden sm:inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold
          bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600
          shadow-sm hover:shadow transition active:scale-[0.98]">
          <Plus class="h-4 w-4" />
          Nueva
        </button>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4">

      <!-- HERO -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-4 sm:px-6 py-4 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="min-w-0">
            <div class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-neutral-100 truncate">
              Control y seguimiento de requisiciones
            </div>
            <div class="text-sm text-slate-500 dark:text-neutral-300 truncate">
              {{ headline }}
            </div>
          </div>

          <button type="button" @click="goCreate"
            class="sm:hidden inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-2.5 text-sm font-semibold
            bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600
            shadow-sm hover:shadow transition active:scale-[0.98] w-full">
            <Plus class="h-4 w-4" />
            Nueva requisición
          </button>
        </div>
      </div>

      <!-- TABS -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-3 py-3">
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            @click="t.enabled !== false && (state.tab = t.key as any)"
            class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold border transition active:scale-[0.98]"
            :class="[
              state.tab === t.key
                ? 'bg-slate-900 text-white border-slate-900 shadow-sm dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:-translate-y-[1px] dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40',
              t.enabled === false ? 'opacity-40 pointer-events-none' : ''
            ]"
          >
            <span class="truncate">{{ t.label }}</span>
            <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full text-xs font-extrabold"
              :class="state.tab === t.key
                ? 'bg-white/15 text-white dark:bg-neutral-900 dark:text-neutral-100'
                : 'bg-slate-100 text-slate-800 dark:bg-neutral-950/40 dark:text-neutral-100'">
              {{ t.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- FILTROS -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4 sm:p-5 relative z-[50]">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

          <!-- CORPORATIVO -->
          <div class="lg:col-span-3 min-w-0 relative z-[20]">
            <SearchableSelect
              v-model="state.comprador_corp_id"
              :options="corporativosActive"
              label="Corporativo"
              placeholder="Todos"
              searchPlaceholder="Buscar corporativo..."
              :allowNull="true"
              nullLabel="Todos"
              rounded="2xl"
              zIndexClass="z-50"
              labelKey="nombre"
              valueKey="id"
            />
          </div>

          <!-- SUCURSAL (filtrada por corporativo) -->
          <div class="lg:col-span-3 min-w-0 relative z-[15]">
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
              valueKey="id"
              :disabled="!state.comprador_corp_id"
            />
            <p v-if="!state.comprador_corp_id" class="mt-1 text-[11px] text-slate-500 dark:text-neutral-400">
              Elige un corporativo para filtrar sucursales.
            </p>
          </div>

          <!-- SOLICITANTE (solo admin/conta) -->
          <div class="lg:col-span-3 min-w-0 relative z-[10]">
            <template v-if="showSolicitante">
              <SearchableSelect
                v-model="state.solicitante_id"
                :options="empleadosActive"
                label="Solicitante"
                placeholder="Todos"
                searchPlaceholder="Buscar empleado..."
                :allowNull="true"
                nullLabel="Todos"
                rounded="2xl"
                zIndexClass="z-30"
                labelKey="nombre"
                valueKey="id"
              />
            </template>

            <template v-else>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">
                Solicitante
              </label>
              <div class="mt-1 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 px-4 py-3">
                <div class="font-semibold text-slate-900 dark:text-neutral-100">Mis requisiciones</div>
              </div>
            </template>
          </div>

          <!-- STATUS -->
          <div class="lg:col-span-3 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select v-model="state.status" class="mt-1 w-full rounded-2xl px-3 py-3 text-sm border
              border-slate-200 bg-white text-slate-900 hover:border-slate-300
              dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:border-white/20 transition">
              <option value="">Todos</option>
              <option v-for="o in statusOptions" :key="o.value" :value="o.value">
                {{ o.label }}
              </option>
            </select>
          </div>

          <!-- FECHAS (Shadcn) -->
          <div class="lg:col-span-4 min-w-0 relative z-[10]">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Rango de fechas</label>

            <button ref="dateAnchorRef" type="button"
              @click="dateOpen ? closeDate() : openDate()"
              class="mt-1 w-full rounded-2xl px-4 py-3 text-left text-sm font-semibold border
              border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:border-slate-300
              dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60 dark:hover:border-white/20
              transition active:scale-[0.99]">
              <div class="flex items-center justify-between gap-3">
                <span class="truncate">{{ dateLabel }}</span>
                <span class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-500 dark:text-neutral-300">
                  <span class="h-2 w-2 rounded-full bg-emerald-500/80"></span>Rango
                </span>
              </div>
            </button>

            <transition name="fadeUp">
              <div v-if="dateOpen" ref="datePanelRef"
                class="absolute left-0 top-full mt-2 w-full max-w-[620px]
                rounded-3xl border border-slate-200/70 dark:border-white/10
                bg-white/95 dark:bg-neutral-950/90 backdrop-blur shadow-2xl p-4 z-[9999]">

                <!-- presets -->
                <div class="flex flex-wrap gap-2">
                  <button type="button" @click="presetToday"
                    class="chip">Hoy</button>
                  <button type="button" @click="presetLast7"
                    class="chip">Últimos 7 días</button>
                  <button type="button" @click="presetThisMonth"
                    class="chip">Mes actual</button>
                </div>

                <!-- datepickers -->
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <DatePickerShadcn v-model="tempFrom" label="Desde" placeholder="Selecciona fecha" />
                  <DatePickerShadcn v-model="tempTo" label="Hasta" placeholder="Selecciona fecha" />
                </div>

                <!-- actions -->
                <div class="mt-4 flex items-center justify-between gap-2">
                  <button type="button" @click="clearDate" class="btnDanger">
                    Limpiar
                  </button>

                  <div class="flex gap-2">
                    <button type="button" @click="closeDate" class="btnGhost">
                      Cerrar
                    </button>
                    <button type="button" @click="applyDate" class="btnOk">
                      Aplicar
                    </button>
                  </div>
                </div>

                <div class="mt-2 text-[11px] text-slate-500 dark:text-neutral-400">
                  Tip: Escape o clic fuera para cerrar.
                </div>
              </div>
            </transition>
          </div>

          <!-- BUSCAR -->
          <div class="lg:col-span-4 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Buscar</label>
            <input v-model="state.q" type="text" placeholder="Folio, proveedor, observaciones..."
              :class="inputBase + ' rounded-2xl hover:border-slate-300 dark:hover:border-white/20'" />
          </div>

          <!-- PER PAGE -->
          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
            <select v-model.number="state.perPage"
              class="mt-1 w-full rounded-2xl px-3 py-3 text-sm border
              border-slate-200 bg-white text-slate-900 hover:border-slate-300
              dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:border-white/20 transition">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="20">20</option>
              <option :value="30">30</option>
              <option :value="50">50</option>
            </select>
          </div>

          <!-- EXPORTS + ACCIONES -->
          <div class="lg:col-span-12 flex flex-wrap items-center gap-3 pt-2">
            <button type="button" @click="downloadFile(exportExcelUrl)" class="exportBtn" title="Exportar Excel">
              <span class="exportIconWrap">
                <img :src="ICON_EXCEL" alt="Excel" class="h-6 w-6" />
              </span>
              <span class="exportText">Excel</span>
            </button>

            <button type="button" @click="downloadFile(exportPdfUrl)" class="exportBtn" title="Exportar PDF">
              <span class="exportIconWrap">
                <img :src="ICON_PDF" alt="PDF" class="h-6 w-6" />
              </span>
              <span class="exportText">PDF</span>
            </button>

            <button type="button" @click="toggleSort"
              class="ml-auto inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-xs font-semibold
              bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition"
              title="Cambiar orden">
              <Filter class="h-4 w-4" />
              Orden: {{ sortLabel }}
            </button>

            <SecondaryButton v-if="hasActiveFilters" type="button" @click="clearFilters" class="rounded-2xl">
              <span class="inline-flex items-center gap-2">
                <RefreshCw class="h-4 w-4" />
                Limpiar
              </span>
            </SecondaryButton>

            <!-- Bulk actions -->
            <div v-if="canDelete && selectedCount > 0" class="w-full sm:w-auto sm:ml-2 flex items-center gap-2">
              <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">
                Seleccionadas: <span class="font-extrabold">{{ selectedCount }}</span>
              </div>
              <button type="button" @click="destroySelected" class="btnDanger">
                Eliminar seleccionadas
              </button>
              <button type="button" @click="clearSelection" class="btnGhost">
                Limpiar selección
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TABLE (lg+) -->
      <div class="hidden lg:block rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-neutral-950/40">
              <tr class="text-left text-xs font-bold text-slate-600 dark:text-neutral-300">
                <th v-if="canDelete" class="px-4 py-3 w-[52px]">
                  <input type="checkbox" :checked="isAllSelectedOnPage" @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"
                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                </th>
                <th class="px-4 py-3">Folio</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3">Solicitante</th>
                <th class="px-4 py-3">Corporativo</th>
                <th class="px-4 py-3">Sucursal</th>
                <th class="px-4 py-3">Concepto</th>
                <th class="px-4 py-3">Proveedor</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3">Estatus</th>
                <th class="px-4 py-3 text-right w-[230px]">Acciones</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="r in rows" :key="r.id"
                class="border-t border-slate-100 dark:border-white/5 hover:bg-slate-50/60 dark:hover:bg-white/5 transition">
                <td v-if="canDelete" class="px-4 py-3">
                  <input type="checkbox"
                    :checked="selectedIds.has(r.id)"
                    @change="toggleRow(r.id, ($event.target as HTMLInputElement).checked)"
                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                </td>

                <td class="px-4 py-3 font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ r.folio ?? ('#' + r.id) }}
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                  {{ r.fecha_solicitud_fmt ?? r.fecha_solicitud ?? '—' }}
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                  {{ r.solicitante_nombre ?? r.solicitante?.nombre ?? '—' }}
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                  {{ r.comprador_nombre ?? r.comprador?.nombre ?? '—' }}
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                  {{ r.sucursal_nombre ?? r.sucursal?.nombre ?? '—' }}
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                  {{ r.concepto_nombre ?? r.concepto?.nombre ?? '—' }}
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                  {{ r.proveedor_razon_social ?? r.proveedor?.razon_social ?? '—' }}
                </td>

                <td class="px-4 py-3 text-right font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ money(r.monto_total ?? r.total ?? 0) }}
                </td>

                <td class="px-4 py-3">
                  <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-extrabold border"
                    :class="statusPill(String(r.status ?? ''))">
                    <span class="h-2 w-2 rounded-full bg-emerald-500/70"></span>
                    {{ statusLabel(String(r.status ?? '')) }}
                  </span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Detalles -->
                    <button type="button" class="iconBtn" title="Detalles" @click="goShow(r.id)">
                      <Search class="h-4 w-4" />
                    </button>

                    <!-- Pagar (solo icono y navegación base) -->
                    <button type="button"
                      class="iconBtn"
                      :class="canPay ? '' : 'opacity-40 pointer-events-none'"
                      :title="canPay ? 'Pagar' : 'Solo contabilidad/admin'"
                      @click="canPay && goPay(r.id)">
                      <Banknote class="h-4 w-4" />
                    </button>

                    <!-- Comprobación -->
                    <button type="button"
                      class="iconBtn"
                      :class="canUploadComprobantes ? '' : 'opacity-40 pointer-events-none'"
                      :title="canUploadComprobantes ? 'Comprobación' : 'Sin acceso'"
                      @click="canUploadComprobantes && goComprobar(r.id)">
                      <FileCheck2 class="h-4 w-4" />
                    </button>

                    <!-- Imprimir (SOLO ICONO: sin función) -->
                    <button type="button" class="iconBtn opacity-60 cursor-not-allowed" title="Imprimir (próximamente)">
                      <Printer class="h-4 w-4" />
                    </button>

                    <!-- Eliminar (solo admin/conta) -->
                    <button v-if="canDelete" type="button" class="iconBtnDanger" title="Eliminar"
                      @click="destroyRow(r)">
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="rows.length === 0">
                <td :colspan="canDelete ? 11 : 10" class="px-6 py-10 text-center">
                  <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">No hay requisiciones para mostrar</div>
                  <div class="text-sm text-slate-500 dark:text-neutral-400 mt-1">
                    Ajusta filtros o crea una nueva requisición.
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINACIÓN -->
        <div v-if="safePagerLinks.length > 0" class="px-4 py-3 border-t border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900">
          <div class="flex items-center justify-between gap-3">
            <div class="text-xs text-slate-500 dark:text-neutral-400">
              Navegación
            </div>

            <div class="flex items-center gap-1">
              <button
                type="button"
                class="pageBtn"
                :disabled="!safePagerLinks[0]?.url"
                @click="goTo(safePagerLinks[0]?.url)"
                title="Atrás"
              >
                <ChevronLeft class="h-4 w-4" />
              </button>

              <button
                v-for="l in safePagerLinks.filter(x => x.cleanLabel && /^\d+$/.test(x.cleanLabel)).slice(0, 9)"
                :key="l.label"
                type="button"
                class="pageBtn"
                :class="l.active ? 'pageBtnActive' : ''"
                @click="goTo(l.url)"
              >
                {{ l.cleanLabel }}
              </button>

              <button
                type="button"
                class="pageBtn"
                :disabled="!safePagerLinks[safePagerLinks.length - 1]?.url"
                @click="goTo(safePagerLinks[safePagerLinks.length - 1]?.url)"
                title="Siguiente"
              >
                <ChevronRight class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- CARDS (mobile/tablet) -->
      <div class="lg:hidden space-y-3">
        <div v-for="r in rows" :key="r.id"
          class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                {{ r.folio ?? ('#' + r.id) }}
              </div>
              <div class="text-xs text-slate-500 dark:text-neutral-400">
                {{ r.fecha_solicitud_fmt ?? r.fecha_solicitud ?? '—' }}
              </div>
            </div>

            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-extrabold border"
              :class="statusPill(String(r.status ?? ''))">
              <span class="h-2 w-2 rounded-full bg-emerald-500/70"></span>
              {{ statusLabel(String(r.status ?? '')) }}
            </span>
          </div>

          <div class="mt-3 grid grid-cols-1 gap-1 text-sm">
            <div class="text-slate-700 dark:text-neutral-200">
              <span class="text-slate-500 dark:text-neutral-400">Solicitante:</span>
              <span class="font-semibold"> {{ r.solicitante_nombre ?? r.solicitante?.nombre ?? '—' }}</span>
            </div>

            <div class="text-slate-700 dark:text-neutral-200">
              <span class="text-slate-500 dark:text-neutral-400">Sucursal:</span>
              <span class="font-semibold"> {{ r.sucursal_nombre ?? r.sucursal?.nombre ?? '—' }}</span>
            </div>

            <div class="text-slate-700 dark:text-neutral-200">
              <span class="text-slate-500 dark:text-neutral-400">Total:</span>
              <span class="font-extrabold"> {{ money(r.monto_total ?? r.total ?? 0) }}</span>
            </div>
          </div>

          <div class="mt-4 flex items-center justify-end gap-2">
            <button type="button" class="iconBtn" title="Detalles" @click="goShow(r.id)">
              <Search class="h-4 w-4" />
            </button>

            <button type="button" class="iconBtn" :class="canPay ? '' : 'opacity-40 pointer-events-none'"
              :title="canPay ? 'Pagar' : 'Solo contabilidad/admin'" @click="canPay && goPay(r.id)">
              <Banknote class="h-4 w-4" />
            </button>

            <button type="button" class="iconBtn" :class="canUploadComprobantes ? '' : 'opacity-40 pointer-events-none'"
              :title="canUploadComprobantes ? 'Comprobación' : 'Sin acceso'" @click="canUploadComprobantes && goComprobar(r.id)">
              <FileCheck2 class="h-4 w-4" />
            </button>

            <button type="button" class="iconBtn opacity-60 cursor-not-allowed" title="Imprimir (próximamente)">
              <Printer class="h-4 w-4" />
            </button>

            <button v-if="canDelete" type="button" class="iconBtnDanger" title="Eliminar" @click="destroyRow(r)">
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div v-if="rows.length === 0" class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-6 text-center">
          <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">No hay requisiciones</div>
          <div class="text-sm text-slate-500 dark:text-neutral-400 mt-1">Ajusta filtros o crea una nueva.</div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.fadeUp-enter-active,
.fadeUp-leave-active {
  transition: all 160ms ease;
}
.fadeUp-enter-from,
.fadeUp-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

/* Chips */
.chip{
  @apply inline-flex items-center justify-center rounded-2xl px-3 py-2 text-xs font-extrabold border
  border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100
  dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition;
}

/* Buttons */
.btnGhost{
  @apply inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold border
  border-slate-200 bg-white text-slate-800 hover:bg-slate-50
  dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition active:scale-[0.99];
}

.btnOk{
  @apply inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-extrabold
  bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600
  shadow-sm hover:shadow transition active:scale-[0.99];
}

.btnDanger{
  @apply inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-extrabold
  border border-rose-500/25 bg-rose-500/10 text-rose-700 hover:bg-rose-500/15
  dark:text-rose-200 dark:border-rose-500/25 dark:bg-rose-500/10 dark:hover:bg-rose-500/15
  transition active:scale-[0.99];
}

/* Export */
.exportBtn{
  @apply inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-sm font-extrabold
  border border-slate-200 bg-white/90 text-slate-900 shadow-sm hover:shadow-md hover:-translate-y-[1px]
  dark:border-white/10 dark:bg-zinc-900/60 dark:text-zinc-100 dark:hover:bg-zinc-900/75
  transition active:scale-[0.99];
}
.exportIconWrap{
  @apply inline-flex items-center justify-center h-9 w-9 rounded-2xl
  bg-slate-100 border border-slate-200
  dark:bg-white/10 dark:border-white/10;
}
.exportText{ @apply pr-2; }

/* Icon buttons */
.iconBtn{
  @apply inline-flex items-center justify-center h-9 w-9 rounded-2xl border
  border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 hover:-translate-y-[1px]
  dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/10 dark:hover:border-white/20
  transition active:scale-[0.98];
}
.iconBtnDanger{
  @apply inline-flex items-center justify-center h-9 w-9 rounded-2xl border
  border-rose-500/25 bg-rose-500/10 text-rose-700 hover:bg-rose-500/15 hover:-translate-y-[1px]
  dark:text-rose-200 dark:border-rose-500/25 dark:bg-rose-500/10 dark:hover:bg-rose-500/15
  transition active:scale-[0.98];
}

/* Pagination */
.pageBtn{
  @apply inline-flex items-center justify-center h-9 min-w-[36px] px-3 rounded-2xl border
  border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300
  dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/10 dark:hover:border-white/20
  transition active:scale-[0.98] disabled:opacity-40 disabled:pointer-events-none;
}
.pageBtnActive{
  @apply bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100;
}
</style>
