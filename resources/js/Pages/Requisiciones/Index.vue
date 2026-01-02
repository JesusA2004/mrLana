<script setup lang="ts">
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

import type { RequisicionesPageProps } from './Requisiciones.types'
import { useRequisicionesIndex } from './useRequisicionesIndex'

import ICON_PDF from '@/img/pdf.png'
import ICON_EXCEL from '@/img/excel.png'
import { toQS, downloadFile } from '@/Utils/exports'

const props = defineProps<RequisicionesPageProps>()

const page = usePage()
const userRole = computed(
  () => ((page.props as any)?.auth?.user?.rol ?? 'COLABORADOR') as 'ADMIN' | 'CONTADOR' | 'COLABORADOR'
)

const {
  // permisos
  canDelete,
  canPay,
  canUploadComprobantes,

  // data + filtros
  state,
  rows,
  tabs,
  corporativosActive,
  sucursalesActive,
  empleadosActive,
  inputBase,
  selectBase,
  hasActiveFilters,
  clearFilters,
  sortLabel,
  toggleSort,

  // selección
  selectedIds,
  selectedCount,
  isAllSelectedOnPage,
  toggleRow,
  toggleAllOnPage,
  clearSelection,
  destroySelected,

  // navegación
  goTo,
  goShow,
  goCreate,
  goPay,
  goComprobar,
  printReq,

  // helpers UI
  money,
  statusPill,

  // “eliminar”
  destroyRow,

  // tabs
  setTab,

  // date range popover
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

  // paginación
  safePagerLinks,
} = useRequisicionesIndex(props)

const exportPdfUrl = computed(() => route('requisiciones.export.pdf') + toQS(state))
const exportExcelUrl = computed(() => route('requisiciones.export.excel') + toQS(state))

const isColaborador = computed(() => userRole.value === 'COLABORADOR')

const headline = computed(() => {
  if (isColaborador.value) return 'Mostrando tus requisiciones.'
  if (state.solicitante_id) return 'Mostrando requisiciones del solicitante seleccionado.'
  return 'Mostrando requisiciones de todos los usuarios.'
})

