<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

import type { EmpleadosPageProps, EmpleadoRow } from './Empleados.types'
import { useEmpleadosIndex } from './useEmpleadosIndex'

/**
 * ======================================================
 * Empleados/Index.vue
 * - Tabla (lg+) + Cards (móvil/tablet)
 * - Filtros en tiempo real con debounce (composable)
 * - Modal Create/Edit: NO excede viewport + scroll interno
 * ======================================================
 */

const props = defineProps<EmpleadosPageProps>()

const {
  state,
  safeLinks,
  goTo,
  hasActiveFilters,
  clearFilters,
  sortLabel,
  toggleSort,

  corporativosActive,
  sucursalesByCorp,
  areasByCorp,

  modalSucursales,
  modalAreas,

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
} = useEmpleadosIndex(props)

function fullName(r: EmpleadoRow) {
  return `${r.nombre} ${r.apellido_paterno}${r.apellido_materno ? ` ${r.apellido_materno}` : ''}`.trim()
}

function statusPill(active: boolean) {
  return active
    ? 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
    : 'bg-slate-500/10 text-slate-200 border-white/10'
}

/** Agrupar desde inicio por Sucursal (page actual) */
const grouped = computed(() => {
  const map = new Map<string, { key: string; label: string; rows: EmpleadoRow[] }>()
  for (const r of props.empleados.data ?? []) {
    const label = r.sucursal?.nombre ?? 'Sin sucursal'
    const key = `${r.sucursal_id}__${label}`
    if (!map.has(key)) map.set(key, { key, label, rows: [] })
    map.get(key)!.rows.push(r)
  }
  return Array.from(map.values()).sort((a, b) => a.label.localeCompare(b.label, 'es'))
})

/** Bases UI */
const selectBase =
  'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10'

const inputBase =
  'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'

/**
 * ======================================================
 * CLAVES PARA QUE EL MODAL NO “SE MUERA” EN TABLET/MÓVIL
 * - max-h con dvh (viewport real en móvil)
 * - flex-col
 * - header/footer sticky
 * - body con overflow-y-auto
 * ======================================================
 */
const modalShell =
  'w-full max-w-3xl mx-auto ' +
  'max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)] ' +
  'overflow-hidden'

const modalPanel =
  'rounded-3xl border border-slate-200/60 dark:border-white/10 ' +
  'bg-white dark:bg-neutral-900 shadow-2xl ' +
  'flex flex-col ' +
  'max-h-[calc(100dvh-1.5rem)] sm:max-h-[calc(100dvh-3rem)]'

const modalHeader =
  'sticky top-0 z-10 ' +
  'px-5 sm:px-7 py-4 sm:py-5 ' +
  'bg-white/90 dark:bg-neutral-900/85 backdrop-blur ' +
  'border-b border-slate-200/70 dark:border-white/10'

const modalBody =
  'px-5 sm:px-7 py-5 sm:py-6 ' +
  'overflow-y-auto overscroll-contain ' +
  'min-h-0' // clave para que el scroll funcione en flex

