<!-- resources/js/Pages/Requisiciones/Create.vue -->
<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import type { Catalogos } from './Requisiciones.types'
import { useRequisicionCreate } from './useRequisicionCreate'

const page = usePage<any>()
const catalogos = (page.props as any)?.catalogos as Catalogos

// Si la página recibe una plantilla desde Inertia (por query), la cargamos aquí.
// Puedes ajustar `plantilla` para que sea null si no hay props.plantilla.
const plantilla = (page.props as any)?.plantilla ?? null

const { state, items, addItem, removeItem, save, money } = useRequisicionCreate(catalogos, plantilla)

// Helpers para selects: se muestran sólo activos
const corporativosActive = (catalogos.corporativos ?? []).filter((c) => c.activo !== false)
const sucursalesActive   = (catalogos.sucursales ?? []).filter((s) => s.activo !== false)
const empleadosActive    = (catalogos.empleados ?? []).filter((e) => e.activo !== false)
const conceptosActive    = (catalogos.conceptos ?? []).filter((c) => c.activo !== false)
const proveedoresList    = catalogos.proveedores ?? []
</script>

<template>
  <Head title="Crear requisición" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
        Nueva requisición
      </h2>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <form @submit.prevent="save" class="space-y-6">

        <!-- Cabecera -->
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6 space-y-4">
          <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Datos generales</h3>

          <!-- Tipo de requisición -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo</label>
              <select v-model="state.tipo" class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
                <option value="ANTICIPO">Anticipo</option>
                <option value="REEMBOLSO">Reembolso</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Sucursal</label>
              <SearchableSelect v-model="state.sucursal_id"
                :options="sucursalesActive"
                label="Sucursal"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar sucursal..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
                secondaryKey="codigo"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Solicitante</label>
              <SearchableSelect v-model="state.solicitante_id"
                :options="empleadosActive"
                label="Solicitante"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar empleado..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
                secondaryKey="puesto"
              />
            </div>
          </div>

          <!-- Segunda fila -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Concepto</label>
              <SearchableSelect v-model="state.concepto_id"
                :options="conceptosActive"
                label="Concepto"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar concepto..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Proveedor</label>
              <SearchableSelect v-model="state.proveedor_id"
                :options="proveedoresList"
                label="Proveedor"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar proveedor..."
                :allowNull="true"
                nullLabel="Sin proveedor"
                rounded="2xl"
                labelKey="nombre"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha de solicitud</label>
              <input v-model="state.fecha_solicitud" type="date" class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha de autorización</label>
              <input v-model="state.fecha_autorizacion" type="date" class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Observaciones</label>
              <textarea v-model="state.observaciones" rows="2" class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100"></textarea>
            </div>
          </div>
        </div>

        <!-- Detalles (carrito) -->
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Items de la requisición</h3>
            <button type="button" @click="addItem"
              class="rounded-2xl px-4 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
              Agregar item
            </button>
          </div>

          <div v-if="items.length > 0" class="space-y-3">
            <div v-for="(item, index) in items" :key="index"
              class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4 grid grid-cols-1 sm:grid-cols-6 gap-2">

              <div class="sm:col-span-1">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Cantidad</label>
                <input v-model.number="item.cantidad" type="number" min="0" step="0.01"
                  class="w-full rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100" />
              </div>

              <div class="sm:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Descripción</label>
                <input v-model="item.descripcion" type="text"
                  class="w-full rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100" />
              </div>

              <div class="sm:col-span-1">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Precio unitario</label>
                <input v-model.number="item.precio_unitario" type="number" min="0" step="0.01"
                  class="w-full rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100" />
              </div>

              <div class="sm:col-span-1">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Subtotal</label>
                <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-neutral-100">
                  {{ money(item.subtotal) }}
                </div>
              </div>

              <div class="sm:col-span-1">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">IVA</label>
                <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-neutral-100">
                  {{ money(item.iva) }}
                </div>
              </div>

              <div class="sm:col-span-1 flex items-center justify-between sm:justify-center gap-2">
                <div>
                  <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Total</label>
                  <div class="mt-1 text-sm font-extrabold text-slate-900 dark:text-neutral-100">
                    {{ money(item.total) }}
                  </div>
                </div>
                <button type="button" @click="removeItem(index)"
                  class="rounded-full p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                  ✕
                </button>
              </div>
            </div>
          </div>

          <div v-else class="text-center text-sm text-slate-500 dark:text-neutral-400">Agrega items para comenzar</div>

          <div class="text-right mt-4">
            <div class="text-sm text-slate-600 dark:text-neutral-300">Subtotal: <span class="font-bold">{{ money(state.monto_subtotal) }}</span></div>
            <div class="text-sm text-slate-600 dark:text-neutral-300">Total: <span class="font-bold">{{ money(state.monto_total) }}</span></div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-3">
          <SecondaryButton type="button" @click="$inertia.visit(route('requisiciones.index'))" class="rounded-2xl">Cancelar</SecondaryButton>
          <button type="submit"
            class="rounded-2xl px-4 py-3 text-sm font-extrabold bg-emerald-600 text-white hover:bg-emerald-700 transition active:scale-[0.99]">
            Guardar
          </button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