const solicitanteDisabled = computed(() => isColaborador.value)
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
        class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10
               bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-4 sm:px-6 py-4 sm:py-5
               flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
      >
        <div class="min-w-0">
          <div class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-neutral-100 truncate">
            Control y seguimiento de requisiciones
          </div>
          <div class="text-sm text-slate-500 dark:text-neutral-300 truncate">
            {{ headline }}
          </div>
        </div>

        <button
          type="button"
          @click="goCreate"
          class="inline-flex items-center justify-center gap-2
                 rounded-2xl px-5 py-2.5 text-sm font-semibold
                 bg-emerald-600 text-white hover:bg-emerald-700
                 dark:bg-emerald-500 dark:hover:bg-emerald-600
                 shadow-sm hover:shadow transition active:scale-[0.98] w-full sm:w-auto"
        >
          <span class="inline-block h-2 w-2 rounded-full bg-white/80"></span>
          Nueva requisición
        </button>
      </div>

      <!-- TABS -->
      <div
        class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10
               bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-3 py-3"
      >
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            @click="setTab(t.key as any)"
            class="inline-flex items-center gap-2 rounded-2xl px-4 py-2
                   text-sm font-semibold border transition active:scale-[0.98]"
            :class="
              state.tab === t.key
                ? 'bg-slate-900 text-white border-slate-900 shadow-sm dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:-translate-y-[1px] dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'
            "
          >
            <span class="truncate">{{ t.label }}</span>
            <span
              class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full text-xs font-extrabold"
              :class="
                state.tab === t.key
                  ? 'bg-white/15 text-white dark:bg-neutral-900 dark:text-neutral-100'
                  : 'bg-slate-100 text-slate-800 dark:bg-neutral-950/40 dark:text-neutral-100'
              "
            >
              {{ t.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- FILTROS (realtime, sin botón Filtrar) -->
      <div
        class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10
               bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4 sm:p-5"
      >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

          <!-- SOLICITANTE -->
          <div class="lg:col-span-4 min-w-0">
            <template v-if="!solicitanteDisabled">
              <SearchableSelect
                v-model="state.solicitante_id"
                :options="empleadosActive"
                label="Mostrar requisiciones de"
                placeholder="Todos los usuarios"
                searchPlaceholder="Buscar empleado..."
                :allowNull="true"
                nullLabel="Todos los usuarios"
                rounded="2xl"
                zIndexClass="z-40"
                labelKey="nombre"
                secondaryKey="puesto"
              />
            </template>

            <template v-else>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">
                Mostrar requisiciones de
              </label>
              <div
                class="mt-1 rounded-2xl border border-slate-200/70 dark:border-white/10
                       bg-slate-50/80 dark:bg-neutral-950/40 px-4 py-3"
              >
                <div class="font-semibold text-slate-900 dark:text-neutral-100">Mis requisiciones</div>
              </div>
            </template>
          </div>

          <!-- FECHAS -->
          <div class="lg:col-span-4 min-w-0 relative">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Filtrar por fechas</label>

            <button
              ref="dateAnchorRef"
              type="button"
              @click="dateOpen ? closeDate() : openDate()"
              class="mt-1 w-full rounded-2xl px-4 py-3 text-left
                     text-sm font-semibold border
                     border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:border-slate-300
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60 dark:hover:border-white/20
                     transition active:scale-[0.99]"
            >
              <div class="flex items-center justify-between gap-3">
                <span class="truncate">{{ dateLabel }}</span>
                <span class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-500 dark:text-neutral-300">
                  <span class="h-2 w-2 rounded-full bg-emerald-500/80"></span>
                  Rango
                </span>
              </div>
            </button>

            <transition name="fadeUp">
              <div
                v-if="dateOpen"
                ref="datePanelRef"
                class="absolute left-0 top-full mt-2 w-full max-w-[560px]
                       rounded-3xl border border-slate-200/70 dark:border-white/10
                       bg-white/95 dark:bg-neutral-950/90 backdrop-blur
                       shadow-2xl p-3 z-[9999]"
              >
                <!-- chips -->
                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    @click="presetToday"
                    class="chip
                           border border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100
                           dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15"
                  >
                    Hoy
                  </button>

                  <button
                    type="button"
                    @click="presetLast7"
                    class="chip
                           border border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100
                           dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15"
                  >
                    Últimos 7 días
                  </button>

                  <button
                    type="button"
                    @click="presetThisMonth"
                    class="chip
                           border border-slate-200 bg-slate-50 text-slate-800 hover:bg-slate-100
                           dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15"
                  >
                    Mes actual
                  </button>
                </div>

                <!-- inputs -->
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div class="min-w-0">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-300">Desde</label>
                    <input
                      v-model="tempFrom"
                      type="date"
                      :class="inputBase + ' rounded-2xl'"
                      class="!bg-white dark:!bg-neutral-900/60"
                    />
                  </div>

                  <div class="min-w-0">
                    <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-300">Hasta</label>
                    <input
                      v-model="tempTo"
                      type="date"
                      :class="inputBase + ' rounded-2xl'"
                      class="!bg-white dark:!bg-neutral-900/60"
                    />
                  </div>
                </div>

                <!-- acciones -->
                <div class="mt-3 flex items-center justify-between gap-2">
                  <button
                    type="button"
                    @click="clearDate"
                    class="btn
                           border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100
                           dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15"
                  >
                    Limpiar
                  </button>

                  <div class="flex gap-2">
                    <button
                      type="button"
                      @click="closeDate"
                      class="btn
                             border border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                             dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15"
                    >
                      Cerrar
                    </button>

                    <button
                      type="button"
                      @click="applyDate"
                      class="btn
                             bg-emerald-600 text-white hover:bg-emerald-700
                             dark:bg-emerald-500 dark:hover:bg-emerald-600"
                    >
                      Aplicar
                    </button>
                  </div>
                </div>

                <div class="mt-2 text-[11px] text-slate-500 dark:text-neutral-400">
                  Tip: Escape o clic afuera para cerrar.
                </div>
              </div>
            </transition>
          </div>

          <!-- BUSCAR -->
          <div class="lg:col-span-4 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Buscar</label>
            <input
              v-model="state.q"
              type="text"
              placeholder="Folio, observaciones..."
              :class="inputBase + ' rounded-2xl hover:border-slate-300 dark:hover:border-white/20'"
            />
          </div>

          <!-- EXPORTS + ACCIONES -->
          <div class="lg:col-span-12 flex flex-wrap items-center gap-3 pt-2">
            <button
              type="button"
              @click="downloadFile(exportExcelUrl)"
              class="exportBtn
                     border border-slate-200 bg-white/90 text-slate-900 shadow-sm hover:shadow-md
                     dark:border-white/10 dark:bg-zinc-900/60 dark:text-zinc-100 dark:hover:bg-zinc-900/75"
              title="Exportar Excel"
            >
              <img :src="ICON_EXCEL" alt="Excel" class="h-6 w-6" />
              <span>Excel</span>
            </button>

            <button
              type="button"
              @click="downloadFile(exportPdfUrl)"
              class="exportBtn
                     border border-slate-200 bg-white/90 text-slate-900 shadow-sm hover:shadow-md
                     dark:border-white/10 dark:bg-zinc-900/60 dark:text-zinc-100 dark:hover:bg-zinc-900/75"
              title="Exportar PDF"
            >
              <img :src="ICON_PDF" alt="PDF" class="h-6 w-6" />
              <span>PDF</span>
            </button>

            <!-- orden opcional -->
            <button
              type="button"
              @click="toggleSort"
              class="ml-auto inline-flex items-center justify-center rounded-2xl px-4 py-2
                     text-xs font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200
                     dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition"
              title="Cambiar orden"
            >
              Orden: {{ sortLabel }}
            </button>

            <SecondaryButton
              v-if="hasActiveFilters"
              type="button"
              @click="clearFilters"
              class="rounded-2xl"
            >
              Limpiar
            </SecondaryButton>
          </div>
        </div>
      </div>

      <!-- TABLA (desktop) -->
      <div
        class="hidden 2xl:block rounded-3xl border border-slate-200/70 dark:border-white/10
               bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm overflow-visible"
      >
        <table class="w-full table-auto text-sm">
          <thead class="bg-slate-50 dark:bg-neutral-950/60">
            <tr class="text-left text-slate-600 dark:text-neutral-300">
              <th class="px-4 py-3 font-semibold w-12">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                  :checked="isAllSelectedOnPage"
                  @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"
                />
              </th>
              <th class="px-4 py-3 font-semibold">Folio</th>
              <th class="px-4 py-3 font-semibold">Tipo</th>
              <th class="px-4 py-3 font-semibold">Corporativo</th>
              <th class="px-4 py-3 font-semibold">Sucursal</th>
              <th class="px-4 py-3 font-semibold">Solicitante</th>
              <th class="px-4 py-3 font-semibold">Estatus</th>
              <th class="px-4 py-3 font-semibold text-right">Total</th>
              <th class="px-4 py-3 font-semibold text-right">Acciones</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="row in rows"
              :key="row.id"
              class="border-t border-slate-200/70 dark:border-white/10
                     hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition"
            >
              <td class="px-4 py-3 align-middle">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                  :checked="selectedIds.has(row.id)"
                  @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
                />
              </td>

              <td class="px-4 py-3 min-w-0">
                <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                  {{ row.folio }}
                </div>
                <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">
                  ID: {{ row.id }} · {{ row.fecha_captura ? String(row.fecha_captura).slice(0,10) : '—' }}
                </div>
              </td>

              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                             border-slate-200 bg-slate-50 text-slate-700
                             dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
                  {{ row.tipo === 'ANTICIPO' ? 'Anticipo' : 'Reembolso' }}
                </span>
              </td>

              <td class="px-4 py-3 min-w-0">
                <div class="text-slate-900 dark:text-neutral-100 truncate">
                  {{ row.comprador?.nombre ?? '—' }}
                </div>
              </td>

              <td class="px-4 py-3 min-w-0">
                <div class="text-slate-900 dark:text-neutral-100 truncate">
                  {{ row.sucursal?.nombre ?? '—' }}
                </div>
              </td>

              <td class="px-4 py-3 min-w-0">
                <div class="text-slate-900 dark:text-neutral-100 truncate">
                  {{ row.solicitante?.nombre ?? '—' }}
                </div>
              </td>

              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                      :class="statusPill(row.status)">
                  <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                  <span class="truncate">{{ row.status }}</span>
                </span>
              </td>

              <td class="px-4 py-3 text-right whitespace-nowrap font-extrabold text-slate-900 dark:text-neutral-100">
                {{ money(row.monto_total) }}
              </td>

              <td class="px-4 py-3 whitespace-nowrap text-right">
                <div class="inline-flex gap-2">
                  <button @click="goShow(row.id)" type="button"
                    class="btn border border-slate-200 bg-slate-50 text-slate-900 hover:bg-slate-100
                           dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15">
                    Ver
                  </button>

                  <button @click="printReq(row.id)" type="button"
                    class="btn border border-slate-200 bg-white text-slate-900 hover:bg-slate-50
                           dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15">
                    Imprimir
                  </button>

                  <button v-if="canPay" @click="goPay(row.id)" type="button"
                    class="btn bg-sky-600 text-white hover:bg-sky-700 dark:bg-sky-500 dark:hover:bg-sky-600">
                    Pagar
                  </button>

                  <button v-if="canUploadComprobantes" @click="goComprobar(row.id)" type="button"
                    class="btn bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Comprobar
                  </button>

                  <button
                    v-if="canDelete && !isColaborador"
                    @click="destroyRow(row)"
                    type="button"
                    class="btn border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100
                           dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15"
                    title="Rechazar (no se elimina de BD)"
                  >
                    Rechazar
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="rows.length === 0">
              <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-neutral-400">
                No hay requisiciones con los filtros actuales.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- paginación -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                    border-t border-slate-200/70 dark:border-white/10
                    px-4 py-3 bg-white/90 dark:bg-neutral-900/80">
          <div class="text-xs text-slate-600 dark:text-neutral-300">
            Página <span class="font-semibold">{{ props.requisiciones?.current_page ?? 1 }}</span> de
            <span class="font-semibold">{{ props.requisiciones?.last_page ?? 1 }}</span>
          </div>

          <nav class="flex flex-wrap gap-2 max-w-full">
            <button
              v-for="(link, i) in safePagerLinks"
              :key="`${i}-${link.cleanLabel}`"
              type="button"
              @click="link.url ? goTo(link.url) : null"
              :disabled="!link.url"
              class="rounded-2xl px-3 py-1.5 text-sm font-semibold border transition
                     border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:-translate-y-[1px]
                     disabled:opacity-50 disabled:cursor-not-allowed
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60"
              :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
              v-html="link.label"
            />
          </nav>
        </div>
      </div>

      <!-- Cards (mobile/tablet/desktop <2xl) -->
      <div class="2xl:hidden grid gap-3">
        <transition-group name="list" tag="div" class="grid gap-3">
          <div
            v-for="row in rows"
            :key="row.id"
            class="rounded-3xl border border-slate-200/70 dark:border-white/10
                   bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-start gap-3 min-w-0">
                <input
                  type="checkbox"
                  class="mt-1 h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900 shrink-0"
                  :checked="selectedIds.has(row.id)"
                  @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
                />

                <div class="min-w-0">
                  <div class="flex items-center gap-2 min-w-0">
                    <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                      {{ row.folio }}
                    </div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border shrink-0
                                 border-slate-200 bg-slate-50 text-slate-700
                                 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
                      {{ row.tipo === 'ANTICIPO' ? 'Anticipo' : 'Reembolso' }}
                    </span>
                  </div>

                  <div class="mt-0.5 text-xs text-slate-500 dark:text-neutral-400 truncate">
                    {{ row.comprador?.nombre ?? '—' }} · {{ row.sucursal?.nombre ?? '—' }}
                  </div>
                </div>
              </div>

              <span class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border"
                    :class="statusPill(row.status)">
                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                <span class="truncate">{{ row.status }}</span>
              </span>
            </div>

            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-700 dark:text-neutral-200">
              <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 p-3">
                <div class="opacity-70">Solicitante</div>
                <div class="font-semibold truncate">{{ row.solicitante?.nombre ?? '—' }}</div>
              </div>

              <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 p-3">
                <div class="opacity-70">Total</div>
                <div class="font-extrabold truncate">{{ money(row.monto_total) }}</div>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
              <button @click="goShow(row.id)" type="button"
                class="btn border border-slate-200 bg-slate-50 text-slate-900 hover:bg-slate-100
                       dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15">
                Ver
              </button>

              <button @click="printReq(row.id)" type="button"
                class="btn border border-slate-200 bg-white text-slate-900 hover:bg-slate-50
                       dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15">
                Imprimir
              </button>

              <button v-if="canPay" @click="goPay(row.id)" type="button"
                class="btn bg-sky-600 text-white hover:bg-sky-700 dark:bg-sky-500 dark:hover:bg-sky-600">
                Pagar
              </button>

              <button v-if="canUploadComprobantes" @click="goComprobar(row.id)" type="button"
                class="btn bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                Comprobar
              </button>

              <button
                v-if="canDelete && !isColaborador"
                @click="destroyRow(row)"
                type="button"
                class="col-span-2 btn border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100
                       dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15"
              >
                Rechazar
              </button>
            </div>
          </div>
        </transition-group>
      </div>

    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
:global(html),
:global(body){ max-width:100%; overflow-x:hidden; }

:global(html.dark select option){ background:#0a0a0a; color:#f5f5f5; }

.fadeUp-enter-active,.fadeUp-leave-active{ transition: all 160ms ease; }
.fadeUp-enter-from,.fadeUp-leave-to{ opacity:0; transform: translateY(6px); }

.list-enter-active,.list-leave-active{ transition: all 180ms ease; }
.list-enter-from{ opacity:0; transform: translateY(6px); }
.list-leave-to{ opacity:0; transform: translateY(-6px); }

/* SOLO animación / layout (sin colores) */
.chip{
  border-radius: 9999px;
  padding: .35rem .75rem;
  font-size: .75rem;
  font-weight: 800;
  transition: transform 150ms ease, filter 150ms ease;
}
.chip:hover{ transform: translateY(-1px); filter: brightness(.98); }

.exportBtn{
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .65rem 1rem;
  border-radius: 1rem;
  font-size: .85rem;
  font-weight: 800;
  transition: transform 150ms ease, box-shadow 150ms ease;
}
.exportBtn:hover{ transform: translateY(-1px); }
.exportBtn:active{ transform: scale(.98); }

/* base para botones del listado (sin colores) */
.btn{
  border-radius: 1rem;
  padding: .6rem .9rem;
  font-size: .75rem;
  font-weight: 900;
  transition: transform 150ms ease, filter 150ms ease, box-shadow 150ms ease;
}
.btn:hover{ transform: translateY(-1px); filter: brightness(.99); }
.btn:active{ transform: scale(.98); }
</style>
