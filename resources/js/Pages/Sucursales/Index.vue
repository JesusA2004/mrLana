<script setup lang="ts">
    /**
     * ==========================================================
     * Sucursales/Index.vue
     * ----------------------------------------------------------
     * Vista de listado con:
     * - Filtros (búsqueda, corporativo, estatus, perPage, orden A-Z/Z-A)
     * - Selección por fila + selección masiva por página
     * - Bulk delete (endpoint bulkDestroy)
     * - Responsive: tabla (lg+) y cards (mobile/tablet)
     * - Modal create/edit con validación inline
     * - Modo oscuro premium (Tailwind dark:)
     * ==========================================================
     */

    import { computed } from 'vue'
    import { Head } from '@inertiajs/vue3'
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

    import Modal from '@/Components/Modal.vue'
    import SearchableSelect from '@/Components/ui/SearchableSelect.vue'

    import AppInput from '@/Components/ui/AppInput.vue'
    import AppSelect from '@/Components/ui/AppSelect.vue'
    import AppPagination from '@/Components/ui/AppPagination.vue'

    import AppCheckbox from '@/Components/Checkbox.vue'

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

        corporativosForSelect,

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
    } = useSucursalesIndex(props)

    const pageRows = computed(() => props.sucursales?.data ?? [])
</script>