const modalFooter =
  'sticky bottom-0 z-10 ' +
  'px-5 sm:px-7 py-4 ' +
  'bg-white/90 dark:bg-neutral-900/85 backdrop-blur ' +
  'border-t border-slate-200/70 dark:border-white/10'
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
              Administra empleados por sucursal
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
              @click="destroySelected"
              class="rounded-xl px-3 py-2 text-sm font-extrabold
                     bg-rose-600 text-white hover:bg-rose-500
                     transition active:scale-[0.98]"
            >
              Eliminar seleccionados
            </button>
          </div>
        </div>

        <!-- Filtros -->
        <div
          class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
        >
          <div class="lg:col-span-5 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
            <input v-model="state.q" type="text" placeholder="Nombre, apellidos, email, teléfono, puesto..." :class="inputBase" />
          </div>

          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Corporativo</label>
            <select v-model="state.corporativo_id" :class="selectBase">
              <option value="">Todos</option>
              <option v-for="c in corporativosActive" :key="c.id" :value="c.id">
                {{ c.nombre }}<span v-if="c.codigo"> ({{ c.codigo }})</span>
              </option>
            </select>
          </div>

          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Sucursal</label>
            <select v-model="state.sucursal_id" :class="selectBase">
              <option value="">Todas</option>
              <option v-for="s in sucursalesByCorp" :key="s.id" :value="s.id">
                {{ s.nombre }}<span v-if="s.codigo"> ({{ s.codigo }})</span>
              </option>
            </select>
          </div>

          <div class="lg:col-span-2 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Área</label>
            <select v-model="state.area_id" :class="selectBase">
              <option value="">Todas</option>
              <option v-for="a in areasByCorp" :key="a.id" :value="a.id">
                {{ a.nombre }}
              </option>
            </select>
          </div>

          <div class="lg:col-span-1 min-w-0">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
            <select v-model="state.activo" :class="selectBase">
              <option value="">Todos</option>
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

        <!-- TABLA (PC) -->
        <div
          class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
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
                        <div class="text-xs text-slate-500 dark:text-neutral-400">
                          {{ g.rows.length }} empleado(s)
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
                      <div class="truncate max-w-[260px]">{{ row.email ?? '—' }}</div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">{{ row.telefono ?? '—' }}</div>
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                      <div class="truncate max-w-[240px]">{{ row.user?.email ?? '—' }}</div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">
                        {{ row.user?.rol ?? '—' }}
                      </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border" :class="statusPill(!!row.activo)">
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
                        <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
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

        <!-- CARDS (móvil/tablet) -->
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
                      {{ row.user?.email ?? row.email ?? '—' }}
                    </div>
                  </div>
                </div>

                <span class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border" :class="statusPill(!!row.activo)">
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
                <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ======================================================
         MODAL Create/Edit (FIX: NO EXCEDE PANTALLA + SCROLL)
         ====================================================== -->
    <Modal :show="modalOpen" maxWidth="3xl" @close="closeModal">
      <div class="p-2 sm:p-4">
        <div :class="modalShell">
          <div :class="modalPanel">
            <!-- HEADER sticky -->
            <div :class="modalHeader">
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <h3 class="text-xl font-extrabold text-slate-900 dark:text-neutral-100">
                    {{ isEdit ? 'Editar empleado' : 'Nuevo empleado' }}
                  </h3>
                  <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                    Empleado + usuario (rol) en un solo flujo.
                  </p>
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

            <!-- BODY scrollable -->
            <div :class="modalBody">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Corporativo -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Corporativo</label>
                  <select v-model="form.corporativo_id" :class="selectBase">
                    <option value="">Todos</option>
                    <option v-for="c in corporativosActive" :key="c.id" :value="c.id">
                      {{ c.nombre }}<span v-if="c.codigo"> ({{ c.codigo }})</span>
                    </option>
                  </select>
                </div>

                <!-- Sucursal -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Sucursal *</label>
                  <select v-model="form.sucursal_id" :class="selectBase">
                    <option value="">Selecciona...</option>
                    <option v-for="s in modalSucursales" :key="s.id" :value="s.id">
                      {{ s.nombre }}<span v-if="s.codigo"> ({{ s.codigo }})</span>
                    </option>
                  </select>
                  <p v-if="errors.sucursal_id" class="mt-1 text-xs text-rose-500">{{ errors.sucursal_id }}</p>
                </div>

                <!-- Área -->
                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Área</label>
                  <select v-model="form.area_id" :class="selectBase">
                    <option value="">Sin área</option>
                    <option v-for="a in modalAreas" :key="a.id" :value="a.id">
                      {{ a.nombre }}
                    </option>
                  </select>
                </div>

                <!-- EMPLEADO -->
                <div class="sm:col-span-2">
                  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/60 dark:bg-white/5 px-4 py-3">
                    <div class="text-xs font-extrabold text-slate-900 dark:text-neutral-100">Datos del empleado</div>
                    <div class="text-[11px] text-slate-500 dark:text-neutral-400">Obligatorios: Nombre + Apellido paterno</div>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre *</label>
                  <input v-model="form.nombre" type="text" placeholder="Ej. Carlos" :class="inputBase" />
                  <p v-if="errors.nombre" class="mt-1 text-xs text-rose-500">{{ errors.nombre }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Apellido paterno *</label>
                  <input v-model="form.apellido_paterno" type="text" placeholder="Ej. Ascencio" :class="inputBase" />
                  <p v-if="errors.apellido_paterno" class="mt-1 text-xs text-rose-500">{{ errors.apellido_paterno }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Apellido materno</label>
                  <input v-model="form.apellido_materno" type="text" placeholder="Opcional" :class="inputBase" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Email empleado</label>
                  <input v-model="form.email" type="email" placeholder="Opcional" :class="inputBase" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Teléfono</label>
                  <input v-model="form.telefono" type="text" placeholder="Opcional" :class="inputBase" />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Puesto</label>
                  <input v-model="form.puesto" type="text" placeholder="Opcional" :class="inputBase" />
                </div>

                <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                  <input id="emp-activo" type="checkbox" v-model="form.activo" class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900" />
                  <label for="emp-activo" class="text-sm font-semibold text-slate-800 dark:text-neutral-100">Empleado activo</label>
                </div>

                <!-- USER -->
                <div class="sm:col-span-2">
                  <div class="mt-2 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/60 dark:bg-white/5 px-4 py-3">
                    <div class="text-xs font-extrabold text-slate-900 dark:text-neutral-100">Usuario del sistema</div>
                    <div class="text-[11px] text-slate-500 dark:text-neutral-400">
                      Obligatorios: Nombre, Email, Rol
                      <span v-if="!isEdit">, Contraseña</span>
                      <span v-else> (contraseña opcional si no deseas cambiarla)</span>
                    </div>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre usuario *</label>
                  <input v-model="form.user_name" type="text" placeholder="Ej. Carlos Ascencio" :class="inputBase" />
                  <p v-if="errors.user_name" class="mt-1 text-xs text-rose-500">{{ errors.user_name }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Email usuario *</label>
                  <input v-model="form.user_email" type="email" placeholder="ej. nombre@dominio.com" :class="inputBase" />
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

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">
                    Contraseña <span v-if="!isEdit">*</span>
                  </label>
                  <input
                    v-model="form.user_password"
                    type="password"
                    :placeholder="isEdit ? 'Opcional (solo si deseas cambiarla)' : 'Obligatoria'"
                    :class="inputBase"
                  />
                  <p v-if="errors.user_password" class="mt-1 text-xs text-rose-500">{{ errors.user_password }}</p>
                </div>

                <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                  <input id="user-activo" type="checkbox" v-model="form.user_activo" class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900" />
                  <label for="user-activo" class="text-sm font-semibold text-slate-800 dark:text-neutral-100">Usuario activo</label>
                </div>
              </div>
            </div>

            <!-- FOOTER sticky -->
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
                  {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Dropdown nativo en dark: ayuda a legibilidad en algunos navegadores */
:global(html.dark select option) {
  background: #0a0a0a;
  color: #f5f5f5;
}
</style>
