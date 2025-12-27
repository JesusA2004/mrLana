<script setup lang="ts">
/**
 * ======================================================
 * Empleados/Index.vue
 * ------------------------------------------------------
 * - Tabla (lg+) + Cards (mobile/tablet)
 * - Filtros realtime (debounce en composable)
 * - Dependencias: corporativo -> habilita sucursal/área (filtros y modal)
 * - Baja lógica + reactivar (botón cambia por estatus)
 * - Modal Create/Edit: viewport-safe + scroll interno
 * - Secciones plegables (accordion): Datos / Acceso
 * - Email único (no se pide doble)
 * - Password NO se captura (backend genera y envía por correo)
 * - Fix dropdown: z-index para que NO lo tape la tabla
 * ======================================================
 */

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

// UI
import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import AppCombobox from '@/Components/ui/SearchableSelect.vue'

import type { EmpleadosPageProps, EmpleadoRow } from './Empleados.types'
import { useEmpleadosIndex } from './useEmpleadosIndex'

const props = defineProps<EmpleadosPageProps>()

const {
  // filtros + paginación
  state,
  safeLinks,
  goTo,
  hasActiveFilters,
  clearFilters,
  sortLabel,
  toggleSort,

  // dependencias filtros
  canPickSucursalFilter,
  canPickAreaFilter,

  // datasets
  corporativosActive,
  sucursalesByCorp,
  areasByCorp,

  // modal datasets + dependencias
  modalSucursales,
  modalAreas,
  canPickSucursalModal,
  canPickAreaModal,

  // modal/form
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

  // acciones
  confirmDeactivate,
  confirmActivate,
  confirmBulkDeactivate,

  // selección
  selectedIds,
  selectedCount,
  isAllSelectedOnPage,
  toggleRow,
  toggleAllOnPage,
  clearSelection,
} = useEmpleadosIndex(props)

function fullName(r: EmpleadoRow) {
  const a = `${r.nombre ?? ''} ${r.apellido_paterno ?? ''}${r.apellido_materno ? ` ${r.apellido_materno}` : ''}`
  return a.trim() || '—'
}

function statusPill(active: boolean) {
  return active
    ? 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
    : 'bg-slate-500/10 text-slate-200 border-white/10'
}

/**
 * Agrupar por Sucursal (en el page actual)
 */
