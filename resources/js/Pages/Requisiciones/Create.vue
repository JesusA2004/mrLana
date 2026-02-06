<!-- resources/js/Pages/Requisiciones/Create.vue -->
<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DatePickerShadcn from '@/Components/ui/DatePickerShadcn.vue'

import { useRequisicionCreate } from './useRequisicionCreate'
import type { Catalogos } from './Requisiciones.types'

const page = usePage<any>()
const catalogos = (page.props as any)?.catalogos as Catalogos
const plantilla = (page.props as any)?.plantilla ?? null

const {
  state,
  items,
  corporativosActive,
  sucursalesFiltered,
  empleadosActive,
  conceptosActive,
  proveedoresList,
  selectedProveedor,
  addItem,
  removeItem,
  saveDraft,
  sendRequi,
  money,
  role,
  saving,
  showError,
  fieldError,
} = useRequisicionCreate(catalogos, plantilla)
</script>

<template>
  <Head title="Nueva requisición" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Nueva requisición</h2>
        <div v-if="saving" class="text-xs font-semibold text-slate-500 dark:text-neutral-400">Procesando…</div>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <form class="space-y-6" @submit.prevent>
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6 space-y-4">
          <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Datos generales</h3>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <SearchableSelect
                v-model="state.corporativo_id"
                :options="corporativosActive"
                label="Comprador"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar corporativo..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
                valueKey="id"
              />
              <p v-if="fieldError('comprador_corp_id') || (showError && !state.corporativo_id)" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('comprador_corp_id') || 'Selecciona un comprador.' }}
              </p>
            </div>

            <div>
              <SearchableSelect
                v-model="state.sucursal_id"
                :options="sucursalesFiltered"
                label="Sucursal"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar sucursal..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
                valueKey="id"
                :disabled="!state.corporativo_id"
              />
              <p v-if="fieldError('sucursal_id') || (showError && !state.sucursal_id)" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('sucursal_id') || 'Selecciona una sucursal.' }}
              </p>
              <p v-if="!state.corporativo_id" class="mt-1 text-[11px] text-slate-500 dark:text-neutral-400">
                Primero elige un corporativo para ver sus sucursales.
              </p>
            </div>

            <div>
              <SearchableSelect
                v-model="state.solicitante_id"
                :options="empleadosActive"
                label="Solicitante"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar solicitante..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
                valueKey="id"
                :disabled="role === 'COLABORADOR'"
              />
              <p v-if="fieldError('solicitante_id') || (showError && !state.solicitante_id)" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('solicitante_id') || 'Selecciona un solicitante.' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <SearchableSelect
                v-model="state.concepto_id"
                :options="conceptosActive"
                label="Concepto"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar concepto..."
                :allowNull="false"
                rounded="2xl"
                labelKey="nombre"
                valueKey="id"
              />
              <p v-if="fieldError('concepto_id') || (showError && !state.concepto_id)" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('concepto_id') || 'Selecciona un concepto.' }}
              </p>
            </div>

            <div>
              <SearchableSelect
                v-model="state.proveedor_id"
                :options="proveedoresList"
                label="Proveedor"
                placeholder="Seleccione..."
                searchPlaceholder="Buscar proveedor..."
                :allowNull="true"
                nullLabel="—"
                rounded="2xl"
                labelKey="razon_social"
                valueKey="id"
              />
              <p v-if="fieldError('proveedor_id')" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('proveedor_id') }}
              </p>
            </div>

            <div>
              <DatePickerShadcn v-model="state.fecha_solicitud" label="Fecha de solicitud" placeholder="Selecciona fecha" />
              <p v-if="fieldError('fecha_solicitud') || (showError && !state.fecha_solicitud)" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('fecha_solicitud') || 'La fecha de solicitud es obligatoria.' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" v-if="role !== 'COLABORADOR'">
            <div>
              <DatePickerShadcn v-model="state.fecha_autorizacion" label="Fecha de autorización (opcional)" placeholder="Selecciona fecha" />
              <p v-if="fieldError('fecha_autorizacion')" class="mt-1 text-xs text-rose-600 dark:text-rose-400">
                {{ fieldError('fecha_autorizacion') }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Observaciones</label>
              <textarea
                v-model="state.observaciones"
                rows="2"
                class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white
                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100"
              />
            </div>
          </div>

          <div v-if="selectedProveedor" class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
            <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Datos del proveedor</div>
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
              <div class="text-slate-700 dark:text-neutral-200">
                <span class="text-slate-500 dark:text-neutral-400">Razón social:</span>
                <span class="font-semibold"> {{ selectedProveedor.razon_social }}</span>
              </div>
              <div class="text-slate-700 dark:text-neutral-200">
                <span class="text-slate-500 dark:text-neutral-400">RFC:</span>
                <span class="font-semibold"> {{ selectedProveedor.rfc }}</span>
              </div>
              <div class="text-slate-700 dark:text-neutral-200">
                <span class="text-slate-500 dark:text-neutral-400">CLABE:</span>
                <span class="font-semibold"> {{ selectedProveedor.clabe }}</span>
              </div>
              <div class="text-slate-700 dark:text-neutral-200">
                <span class="text-slate-500 dark:text-neutral-400">Banco:</span>
                <span class="font-semibold"> {{ selectedProveedor.banco }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Items de la requisición</h3>
            <button
              type="button"
              @click="addItem"
              class="rounded-2xl px-4 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700
              dark:bg-emerald-500 dark:hover:bg-emerald-600 transition active:scale-[0.99]"
            >
              Agregar item
            </button>
          </div>

          <p v-if="fieldError('detalles') || (showError && items.length === 0)" class="text-xs text-rose-600 dark:text-rose-400">
            {{ fieldError('detalles') || 'Agrega al menos un item.' }}
          </p>

          <div v-if="items.length > 0" class="space-y-3">
            <div
              v-for="(item, index) in items"
              :key="index"
              class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4 grid grid-cols-1 sm:grid-cols-7 gap-2"
            >
              <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Cantidad</label>
                <input v-model.number="item.cantidad" type="number" min="0" step="0.01"
                  class="w-full rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white
                  dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100" />
              </div>

              <div class="sm:col-span-2">
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Descripción</label>
                <input v-model="item.descripcion" type="text"
                  class="w-full rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white
                  dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100" />
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Precio unitario</label>
                <input v-model.number="item.precio_unitario" type="number" min="0" step="0.01"
                  class="w-full rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white
                  dark:border-white/10 dark:bg-neutral-900 dark:text-neutral-100" />
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Genera IVA</label>
                <input v-model="item.genera_iva" type="checkbox"
                  class="mt-2 h-4 w-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" />
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Subtotal</label>
                <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-neutral-100">{{ money(item.subtotal) }}</div>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">IVA</label>
                <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-neutral-100">{{ money(item.iva) }}</div>
              </div>

              <div class="flex items-center justify-between sm:justify-center gap-2">
                <div>
                  <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-400">Total</label>
                  <div class="mt-1 text-sm font-extrabold text-slate-900 dark:text-neutral-100">{{ money(item.total) }}</div>
                </div>
                <button type="button" @click="removeItem(index)"
                  class="rounded-full p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition"
                  aria-label="Eliminar item">
                  X
                </button>
              </div>
            </div>
          </div>

          <div v-else class="text-center text-sm text-slate-500 dark:text-neutral-400">
            Agrega items para comenzar.
          </div>

          <div class="text-right mt-4">
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Subtotal: <span class="font-bold">{{ money(state.monto_subtotal) }}</span>
            </div>
            <div class="text-sm text-slate-600 dark:text-neutral-300">
              Total: <span class="font-bold">{{ money(state.monto_total) }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3">
          <SecondaryButton type="button" @click="$inertia.visit(route('requisiciones.index'))" class="rounded-2xl">
            Cancelar
          </SecondaryButton>

          <button type="button" @click="saveDraft"
            class="rounded-2xl px-4 py-3 text-sm font-extrabold bg-slate-900 text-white hover:bg-slate-800
            dark:bg-neutral-800 dark:hover:bg-neutral-700 transition active:scale-[0.99] disabled:opacity-60"
            :disabled="saving">
            Guardar como borrador
          </button>

          <button type="button" @click="sendRequi"
            class="rounded-2xl px-4 py-3 text-sm font-extrabold bg-emerald-600 text-white hover:bg-emerald-700
            dark:bg-emerald-500 dark:hover:bg-emerald-600 transition active:scale-[0.99] disabled:opacity-60"
            :disabled="saving">
            Enviar requisición
          </button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
