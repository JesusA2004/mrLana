<!-- resources/js/Pages/Plantillas/Index.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue' // Si quieres filtros avanzados
import SecondaryButton from '@/Components/SecondaryButton.vue'
import type { PlantillasPageProps } from './Plantillas.types'
import { usePlantillasIndex } from './usePlantillasIndex'

const props = defineProps<PlantillasPageProps>()
const {
  state,
  rows,
  safePagerLinks,
  sortLabel,
  toggleSort,
  goCreatePlantilla,
  goEdit,
  goNewRequisicion,
  destroyRow,
} = usePlantillasIndex(props)
</script>

<template>
  <Head title="Plantillas" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100 truncate">
          Plantillas
        </h2>
        <button @click="goCreatePlantilla"
          class="rounded-2xl px-4 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
          Nueva plantilla
        </button>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <!-- Filtros rápidos -->
      <div class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Buscar</label>
            <input v-model="state.q" type="text" placeholder="Nombre, observaciones..."
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estado</label>
            <select v-model="state.status"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
              <option value="">Todas</option>
              <option value="BORRADOR">Borrador</option>
              <option value="ELIMINADA">Eliminada</option>
            </select>
          </div>

          <div class="flex items-end gap-2">
            <button type="button" @click="toggleSort"
              class="rounded-2xl px-4 py-2 text-xs font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15 transition">
              Orden: {{ sortLabel }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm overflow-x-auto">
        <table class="min-w-full table-auto text-sm">
          <thead class="bg-slate-50 dark:bg-neutral-950/60">
            <tr class="text-left text-slate-600 dark:text-neutral-300">
              <th class="px-4 py-3 font-semibold">Nombre</th>
              <th class="px-4 py-3 font-semibold">Sucursal</th>
              <th class="px-4 py-3 font-semibold">Solicitante</th>
              <th class="px-4 py-3 font-semibold">Total</th>
              <th class="px-4 py-3 font-semibold">Estado</th>
              <th class="px-4 py-3 font-semibold text-right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id"
              class="border-t border-slate-200/70 dark:border-white/10 hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition">
              <td class="px-4 py-3">{{ row.nombre }}</td>
              <td class="px-4 py-3">{{ row.sucursal?.nombre ?? '—' }}</td>
              <td class="px-4 py-3">{{ row.solicitante?.nombre ?? '—' }}</td>
              <td class="px-4 py-3">{{ row.monto_total }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border"
                  :class="row.status === 'BORRADOR'
                    ? 'bg-zinc-500/10 text-zinc-700 border-zinc-300/50 dark:text-zinc-200 dark:border-white/10'
                    : 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200'">
                  <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                  <span class="truncate">{{ row.status }}</span>
                </span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-right">
                <div class="inline-flex gap-2">
                  <button @click="goNewRequisicion(row.id)" type="button"
                    class="btn bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                    Nueva requisición
                  </button>
                  <button @click="goEdit(row.id)" type="button"
                    class="btn border border-slate-200 bg-slate-50 text-slate-900 hover:bg-slate-100 dark:border-white/10 dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15">
                    Editar
                  </button>
                  <button @click="destroyRow(row)" type="button"
                    class="btn border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15">
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="6" class="px-4 py-12 text-center text-slate-500 dark:text-neutral-400">
                No hay plantillas con los filtros actuales.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginación -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-slate-200/70 dark:border-white/10 px-4 py-3 bg-white/90 dark:bg-neutral-900/80">
          <div class="text-xs text-slate-600 dark:text-neutral-300">
            Página <span class="font-semibold">{{ props.plantillas?.current_page ?? 1 }}</span> de
            <span class="font-semibold">{{ props.plantillas?.last_page ?? 1 }}</span>
          </div>
          <nav class="flex flex-wrap gap-2 max-w-full">
            <button v-for="(link, i) in safePagerLinks"
              :key="`${i}-${link.cleanLabel}`" type="button"
              @click="link.url ? router.visit(link.url, { preserveScroll: true, preserveState: true }) : null"
              :disabled="!link.url"
              class="rounded-2xl px-3 py-1.5 text-sm font-semibold border transition border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:-translate-y-[1px] disabled:opacity-50 disabled:cursor-not-allowed dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60"
              :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
              v-html="link.label" />
          </nav>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
