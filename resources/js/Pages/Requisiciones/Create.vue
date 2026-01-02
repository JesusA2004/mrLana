<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

import { Calendar } from '@/Components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { Calendar as CalendarIcon } from 'lucide-vue-next'

import '@/css/requisiciones-create.css'

import type { CreateProps } from './useRequisicionCreate'
import { useRequisicionCreate } from './useRequisicionCreate'

const props = defineProps<CreateProps>()

const {
  isColab,

  sucursalesActive,
  conceptosActive,
  proveedoresActive,
  empleadosForUI,

  form,
  corporativoName,
  sucursalName,
  solicitanteName,

  deliveryDateModel,
  deliveryLabel,
  deliveryOpen,

  draft,
  computedRows,
  subtotal,
  ivaTotal,
  total,

  saving,
  errors,
  banner,
  savedDraftOnce,
  canSubmit,

  submit,
  addItem,
  removeItem,
  startEdit,
  cancelEdit,
  saveEdit,
  editIdx,
  editDraft,
  onDraftQtyInput,
  onDraftPriceInput,
  onEditQtyInput,
  onEditPriceInput,

  provModalOpen,
  provSaving,
  provErrors,
  provForm,
  openProveedorModal,
  closeProveedorModal,
  createProveedor,

  money,

  allowKeyNumericInteger,
  allowKeyNumericDecimal,
  beforeInputInteger,
  beforeInputDecimal,
  onPasteInteger,
  onPasteDecimal,
} = useRequisicionCreate(props)
</script>

