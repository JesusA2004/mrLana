<script setup lang="ts">
import { computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SecondaryButton from '@/Components/SecondaryButton.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue'

import type { RequisicionesPageProps } from './Requisiciones.types'
import { useRequisicionesIndex } from './useRequisicionesIndex'

const props = defineProps<RequisicionesPageProps>()

const page = usePage<any>()
const userRole = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
const canDelete = computed(() => userRole.value === 'ADMIN' || userRole.value === 'CONTADOR')
const canPay = computed(() => userRole.value === 'CONTADOR')
const canUploadComprobantes = computed(() => ['ADMIN', 'CONTADOR', 'COLABORADOR'].includes(userRole.value))

const {
  state,
  safeLinks,
  rows,
  goTo,
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

  setTab,
} = useRequisicionesIndex(props)

const tabs = computed(() => [
  { key: 'PENDIENTES', label: 'Pendientes', count: props.counts?.pendientes ?? 0 },
  { key: 'APROBADAS', label: 'Aprobadas', count: props.counts?.aprobadas ?? 0 },
  { key: 'RECHAZADAS', label: 'Rechazadas', count: props.counts?.rechazadas ?? 0 },
  { key: 'TODAS', label: 'Todas', count: props.counts?.todas ?? 0 },
])

const corporativosActive = computed(() => (props.catalogos?.corporativos ?? []).filter((c: any) => c.activo !== false))
const sucursalesActive = computed(() => (props.catalogos?.sucursales ?? []).filter((s: any) => s.activo !== false))
const empleadosActive = computed(() => (props.catalogos?.empleados ?? []).filter((e: any) => e.activo !== false))

function money(v: any) {
  const n = Number(v ?? 0)
  try {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
  } catch {
    return String(v ?? '')
  }
}

function statusPill(status: string) {
  const s = String(status || '').toUpperCase()
  if (s === 'ACEPTADA') return 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
  if (s === 'PAGADA') return 'bg-sky-500/10 text-sky-200 border-sky-500/20'
  if (s === 'COMPROBADA') return 'bg-indigo-500/10 text-indigo-200 border-indigo-500/20'
  if (s === 'POR_COMPROBAR') return 'bg-amber-500/10 text-amber-200 border-amber-500/20'
  if (s === 'CAPTURADA') return 'bg-slate-500/10 text-slate-200 border-white/10'
  if (s === 'RECHAZADA') return 'bg-rose-500/10 text-rose-200 border-rose-500/20'
  if (s === 'BORRADOR') return 'bg-zinc-500/10 text-zinc-200 border-white/10'
  return 'bg-slate-500/10 text-slate-200 border-white/10'
}

function goShow(id: number) {
  router.visit(route('requisiciones.show', id))
}

function goCreate() {
  router.visit(route('requisiciones.create'))
}

function goPay(id: number) {
  router.visit(route('requisiciones.pagar', id))
}

function goComprobar(id: number) {
  router.visit(route('requisiciones.comprobar', id))
}

function printReq(id: number) {
  // Ideal: endpoint que entrega el PDF final (ya con logo del corporativo del usuario/requisición)
  const url = route('requisiciones.print', id)
  const w = window.open(url, '_blank', 'noopener,noreferrer')
  // UX: algunos navegadores no permiten auto-print; si sí, aquí podrías disparar w?.print() al cargar
  w?.focus()
}

function destroyRow(row: any) {
  if (!canDelete.value) return
  if (!confirm(`¿Eliminar requisición ${row.folio}? Esta acción no se puede deshacer.`)) return
  router.delete(route('requisiciones.destroy', row.id), { preserveScroll: true })
}

const selectBase =
  'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10'

const inputBase =
  'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'
</script>

<template>
  <Head title="Requisiciones" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Requisiciones</h2>
    </template>

    <div class="w-full max-w-full min-w-0 overflow-x-hidden">
      <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Header -->
        <div
          class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm px-4 py-4"
        >
          <div class="min-w-0">
            <h1 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 truncate">
              Control y seguimiento de requisiciones
            </h1>
            <p class="mt-1 text-xs text-slate-600 dark:text-neutral-300 truncate">
              Operación con gobierno: filtros, trazabilidad, PDFs y acciones por rol.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0 w-full sm:w-auto">
            <div
              v-if="selectedCount > 0 && canDelete"
              class="flex flex-wrap items-center gap-2
                     rounded-2xl border border-slate-200/70 dark:border-white/10
                     bg-slate-50 dark:bg-neutral-950/40 px-3 py-2"
            >
              <div class="text-xs text-slate-700 dark:text-neutral-200">
                Seleccionados: <span class="font-semibold">{{ selectedCount }}</span>
              </div>

              <button
                type="button"
                @click="clearSelection"
                class="rounded-xl px-3 py-1.5 text-xs font-semibold
                       bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                       dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                       transition active:scale-[0.98]"
              >
                Limpiar
              </button>

              <button
                type="button"
                @click="destroySelected"
                class="rounded-xl px-3 py-1.5 text-xs font-semibold
                       bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                       dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                       transition active:scale-[0.98]"
              >
                Eliminar
              </button>
            </div>

            <button
              type="button"
              @click="goCreate"
              class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                     bg-emerald-600 text-white hover:bg-emerald-700
                     dark:bg-emerald-500 dark:hover:bg-emerald-600
                     transition active:scale-[0.98] w-full sm:w-auto"
            >
              Nueva requisición
            </button>
          </div>
        </div>

        <!-- Tabs -->
        <div
          class="mb-4 flex flex-wrap items-center gap-2
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm px-3 py-3"
        >
          <button
            v-for="t in tabs"
            :key="t.key"
            type="button"
            @click="setTab(t.key as any)"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold border transition"
            :class="
              state.tab === t.key
                ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'
            "
          >
            <span>{{ t.label }}</span>
            <span
              class="inline-flex items-center justify-center min-w-[26px] h-6 px-2 rounded-full text-xs font-extrabold"
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

        <!-- Filtros -->
        <div
          class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-4"
        >
          <div class="lg:col-span-4 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
            <input v-model="state.q" type="text" placeholder="Folio, proveedor, observaciones..." :class="inputBase" />
          </div>

          <div class="lg:col-span-3 min-w-0">
            <SearchableSelect
              v-model="state.comprador_corp_id"
              :options="corporativosActive"
              label="Corporativo"
              placeholder="Todos"
              searchPlaceholder="Buscar corporativo..."
              :allowNull="true"
              nullLabel="Todos"
              rounded="xl"
              zIndexClass="z-30"
            />
          </div>

          <div class="lg:col-span-3 min-w-0">
            <SearchableSelect
              v-model="state.sucursal_id"
              :options="sucursalesActive"
              label="Sucursal"
              placeholder="Todas"
              searchPlaceholder="Buscar sucursal..."
              :allowNull="true"
              nullLabel="Todas"
              rounded="xl"
              zIndexClass="z-30"
              labelKey="nombre"
              secondaryKey="codigo"
            />
          </div>

          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo</label>
            <select v-model="state.tipo" :class="selectBase">
              <option value="">Todos</option>
              <option value="ANTICIPO">Anticipo</option>
              <option value="REEMBOLSO">Reembolso</option>
            </select>
          </div>

          <div class="lg:col-span-3 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select v-model="state.status" :class="selectBase">
              <option value="">Todos</option>
              <option value="BORRADOR">BORRADOR</option>
              <option value="CAPTURADA">CAPTURADA</option>
              <option value="ACEPTADA">ACEPTADA</option>
              <option value="RECHAZADA">RECHAZADA</option>
              <option value="PAGADA">PAGADA</option>
              <option value="POR_COMPROBAR">POR_COMPROBAR</option>
              <option value="COMPROBADA">COMPROBADA</option>
            </select>
          </div>

          <div class="lg:col-span-4 min-w-0">
            <SearchableSelect
              v-model="state.solicitante_id"
              :options="empleadosActive"
              label="Solicitante"
              placeholder="Todos"
              searchPlaceholder="Buscar empleado..."
              :allowNull="true"
              nullLabel="Todos"
              rounded="xl"
              zIndexClass="z-30"
              labelKey="nombre"
              secondaryKey="puesto"
            />
          </div>

          <div class="lg:col-span-3 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fechas (captura)</label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model="state.fecha_from" type="date" :class="inputBase" />
              <input v-model="state.fecha_to" type="date" :class="inputBase" />
            </div>
          </div>

          <div class="lg:col-span-1 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
            <select v-model.number="state.perPage" :class="selectBase">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <div class="lg:col-span-1 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Orden</label>
            <button
              type="button"
              @click="toggleSort"
              class="mt-1 w-full rounded-xl px-3 py-2 text-sm font-extrabold
                     border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                     transition active:scale-[0.98]"
              :title="`Cambiar orden (${sortLabel})`"
            >
              {{ sortLabel }}
            </button>
          </div>

          <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-w-0">
            <div class="text-sm text-slate-600 dark:text-neutral-300 truncate">
              Mostrando
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.requisiciones.from ?? 0 }}</span>
              a
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.requisiciones.to ?? 0 }}</span>
              de
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.requisiciones.total ?? 0 }}</span>
            </div>

            <SecondaryButton
              type="button"
              @click="clearFilters"
              :disabled="!hasActiveFilters"
              class="rounded-xl disabled:opacity-50 shrink-0"
            >
              Limpiar
            </SecondaryButton>
          </div>
        </div>

        <!-- TABLA DESKTOP -->
        <div
          class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm"
        >
          <div class="w-full max-w-full min-w-0 overflow-x-auto">
            <table class="w-full min-w-[1200px] text-sm">
              <thead class="bg-slate-50 dark:bg-neutral-950/60">
                <tr class="text-left text-slate-600 dark:text-neutral-300">
                  <th class="px-4 py-3 font-semibold w-[46px]">
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
                    <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate max-w-[220px]">
                      {{ row.folio }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-neutral-400">
                      ID: {{ row.id }} · {{ row.fecha_captura ? String(row.fecha_captura).slice(0, 10) : '—' }}
                    </div>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">
                    <span
                      class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                             border-slate-200 bg-slate-50 text-slate-700
                             dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100"
                    >
                      {{ row.tipo === 'ANTICIPO' ? 'Anticipo' : 'Reembolso' }}
                    </span>
                  </td>

                  <td class="px-4 py-3">
                    <div class="text-slate-900 dark:text-neutral-100 truncate max-w-[220px]">
                      {{ row.comprador?.nombre ?? '—' }}
                    </div>
                  </td>

                  <td class="px-4 py-3">
                    <div class="text-slate-900 dark:text-neutral-100 truncate max-w-[180px]">
                      {{ row.sucursal?.nombre ?? '—' }}
                    </div>
                  </td>

                  <td class="px-4 py-3">
                    <div class="text-slate-900 dark:text-neutral-100 truncate max-w-[220px]">
                      {{ row.solicitante?.nombre ?? '—' }}
                    </div>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                          :class="statusPill(row.status)">
                      <span class="h-1.5 w-1.5 rounded-full bg-white/50"></span>
                      {{ row.status }}
                    </span>
                  </td>

                  <td class="px-4 py-3 text-right whitespace-nowrap font-extrabold text-slate-900 dark:text-neutral-100">
                    {{ money(row.monto_total) }}
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="inline-flex gap-2">
                      <button
                        type="button"
                        @click="goShow(row.id)"
                        class="rounded-xl px-3 py-2 text-xs font-semibold
                               bg-slate-100 text-slate-800 hover:bg-slate-200
                               dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                               transition active:scale-[0.98]"
                      >
                        Ver
                      </button>

                      <button
                        type="button"
                        @click="printReq(row.id)"
                        class="rounded-xl px-3 py-2 text-xs font-semibold
                               bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                               dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-white/5
                               transition active:scale-[0.98]"
                        title="Imprimir PDF"
                      >
                        Imprimir
                      </button>

                      <button
                        v-if="canPay"
                        type="button"
                        @click="goPay(row.id)"
                        class="rounded-xl px-3 py-2 text-xs font-semibold
                               bg-sky-600 text-white hover:bg-sky-700
                               transition active:scale-[0.98]"
                      >
                        Pagar
                      </button>

                      <button
                        v-if="canUploadComprobantes"
                        type="button"
                        @click="goComprobar(row.id)"
                        class="rounded-xl px-3 py-2 text-xs font-semibold
                               bg-indigo-600 text-white hover:bg-indigo-700
                               transition active:scale-[0.98]"
                      >
                        Comprobar
                      </button>

                      <button
                        v-if="canDelete"
                        type="button"
                        @click="destroyRow(row)"
                        class="rounded-xl px-3 py-2 text-xs font-semibold
                               bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                               dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                               transition active:scale-[0.98]"
                      >
                        Eliminar
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
          </div>

          <!-- Paginación -->
          <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                   border-t border-slate-200/70 dark:border-white/10
                   px-4 py-3 bg-white dark:bg-neutral-900"
          >
            <div class="text-xs text-slate-600 dark:text-neutral-300">
              Página <span class="font-semibold">{{ props.requisiciones.current_page ?? 1 }}</span> de
              <span class="font-semibold">{{ props.requisiciones.last_page ?? 1 }}</span>
            </div>

            <nav class="flex flex-wrap gap-2 max-w-full">
              <button
                v-for="(link, i) in safeLinks"
                :key="`${i}-${link?.label ?? 'link'}`"
                type="button"
                @click="link?.url ? goTo(link.url) : null"
                :disabled="!link?.url"
                class="rounded-xl px-3 py-1.5 text-sm font-semibold border transition
                       border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                       disabled:opacity-50 disabled:cursor-not-allowed
                       dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5"
                :class="link?.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
                v-html="link?.label ?? ''"
              />
            </nav>
          </div>
        </div>

        <!-- CARDS MÓVIL/TABLET -->
        <div class="lg:hidden grid gap-3">
          <div
            v-for="row in rows"
            :key="row.id"
            class="w-full overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-4"
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
                  <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                    {{ row.folio }}
                  </div>
                  <div class="mt-0.5 text-xs text-slate-500 dark:text-neutral-400 truncate">
                    {{ row.comprador?.nombre ?? '—' }} · {{ row.sucursal?.nombre ?? '—' }}
                  </div>
                </div>
              </div>

              <span
                class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border"
                :class="statusPill(row.status)"
              >
                <span class="h-1.5 w-1.5 rounded-full bg-white/50"></span>
                {{ row.status }}
              </span>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-700 dark:text-neutral-200">
              <div class="rounded-xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-3">
                <div class="opacity-70">Solicitante</div>
                <div class="font-semibold truncate">{{ row.solicitante?.nombre ?? '—' }}</div>
              </div>
              <div class="rounded-xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-3">
                <div class="opacity-70">Total</div>
                <div class="font-extrabold truncate">{{ money(row.monto_total) }}</div>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
              <button
                type="button"
                @click="goShow(row.id)"
                class="rounded-xl px-3 py-2 text-xs font-semibold
                       bg-slate-100 text-slate-800 hover:bg-slate-200
                       dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                       transition active:scale-[0.98]"
              >
                Ver
              </button>

              <button
                type="button"
                @click="printReq(row.id)"
                class="rounded-xl px-3 py-2 text-xs font-semibold
                       bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                       dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-white/5
                       transition active:scale-[0.98]"
              >
                Imprimir
              </button>

              <button
                v-if="canPay"
                type="button"
                @click="goPay(row.id)"
                class="rounded-xl px-3 py-2 text-xs font-semibold
                       bg-sky-600 text-white hover:bg-sky-700
                       transition active:scale-[0.98]"
              >
                Pagar
              </button>

              <button
                v-if="canUploadComprobantes"
                type="button"
                @click="goComprobar(row.id)"
                class="rounded-xl px-3 py-2 text-xs font-semibold
                       bg-indigo-600 text-white hover:bg-indigo-700
                       transition active:scale-[0.98]"
              >
                Comprobar
              </button>

              <button
                v-if="canDelete"
                type="button"
                @click="destroyRow(row)"
                class="col-span-2 rounded-xl px-3 py-2 text-xs font-semibold
                       bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                       dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                       transition active:scale-[0.98]"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
:global(html.dark select option) {
  background: #0a0a0a;
  color: #f5f5f5;
}
</style>