const grouped = computed(() => {
  const map = new Map<string, { key: string; label: string; rows: EmpleadoRow[] }>()
  for (const r of props.empleados.data ?? []) {
    const label = r.sucursal?.nombre ?? 'Sin sucursal'
    const key = `${r.sucursal_id ?? 'null'}__${label}`
    if (!map.has(key)) map.set(key, { key, label, rows: [] })
    map.get(key)!.rows.push(r)
  }
  return Array.from(map.values()).sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

/**
 * Bases UI
 */
const selectBase =
  'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10'

const inputBase =
  'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'

const disabledSelectBase =
  selectBase + ' opacity-60 pointer-events-none select-none'

/**
 * Modal (viewport-safe)
 */
const modalShell =
  'w-full max-w-3xl mx-auto ' +
  'max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)] overflow-hidden'

const modalPanel =
  'rounded-3xl border border-slate-200/60 dark:border-white/10 ' +
  'bg-white dark:bg-neutral-900 shadow-2xl flex flex-col ' +
  'max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)]'

const modalHeader =
  'sticky top-0 z-10 px-5 sm:px-7 py-4 sm:py-5 ' +
  'bg-white/90 dark:bg-neutral-900/85 backdrop-blur ' +
  'border-b border-slate-200/70 dark:border-white/10'

const modalBody = 'px-5 sm:px-7 py-5 sm:py-6 overflow-y-auto overscroll-contain min-h-0'
const modalFooter =
  'sticky bottom-0 z-10 px-5 sm:px-7 py-4 ' +
  'bg-white/90 dark:bg-neutral-900/85 backdrop-blur ' +
  'border-t border-slate-200/70 dark:border-white/10'

/**
 * Accordion (secciones plegables)
 */
const secEmpleadoOpen = ref(true)
const secAccesoOpen = ref(true)

function toggleSecEmpleado() {
  secEmpleadoOpen.value = !secEmpleadoOpen.value
}
function toggleSecAcceso() {
  secAccesoOpen.value = !secAccesoOpen.value
}
</script>

<template>
  <Head title="Empleados" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Empleados</h2>
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
              Administra empleados por sucursal y área
            </h1>
          </div>

          <button
            type="button"
            @click="openCreate"
            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                   bg-slate-900 text-white hover:bg-slate-800
                   dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                   transition active:scale-[0.98] w-full sm:w-auto"
          >
            Nuevo empleado
          </button>
        </div>

        <!-- Bulk bar -->
        <div
          v-if="selectedCount > 0"
          class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-slate-50 dark:bg-white/5 px-4 py-3"
        >
          <div class="text-sm text-slate-700 dark:text-neutral-200">
            Seleccionados: <span class="font-extrabold">{{ selectedCount }}</span>
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
              @click="confirmBulkDeactivate"
              class="rounded-xl px-3 py-2 text-sm font-extrabold
                     bg-rose-600 text-white hover:bg-rose-500
                     transition active:scale-[0.98]"
            >
              Dar de baja seleccionados
            </button>
          </div>
        </div>

        <!-- Filtros (FIX: z-index arriba de tabla) -->
        <div
          class="relative z-[80] mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
        >
          <div class="lg:col-span-5 sm:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
            <input
              v-model="state.q"
              type="text"
              placeholder="Nombre, apellidos, email, teléfono, puesto..."
              :class="inputBase"
            />
          </div>

          <!-- Corporativo -->
          <div class="lg:col-span-2 min-w-0">
            <AppCombobox
              :modelValue="state.corporativo_id || ''"
              @update:modelValue="(v) => (state.corporativo_id = (v ?? null) as any)"
              :options="corporativosActive"
              label="Corporativo"
              placeholder="Selecciona..."
              searchPlaceholder="Buscar corporativo..."
              labelKey="nombre"
              secondaryKey="codigo"
              :allowNull="true"
              nullLabel="Selecciona..."
              :buttonClass="selectBase"
              zIndexClass="z-[90]"
              rounded="2xl"
            />
          </div>

          <!-- Sucursal (BLOQUEADA hasta corporativo) -->
          <div class="lg:col-span-2 min-w-0">
            <AppCombobox
              :modelValue="state.sucursal_id || ''"
              @update:modelValue="(v) => (state.sucursal_id = (v ?? null) as any)"
              :options="sucursalesByCorp"
              label="Sucursal"
              :placeholder="canPickSucursalFilter ? 'Todas' : 'Elige corporativo primero'"
              :searchPlaceholder="canPickSucursalFilter ? 'Buscar sucursal...' : 'Selecciona corporativo...'"
              labelKey="nombre"
              secondaryKey="codigo"
              :allowNull="true"
              :nullLabel="canPickSucursalFilter ? 'Todas' : 'Selecciona corporativo'"
              :buttonClass="canPickSucursalFilter ? selectBase : disabledSelectBase"
              zIndexClass="z-[90]"
              rounded="2xl"
            />
          </div>

          <!-- Área (BLOQUEADA hasta corporativo) -->
          <div class="lg:col-span-2 min-w-0">
            <AppCombobox
              :modelValue="state.area_id || ''"
              @update:modelValue="(v) => (state.area_id = (v ?? null) as any)"
              :options="areasByCorp"
              label="Área"
              :placeholder="canPickAreaFilter ? 'Todas' : 'Elige corporativo primero'"
              :searchPlaceholder="canPickAreaFilter ? 'Buscar área...' : 'Selecciona corporativo...'"
              labelKey="nombre"
              :allowNull="true"
              :nullLabel="canPickAreaFilter ? 'Todas' : 'Selecciona corporativo'"
              :buttonClass="canPickAreaFilter ? selectBase : disabledSelectBase"
              zIndexClass="z-[90]"
              rounded="2xl"
            />
          </div>

          <div class="lg:col-span-1 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select v-model="state.activo" :class="selectBase">
              <option value="all">Todos</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </select>
          </div>

          <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Mostrando
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.empleados.from ?? 0 }}</span>
              a
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.empleados.to ?? 0 }}</span>
              de
              <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.empleados.total }}</span>
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

        <!-- TABLA (lg+) (FIX: z-index abajo) -->
        <div
          class="relative z-0 hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm max-w-full"
        >
          <div class="overflow-x-auto">
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

                  <th class="px-4 py-3 font-semibold">
                    <div class="inline-flex items-center gap-2">
                      <span>Empleado</span>
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

                  <th class="px-4 py-3 font-semibold">Sucursal</th>
                  <th class="px-4 py-3 font-semibold">Área</th>
                  <th class="px-4 py-3 font-semibold">Contacto</th>
                  <th class="px-4 py-3 font-semibold">Usuario</th>
                  <th class="px-4 py-3 font-semibold">Estatus</th>
                  <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <template v-for="g in grouped" :key="g.key">
                  <tr class="border-t border-slate-200/70 dark:border-white/10">
                    <td colspan="8" class="px-4 py-3">
                      <div class="flex items-center justify-between">
                        <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">
                          {{ g.label }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-neutral-400">{{ g.rows.length }} empleado(s)</div>
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
                        {{ fullName(row) }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">
                        {{ row.puesto ?? '—' }}
                      </div>
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                      {{ row.sucursal?.nombre ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                      {{ row.area?.nombre ?? '—' }}
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                      <div class="truncate max-w-[260px]">{{ row.user?.email ?? '—' }}</div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">{{ row.telefono ?? '—' }}</div>
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                      <div class="truncate max-w-[240px]">{{ row.user?.email ?? '—' }}</div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">{{ row.user?.rol ?? '—' }}</div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                      <span
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                        :class="statusPill(!!row.activo)"
                      >
                        <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-400' : 'bg-slate-400'" />
                        {{ row.activo ? 'Activo' : 'Inactivo' }}
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

                        <button
                          v-if="row.activo"
                          type="button"
                          class="rounded-xl px-3 py-2 text-xs font-extrabold bg-rose-600 text-white hover:bg-rose-500 transition active:scale-[0.98]"
                          @click="confirmDeactivate(row)"
                        >
                          Dar de baja
                        </button>

                        <button
                          v-else
                          type="button"
                          class="rounded-xl px-3 py-2 text-xs font-extrabold bg-emerald-600 text-white hover:bg-emerald-500 transition active:scale-[0.98]"
                          @click="confirmActivate(row)"
                        >
                          Activar
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>

                <tr v-if="props.empleados.data.length === 0">
                  <td colspan="8" class="px-4 py-12 text-center text-slate-500 dark:text-neutral-400">
                    No hay empleados con los filtros actuales.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- paginación -->
          <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                   border-t border-slate-200/70 dark:border-white/10
                   px-4 py-3 bg-white dark:bg-neutral-900"
          >
            <div class="text-xs text-slate-600 dark:text-neutral-300">
              Página <span class="font-semibold">{{ props.empleados.current_page }}</span> de
              <span class="font-semibold">{{ props.empleados.last_page }}</span>
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

        <!-- CARDS (mobile/tablet) -->
        <div class="lg:hidden grid gap-3 max-w-full">
          <template v-for="g in grouped" :key="g.key">
            <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm px-4 py-3">
              <div class="flex items-center justify-between gap-2">
                <div class="font-extrabold text-slate-900 dark:text-neutral-100">{{ g.label }}</div>
                <div class="text-xs text-slate-500 dark:text-neutral-400">{{ g.rows.length }} empleado(s)</div>
              </div>
            </div>

            <div
              v-for="row in g.rows"
              :key="row.id"
              class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4
                     hover:shadow-md dark:hover:shadow-black/40 transition"
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
                    <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">{{ fullName(row) }}</div>
                    <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">
                      {{ row.sucursal?.nombre ?? '—' }} · {{ row.area?.nombre ?? '—' }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-neutral-400 truncate">
                      {{ row.user?.email ?? '—' }}
                    </div>
                  </div>
                </div>

                <span
                  class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border"
                  :class="statusPill(!!row.activo)"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-400' : 'bg-slate-400'" />
                  {{ row.activo ? 'Activo' : 'Inactivo' }}
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

                <button
                  v-if="row.activo"
                  type="button"
                  class="rounded-xl px-3 py-2 text-xs font-extrabold bg-rose-600 text-white hover:bg-rose-500 transition active:scale-[0.98]"
                  @click="confirmDeactivate(row)"
                >
                  Dar de baja
                </button>

                <button
                  v-else
                  type="button"
                  class="rounded-xl px-3 py-2 text-xs font-extrabold bg-emerald-600 text-white hover:bg-emerald-500 transition active:scale-[0.98]"
                  @click="confirmActivate(row)"
                >
                  Activar
                </button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- MODAL Create/Edit -->
    <Modal :show="modalOpen" maxWidth="3xl" @close="closeModal">
        <div :class="modalShell">
          <div :class="modalPanel">
            <!-- HEADER -->
            <div :class="modalHeader">
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <h3 class="text-xl font-extrabold text-slate-900 dark:text-neutral-100">
                    {{ isEdit ? 'Editar empleado' : 'Nuevo empleado' }}
                  </h3>
                </div>

                <button
                  type="button"
                  class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold
                         border border-slate-200 bg-white hover:bg-slate-50
                         dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 dark:text-neutral-100
                         transition active:scale-[0.98]"
                  @click="closeModal"
                >
                  Cerrar
                </button>
              </div>
            </div>

            <!-- BODY -->
            <div :class="modalBody">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Corporativo -->
                <div class="sm:col-span-2">
                  <AppCombobox
                    :modelValue="form.corporativo_id || ''"
                    @update:modelValue="(v) => (form.corporativo_id = (v ?? null) as any)"
                    :options="corporativosActive"
                    label="Corporativo *"
                    placeholder="Selecciona..."
                    searchPlaceholder="Buscar corporativo..."
                    labelKey="nombre"
                    secondaryKey="codigo"
                    :allowNull="true"
                    nullLabel="Selecciona..."
                    :buttonClass="selectBase"
                    zIndexClass="z-[100]"
                    rounded="2xl"
                  />
                  <p v-if="errors.corporativo_id" class="mt-1 text-xs text-rose-500">{{ errors.corporativo_id }}</p>
                </div>

                <!-- Sucursal (bloqueada hasta corporativo) -->
                <div class="sm:col-span-2">
                  <AppCombobox
                    :modelValue="form.sucursal_id || ''"
                    @update:modelValue="(v) => (form.sucursal_id = (v ?? null) as any)"
                    :options="modalSucursales"
                    label="Sucursal *"
                    :placeholder="canPickSucursalModal ? 'Selecciona...' : 'Elige corporativo primero'"
                    :searchPlaceholder="canPickSucursalModal ? 'Buscar sucursal...' : 'Selecciona corporativo...'"
                    labelKey="nombre"
                    secondaryKey="codigo"
                    :allowNull="true"
                    :nullLabel="canPickSucursalModal ? 'Selecciona...' : 'Selecciona corporativo'"
                    :buttonClass="canPickSucursalModal ? selectBase : disabledSelectBase"
                    zIndexClass="z-[100]"
                    rounded="2xl"
                  />
                  <p v-if="errors.sucursal_id" class="mt-1 text-xs text-rose-500">{{ errors.sucursal_id }}</p>
                </div>

                <!-- Área (bloqueada hasta corporativo) -->
                <div class="sm:col-span-2">
                  <AppCombobox
                    :modelValue="form.area_id || ''"
                    @update:modelValue="(v) => (form.area_id = (v ?? null) as any)"
                    :options="modalAreas"
                    label="Área"
                    :placeholder="canPickAreaModal ? 'Sin área' : 'Elige corporativo primero'"
                    :searchPlaceholder="canPickAreaModal ? 'Buscar área...' : 'Selecciona corporativo...'"
                    labelKey="nombre"
                    :allowNull="true"
                    :nullLabel="canPickAreaModal ? 'Sin área' : 'Selecciona corporativo'"
                    :buttonClass="canPickAreaModal ? selectBase : disabledSelectBase"
                    zIndexClass="z-[100]"
                    rounded="2xl"
                  />
                </div>

                <!-- ACCORDION: DATOS DEL EMPLEADO -->
                <div class="sm:col-span-2">
                  <button
                    type="button"
                    @click="toggleSecEmpleado"
                    class="w-full flex items-center justify-between gap-3 rounded-2xl px-4 py-3
                           border border-slate-200/70 dark:border-white/10
                           bg-slate-50/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 transition"
                  >
                    <div class="text-left">
                      <div class="text-xs font-extrabold text-slate-900 dark:text-neutral-100">Datos del empleado</div>
                      <div class="text-[11px] text-slate-500 dark:text-neutral-400">
                        Obligatorios: Nombre + Apellido paterno
                      </div>
                    </div>
                    <div class="text-xs font-extrabold text-slate-700 dark:text-neutral-200">
                      {{ secEmpleadoOpen ? 'Minimizar' : 'Expandir' }}
                    </div>
                  </button>
                </div>

                <template v-if="secEmpleadoOpen">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre *</label>
                    <input v-model="form.nombre" type="text" placeholder="Ej. Jesús" :class="inputBase" />
                    <p v-if="errors.nombre" class="mt-1 text-xs text-rose-500">{{ errors.nombre }}</p>
                  </div>

                  <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Apellido paterno *</label>
                    <input v-model="form.apellido_paterno" type="text" placeholder="Ej. Arizmendi" :class="inputBase" />
                    <p v-if="errors.apellido_paterno" class="mt-1 text-xs text-rose-500">{{ errors.apellido_paterno }}</p>
                  </div>

                  <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Apellido materno</label>
                    <input v-model="form.apellido_materno" type="text" placeholder="Opcional" :class="inputBase" />
                  </div>

                  <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Teléfono</label>
                    <input v-model="form.telefono" type="text" placeholder="Opcional" :class="inputBase" />
                  </div>

                  <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Puesto</label>
                    <input v-model="form.puesto" type="text" placeholder="Opcional" :class="inputBase" />
                  </div>
                </template>

                <!-- ACCORDION: ACCESO -->
                <div class="sm:col-span-2">
                  <button
                    type="button"
                    @click="toggleSecAcceso"
                    class="w-full flex items-center justify-between gap-3 rounded-2xl px-4 py-3
                           border border-slate-200/70 dark:border-white/10
                           bg-slate-50/70 dark:bg-white/5 hover:bg-slate-50 dark:hover:bg-white/10 transition"
                  >
                    <div class="text-left">
                      <div class="text-xs font-extrabold text-slate-900 dark:text-neutral-100">Acceso al sistema</div>
                      <div class="text-[11px] text-slate-500 dark:text-neutral-400">
                        Email + Rol. La contraseña se genera automáticamente y se envía por correo.
                      </div>
                    </div>
                    <div class="text-xs font-extrabold text-slate-700 dark:text-neutral-200">
                      {{ secAccesoOpen ? 'Minimizar' : 'Expandir' }}
                    </div>
                  </button>
                </div>

                <template v-if="secAccesoOpen">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Email *</label>
                    <input v-model="form.user_email" type="email" placeholder="ej. jesus@empresa.com" :class="inputBase" />
                    <p v-if="errors.user_email" class="mt-1 text-xs text-rose-500">{{ errors.user_email }}</p>
                  </div>

                  <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Rol *</label>
                    <select v-model="form.user_rol" :class="selectBase">
                      <option value="ADMIN">ADMIN</option>
                      <option value="CONTADOR">CONTADOR</option>
                      <option value="COLABORADOR">COLABORADOR</option>
                    </select>
                    <p v-if="errors.user_rol" class="mt-1 text-xs text-rose-500">{{ errors.user_rol }}</p>
                  </div>
                </template>
              </div>
            </div>

            <!-- FOOTER -->
            <div :class="modalFooter">
              <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
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
                  {{ saving ? 'Guardando...' : isEdit ? 'Actualizar' : 'Crear' }}
                </button>
              </div>
            </div>

          </div>
        </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
:global(html.dark select option) {
  background: #0a0a0a;
  color: #f5f5f5;
}
</style>
