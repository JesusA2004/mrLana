<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

import type { AreasPageProps, AreaRow } from './Areas.types'
import { useAreasIndex } from './useAreasIndex'

const props = defineProps<AreasPageProps>()

const {
  // filtros + paginación
  state,
  safeLinks,
  goTo,
  hasActiveFilters,
  clearFilters,
  sortLabel,
  toggleSort,

  // corporativo combobox (filtros)
  corpOpen,
  corpQuery,
  corpButtonRef,
  corporativosFiltered,
  selectedCorp,
  selectCorp,

  // modal
  modalOpen,
  isEdit,
  saving,
  form,
  errors,
  canSubmit,
  openCreate,
  openEdit,
  closeModal,
  submit,
  destroyRow,

  // bulk
  selectedIds,
  selectedCount,
  isAllSelectedOnPage,
  toggleRow,
  toggleAllOnPage,
  clearSelection,
  destroySelected,
} = useAreasIndex(props)

/** Agrupa por corporativo (solo page actual) */
const grouped = computed(() => {
  const map = new Map<string, { key: string; label: string; corporativoId: number | null; rows: AreaRow[] }>()
  for (const r of props.areas.data ?? []) {
    const corpName = r.corporativo?.nombre ?? 'Sin corporativo'
    const corpId = (r.corporativo_id ?? null) as number | null
    const key = `${corpId ?? 'null'}__${corpName}`

    if (!map.has(key)) map.set(key, { key, label: corpName, corporativoId: corpId, rows: [] })
    map.get(key)!.rows.push(r)
  }

  return Array.from(map.values()).sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

function statusPill(active: boolean) {
  return active
    ? 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
    : 'bg-slate-500/10 text-slate-200 border-white/10'
}
</script>

<template>
  <Head title="Áreas" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Áreas</h2>
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
            <h1 class="text-base font-bold text-slate-900 dark:text-neutral-100 truncate">
              Administra áreas por corporativo
            </h1>
            <p class="mt-0.5 text-sm text-slate-600 dark:text-neutral-300">
              Filtros en tiempo real, orden A-Z/Z-A y eliminación masiva.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <button
              type="button"
              @click="openCreate"
              class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                     bg-slate-900 text-white hover:bg-slate-800
                     dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                     transition active:scale-[0.98] w-full sm:w-auto"
            >
              Nueva área
            </button>
          </div>
        </div>

        <!-- Barra bulk (solo si hay selección) -->
        <div
          v-if="selectedCount > 0"
          class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-slate-50 dark:bg-white/5 px-4 py-3"
        >
          <div class="text-sm text-slate-700 dark:text-neutral-200">
            Seleccionadas: <span class="font-extrabold">{{ selectedCount }}</span>
          </div>

          <div class="flex flex-col sm:flex-row gap-2">
            <button
              type="button"
              @click="clearSelection"
              class="rounded-xl px-3 py-2 text-sm font-semibold
                     bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                     dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-white/10
                     transition active:scale-[0.98]"
            >
              Limpiar
            </button>

            <button
              type="button"
              @click="destroySelected"
              class="rounded-xl px-3 py-2 text-sm font-extrabold
                     bg-rose-600 text-white hover:bg-rose-500
                     transition active:scale-[0.98]"
            >
              Eliminar seleccionadas
            </button>
          </div>
        </div>

        <!-- Filtros -->
        <div
          class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
        >
          <!-- búsqueda -->
          <div class="lg:col-span-6 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
            <input
              v-model="state.q"
              type="text"
              placeholder="Nombre del área..."
              class="mt-1 w-full rounded-xl px-3 py-2 text-sm
                     border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                     focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10
                     transition"
            />
          </div>

          <!-- corporativo combobox -->
          <div class="lg:col-span-3 min-w-0 relative">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Corporativo</label>

            <button
              ref="corpButtonRef"
              type="button"
              @click="corpOpen = !corpOpen"
              class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                     px-3 py-2 text-left flex items-center justify-between gap-2
                     hover:bg-slate-50
                     focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5 dark:focus:ring-white/10
                     transition"
            >
              <span class="truncate">
                <template v-if="selectedCorp">
                  {{ selectedCorp.nombre }}<span v-if="selectedCorp.codigo"> ({{ selectedCorp.codigo }})</span>
                </template>
                <template v-else>Todos</template>
              </span>

              <svg class="h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                  fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>

            <div
              v-if="corpOpen"
              id="corp-dropdown-panel"
              class="absolute z-40 mt-2 w-full overflow-hidden rounded-2xl
                     border border-slate-200/70 bg-white shadow-2xl
                     dark:border-white/10 dark:bg-neutral-950"
            >
              <div class="p-3 border-b border-slate-200/70 dark:border-white/10">
                <input
                  v-model="corpQuery"
                  type="text"
                  placeholder="Buscar corporativo..."
                  class="w-full rounded-xl px-3 py-2 text-sm
                         border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                         focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                         dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
                />
              </div>

              <div class="max-h-64 overflow-auto p-2">
                <button
                  type="button"
                  @click="selectCorp('')"
                  class="w-full text-left px-3 py-2 rounded-xl text-sm font-semibold
                         text-slate-800 hover:bg-slate-50
                         dark:text-neutral-100 dark:hover:bg-white/5 transition"
                >
                  Todos
                </button>

                <button
                  v-for="c in corporativosFiltered"
                  :key="c.id"
                  type="button"
                  @click="selectCorp(c.id)"
                  class="w-full text-left px-3 py-2 rounded-xl text-sm
                         text-slate-800 hover:bg-slate-50
                         dark:text-neutral-100 dark:hover:bg-white/5 transition"
                  :class="Number(state.corporativo_id) === c.id ? 'bg-slate-100 dark:bg-white/10 font-semibold' : ''"
                >
                  {{ c.nombre }}<span v-if="c.codigo"> ({{ c.codigo }})</span>
                </button>

                <div v-if="corporativosFiltered.length === 0" class="px-3 py-3 text-sm text-slate-500 dark:text-neutral-400">
                  Sin resultados.
                </div>
              </div>
            </div>
          </div>

          <!-- estatus -->
          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select
              v-model="state.activo"
              class="mt-1 w-full rounded-xl px-3 py-2 text-sm
                     border border-slate-200 bg-white text-slate-900
                     focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10
                     transition"
            >
              <option value="">Todos</option>
              <option value="1">Activas</option>
              <option value="0">Inactivas</option>
            </select>
          </div>

          <!-- per page -->
          <div class="lg:col-span-1 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
            <select
              v-model="state.perPage"
              class="mt-1 w-full rounded-xl px-3 py-2 text-sm
                     border border-slate-200 bg-white text-slate-900
                     focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                     dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10
                     transition"
            >
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <!-- footer filtros -->
          <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Mostrando
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.areas.from ?? 0 }}</span>
              a
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.areas.to ?? 0 }}</span>
              de
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.areas.total }}</span>
            </div>

            <SecondaryButton
              type="button"
              @click="clearFilters"
              :disabled="!hasActiveFilters"
              class="rounded-xl disabled:opacity-50"
            >
              Limpiar
            </SecondaryButton>
          </div>
        </div>

        <!-- TABLA (PC) -->
        <div
          class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm max-w-full"
        >
          <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
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

                  <!-- AQUÍ va el sort: pegado a "Área" -->
                  <th class="px-4 py-3 font-semibold">
                    <div class="inline-flex items-center gap-2">
                      <span>Área</span>
                      <button
                        type="button"
                        @click="toggleSort"
                        class="rounded-lg border px-2 py-1 text-xs font-extrabold
                               border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                               dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                               transition active:scale-[0.98]"
                        :title="`Ordenar ${sortLabel}`"
                      >
                        {{ sortLabel }}
                      </button>
                    </div>
                  </th>

                  <th class="px-4 py-3 font-semibold">Corporativo</th>
                  <th class="px-4 py-3 font-semibold">Estatus</th>
                  <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <template v-for="g in grouped" :key="g.key">
                  <!-- header grupo -->
                  <tr class="border-t border-slate-200/70 dark:border-white/10">
                    <td colspan="5" class="px-4 py-3">
                      <div class="flex items-center justify-between">
                        <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">
                          {{ g.label }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-neutral-400">
                          {{ g.rows.length }} área(s)
                        </div>
                      </div>
                    </td>
                  </tr>

                  <tr
                    v-for="row in g.rows"
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

                    <td class="px-4 py-3">
                      <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                        {{ row.nombre }}
                      </div>
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                      {{ row.corporativo?.nombre ?? '—' }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                      <span
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                        :class="statusPill(!!row.activo)"
                      >
                        <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-400' : 'bg-slate-400'" />
                        {{ row.activo ? 'Activa' : 'Inactiva' }}
                      </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-right">
                      <div class="inline-flex gap-2">
                        <button
                          type="button"
                          class="rounded-xl px-3 py-2 text-xs font-extrabold
                                 border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                                 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                                 transition active:scale-[0.98]"
                          @click="openEdit(row)"
                        >
                          Editar
                        </button>

                        <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-if="props.areas.data.length === 0">
                  <td colspan="5" class="px-4 py-12 text-center text-slate-500 dark:text-neutral-400">
                    No hay áreas con los filtros actuales.
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
              Página <span class="font-semibold">{{ props.areas.current_page }}</span> de
              <span class="font-semibold">{{ props.areas.last_page }}</span>
            </div>

            <nav class="flex flex-wrap gap-2">
              <button
                v-for="(link, i) in safeLinks"
                :key="i"
                type="button"
                @click="goTo(link.url)"
                :disabled="!link.url"
                class="rounded-xl px-3 py-1.5 text-sm font-semibold border transition
                       border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                       disabled:opacity-50 disabled:cursor-not-allowed
                       dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5"
                :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
              >
                {{ link.label }}
              </button>
            </nav>
          </div>
        </div>

        <!-- CARDS (móvil/tablet) -->
        <div class="lg:hidden grid gap-3 max-w-full">
          <template v-for="g in grouped" :key="g.key">
            <div
              class="rounded-2xl border border-slate-200/70 dark:border-white/10
                     bg-white dark:bg-neutral-900 shadow-sm px-4 py-3"
            >
              <div class="flex items-center justify-between gap-2">
                <div class="font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ g.label }}
                </div>

                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="toggleSort"
                    class="rounded-lg border px-2 py-1 text-xs font-extrabold
                           border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                           dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                           transition active:scale-[0.98]"
                  >
                    {{ sortLabel }}
                  </button>

                  <div class="text-xs text-slate-500 dark:text-neutral-400">
                    {{ g.rows.length }} área(s)
                  </div>
                </div>
              </div>
            </div>

            <div
              v-for="row in g.rows"
              :key="row.id"
              class="w-full max-w-full min-w-0 overflow-hidden
                     rounded-2xl border border-slate-200/70 dark:border-white/10
                     bg-white dark:bg-neutral-900 shadow-sm p-4
                     hover:shadow-md transition"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                  <input
                    type="checkbox"
                    class="mt-1 h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                    :checked="selectedIds.has(row.id)"
                    @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
                  />

                  <div class="min-w-0">
                    <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">{{ row.nombre }}</div>
                    <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">
                      {{ row.corporativo?.nombre ?? '—' }}
                    </div>
                  </div>
                </div>

                <span
                  class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border"
                  :class="statusPill(!!row.activo)"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-400' : 'bg-slate-400'" />
                  {{ row.activo ? 'Activa' : 'Inactiva' }}
                </span>
              </div>

              <div class="mt-4 grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="rounded-xl px-3 py-2 text-xs font-extrabold
                         border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                         dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                         transition active:scale-[0.98]"
                  @click="openEdit(row)"
                >
                  Editar
                </button>

                <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
              </div>
            </div>
          </template>

          <div
            v-if="props.areas.data.length === 0"
            class="rounded-2xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400"
          >
            No hay áreas con los filtros actuales.
          </div>

          <!-- paginación mobile -->
          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4 overflow-hidden">
            <div class="flex items-center justify-between mb-3 gap-2">
              <div class="text-xs text-slate-600 dark:text-neutral-300">
                <span class="font-semibold">{{ props.areas.from ?? 0 }}</span> -
                <span class="font-semibold">{{ props.areas.to ?? 0 }}</span> /
                <span class="font-semibold">{{ props.areas.total }}</span>
              </div>

              <button
                type="button"
                @click="toggleSort"
                class="rounded-xl px-3 py-2 text-xs font-extrabold border
                       border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                       dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                       transition active:scale-[0.98]"
              >
                {{ sortLabel }}
              </button>
            </div>

            <div class="flex flex-wrap gap-2 max-w-full">
              <button
                v-for="(link, i) in safeLinks"
                :key="i"
                type="button"
                @click="goTo(link.url)"
                :disabled="!link.url"
                class="rounded-xl px-3 py-2 text-xs font-semibold border
                       disabled:opacity-50 disabled:cursor-not-allowed
                       transition active:scale-[0.98]"
                :class="
                  link.active
                    ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'
                "
              >
                {{ link.label }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Create/Edit -->
    <Modal :show="modalOpen" maxWidth="3xl" @close="closeModal">
      <div class="p-6 sm:p-7">
        <div class="rounded-3xl border border-slate-200/60 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-2xl">
          <div class="p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <h3 class="text-xl font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ isEdit ? 'Editar área' : 'Nueva área' }}
                </h3>
                <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                  Captura datos sin romper el layout.
                </p>
              </div>

              <button
                type="button"
                class="rounded-full px-4 py-2 text-sm font-semibold
                       border border-slate-200 bg-white hover:bg-slate-50
                       dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 dark:text-neutral-100
                       transition active:scale-[0.98]"
                @click="closeModal"
              >
                Cerrar
              </button>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Corporativo -->
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Corporativo</label>
                <select
                  v-model.number="form.corporativo_id"
                  class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                         border border-slate-200 bg-white text-slate-900
                         focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                         dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10
                         transition"
                >
                  <option :value="null">Sin corporativo</option>
                  <option v-for="c in props.corporativos" :key="c.id" :value="c.id">
                    {{ c.nombre }}<span v-if="c.codigo"> ({{ c.codigo }})</span>
                  </option>
                </select>
              </div>

              <!-- Nombre -->
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre *</label>
                <input
                  v-model="form.nombre"
                  type="text"
                  placeholder="Ej. Recursos Humanos"
                  class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                         border border-slate-200 bg-white text-slate-900 placeholder:text-slate-400
                         focus:outline-none focus:ring-2 focus:ring-slate-200 focus:border-slate-300
                         dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10
                         transition"
                />
                <p v-if="errors.nombre" class="mt-1 text-xs text-rose-500">{{ errors.nombre }}</p>
              </div>

              <!-- Activo -->
              <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                <input
                  id="area-activo"
                  type="checkbox"
                  v-model="form.activo"
                  class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                />
                <label for="area-activo" class="text-sm font-semibold text-slate-800 dark:text-neutral-100">
                  Área activa
                </label>
              </div>
            </div>

            <div class="mt-7 flex flex-col sm:flex-row gap-3 sm:justify-end">
              <SecondaryButton class="rounded-2xl" @click="closeModal">Cancelar</SecondaryButton>

              <button
                type="button"
                @click="submit"
                :disabled="!canSubmit"
                class="rounded-2xl px-6 py-3 text-sm font-extrabold tracking-wide
                       bg-slate-900 text-white hover:bg-slate-800
                       dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                       disabled:opacity-50 disabled:cursor-not-allowed
                       transition active:scale-[0.98]"
              >
                {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>
