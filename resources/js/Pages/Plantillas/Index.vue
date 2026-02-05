<!-- resources/js/Pages/Plantillas/Index.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import type { PlantillasPageProps } from './Plantillas.types'
import { usePlantillasIndex } from './usePlantillasIndex'

import {
  Plus,
  FilePlus2,
  Pencil,
  Trash2,
  ArrowUpDown,
  Search,
  Filter,
  ChevronDown,
} from 'lucide-vue-next'

const props = defineProps<PlantillasPageProps>()

const {
  state,
  rows,
  pagerLinks,
  sortLabel,
  toggleSort,
  goCreatePlantilla,
  goEdit,
  goNewRequisicion,
  destroyRow,
  goToUrl,
  money,
} = usePlantillasIndex(props)
</script>

<template>
  <Head title="Plantillas" />

  <AuthenticatedLayout>
    <template #header>
      <div class="min-w-0 max-w-full">
        <h2 class="text-lg sm:text-xl font-extrabold leading-tight text-slate-900 dark:text-zinc-100 truncate">
          Plantillas
        </h2>
        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-neutral-400 truncate">
          Admin/Contador: ven todas. Colaborador: solo las suyas.
        </p>
      </div>
    </template>

    <!-- Page wrapper (sin scroll X) -->
    <div
      class="w-full min-w-0 max-w-full overflow-x-hidden
             px-2.5 sm:px-4 lg:px-6
             py-3 sm:py-6"
    >
      <!-- RAIL CONTAINER: centra y agrega borde izq/der en TODOS los responsive -->
      <div
        class="mx-auto w-full min-w-0
               max-w-[980px] lg:max-w-[1120px] 2xl:max-w-[1200px]
               border-x border-slate-200/70 dark:border-white/10
               px-2.5 sm:px-4 lg:px-6
               space-y-3 sm:space-y-4"
      >
        <!-- Barra: filtros + CTA -->
        <div
          class="max-w-full min-w-0 overflow-hidden
                 rounded-[22px] sm:rounded-[28px]
                 bg-white/70 dark:bg-neutral-900/60 backdrop-blur-xl
                 ring-1 ring-black/5 dark:ring-white/10
                 shadow-[0_12px_45px_-28px_rgba(0,0,0,.35)]
                 p-3 sm:p-5"
        >
          <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-3 sm:gap-4 min-w-0 max-w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2.5 sm:gap-3 flex-1 min-w-0 max-w-full">
              <!-- Buscar -->
              <div class="min-w-0 max-w-full">
                <label class="block text-[10px] sm:text-[11px] font-extrabold tracking-wide text-slate-600 dark:text-neutral-300">
                  Buscar
                </label>

                <div
                  class="mt-1 relative rounded-xl sm:rounded-2xl
                         bg-white/65 dark:bg-neutral-950/30
                         ring-1 ring-black/5 dark:ring-white/10
                         focus-within:ring-2 focus-within:ring-emerald-500/25
                         transition-all duration-200 max-w-full"
                >
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 opacity-60" />
                  <input
                    v-model="state.q"
                    type="text"
                    placeholder="Nombre, observaciones..."
                    class="w-full h-9 sm:h-10 pl-10 pr-3 rounded-xl sm:rounded-2xl bg-transparent
                           text-[13px] sm:text-sm text-slate-900 dark:text-neutral-100
                           placeholder:text-slate-400 dark:placeholder:text-neutral-500
                           border-0 ring-0 shadow-none outline-none
                           focus:outline-none focus:ring-0 focus:border-0"
                  />
                </div>
              </div>

              <!-- Estado -->
              <div class="min-w-0 max-w-full">
                <label class="block text-[10px] sm:text-[11px] font-extrabold tracking-wide text-slate-600 dark:text-neutral-300">
                  Estado
                </label>

                <div
                  class="mt-1 relative rounded-xl sm:rounded-2xl
                         bg-white/65 dark:bg-neutral-950/30
                         ring-1 ring-black/5 dark:ring-white/10
                         focus-within:ring-2 focus-within:ring-emerald-500/25
                         transition-all duration-200 max-w-full"
                >
                  <Filter class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 opacity-60" />
                  <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 opacity-60 pointer-events-none" />

                  <select
                    v-model="state.status"
                    class="w-full h-9 sm:h-10 pl-10 pr-10 rounded-xl sm:rounded-2xl bg-transparent
                           text-[13px] sm:text-sm text-slate-900 dark:text-neutral-100
                           border-0 ring-0 shadow-none outline-none appearance-none
                           focus:outline-none focus:ring-0 focus:border-0"
                  >
                    <option value="">Todas</option>
                    <option value="BORRADOR">Borrador</option>
                    <option value="ELIMINADA">Eliminada</option>
                  </select>
                </div>
              </div>

              <!-- Orden -->
              <div class="min-w-0 max-w-full xl:flex xl:justify-end">
                <div class="xl:w-auto min-w-0 max-w-full">
                  <label class="block text-[10px] sm:text-[11px] font-extrabold tracking-wide text-slate-600 dark:text-neutral-300">
                    Orden
                  </label>

                  <button
                    type="button"
                    @click="toggleSort"
                    class="mt-1 w-full xl:w-auto inline-flex items-center justify-center gap-2
                           h-9 sm:h-10 rounded-xl sm:rounded-2xl px-3.5 sm:px-4
                           text-[13px] sm:text-sm font-extrabold
                           bg-white/65 dark:bg-neutral-950/30
                           ring-1 ring-black/5 dark:ring-white/10
                           hover:bg-white/80 dark:hover:bg-neutral-950/40
                           transition-all duration-200
                           active:scale-[0.99]"
                  >
                    <ArrowUpDown class="h-4 w-4 opacity-80 shrink-0" />
                    <span class="truncate">{{ sortLabel }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- CTA -->
            <div class="flex items-center justify-stretch xl:justify-end min-w-0 max-w-full">
              <button
                type="button"
                @click="goCreatePlantilla"
                class="w-full xl:w-auto inline-flex items-center justify-center gap-2
                       h-9 sm:h-10 rounded-xl sm:rounded-2xl px-4 sm:px-5
                       text-[13px] sm:text-sm font-extrabold text-white
                       bg-gradient-to-r from-emerald-600 to-emerald-500
                       shadow-[0_14px_40px_-26px_rgba(16,185,129,.85)]
                       hover:shadow-[0_18px_55px_-28px_rgba(16,185,129,.98)]
                       hover:-translate-y-[1px]
                       transition-all duration-200
                       active:translate-y-0 active:scale-[0.99]"
              >
                <Plus class="h-4 w-4 shrink-0" />
                Nueva plantilla
              </button>
            </div>
          </div>
        </div>

        <!-- MOBILE/TABLET: cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:hidden w-full min-w-0 max-w-full overflow-x-hidden">
          <div
            v-for="row in rows"
            :key="row.id"
            class="rounded-[22px] sm:rounded-[28px] overflow-hidden
                   bg-white/70 dark:bg-neutral-900/60 backdrop-blur-xl
                   ring-1 ring-black/5 dark:ring-white/10
                   shadow-[0_12px_45px_-28px_rgba(0,0,0,.35)]
                   p-3.5 sm:p-5 min-w-0 max-w-full"
          >
            <div class="min-w-0 max-w-full">
              <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate text-[15px] sm:text-base">
                {{ row.nombre }}
              </div>

              <div class="mt-2 space-y-1 text-[11px] sm:text-xs text-slate-600 dark:text-neutral-300 min-w-0">
                <div class="truncate">
                  <span class="opacity-70">Sucursal:</span> {{ row.sucursal?.nombre ?? '—' }}
                </div>
                <div class="truncate">
                  <span class="opacity-70">Solicitante:</span> {{ row.solicitante?.nombre ?? '—' }}
                </div>
              </div>

              <div class="mt-3 flex flex-wrap items-center justify-between gap-2 min-w-0">
                <span
                  class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-[10px] sm:text-[11px] font-extrabold border shrink-0"
                  :class="row.status === 'BORRADOR'
                    ? 'bg-zinc-500/10 text-zinc-700 border-zinc-300/50 dark:text-zinc-200 dark:border-white/10'
                    : 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200 dark:border-rose-500/25'"
                >
                  <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                  {{ row.status }}
                </span>

                <span class="text-[13px] sm:text-sm font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ money(row.monto_total) }}
                </span>
              </div>

              <div class="mt-3.5 sm:mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2 min-w-0 max-w-full">
                <button
                  type="button"
                  @click="goNewRequisicion(row.id)"
                  class="rounded-xl sm:rounded-2xl px-3.5 sm:px-4 py-2 text-[13px] sm:text-sm font-extrabold text-white
                         bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600
                         shadow-sm hover:shadow-md hover:-translate-y-[1px]
                         transition-all duration-200
                         active:translate-y-0 active:scale-[0.99]
                         inline-flex items-center justify-center gap-2"
                >
                  <FilePlus2 class="h-4 w-4 shrink-0" />
                  Nueva
                </button>

                <button
                  type="button"
                  @click="goEdit(row.id)"
                  class="rounded-xl sm:rounded-2xl px-3.5 sm:px-4 py-2 text-[13px] sm:text-sm font-extrabold
                         bg-white/65 dark:bg-neutral-950/30
                         ring-1 ring-black/5 dark:ring-white/10
                         hover:bg-white/80 dark:hover:bg-neutral-950/40
                         shadow-sm hover:shadow-md hover:-translate-y-[1px]
                         transition-all duration-200
                         active:translate-y-0 active:scale-[0.99]
                         inline-flex items-center justify-center gap-2
                         text-slate-900 dark:text-neutral-100"
                >
                  <Pencil class="h-4 w-4 shrink-0" />
                  Editar
                </button>

                <button
                  type="button"
                  @click="destroyRow(row)"
                  class="rounded-xl sm:rounded-2xl px-3.5 sm:px-4 py-2 text-[13px] sm:text-sm font-extrabold
                         bg-rose-500/10 text-rose-700 hover:bg-rose-500/15
                         dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15
                         ring-1 ring-rose-500/20 dark:ring-rose-500/25
                         shadow-sm hover:shadow-md hover:-translate-y-[1px]
                         transition-all duration-200
                         active:translate-y-0 active:scale-[0.99]
                         inline-flex items-center justify-center gap-2"
                >
                  <Trash2 class="h-4 w-4 shrink-0" />
                  Eliminar
                </button>
              </div>
            </div>
          </div>

          <div
            v-if="rows.length === 0"
            class="rounded-[22px] sm:rounded-[28px] overflow-hidden
                   bg-white/70 dark:bg-neutral-900/60
                   ring-1 ring-black/5 dark:ring-white/10
                   p-8 sm:p-10 text-center text-slate-500 dark:text-neutral-400
                   w-full min-w-0 max-w-full"
          >
            No hay plantillas con los filtros actuales.
          </div>
        </div>

        <!-- DESKTOP lg+: tabla con scroll horizontal -->
        <div
          class="hidden lg:block rounded-[28px]
                 bg-white/70 dark:bg-neutral-900/60 backdrop-blur-xl
                 ring-1 ring-black/5 dark:ring-white/10
                 shadow-[0_12px_45px_-28px_rgba(0,0,0,.35)]
                 overflow-hidden"
        >
          <div class="table-scroll overflow-x-auto">
            <table class="w-full min-w-[1040px] table-auto text-sm">
              <thead class="bg-white/45 dark:bg-neutral-950/30">
                <tr class="text-left text-slate-600 dark:text-neutral-300">
                  <th class="px-5 py-4 font-extrabold w-[26%]">Nombre</th>
                  <th class="px-5 py-4 font-extrabold w-[20%]">Sucursal</th>
                  <th class="px-5 py-4 font-extrabold w-[22%]">Solicitante</th>
                  <th class="px-5 py-4 font-extrabold w-[12%]">Total</th>
                  <th class="px-5 py-4 font-extrabold w-[12%]">Estado</th>
                  <th class="px-5 py-4 font-extrabold text-right w-[8%]">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="row in rows"
                  :key="row.id"
                  class="border-t border-slate-200/40 dark:border-white/10
                         hover:bg-white/45 dark:hover:bg-neutral-950/25
                         transition-all duration-200"
                >
                  <td class="px-5 py-4 font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                    {{ row.nombre }}
                  </td>
                  <td class="px-5 py-4 text-slate-700 dark:text-neutral-200 truncate">
                    {{ row.sucursal?.nombre ?? '—' }}
                  </td>
                  <td class="px-5 py-4 text-slate-700 dark:text-neutral-200 truncate">
                    {{ row.solicitante?.nombre ?? '—' }}
                  </td>
                  <td class="px-5 py-4 font-extrabold text-slate-900 dark:text-neutral-100">
                    {{ money(row.monto_total) }}
                  </td>

                  <td class="px-5 py-4">
                    <span
                      class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-extrabold border"
                      :class="row.status === 'BORRADOR'
                        ? 'bg-zinc-500/10 text-zinc-700 border-zinc-300/50 dark:text-zinc-200 dark:border-white/10'
                        : 'bg-rose-500/10 text-rose-700 border-rose-500/20 dark:text-rose-200 dark:border-rose-500/25'"
                    >
                      <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                      {{ row.status }}
                    </span>
                  </td>

                  <td class="px-5 py-4">
                    <div class="flex items-center justify-end">
                      <div
                        class="inline-flex items-center gap-1 p-1 rounded-2xl
                               bg-white/65 dark:bg-neutral-950/30
                               ring-1 ring-black/5 dark:ring-white/10"
                      >
                        <button
                          type="button"
                          @click="goNewRequisicion(row.id)"
                          class="h-9 inline-flex items-center gap-2 px-3 rounded-xl
                                 bg-emerald-600 text-white
                                 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600
                                 transition-all duration-200
                                 active:scale-[0.99]"
                        >
                          <FilePlus2 class="h-4 w-4 shrink-0" />
                          <span class="hidden xl:inline font-extrabold text-sm">Nueva</span>
                        </button>

                        <button
                          type="button"
                          @click="goEdit(row.id)"
                          class="h-9 inline-flex items-center gap-2 px-3 rounded-xl
                                 bg-white/60 dark:bg-neutral-950/20
                                 text-slate-900 dark:text-neutral-100
                                 hover:bg-white/85 dark:hover:bg-neutral-950/35
                                 transition-all duration-200
                                 active:scale-[0.99]"
                        >
                          <Pencil class="h-4 w-4 shrink-0" />
                          <span class="hidden xl:inline font-extrabold text-sm">Editar</span>
                        </button>

                        <button
                          type="button"
                          @click="destroyRow(row)"
                          class="h-9 inline-flex items-center gap-2 px-3 rounded-xl
                                 bg-rose-500/10 text-rose-700
                                 hover:bg-rose-500/15
                                 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/15
                                 transition-all duration-200
                                 active:scale-[0.99]"
                        >
                          <Trash2 class="h-4 w-4 shrink-0" />
                          <span class="hidden xl:inline font-extrabold text-sm">Eliminar</span>
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>

                <tr v-if="rows.length === 0">
                  <td colspan="6" class="px-5 py-12 text-center text-slate-500 dark:text-neutral-400">
                    No hay plantillas con los filtros actuales.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3
                   border-t border-slate-200/40 dark:border-white/10 px-5 py-4"
          >
            <div class="text-xs text-slate-600 dark:text-neutral-300">
              Página <span class="font-semibold">{{ props.plantillas?.current_page ?? 1 }}</span> de
              <span class="font-semibold">{{ props.plantillas?.last_page ?? 1 }}</span>
            </div>

            <nav class="flex flex-wrap gap-2 max-w-full">
              <button
                v-for="(link, i) in pagerLinks"
                :key="`${i}-${link.cleanLabel}`"
                type="button"
                @click="goToUrl(link.url)"
                :disabled="!link.url"
                class="rounded-2xl px-3 py-1.5 text-sm font-extrabold transition-all duration-200
                       bg-white/65 dark:bg-neutral-950/30
                       ring-1 ring-black/5 dark:ring-white/10
                       hover:bg-white/85 dark:hover:bg-neutral-950/40
                       disabled:opacity-50 disabled:cursor-not-allowed
                       text-slate-900 dark:text-neutral-100
                       active:scale-[0.99]"
                :class="link.active ? 'ring-2 ring-emerald-500/20' : ''"
                v-html="link.label"
              />
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Mata cualquier scroll horizontal del documento mientras estás en esta vista */
:global(html),
:global(body) {
  overflow-x: hidden;
}

/* Scrollbar horizontal bonita para la tabla (lg+) */
.table-scroll::-webkit-scrollbar {
  height: 10px;
}
.table-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.table-scroll::-webkit-scrollbar-thumb {
  background: rgba(100, 116, 139, 0.35);
  border-radius: 999px;
}
.table-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(100, 116, 139, 0.55);
}
</style>
