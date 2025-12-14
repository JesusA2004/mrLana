<script setup lang="ts">
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import type { CorporativosProps } from './Corporativos.types'
import { useCorporativosIndex } from './useCorporativosIndex'

import AppInput from '@/Components/ui/AppInput.vue'
import AppSelect from '@/Components/ui/AppSelect.vue'
import AppPagination from '@/Components/ui/AppPagination.vue'

const props = defineProps<CorporativosProps>()

const {
  q,
  activo,
  perPage,
  selectedIds,
  headerCheckbox,
  isAllSelected,
  selectedCount,
  headerAriaChecked,
  paginationLinks,
  logoSrc,
  toggleRow,
  toggleAllOnPage,
  clearSelection,
  goTo,
  openCreate,
  openEdit,
  confirmDelete,
  confirmBulkDelete,
} = useCorporativosIndex(props)

// Paginación móvil/tablet sin romper ancho
const mobileLinks = computed(() => {
  const links = (paginationLinks.value ?? []) as any[]
  return links.filter((l) => l && typeof l.label === 'string')
})

function linkLabelShort(label: string) {
  const t = String(label)
    .replace(/&laquo;|&raquo;|&hellip;/g, '')
    .replace(/<[^>]*>/g, '')
    .trim()

  const low = t.toLowerCase()
  if (low.includes('atrás')) return 'Atrás'
  if (low.includes('siguiente')) return 'Siguiente'
  if (/^\d+$/.test(t)) return t
  if (t.length > 6) return t.slice(0, 6)
  return t || '…'
}
</script>