<template>
    <Head title="Sucursales" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
                Sucursales
            </h2>
        </template>

        <!-- Contenedor anti-overflow horizontal -->
        <div class="w-full max-w-full min-w-0 overflow-x-hidden">
            <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">

                <!-- =========================
                    Header operativo
                    ========================= -->
                <div
                    class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm px-4 py-4"
                >
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-slate-900 dark:text-neutral-100 truncate">
                            Administra tus sucursales
                        </h1>
                        <p class="text-xs text-slate-600 dark:text-neutral-300">
                            Filtra por corporativo, estatus y ordena A-Z / Z-A sin romper el layout.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0">
                        <!-- Acciones masivas -->
                        <div
                            v-if="selectedCount > 0"
                            class="flex flex-wrap items-center gap-2
                            rounded-2xl border border-slate-200/70 dark:border-white/10
                            bg-slate-50 dark:bg-neutral-950/40 px-3 py-2 min-w-0 max-w-full"
                        >
                            <div class="text-xs text-slate-700 dark:text-neutral-200">
                                Seleccionadas: <span class="font-semibold">{{ selectedCount }}</span>
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
                                @click="destroySelected"
                                class="rounded-xl px-3 py-1.5 text-xs font-semibold
                                bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                                dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                transition active:scale-[0.98]"
                            >
                                Eliminar
                            </button>
                        </div>

                        <!-- CTA -->
                        <button
                            type="button"
                            @click="openCreate"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                            bg-slate-900 text-white hover:bg-slate-800
                            dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                            transition active:scale-[0.98] w-full sm:w-auto"
                        >
                            Nueva
                        </button>
                    </div>
                </div>

                <!-- =========================
                    Filtros
                    ========================= -->
                <div
                    class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
                >
                    <div class="lg:col-span-4 min-w-0">
                        <AppInput
                            v-model="state.q"
                            label="Búsqueda"
                            placeholder="Buscar por nombre, código, ciudad, estado..."
                        />
                    </div>

                    <div class="lg:col-span-4 min-w-0">
                        <!-- SearchableSelect: null = Todos -->
                        <div class="space-y-1">
                            <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">
                                Corporativo
                            </div>

                            <SearchableSelect
                                v-model="state.corporativo_id"
                                :options="corporativosForSelect"
                                label-key="nombre"
                                value-key="id"
                                :nullable="true"
                                null-label="Todos"
                                placeholder="Selecciona corporativo..."
                                class="w-full"
                            />
                        </div>
                    </div>

                    <div class="lg:col-span-2 min-w-0">
                        <AppSelect v-model="state.activo" label="Estatus">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </AppSelect>
                    </div>

                    <div class="lg:col-span-2 min-w-0">
                        <AppSelect v-model="state.perPage" label="Registros por página">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </AppSelect>
                    </div>

                    <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <button
                            type="button"
                            @click="toggleSort"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-xs font-semibold
                            bg-slate-100 text-slate-800 hover:bg-slate-200
                            dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15
                            transition active:scale-[0.98] w-full sm:w-auto"
                        >
                            Orden: {{ sortLabel }}
                        </button>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="clearFilters"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-xs font-semibold
                            bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                            dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                            transition active:scale-[0.98] w-full sm:w-auto"
                        >
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- =========================
                    TABLA (Desktop lg+)
                    ========================= -->
                <div
                    class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm max-w-full"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-sm">
                            <thead class="bg-slate-50 dark:bg-neutral-950/60">
                                <tr class="text-left text-slate-600 dark:text-neutral-300">
                                    <th class="px-4 py-3 font-semibold w-[46px]">
                                        <AppCheckbox
                                            :checked="isAllSelectedOnPage"
                                            @update:checked="(v) => toggleAllOnPage(!!v)"
                                            :label="'Seleccionar todas en la página'"
                                        />
                                    </th>
                                    <th class="px-4 py-3 font-semibold">Sucursal</th>
                                    <th class="px-4 py-3 font-semibold">Código</th>
                                    <th class="px-4 py-3 font-semibold">Corporativo</th>
                                    <th class="px-4 py-3 font-semibold">Ciudad / Estado</th>
                                    <th class="px-4 py-3 font-semibold">Dirección</th>
                                    <th class="px-4 py-3 font-semibold">Estatus</th>
                                    <th class="px-4 py-3 font-semibold text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="row in pageRows"
                                    :key="row.id"
                                    class="border-t border-slate-200/70 dark:border-white/10
                                    hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition"
                                >
                                    <td class="px-4 py-3 align-middle">
                                        <AppCheckbox
                                            :checked="selectedIds.has(row.id)"
                                            @update:checked="(v) => toggleRow(row.id, !!v)"
                                            :label="`Seleccionar sucursal ${row.nombre}`"
                                        />
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900 dark:text-neutral-100">
                                            {{ row.nombre }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">
                                        {{ row.codigo ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                                        {{ row.corporativo_nombre ?? row.corporativo?.nombre ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                                        <div>{{ row.ciudad ?? '—' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-neutral-400">
                                            {{ row.estado ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">
                                        {{ row.direccion ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border
                                            bg-slate-100 text-slate-700 border-slate-200
                                            dark:bg-white/5 dark:text-neutral-200 dark:border-white/10"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'"
                                            />
                                            {{ row.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center gap-2">
                                            <button
                                                type="button"
                                                @click="openEdit(row)"
                                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                bg-slate-100 text-slate-800 hover:bg-slate-200
                                                dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                                transition active:scale-[0.98]"
                                                title="Editar"
                                            >
                                                Editar
                                            </button>

                                            <button
                                                type="button"
                                                @click="destroyRow(row)"
                                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                                                dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                                transition active:scale-[0.98]"
                                                title="Eliminar"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!pageRows.length">
                                    <td colspan="8" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">
                                        No hay sucursales con los filtros actuales.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer tabla -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                        border-t border-slate-200/70 dark:border-white/10
                        px-4 py-3 bg-white dark:bg-neutral-900"
                    >
                        <div class="text-xs text-slate-600 dark:text-neutral-300">
                            <span class="font-semibold">Mostrando {{ props.sucursales?.from ?? 0 }}</span> a
                            <span class="font-semibold">{{ props.sucursales?.to ?? 0 }}</span> de
                            <span class="font-semibold">{{ props.sucursales?.total ?? 0 }}</span>
                        </div>

                        <AppPagination :links="safeLinks" @go="goTo" />
                    </div>
                </div>

                <!-- =========================
                    CARDS (Mobile/Tablet < lg)
                    ========================= -->
                <div class="lg:hidden grid gap-3 max-w-full">
                    <div
                        v-for="row in pageRows"
                        :key="row.id"
                        class="w-full max-w-full min-w-0 overflow-hidden
                        rounded-2xl border border-slate-200/70 dark:border-white/10
                        bg-white dark:bg-neutral-900 shadow-sm p-4"
                    >
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="pt-1 shrink-0">
                                <AppCheckbox
                                    :checked="selectedIds.has(row.id)"
                                    @update:checked="(v) => toggleRow(row.id, !!v)"
                                    :label="`Seleccionar sucursal ${row.nombre}`"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2 min-w-0">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                                            {{ row.nombre }}
                                        </div>
                                        <div class="text-xs text-slate-600 dark:text-neutral-300 truncate">
                                            {{ row.corporativo_nombre ?? row.corporativo?.nombre ?? '—' }}
                                        </div>
                                    </div>

                                    <span
                                        class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border
                                        bg-slate-100 text-slate-700 border-slate-200
                                        dark:bg-white/5 dark:text-neutral-200 dark:border-white/10"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'"
                                        />
                                        {{ row.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-slate-700 dark:text-neutral-200">
                                    <div class="text-xs break-all">
                                        <span class="font-semibold">Código:</span> {{ row.codigo ?? '—' }}
                                    </div>
                                    <div class="text-xs break-all">
                                        <span class="font-semibold">Ciudad:</span> {{ row.ciudad ?? '—' }}
                                    </div>
                                    <div class="text-xs break-all">
                                        <span class="font-semibold">Estado:</span> {{ row.estado ?? '—' }}
                                    </div>
                                    <div class="text-xs break-all">
                                        <span class="font-semibold">Dirección:</span> {{ row.direccion ?? '—' }}
                                    </div>
                                </div>

                                <!-- Acciones (SIN <td>, correcto) -->
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="openEdit(row)"
                                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                        bg-slate-100 text-slate-800 hover:bg-slate-200
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                        transition active:scale-[0.98]"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        @click="destroyRow(row)"
                                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
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
                        v-if="!pageRows.length"
                        class="rounded-2xl border border-slate-200/70 dark:border-white/10
                        bg-white dark:bg-neutral-900 shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400"
                    >
                        No hay sucursales con los filtros actuales.
                    </div>

                    <!-- Paginación simple (mobile/tablet) -->
                    <div
                        class="rounded-2xl border border-slate-200/70 dark:border-white/10
                        bg-white dark:bg-neutral-900 shadow-sm p-4 overflow-hidden"
                    >
                        <div class="flex items-center justify-between mb-3 gap-2">
                            <div class="text-xs text-slate-600 dark:text-neutral-300">
                                <span class="font-semibold">{{ props.sucursales?.from ?? 0 }}</span> -
                                <span class="font-semibold">{{ props.sucursales?.to ?? 0 }}</span> /
                                <span class="font-semibold">{{ props.sucursales?.total ?? 0 }}</span>
                            </div>

                            <button
                                type="button"
                                class="text-xs font-semibold text-slate-700 hover:text-slate-900
                                dark:text-neutral-300 dark:hover:text-white shrink-0"
                                @click="toggleAllOnPage(!isAllSelectedOnPage)"
                            >
                                {{ isAllSelectedOnPage ? 'Quitar' : 'Seleccionar' }}
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 max-w-full">
                            <button
                                v-for="(l, idx) in safeLinks"
                                :key="idx"
                                type="button"
                                @click="goTo(l.url)"
                                :disabled="!l.url"
                                class="px-3 py-2 text-xs font-semibold rounded-xl border
                                max-w-full min-w-0
                                disabled:opacity-50 disabled:cursor-not-allowed
                                transition active:scale-[0.98]"
                                :class="l.active
                                    ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'"
                            >
                                {{ l.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- =========================
                    MODAL Create/Edit
                    ========================= -->
                <Modal :show="modalOpen" @close="closeModal">
                    <div
                        class="p-4 sm:p-6 rounded-2xl
                        bg-white dark:bg-neutral-900
                        border border-slate-200/70 dark:border-white/10"
                    >
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="min-w-0">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-neutral-100">
                                    {{ isEdit ? 'Editar sucursal' : 'Nueva sucursal' }}
                                </h3>
                                <p class="text-xs text-slate-600 dark:text-neutral-300">
                                    Completa los campos y guarda. Validación inline + UI premium.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-xl px-3 py-2 text-xs font-semibold
                                bg-slate-100 text-slate-800 hover:bg-slate-200
                                dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15
                                transition active:scale-[0.98]"
                            >
                                Cerrar
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- corporativo -->
                            <div class="sm:col-span-2">
                                <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">
                                    Corporativo <span class="text-rose-500">*</span>
                                </div>

                                <SearchableSelect
                                    v-model="form.corporativo_id"
                                    :options="corporativosForSelect"
                                    label-key="nombre"
                                    value-key="id"
                                    :nullable="false"
                                    placeholder="Selecciona corporativo..."
                                    class="w-full"
                                />

                                <div v-if="errors.corporativo_id" class="mt-1 text-xs text-rose-600 dark:text-rose-300">
                                    {{ errors.corporativo_id }}
                                </div>
                            </div>

                            <!-- nombre -->
                            <div class="sm:col-span-2">
                                <AppInput
                                    v-model="form.nombre"
                                    label="Nombre"
                                    placeholder="Ej. Sucursal Centro"
                                />
                                <div v-if="errors.nombre" class="mt-1 text-xs text-rose-600 dark:text-rose-300">
                                    {{ errors.nombre }}
                                </div>
                            </div>

                            <!-- codigo -->
                            <AppInput
                                v-model="form.codigo"
                                label="Código"
                                placeholder="Ej. CEN-01"
                            />

                            <!-- ciudad -->
                            <AppInput
                                v-model="form.ciudad"
                                label="Ciudad"
                                placeholder="Ej. Cuernavaca"
                            />

                            <!-- estado -->
                            <AppInput
                                v-model="form.estado"
                                label="Estado"
                                placeholder="Ej. Morelos"
                            />

                            <!-- direccion -->
                            <div class="sm:col-span-2">
                                <AppInput
                                    v-model="form.direccion"
                                    label="Dirección"
                                    placeholder="Opcional"
                                />
                            </div>

                            <!-- activo -->
                            <div class="sm:col-span-2 flex items-center gap-2 pt-1">
                                <input
                                    id="m_activo"
                                    v-model="form.activo"
                                    type="checkbox"
                                    class="rounded border-slate-300 dark:border-white/20"
                                />
                                <label for="m_activo" class="text-sm text-slate-700 dark:text-neutral-200">
                                    Activo
                                </label>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-xl px-4 py-2 text-sm font-semibold
                                bg-slate-100 text-slate-800 hover:bg-slate-200
                                dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15
                                transition active:scale-[0.98]"
                            >
                                Cancelar
                            </button>

                            <button
                                type="button"
                                @click="submit"
                                :disabled="!canSubmit"
                                class="rounded-xl px-4 py-2 text-sm font-semibold
                                bg-slate-900 text-white hover:bg-slate-800
                                dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                                disabled:opacity-50 disabled:cursor-not-allowed
                                transition active:scale-[0.98]"
                            >
                                {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Guardar') }}
                            </button>
                        </div>
                    </div>
                </Modal>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