<template>
  <Head title="Nueva requisición" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <h2 class="text-xl font-extrabold text-slate-900 dark:text-neutral-100 truncate">
          Nueva requisición
        </h2>
        <span v-if="savedDraftOnce" class="req-chip req-chip--ok hidden sm:inline-flex">BORRADOR OK</span>
      </div>
    </template>

    <div class="req-create">
      <div class="w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="req-card">
          <transition name="reqFadeUp">
            <div v-if="banner" class="req-banner" :class="`req-banner--${banner.type}`">
              <div class="req-banner__title">{{ banner.title }}</div>
              <div class="req-banner__text">{{ banner.text }}</div>
            </div>
          </transition>

          <!-- CABECERA -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 relative z-[30000]">
              <!-- Comprador readonly -->
              <div class="lg:col-span-3">
                <label class="req-label">Comprador</label>
                <div class="req-readonly mt-1 h-12 flex items-center">
                    <span class="truncate">{{ corporativoName || 'El corporativo se carga de acuerdo a la sucursal' }}</span>
                </div>
                <p class="req-error min-h-[14px]">
                  <span v-if="errors.comprador_corp_id">{{ errors.comprador_corp_id }}</span>
                </p>
              </div>

              <!-- Solicitante -->
              <div class="lg:col-span-3">
                <template v-if="isColab">
                  <label class="req-label">Solicitante</label>
                  <div class="req-readonly mt-1">
                    <span class="req-dot req-dot--indigo"></span>
                    <span class="truncate font-extrabold">{{ solicitanteName || '—' }}</span>
                  </div>
                  <p class="req-error min-h-[14px]"><span v-if="errors.solicitante_id">{{ errors.solicitante_id }}</span></p>
                </template>

                <template v-else>
                  <SearchableSelect
                    v-model="form.solicitante_id"
                    :options="empleadosForUI"
                    label="Solicitante"
                    placeholder="Seleccione uno..."
                    searchPlaceholder="Buscar empleado..."
                    :allowNull="true"
                    nullLabel="Seleccione uno..."
                    rounded="2xl"
                    zIndexClass="z-[20000]"
                    labelKey="nombre"
                    secondaryKey="puesto"
                    groupBy="group"
                  />
                  <p class="req-error min-h-[14px]"><span v-if="errors.solicitante_id">{{ errors.solicitante_id }}</span></p>
                </template>
              </div>

              <!-- Proveedor -->
              <div class="lg:col-span-4">
                <div class="flex items-end gap-2">
                  <div class="flex-1 min-w-0">
                    <SearchableSelect
                      v-model="form.proveedor_id"
                      :options="proveedoresActive"
                      label="Proveedor"
                      placeholder="Seleccione uno..."
                      searchPlaceholder="Buscar proveedor..."
                      :allowNull="true"
                      nullLabel="Seleccione uno..."
                      rounded="2xl"
                      zIndexClass="z-[20000]"
                      labelKey="nombre_comercial"
                      secondaryKey="id"
                    />
                  </div>
                  <button type="button" @click="openProveedorModal" class="req-btn req-btn-dark h-12 px-4">
                    Nuevo
                  </button>
                </div>
                <p class="req-error min-h-[14px]"><span v-if="errors.proveedor_id">{{ errors.proveedor_id }}</span></p>
              </div>

              <!-- Concepto -->
              <div class="lg:col-span-2">
                <SearchableSelect
                  v-model="form.concepto_id"
                  :options="conceptosActive"
                  label="Concepto"
                  placeholder="Seleccione uno..."
                  searchPlaceholder="Buscar concepto..."
                  :allowNull="true"
                  nullLabel="Seleccione uno..."
                  rounded="2xl"
                  zIndexClass="z-[20000]"
                  labelKey="nombre"
                  secondaryKey="id"
                />
                <p class="req-error min-h-[14px]"><span v-if="errors.concepto_id">{{ errors.concepto_id }}</span></p>
              </div>
            </div>

            <!-- Sucursal -->
            <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-4 items-end relative z-[10]">
              <div class="lg:col-span-4">
                <template v-if="isColab">
                  <label class="req-label">Sucursal</label>
                  <div class="req-readonly mt-1 h-12 flex items-center gap-2">
                    <span class="req-dot req-dot--emerald"></span>
                    <span class="truncate font-extrabold">{{ sucursalName || '—' }}</span>
                  </div>
                  <p class="req-error min-h-[14px]"><span v-if="errors.sucursal_id">{{ errors.sucursal_id }}</span></p>
                </template>

                <template v-else>
                  <SearchableSelect
                    v-model="form.sucursal_id"
                    :options="sucursalesActive"
                    label="Sucursal"
                    placeholder="Seleccione una sucursal..."
                    searchPlaceholder="Buscar sucursal..."
                    :allowNull="true"
                    nullLabel="Seleccione una sucursal..."
                    rounded="2xl"
                    zIndexClass="z-[20000]"
                    labelKey="nombre"
                    secondaryKey="codigo"
                  />
                  <p class="req-error min-h-[14px]"><span v-if="errors.sucursal_id">{{ errors.sucursal_id }}</span></p>
                </template>
              </div>

              <div class="lg:col-span-8">
                <label class="req-label">Observaciones</label>
                <textarea v-model="form.observaciones" rows="2" class="req-input mt-1" placeholder="Notas..." />
              </div>
            </div>

          <!-- CAPTURA ITEM -->
          <div class="req-capture mt-5">
            <div class="req-capture__head">
              <div class="req-capture__title"><span class="req-spark"></span> Captura nuevo elemento</div>
              <div class="req-capture__subtitle">Precio obligatorio y mayor a 0. Cantidad sin letras.</div>
            </div>

            <div class="req-capture__body">
              <div class="req-capture-grid">
                <div class="req-field">
                  <label class="req-cap-label">Cantidad</label>
                  <input
                    :value="draft.cantidad"
                    @input="onDraftQtyInput"
                    @keydown="allowKeyNumericInteger"
                    @beforeinput="beforeInputInteger"
                    @paste="onPasteInteger"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    class="req-cap-input text-left"
                  />
                  <p class="req-error min-h-[14px]"><span v-if="errors.draft_cantidad">{{ errors.draft_cantidad }}</span></p>
                </div>

                <div class="req-field req-field--grow">
                  <label class="req-cap-label">Descripción</label>
                  <input v-model="draft.descripcion" type="text" class="req-cap-input" />
                  <p class="req-error min-h-[14px]"><span v-if="errors.draft_descripcion">{{ errors.draft_descripcion }}</span></p>
                </div>

                <div class="req-field">
                  <label class="req-cap-label">Precio Unitario (Sin IVA)</label>
                  <input
                    :value="draft.precio_unitario_sin_iva"
                    @input="onDraftPriceInput"
                    @keydown="allowKeyNumericDecimal"
                    @beforeinput="beforeInputDecimal"
                    @paste="onPasteDecimal"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    class="req-cap-input text-left"
                  />
                  <p class="req-error min-h-[14px]"><span v-if="errors.draft_precio">{{ errors.draft_precio }}</span></p>
                </div>

                <div class="req-field req-field--check">
                  <label class="req-cap-label opacity-0">IVA</label>
                  <label class="req-checkline">
                    <input v-model="draft.no_genera_iva" type="checkbox" class="req-check" />
                    <span>No genera IVA</span>
                  </label>
                  <p class="req-error min-h-[14px]"></p>
                </div>

                <div class="req-field req-field--action">
                  <button type="button" @click="addItem" class="req-btn req-btn-primary h-[44px] w-full">
                    Agregar
                  </button>
                  <p class="req-error min-h-[14px]"></p>
                </div>
              </div>

              <p v-if="errors.items" class="mt-3 text-xs text-rose-500">{{ errors.items }}</p>
            </div>
          </div>

          <!-- CARRITO -->
          <div class="req-table mt-6">
            <div class="req-table__head">
              <div class="flex items-center justify-between gap-3">
                <div class="font-extrabold text-slate-900 dark:text-neutral-100">Carrito</div>
                <div class="text-xs text-slate-600 dark:text-neutral-300">
                  Subtotal: <span class="font-extrabold">{{ money(subtotal) }}</span> · IVA:
                  <span class="font-extrabold">{{ money(ivaTotal) }}</span> · Total:
                  <span class="font-extrabold">{{ money(total) }}</span>
                </div>
              </div>
            </div>

            <div class="req-table__body">
              <div v-if="computedRows.length === 0" class="req-empty">Aún no hay elementos.</div>

              <div v-else>
                <div v-for="(r0, idx) in computedRows" :key="`row-${idx}`" class="req-row">
                  <template v-if="editIdx === idx">
                    <div class="grid grid-cols-12 gap-2 items-start">
                      <div class="col-span-2">
                        <label class="req-label">Cantidad</label>
                        <input
                          :value="editDraft.cantidad"
                          @input="onEditQtyInput"
                          @keydown="allowKeyNumericInteger"
                          @beforeinput="beforeInputInteger"
                          @paste="onPasteInteger"
                          type="text"
                          inputmode="numeric"
                          class="req-input mt-1 text-left"
                        />
                      </div>

                      <div class="col-span-4">
                        <label class="req-label">Descripción</label>
                        <input v-model="editDraft.descripcion" type="text" class="req-input mt-1" />
                      </div>

                      <div class="col-span-2">
                        <label class="req-label">IVA</label>
                        <label class="req-checkline mt-2">
                          <input v-model="editDraft.no_genera_iva" type="checkbox" class="req-check" />
                          <span>{{ editDraft.no_genera_iva ? 'Sin IVA' : 'Con IVA 16%' }}</span>
                        </label>
                      </div>

                      <div class="col-span-2">
                        <label class="req-label">P.U.</label>
                        <input
                          :value="editDraft.precio_unitario_sin_iva"
                          @input="onEditPriceInput"
                          @keydown="allowKeyNumericDecimal"
                          @beforeinput="beforeInputDecimal"
                          @paste="onPasteDecimal"
                          type="text"
                          inputmode="decimal"
                          class="req-input mt-1 text-right"
                        />
                      </div>

                      <div class="col-span-2 flex items-end justify-end gap-2">
                        <button type="button" class="req-btn req-btn-ghost px-4 py-2" @click="cancelEdit">Cancelar</button>
                        <button type="button" class="req-btn req-btn-success px-4 py-2" @click="saveEdit">Guardar</button>
                      </div>
                    </div>
                  </template>

                  <template v-else>
                    <div class="grid grid-cols-12 gap-2 text-sm items-start">
                      <div class="col-span-2 font-extrabold text-slate-900 dark:text-neutral-100">{{ r0.cantidad }}</div>
                      <div class="col-span-5 text-slate-800 dark:text-neutral-200">
                        <div class="font-extrabold">{{ r0.descripcion }}</div>
                        <div class="text-[11px] text-slate-500 dark:text-neutral-400">
                          {{ r0.no_genera_iva ? 'Sin IVA' : 'Con IVA 16%' }}
                        </div>
                      </div>
                      <div
                        class="col-span-2 text-right font-extrabold text-slate-900 dark:text-neutral-100"
                      >{{ money(r0.subtotal) }}</div>
                      <div class="col-span-1 text-right font-extrabold text-slate-900 dark:text-neutral-100">{{ money(r0.iva) }}</div>
                      <div class="col-span-2 text-right font-black text-slate-900 dark:text-neutral-100">{{ money(r0.total) }}</div>

                      <div class="col-span-12 flex justify-end gap-2 mt-2">
                        <button type="button" class="req-btn req-btn-ghost px-4 py-2" @click="startEdit(idx)">Editar</button>
                        <button type="button" class="req-btn req-btn-danger px-4 py-2" @click="removeItem(idx)">Quitar</button>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </div>

          <!-- FECHA ENTREGA -->
          <div class="req-section mt-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
              <div class="lg:col-span-4">
                <label class="req-label">Fecha de entrega</label>
                <Popover v-model:open="deliveryOpen">
                    <PopoverTrigger as-child>
                        <button type="button" class="req-datebtn mt-1">
                        <CalendarIcon class="h-4 w-4 opacity-80" />
                        <span class="truncate">{{ deliveryLabel }}</span>
                        </button>
                    </PopoverTrigger>

                    <PopoverContent class="p-2 w-auto" align="start">
                        <Calendar
                        v-model="deliveryDateModel"
                        mode="single"
                        locale="es"
                        :week-starts-on="1"
                        :initial-focus="true"
                        />
                    </PopoverContent>
                </Popover>
              </div>

              <div class="lg:col-span-8 flex items-end justify-end gap-2">
                <SecondaryButton class="rounded-2xl" @click="router.visit(props.routes.index)">
                  Volver
                </SecondaryButton>

                <button
                  type="button"
                  @click="submit"
                  :disabled="!canSubmit"
                  class="req-btn req-btn-success px-6 py-3"
                >
                  <span v-if="saving">{{ savedDraftOnce ? 'Capturando...' : 'Guardando borrador...' }}</span>
                  <span v-else>{{ savedDraftOnce ? 'Enviar (Capturar)' : 'Guardar borrador' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- MODAL PROVEEDOR -->
           <teleport to="body">
          <transition name="reqFadeUp">
            <div v-if="provModalOpen" class="fixed inset-0 z-[99999]">
              <div class="absolute inset-0 bg-black/50" @click="closeProveedorModal"></div>

              <div class="relative inset-0 flex items-center justify-center p-3 sm:p-6">
                <div class="w-full max-w-3xl req-modal max-h-[90vh] overflow-y-auto">
                    <div
                      class="p-4 sm:p-6 sticky top-0 bg-white/95 dark:bg-neutral-900/95 backdrop-blur border-b border-slate-200/70 dark:border-white/10 z-10"
                    >
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <div class="text-base font-black text-slate-900 dark:text-neutral-100">Nuevo proveedor</div>
                          <div class="text-xs text-slate-600 dark:text-neutral-300">Completa todo.</div>
                        </div>

                        <button type="button" @click="closeProveedorModal" class="req-btn req-btn-ghost px-3 py-2">
                          Cerrar
                        </button>
                      </div>
                    </div>

                    <div class="p-4 sm:p-6">
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                          <label class="req-label">Nombre Comercial *</label>
                          <input v-model="provForm.nombre_comercial" type="text" class="req-input mt-1" />
                          <p v-if="provErrors.nombre_comercial" class="req-error">{{ provErrors.nombre_comercial }}</p>
                        </div>

                        <div>
                          <label class="req-label">Razón Social *</label>
                          <input v-model="provForm.razon_social" type="text" class="req-input mt-1" />
                          <p v-if="provErrors.razon_social" class="req-error">{{ provErrors.razon_social }}</p>
                        </div>

                        <div class="sm:col-span-2">
                          <label class="req-label">Dirección *</label>
                          <input v-model="provForm.direccion" type="text" class="req-input mt-1" />
                          <p v-if="provErrors.direccion" class="req-error">{{ provErrors.direccion }}</p>
                        </div>

                        <div>
                          <label class="req-label">Contacto *</label>
                          <input v-model="provForm.contacto" type="text" class="req-input mt-1" />
                          <p v-if="provErrors.contacto" class="req-error">{{ provErrors.contacto }}</p>
                        </div>

                        <div>
                          <label class="req-label">RFC *</label>
                          <input v-model="provForm.rfc" type="text" class="req-input mt-1" />
                          <p v-if="provErrors.rfc" class="req-error">{{ provErrors.rfc }}</p>
                        </div>

                        <div>
                          <label class="req-label">Cuenta *</label>
                          <input
                            v-model="provForm.cuenta"
                            type="text"
                            class="req-input mt-1"
                            @keydown="allowKeyNumericInteger"
                            @beforeinput="beforeInputInteger"
                            @paste="onPasteInteger"
                            inputmode="numeric"
                          />
                          <p v-if="provErrors.cuenta" class="req-error">{{ provErrors.cuenta }}</p>
                        </div>

                        <div>
                          <label class="req-label">CLABE *</label>
                          <input
                            v-model="provForm.clabe"
                            type="text"
                            class="req-input mt-1"
                            @keydown="allowKeyNumericInteger"
                            @beforeinput="beforeInputInteger"
                            @paste="onPasteInteger"
                            inputmode="numeric"
                          />
                          <p v-if="provErrors.clabe" class="req-error">{{ provErrors.clabe }}</p>
                        </div>
                      </div>

                      <div class="mt-6 flex items-center justify-end gap-2">
                        <SecondaryButton class="rounded-2xl" @click="closeProveedorModal">Cancelar</SecondaryButton>

                        <button
                          type="button"
                          @click="createProveedor"
                          :disabled="provSaving"
                          class="req-btn req-btn-success px-5 py-3"
                        >
                          {{ provSaving ? 'Creando...' : 'Crear' }}
                        </button>
                      </div>
                      <div class="h-3"></div>
                    </div>
                </div>
              </div>
            </div>
          </transition>
          </teleport>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