<template>
  <Head title="Corporativos" />

  <AuthenticatedLayout>
    <!-- ✅ Corte total overflow horizontal -->
    <div class="w-full max-w-full min-w-0 overflow-x-hidden">
      <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Header -->
        <div
          class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm px-4 py-4"
        >
          <div class="min-w-0">
            <h1 class="text-xl font-bold text-slate-900 dark:text-neutral-100 truncate">Corporativos</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-neutral-400">
              Administra corporativos, logos y estatus sin perder productividad.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0">
            <div
              v-if="selectedCount > 0"
              class="flex flex-wrap items-center gap-2
                     rounded-2xl border border-slate-200/70 dark:border-white/10
                     bg-slate-50 dark:bg-neutral-950/40 px-3 py-2 min-w-0 max-w-full"
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
                @click="confirmBulkDelete"
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
              @click="openCreate"
              class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                     bg-slate-900 text-white hover:bg-slate-800
                     dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                     transition active:scale-[0.98] w-full sm:w-auto"
            >
              Nuevo
            </button>
          </div>
        </div>

        <!-- Filtros -->
        <div
          class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                 rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
        >
          <div class="lg:col-span-6 min-w-0">
            <AppInput v-model="q" label="Búsqueda" placeholder="Buscar por nombre, RFC, email, teléfono o código..." />
          </div>

          <div class="lg:col-span-3 min-w-0">
            <AppSelect v-model="activo" label="Estatus">
              <option value="all">Todos</option>
              <option value="1">Activos</option>
              <option value="0">Inactivos</option>
            </AppSelect>
          </div>

          <div class="lg:col-span-3 min-w-0">
            <AppSelect v-model="perPage" label="Registros por página">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </AppSelect>
          </div>
        </div>

        <!-- ✅ TABLA SOLO EN DESKTOP (lg+) -->
        <div
          class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm max-w-full"
        >
          <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-sm">
              <thead class="bg-slate-50 dark:bg-neutral-950/60">
                <tr class="text-left text-slate-600 dark:text-neutral-300">
                  <th class="px-4 py-3 font-semibold w-[46px]">
                    <input
                      ref="headerCheckbox"
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                      :checked="isAllSelected"
                      :aria-checked="headerAriaChecked"
                      @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"
                    />
                  </th>
                  <th class="px-4 py-3 font-semibold">Corporativo</th>
                  <th class="px-4 py-3 font-semibold">RFC</th>
                  <th class="px-4 py-3 font-semibold">Contacto</th>
                  <th class="px-4 py-3 font-semibold">Código</th>
                  <th class="px-4 py-3 font-semibold">Estatus</th>
                  <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="row in corporativos.data"
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
                    <div class="flex items-center gap-3 min-w-0">
                      <div
                        class="h-10 w-10 rounded-2xl border border-slate-200/70 dark:border-white/10 overflow-hidden
                               bg-slate-50 dark:bg-neutral-950 grid place-items-center shrink-0"
                      >
                        <img
                          v-if="row.logo_path"
                          :src="logoSrc(row.logo_path)!"
                          class="h-full w-full object-cover"
                          alt="logo"
                          loading="lazy"
                        />
                        <span v-else class="text-[10px] font-bold text-slate-500 dark:text-neutral-400">
                          {{ row.nombre?.slice(0, 2)?.toUpperCase() }}
                        </span>
                      </div>

                      <div class="min-w-0">
                        <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                          {{ row.nombre }}
                        </div>
                        <div class="text-xs text-slate-500 dark:text-neutral-400">ID: {{ row.id }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">{{ row.rfc ?? '—' }}</td>

                  <td class="px-4 py-3 min-w-0">
                    <div class="text-slate-700 dark:text-neutral-200 break-all">{{ row.email ?? '—' }}</div>
                    <div class="text-xs text-slate-500 dark:text-neutral-400 break-all">{{ row.telefono ?? '—' }}</div>
                  </td>

                  <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">{{ row.codigo ?? '—' }}</td>

                  <td class="px-4 py-3">
                    <span
                      class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border
                             bg-slate-100 text-slate-700 border-slate-200
                             dark:bg-white/5 dark:text-neutral-200 dark:border-white/10"
                    >
                      <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'" />
                      {{ row.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>

                  <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                      <button
                        type="button"
                        @click="openEdit(row)"
                        class="rounded-xl px-3 py-2 text-xs font-semibold
                               bg-slate-100 text-slate-800 hover:bg-slate-200
                               dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                               transition active:scale-[0.98]"
                      >
                        Editar
                      </button>

                      <button
                        type="button"
                        @click="confirmDelete(row)"
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

                <tr v-if="!corporativos.data?.length">
                  <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">
                    No hay corporativos con los filtros actuales.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                   border-t border-slate-200/70 dark:border-white/10
                   px-4 py-3 bg-white dark:bg-neutral-900"
          >
            <div class="text-xs text-slate-600 dark:text-neutral-300">
              Mostrando <span class="font-semibold">{{ corporativos.meta.from ?? 0 }}</span> a
              <span class="font-semibold">{{ corporativos.meta.to ?? 0 }} registros por página</span> de
              <span class="font-semibold">{{ corporativos.meta.total }} registros</span>
            </div>

            <AppPagination :links="paginationLinks" @go="goTo" />
          </div>
        </div>

        <!-- CARDS EN MÓVIL + TABLET (hasta lg-1) -->
        <div class="lg:hidden grid gap-3 max-w-full">
          <div
            v-for="row in corporativos.data"
            :key="row.id"
            class="w-full max-w-full min-w-0 overflow-hidden
                   rounded-2xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-4"
          >
            <div class="flex items-start gap-3 min-w-0">
              <div class="pt-1 shrink-0">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                  :checked="selectedIds.has(row.id)"
                  @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
                />
              </div>

              <div
                class="h-12 w-12 rounded-2xl border border-slate-200/70 dark:border-white/10 overflow-hidden
                       bg-slate-50 dark:bg-neutral-950 grid place-items-center shrink-0"
              >
                <img
                  v-if="row.logo_path"
                  :src="logoSrc(row.logo_path)!"
                  class="h-full w-full object-cover"
                  alt="logo"
                  loading="lazy"
                />
                <span v-else class="text-xs font-black text-slate-500 dark:text-neutral-400">
                  {{ row.nombre?.slice(0, 2)?.toUpperCase() }}
                </span>
              </div>

              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2 min-w-0">
                  <div class="min-w-0">
                    <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                      {{ row.nombre }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-500 dark:text-neutral-400">ID: {{ row.id }}</div>
                  </div>

                  <span
                    class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border
                           bg-slate-100 text-slate-700 border-slate-200
                           dark:bg-white/5 dark:text-neutral-200 dark:border-white/10"
                  >
                    <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'" />
                    {{ row.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>

                <div class="mt-3 grid gap-1 text-sm text-slate-700 dark:text-neutral-200">
                  <div class="text-xs break-all"><span class="font-semibold">RFC:</span> {{ row.rfc ?? '—' }}</div>
                  <div class="text-xs break-all"><span class="font-semibold">Código:</span> {{ row.codigo ?? '—' }}</div>
                  <div class="text-xs break-all"><span class="font-semibold">Email:</span> {{ row.email ?? '—' }}</div>
                  <div class="text-xs break-all"><span class="font-semibold">Tel:</span> {{ row.telefono ?? '—' }}</div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                  <button
                    type="button"
                    @click="openEdit(row)"
                    class="rounded-xl px-3 py-2 text-xs font-semibold
                           bg-slate-100 text-slate-800 hover:bg-slate-200
                           dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                           transition active:scale-[0.98]"
                  >
                    Editar
                  </button>

                  <button
                    type="button"
                    @click="confirmDelete(row)"
                    class="rounded-xl px-3 py-2 text-xs font-semibold
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

          <div
            v-if="!corporativos.data?.length"
            class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400"
          >
            No hay corporativos con los filtros actuales.
          </div>

          <!-- Paginación móvil/tablet (sin AppPagination) -->
          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4 overflow-hidden">
            <div class="flex items-center justify-between mb-3 gap-2">
              <div class="text-xs text-slate-600 dark:text-neutral-300">
                <span class="font-semibold">{{ corporativos.meta.from ?? 0 }}</span> -
                <span class="font-semibold">{{ corporativos.meta.to ?? 0 }}</span> /
                <span class="font-semibold">{{ corporativos.meta.total }}</span>
              </div>

              <button
                type="button"
                class="text-xs font-semibold text-slate-700 hover:text-slate-900 dark:text-neutral-300 dark:hover:text-white shrink-0"
                @click="toggleAllOnPage(!isAllSelected)"
              >
                {{ isAllSelected ? 'Quitar' : 'Seleccionar' }}
              </button>
            </div>

            <div class="flex flex-wrap gap-2 max-w-full">
              <button
                v-for="(l, idx) in mobileLinks"
                :key="idx"
                type="button"
                @click="goTo(l.url)"
                :disabled="!l.url"
                class="px-3 py-2 text-xs font-semibold rounded-xl border
                       max-w-full min-w-0
                       disabled:opacity-50 disabled:cursor-not-allowed
                       transition active:scale-[0.98]"
                :class="
                  l.active
                    ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'
                "
              >
                {{ linkLabelShort(l.label) }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
