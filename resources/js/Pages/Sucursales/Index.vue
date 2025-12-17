<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

import AppInput from '@/Components/ui/AppInput.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Modal from '@/Components/Modal.vue'

import type { SucursalesPageProps } from './Sucursales.types'
import { useSucursalesIndex } from './useSucursalesIndex'

const props = defineProps<SucursalesPageProps>()

const {
  state,
  safeLinks,
  goTo,
  hasActiveFilters,
  clearFilters,
  sortLabel,
  toggleSort,

  corpOpen,
  corpQuery,
  corpButtonRef,
  corporativosFiltered,
  selectedCorp,
  selectCorp,

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

  selectedIds,
  selectedCount,
  isAllSelectedOnPage,
  toggleRow,
  toggleAllOnPage,
  clearSelection,
  destroySelected,

  modalCorpOpen,
  modalCorpQuery,
  modalCorpButtonRef,
  modalCorporativosFiltered,
  selectedCorpModal,
  openModalCorp,
  selectCorpModal,
} = useSucursalesIndex(props)

/**
 * Lista base para búsquedas foráneas (otras tablas).
 * - Mantenemos solo activos (si `activo` viene false, se excluye).
 * - El componente hace la búsqueda (query) internamente.
 */
const corporativosActive = computed(() => (props.corporativos ?? []).filter((c) => c.activo !== false))
</script>

