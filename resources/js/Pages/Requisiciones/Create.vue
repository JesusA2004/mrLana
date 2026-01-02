<script setup lang="ts">
    import { Head, router } from '@inertiajs/vue3'
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

    import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
    import { IconUserPlus } from '@tabler/icons-vue';
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
                <h2 class="text-xl font-extrabold text-slate-900
                dark:text-neutral-100 truncate">
                    Nueva requisición
                </h2>
                <span v-if="savedDraftOnce" class="req-chip req-chip--ok hidden sm:inline-flex">BORRADOR OK</span>
            </div>
        </template>

        <div class="req-create">
            <div class="w-full min-w-0 px-3 sm:px-6 xl:px-8 py-4 sm:py-6">
                <div class="req-card">
                    <transition name="reqFadeUp">
                        <div v-if="banner" class="req-banner"
                        :class="`req-banner--${banner.type}`">
                            <div class="req-banner__title">{{ banner.title }}</div>
                            <div class="req-banner__text">{{ banner.text }}</div>
                        </div>
                    </transition>

                    <!-- CABECERA (2 filas en lg+) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 relative z-[30000]">
                    <!-- FILA 1 -->
                        <!-- Sucursal -->
                        <div class="lg:col-span-3 relative z-[10]">
                            <template v-if="isColab">
                                <label class="req-label">Sucursal</label>
                                <div class="req-readonly mt-1 h-12 flex items-center gap-2">
                                    <span class="req-dot req-dot--emerald"></span>
                                    <span class="truncate font-extrabold">{{ sucursalName || '—' }}</span>
                                </div>
                                <p class="req-error min-h-[14px]"><span v-if="errors.sucursal_id">{{ errors.sucursal_id }}</span></p>
                            </template>

                            <template v-else>
                                <SearchableSelect v-model="form.sucursal_id"
                                :options="sucursalesActive" label="Sucursal"
                                placeholder="Seleccione una sucursal..."
                                searchPlaceholder="Buscar sucursal..."
                                :allowNull="true"
                                nullLabel="Seleccione una sucursal..."
                                rounded="2xl" zIndexClass="z-[20000]"
                                labelKey="nombre" secondaryKey="codigo"/>
                                <p class="req-error min-h-[14px]"><span v-if="errors.sucursal_id">{{ errors.sucursal_id }}</span></p>
                            </template>
                        </div>

                        <!-- Proveedor -->
                        <div class="lg:col-span-5">
                            <div class="flex items-end gap-2">
                                <div class="flex-1 min-w-0">
                                    <SearchableSelect v-model="form.proveedor_id"
                                    :options="proveedoresActive" label="Proveedor"
                                    placeholder="Seleccione uno..."
                                    searchPlaceholder="Buscar proveedor..."
                                    :allowNull="true" nullLabel="Seleccione uno..."
                                    rounded="2xl" zIndexClass="z-[20000]"
                                    labelKey="nombre_comercial" secondaryKey="id"/>
                                </div>
                                <button type="button" @click="openProveedorModal"
                                class="inline-flex items-center justify-center
                                h-12 w-12 rounded-3xl bg-white text-black
                                hover:bg-slate-100 active:scale-[0.97]
                                border border-slate-200
                                dark:border-white/20
                                transition-all duration-150
                                dark:bg-neutral-900 dark:text-white
                                dark:hover:bg-zinc-600 focus:outline-none
                                focus-visible:ring-2 focus-visible:ring-slate-400
                                dark:focus-visible:ring-neutral-500"
                                aria-label="Agregar proveedor">
                                    <IconUserPlus :size="22" stroke-width="1.5"/>
                                </button>
                            </div>
                            <p class="req-error min-h-[14px]"><span v-if="errors.proveedor_id">{{ errors.proveedor_id }}</span></p>
                        </div>

                        <!-- Solicitante -->
                        <div class="lg:col-span-4">
                            <template v-if="isColab">
                            <label class="req-label">Solicitante</label>
                            <div class="req-readonly mt-1 h-12 flex items-center gap-2">
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

                    <!-- FILA 2 -->
                        <!-- Comprador readonly -->
                        <div class="lg:col-span-3 lg:col-start-1">
                            <label class="req-label">Comprador</label>
                            <div class="req-readonly mt-1 h-12 flex items-center">
                            <span class="truncate">{{ corporativoName || 'El corporativo se carga de acuerdo a la sucursal' }}</span>
                            </div>
                            <p class="req-error min-h-[14px]">
                            <span v-if="errors.comprador_corp_id">{{ errors.comprador_corp_id }}</span>
                            </p>
                        </div>

                        <!-- Concepto -->
                        <div class="lg:col-span-4">
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

                        <!-- Espacio libre en fila 2 (opcional) -->
                        <div class="hidden xl:block lg:col-span-5"></div>
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
                                    <input :value="draft.cantidad"
                                    @input="onDraftQtyInput"
                                    @keydown="allowKeyNumericInteger"
                                    @beforeinput="beforeInputInteger"
                                    @paste="onPasteInteger"
                                    type="text" inputmode="numeric"
                                    autocomplete="off"
                                    class="req-cap-input text-left"/>
                                    <p class="req-error min-h-[14px]"><span v-if="errors.draft_cantidad">{{ errors.draft_cantidad }}</span></p>
                                </div>

                                <div class="req-field req-field--grow">
                                    <label class="req-cap-label">Descripción</label>
                                    <input v-model="draft.descripcion" type="text" class="req-cap-input" />
                                    <p class="req-error min-h-[14px]">
                                        <span v-if="errors.draft_descripcion">
                                            {{ errors.draft_descripcion }}
                                        </span>
                                    </p>
                                </div>

                                <div class="req-field">
                                    <label class="req-cap-label">Precio Unitario (Sin IVA)</label>
                                    <input :value="draft.precio_unitario_sin_iva"
                                    @input="onDraftPriceInput"
                                    @keydown="allowKeyNumericDecimal"
                                    @beforeinput="beforeInputDecimal"
                                    @paste="onPasteDecimal" type="text"
                                    inputmode="decimal" autocomplete="off"
                                    class="req-cap-input text-left"/>
                                    <p class="req-error min-h-[14px]">
                                        <span v-if="errors.draft_precio">{{ errors.draft_precio }}</span>
                                    </p>
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
                            <div class="flex flex-col gap-2 sm:flex-row
                            sm:items-center sm:justify-between sm:gap-3">
                                <div class="font-extrabold text-slate-900 dark:text-neutral-100">Carrito</div>
                            </div>
                        </div>

                        <div class="req-table__body">
                            <div v-if="computedRows.length === 0"
                            class="req-empty">Aún no hay elementos.</div>

                            <div v-else>
                                <div v-for="(r0, idx) in computedRows" :key="`row-${idx}`" class="req-row">
                                    <template v-if="editIdx === idx">
                                        <div class="grid grid-cols-12 gap-2 items-start">
                                            <div class="col-span-2">
                                                <label class="req-label">Cantidad</label>
                                                <input :value="editDraft.cantidad"
                                                @input="onEditQtyInput"
                                                @keydown="allowKeyNumericInteger"
                                                @beforeinput="beforeInputInteger"
                                                @paste="onPasteInteger"
                                                type="text" inputmode="numeric"
                                                class="req-input mt-1 text-left"/>
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
                                                type="text" inputmode="decimal"
                                                class="req-input mt-1 text-right"/>
                                            </div>

                                            <div class="col-span-2 flex items-end justify-end gap-2">
                                                <button type="button" class="req-btn req-btn-ghost px-4 py-2" @click="cancelEdit">Cancelar</button>
                                                <button type="button" class="req-btn req-btn-success px-4 py-2" @click="saveEdit">Guardar</button>
                                            </div>
                                        </div>
                                    </template>

                                    <template v-else>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 text-sm items-start">
                                            <!-- Cantidad + Desc -->
                                            <div class="sm:col-span-7 flex gap-3 min-w-0">
                                                <div class="w-8 shrink-0 font-extrabold text-slate-900 dark:text-neutral-100">
                                                {{ r0.cantidad }}
                                                </div>

                                                <div class="min-w-0 text-slate-800 dark:text-neutral-200">
                                                    <div class="font-extrabold truncate">{{ r0.descripcion }}</div>
                                                    <div class="text-[11px] text-slate-500 dark:text-neutral-400">
                                                        {{ r0.no_genera_iva ? 'Sin IVA' : 'Con IVA 16%' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Montos (en móvil en 3 filas, en sm+ en columnas) -->
                                            <div class="sm:col-span-5">
                                                <div class="grid grid-cols-3 sm:grid-cols-3 gap-2">
                                                    <div class="text-left sm:text-right">
                                                        <div class="text-[11px] text-slate-500 dark:text-neutral-400">Sub</div>
                                                        <div class="font-extrabold text-slate-900 dark:text-neutral-100 tabular-nums">
                                                            {{ money(r0.subtotal) }}
                                                        </div>
                                                    </div>

                                                    <div class="text-left sm:text-right">
                                                        <div class="text-[11px] text-slate-500 dark:text-neutral-400">IVA</div>
                                                        <div class="font-extrabold text-slate-900 dark:text-neutral-100 tabular-nums">
                                                            {{ money(r0.iva) }}
                                                        </div>
                                                    </div>

                                                    <div class="text-left sm:text-right">
                                                        <div class="text-[11px] text-slate-500 dark:text-neutral-400">Total</div>
                                                        <div class="font-black text-slate-900 dark:text-neutral-100 tabular-nums">
                                                            {{ money(r0.total) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Acciones -->
                                            <div class="sm:col-span-12 flex justify-end gap-2 mt-2">
                                                <button type="button" class="req-btn req-btn-ghost px-4 py-2" @click="startEdit(idx)">Editar</button>
                                                <button type="button" class="req-btn req-btn-danger px-4 py-2" @click="removeItem(idx)">Quitar</button>
                                            </div>
                                            </div>
                                    </template>
                                </div>

                                <!-- FOOTER TOTALES (abajo de todos los items) -->
                                <div class="mt-2 pt-3 border-t border-slate-200/70 dark:border-white/10">
                                    <div class="flex flex-col sm:flex-row gap-3 sm:pr-6 sm:items-end sm:justify-end">
                                        <div class="text-center">
                                            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-neutral-400">Subtotal</div>
                                            <div class="font-black tabular-nums text-base sm:text-lg text-slate-900 dark:text-neutral-100">
                                                {{ money(subtotal) }}
                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-neutral-400">IVA</div>
                                            <div class="font-black tabular-nums text-base sm:text-lg text-slate-900 dark:text-neutral-100">
                                                {{ money(ivaTotal) }}
                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <div class="text-[11px] sm:text-xs text-slate-500 dark:text-neutral-400">Total</div>
                                            <div class="font-black tabular-nums text-lg sm:text-lg text-slate-900 dark:text-neutral-100">
                                                {{ money(total) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FECHA ENTREGA -->
                    <div class="req-section mt-6">
                        <div class="grid lg:grid-cols-12 gap-8 items-start">
                            <div class="lg:col-span-5">
                                <label class="req-label" for="textA">Observaciones</label>
                                <textarea id="textA" v-model="form.observaciones" rows="2" class="req-input mt-1" placeholder="Notas..." />
                            </div>

                            <div class="lg:col-span-4">
                                <label class="req-label" for="fechaE">Fecha de entrega</label>
                                <Popover v-model:open="deliveryOpen">
                                    <PopoverTrigger as-child>
                                        <button type="button" class="req-datebtn mt-2"
                                        id="fechaE">
                                            <CalendarIcon class="h-5 w-5 opacity-80"/>
                                            <span class="truncate">{{ deliveryLabel }}</span>
                                        </button>
                                    </PopoverTrigger>

                                    <PopoverContent class="p-2 w-auto" align="start">
                                        <Calendar v-model="deliveryDateModel"
                                        mode="single" locale="es"
                                        :week-starts-on="1" :initial-focus="true"/>
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div class="lg:col-span-3 flex gap-6 self-center">
                                <SecondaryButton class="rounded-2xl" @click="router.visit(props.routes.index)">
                                    Volver
                                </SecondaryButton>

                                <button type="button" @click="submit"
                                :disabled="!canSubmit"
                                class="req-btn req-btn-success px-6 py-2">
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
                                        <div class="p-4 sm:p-6 sticky top-0
                                        bg-white/95 dark:bg-neutral-900/95
                                        backdrop-blur border-b border-slate-200/70
                                        dark:border-white/10 z-10">
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
                                                    <input v-model="provForm.cuenta"
                                                    type="text"
                                                    class="req-input mt-1"
                                                    @keydown="allowKeyNumericInteger"
                                                    @beforeinput="beforeInputInteger"
                                                    @paste="onPasteInteger"
                                                    inputmode="numeric"/>
                                                    <p v-if="provErrors.cuenta" class="req-error">{{ provErrors.cuenta }}</p>
                                                </div>

                                                <div>
                                                    <label class="req-label">CLABE *</label>
                                                    <input v-model="provForm.clabe"
                                                    type="text" class="req-input mt-1"
                                                    @keydown="allowKeyNumericInteger"
                                                    @beforeinput="beforeInputInteger"
                                                    @paste="onPasteInteger"
                                                    inputmode="numeric"/>
                                                    <p v-if="provErrors.clabe" class="req-error">{{ provErrors.clabe }}</p>
                                                </div>
                                            </div>

                                            <div class="mt-6 flex items-center justify-end gap-2">
                                                <SecondaryButton class="rounded-2xl" @click="closeProveedorModal">Cancelar</SecondaryButton>

                                                <button type="button"
                                                @click="createProveedor"
                                                :disabled="provSaving"
                                                class="req-btn req-btn-success px-5 py-3">
                                                {{ provSaving ? 'Creando...' : 'Crear' }}
                                                </button>
                                            </div>
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