<template>
  <Head title="Sucursales" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Sucursales</h2>
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
            <h1 class="text-l font-bold text-slate-900 dark:text-neutral-100 truncate">
              Administra sucursales por corporativo
            </h1>
          </div>

          <!-- Barra bulk (solo si hay selección) -->
          <div
            v-if="selectedCount > 0"
            class="flex flex-wrap items-center gap-2
                   rounded-2xl border border-slate-200/70 dark:border-white/10
                   bg-slate-50 dark:bg-white/5 px-3 py-2"
          >
            <div class="text-xs text-slate-700 dark:text-neutral-200">
              Seleccionadas: <span class="font-semibold">{{ selectedCount }}</span>
            </div>

            <button
              type="button"
              @click="clearSelection"
              class="rounded-xl px-3 py-1.5 text-xs font-semibold
                     bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                     dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-white/10
                     transition active:scale-[0.98]"
            >
              Limpiar
            </button>

            <button
              type="button"
              @click="destroySelected"
              class="rounded-xl px-3 py-1.5 text-xs font-semibold
                     bg-rose-600 text-white hover:bg-rose-500
                     transition active:scale-[0.98]"
            >
              Eliminar seleccionadas
            </button>
          </div>

          <button
            type="button"
            @click="openCreate"
            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                   bg-slate-900 text-white hover:bg-slate-800
                   dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                   transition active:scale-[0.98] w-full sm:w-auto"
          >
            Nueva sucursal
          </button>
        </div>

        <!-- Filtros -->
        <div
          class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
        >
          <div class="lg:col-span-6 min-w-0">
            <AppInput v-model="state.q" label="Búsqueda" placeholder="Nombre, código, ciudad, estado, dirección..." />
          </div>

          <!-- Combobox corporativos (SearchableSelect) -->
          <div class="lg:col-span-3 min-w-0">
            <SearchableSelect
              v-model="state.corporativo_id"
              :options="corporativosActive"
              label="Corporativo"
              placeholder="Todos"
              searchPlaceholder="Buscar corporativo por nombre o código..."
              :allowNull="true"
              nullLabel="Todos"
              rounded="xl"
              zIndexClass="z-30"
            />
          </div>

          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select
              v-model="state.activo"
              class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                     focus:border-slate-400 focus:ring-0
                     dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100"
            >
              <option value="">Todos</option>
              <option value="1">Activas</option>
              <option value="0">Inactivas</option>
            </select>
          </div>

          <div class="lg:col-span-1 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
            <select
              v-model="state.perPage"
              class="mt-1 w-full min-w-0 rounded-xl border border-slate-200 bg-white text-slate-900
                     focus:border-slate-400 focus:ring-0
                     dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100">
              <option :value="10">10</option>
              <option :value="15">15</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>

          <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Mostrando
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.sucursales.from ?? 0 }}</span>
              a
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.sucursales.to ?? 0 }}</span>
              registros de
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.sucursales.total }} registros</span>
            </div>

            <SecondaryButton
              type="button"
              @click="clearFilters"
              :disabled="!hasActiveFilters"
              class="rounded-xl disabled:opacity-50 w-full sm:w-auto">
              Limpiar
            </SecondaryButton>
          </div>
        </div>

        <!-- TABLA -->
        <div
          class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm max-w-full">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
              <thead class="bg-slate-50 dark:bg-neutral-950/60">
                <tr class="text-left text-slate-600 dark:text-neutral-300">
                  <th class="px-4 py-3 font-semibold w-[46px]">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                      :checked="isAllSelectedOnPage"
                      @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"/>
                  </th>

                  <th class="px-4 py-3 font-semibold">
                    <div class="inline-flex items-center gap-2">
                      <span>Sucursal</span>
                      <button
                        type="button"
                        @click="toggleSort"
                        class="rounded-lg border px-2 py-1 text-xs font-bold
                               border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                               dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-white/5
                               transition"
                        :title="`Ordenar ${sortLabel}`">
                        {{ sortLabel }}
                      </button>
                    </div>
                  </th>

                  <th class="px-4 py-3 font-semibold">Corporativo</th>
                  <th class="px-4 py-3 font-semibold">Ubicación</th>
                  <th class="px-4 py-3 font-semibold">Dirección</th>
                  <th class="px-4 py-3 font-semibold">Estatus</th>
                  <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="row in props.sucursales.data"
                  :key="row.id"
                  class="border-t border-slate-200/70 dark:border-white/10
                         hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition">
                  <td class="px-4 py-3 align-middle">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                      :checked="selectedIds.has(row.id)"
                      @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"/>
                  </td>

                  <td class="px-4 py-3">
                    <div class="min-w-0">
                      <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                        {{ row.nombre }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">
                        {{ row.codigo ? `Código: ${row.codigo}` : 'Sin código' }}
                      </div>
                    </div>
                  </td>

                  <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                    {{ row.corporativo?.nombre ?? '—' }}
                  </td>

                  <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                    {{ row.ciudad ?? '—' }}, {{ row.estado ?? '—' }}
                  </td>

                  <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                    <span class="block truncate max-w-[520px]">{{ row.direccion ?? 'Sin dirección' }}</span>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">
                    <span
                      class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border
                             bg-slate-100 text-slate-700 border-slate-200
                             dark:bg-white/5 dark:text-neutral-200 dark:border-white/10">
                      <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'" />
                      {{ row.activo ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap text-right">
                    <div class="inline-flex gap-2">
                      <SecondaryButton class="rounded-xl" @click="openEdit(row)">Editar</SecondaryButton>
                      <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
                    </div>
                  </td>
                </tr>

                <tr v-if="props.sucursales.data.length === 0">
                  <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">
                    No hay sucursales con los filtros actuales.
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
              Página <span class="font-semibold">{{ props.sucursales.current_page }}</span> de
              <span class="font-semibold">{{ props.sucursales.last_page }}</span>
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
          <div
            v-for="row in props.sucursales.data"
            :key="row.id"
            class="w-full max-w-full min-w-0 overflow-hidden
                   rounded-2xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">{{ row.nombre }}</div>
                <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">
                  {{ row.corporativo?.nombre ?? '—' }}
                </div>
              </div>

              <span
                class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border
                       bg-slate-100 text-slate-700 border-slate-200
                       dark:bg-white/5 dark:text-neutral-200 dark:border-white/10"
              >
                <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'" />
                {{ row.activo ? 'Activa' : 'Inactiva' }}
              </span>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-700 dark:text-neutral-200">
              <div class="min-w-0"><span class="font-semibold">Código:</span> {{ row.codigo ?? '—' }}</div>
              <div class="min-w-0"><span class="font-semibold">Ciudad:</span> {{ row.ciudad ?? '—' }}</div>
              <div class="min-w-0"><span class="font-semibold">Estado:</span> {{ row.estado ?? '—' }}</div>
              <div class="col-span-2 min-w-0 truncate">
                <span class="font-semibold">Dirección:</span> {{ row.direccion ?? 'Sin dirección' }}
              </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
              <SecondaryButton class="rounded-xl" @click="openEdit(row)">Editar</SecondaryButton>
              <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
            </div>
          </div>

          <div
            v-if="props.sucursales.data.length === 0"
            class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400"
          >
            No hay sucursales con los filtros actuales.
          </div>

          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4 overflow-hidden">
            <div class="flex items-center justify-between mb-3 gap-2">
              <div class="text-xs text-slate-600 dark:text-neutral-300">
                <span class="font-semibold">{{ props.sucursales.from ?? 0 }}</span> -
                <span class="font-semibold">{{ props.sucursales.to ?? 0 }}</span> /
                <span class="font-semibold">{{ props.sucursales.total }}</span>
              </div>

              <button
                type="button"
                @click="toggleSort"
                class="rounded-lg border px-2 py-1 text-xs font-bold
                       border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                       dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-white/5
                       transition"
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
                       max-w-full min-w-0
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

    <!-- Modal (Create/Edit) -->
    <Modal :show="modalOpen" maxWidth="3xl" @close="closeModal">

            <!-- Panel dark -->
            <div class="rounded-3xl border border-slate-200/60 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-2xl">
            <div class="p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-neutral-100">
                    {{ isEdit ? 'Editar sucursal' : 'Nueva sucursal' }}
                    </h3>
                </div>

                <!-- Boton x -->
                <button type="button"
                    class="rounded-full px-4 py-2 text-sm font-semibold
                        border border-slate-200 bg-white dark:border-white/10
                        dark:bg-white/10 dark:hover:bg-red-600 dark:text-neutral-100
                        transition active:scale-[0.98] hover:bg-red-500 hover:text-white"
                    @click="closeModal">
                    X
                </button>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <!-- Corporativo (SearchableSelect) -->
                    <div class="sm:col-span-2">
                        <SearchableSelect
                            v-model="form.corporativo_id"
                            :options="corporativosActive"
                            label="Corporativo"
                            placeholder="Busca y selecciona el corporativo..."
                            searchPlaceholder="Buscar corporativo por nombre o código..."
                            :allowNull="true"
                            nullLabel="Sin corporativo"
                            :error="errors.corporativo_id"
                            rounded="3xl"
                            zIndexClass="z-40"
                        />
                    </div>

                    <!-- Nombre -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre</label>
                        <input
                        v-model="form.nombre"
                        type="text"
                        placeholder="Sucursal Centro, Matriz, etc."
                        class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                                border border-slate-200 bg-white text-slate-900
                                placeholder:text-slate-400
                                focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
                        />
                        <p v-if="errors.nombre" class="mt-1 text-xs text-rose-500">{{ errors.nombre }}</p>
                    </div>

                    <!-- Código -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Código</label>
                        <input
                        v-model="form.codigo"
                        type="text"
                        placeholder="Opcional"
                        class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                                border border-slate-200 bg-white text-slate-900
                                placeholder:text-slate-400
                                focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
                        />
                    </div>

                    <!-- Ciudad -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Ciudad</label>
                        <input
                        v-model="form.ciudad"
                        type="text"
                        placeholder="Opcional"
                        class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                                border border-slate-200 bg-white text-slate-900
                                placeholder:text-slate-400
                                focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
                        />
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estado</label>
                        <input
                        v-model="form.estado"
                        type="text"
                        placeholder="Opcional"
                        class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                                border border-slate-200 bg-white text-slate-900
                                placeholder:text-slate-400
                                focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
                        />
                    </div>

                    <div class="hidden sm:block"></div>

                    <!-- Dirección -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Dirección</label>
                        <input
                        v-model="form.direccion"
                        type="text"
                        placeholder="Opcional"
                        class="mt-1 w-full rounded-2xl px-4 py-3 text-sm
                                border border-slate-200 bg-white text-slate-900
                                placeholder:text-slate-400
                                focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
                        />
                    </div>

                    <!-- Activo -->
                    <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                        <input
                        id="suc-activo"
                        type="checkbox"
                        v-model="form.activo"
                        class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                        />
                        <label for="suc-activo" class="text-sm font-semibold text-slate-800 dark:text-neutral-100">
                        Sucursal activa
                        </label>
                    </div>

                </div>

                <div class="mt-7 flex flex-col sm:flex-row gap-3 sm:justify-end">
                <SecondaryButton class="rounded-2xl" @click="closeModal">Cancelar</SecondaryButton>

                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="rounded-2xl px-6 py-3 text-sm font-bold tracking-wide
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

    </Modal>
  </AuthenticatedLayout>
</template>
