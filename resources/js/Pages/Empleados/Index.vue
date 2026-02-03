<script setup lang="ts">
    import { computed } from 'vue'
    import { Head } from '@inertiajs/vue3'
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    // Componentes UI
    import Modal from '@/Components/Modal.vue'
    import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
    import AppInput from '@/Components/ui/AppInput.vue'
    import AppSelect from '@/Components/ui/AppSelect.vue'
    import AppPagination from '@/Components/ui/AppPagination.vue'
    import AppCheckbox from '@/Components/Checkbox.vue'
    // Props + lógica
    import type { EmpleadosPageProps, EmpleadoRow } from './Empleados.types'
    import { useEmpleadosIndex } from './useEmpleadosIndex'
    // Icons
    import ICON_PDF from '@/img/pdf.png'
    import ICON_EXCEL from '@/img/excel.png'
    // Export helpers
    import { toQS, downloadFile } from '@/Utils/exports'

    const props = defineProps<EmpleadosPageProps>()

    const {
        // filtros / orden
        state,
        hasActiveFilters,
        clearFilters,
        sortLabel,
        toggleSort,
        // selects
        corporativosLabeled,
        sucursalesLabeled,
        areasLabeled,
        canPickSucursalFilter,
        canPickAreaFilter,
        // selección
        selectedIdsArray,
        selectedCount,
        isAllSelectedOnPage,
        toggleAllOnPage,
        clearSelection,
        // paginación
        paginationLinks,
        mobileLinks,
        linkLabelShort,
        goTo,
        // modal + form
        modalOpen,
        isEdit,
        saving,
        savingText,
        form,
        errors,
        canSubmitFinal,
        canEditPassword,
        openCreate,
        openEdit,
        closeModal,
        submit,
        // combos modal
        modalCorporativos,
        modalSucursalesSafe,
        modalAreasSafe,
        modalHasInactiveSelection,
        // acciones
        confirmDeactivate,
        confirmActivate,
        confirmBulkDeactivate,
        // helpers display
        fullName,
        corpName,
        sucursalName,
        areaName,
        emailValue,
        badgeText,
        from,
        to,
        total,
    } = useEmpleadosIndex(props)

    // Listado en pantalla
    const pageRows = computed<EmpleadoRow[]>(() => props.empleados?.data ?? [])

    // Export
    const exportPdfUrl = computed(() => route('empleados.export.pdf') + toQS(state))
    const exportExcelUrl = computed(() => route('empleados.export.excel') + toQS(state))

    // Icons acciones (remotos)
    const ICON_EDIT = 'https://img.icons8.com/fluency-systems-regular/48/edit.png'
    const ICON_TRASH = 'https://img.icons8.com/fluency-systems-regular/48/trash.png'
    const ICON_CHECK = 'https://img.icons8.com/fluency-systems-regular/48/checkmark.png'
</script>

<template>
    <Head title="Empleados" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
                Empleados
            </h2>
        </template>

        <div class="w-full max-w-full min-w-0 overflow-x-hidden">
        <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
            <!-- Header -->
            <div
            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-2xl border
            border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm px-4 py-4"
            >
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-slate-900 dark:text-neutral-100 truncate">
                Administración de empleados
                </h1>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0">
                <div
                v-if="selectedCount > 0"
                class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200/70 dark:border-white/10
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
                    @click="confirmBulkDeactivate"
                    class="rounded-xl px-3 py-1.5 text-xs font-semibold inline-flex items-center gap-2
                    bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                    dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                    transition active:scale-[0.98]"
                >
                    <img :src="ICON_TRASH" alt="Eliminación" class="h-5 w-5" />
                    Eliminación
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
            class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3 lg:items-end rounded-2xl border border-slate-200/70
            dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full"
            >
            <!-- Corporativo -->
            <div class="lg:col-span-4 min-w-0">
                <SearchableSelect
                v-model="state.corporativo_id"
                :options="corporativosLabeled"
                label="Corporativo"
                label-key="__label"
                value-key="id"
                :nullable="true"
                null-label="Todos"
                placeholder="Todos"
                class="w-full"
                />
            </div>

            <!-- Búsqueda (yo dejo búsqueda libre; si tú quieres obligar corporativo, vuelve a poner disabled) -->
            <div class="lg:col-span-4 min-w-0">
                <AppInput
                v-model="state.q"
                label="Búsqueda"
                placeholder="Buscar por nombre, correo, sucursal, área, puesto..."
                />
            </div>

            <!-- Sucursal -->
            <div class="lg:col-span-2 min-w-0">
                <SearchableSelect
                :key="`sucursal-filter-${state.corporativo_id ?? 'all'}`"
                v-model="state.sucursal_id"
                :options="sucursalesLabeled"
                label="Sucursal"
                label-key="__label"
                value-key="id"
                :nullable="true"
                null-label="Todas"
                placeholder="Todas"
                class="w-full"
                :disabled="!canPickSucursalFilter"
                />
            </div>

            <!-- Área -->
            <div class="lg:col-span-2 min-w-0">
                <SearchableSelect
                :key="`area-filter-${state.corporativo_id ?? 'all'}`"
                v-model="state.area_id"
                :options="areasLabeled"
                label="Área"
                label-key="__label"
                value-key="id"
                :nullable="true"
                null-label="Todas"
                placeholder="Todas"
                class="w-full"
                :disabled="!canPickAreaFilter"
                />
            </div>

            <div class="lg:col-span-2 min-w-0">
                <AppSelect v-model="state.activo" label="Estatus">
                <option value="all">Todos</option>
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

            <div class="lg:col-span-2 min-w-0 flex flex-wrap items-end gap-x-6 gap-y-2 ml-2">
                <!-- PDF -->
                <button
                type="button"
                @click="downloadFile(exportPdfUrl)"
                class="group flex flex-col items-center gap-1 py-2"
                title="Exportar PDF"
                >
                <img :src="ICON_PDF" alt="PDF" class="h-6 w-6 transition-transform group-hover:scale-125" />
                <span class="text-[11px] leading-none text-slate-700 dark:text-neutral-200 group-hover:underline">
                    Descargar
                </span>
                </button>

                <!-- EXCEL -->
                <button
                type="button"
                @click="downloadFile(exportExcelUrl)"
                class="group flex flex-col items-center gap-1 py-2"
                title="Exportar Excel"
                >
                <img :src="ICON_EXCEL" alt="EXCEL" class="h-6 w-6 transition-transform group-hover:scale-125" />
                <span class="text-[11px] leading-none text-slate-700 dark:text-neutral-200 group-hover:underline">
                    Descargar
                </span>
                </button>

                <!-- Orden -->
                <button
                type="button"
                @click="toggleSort"
                class="ml-0 inline-flex items-center justify-center rounded-full px-3 py-2 text-xs font-semibold
                bg-slate-100 text-slate-800 hover:bg-slate-200
                dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15
                transition whitespace-nowrap"
                title="Cambiar orden"
                >
                Orden: {{ sortLabel }}
                </button>

                <!-- Limpiar -->
                <button
                v-if="hasActiveFilters"
                type="button"
                @click="clearFilters"
                class="ml-0 inline-flex items-center justify-center rounded-full px-3 py-2 text-xs font-semibold
                bg-slate-100 text-slate-800 hover:bg-slate-200
                dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15
                transition whitespace-nowrap"
                >
                Limpiar filtros
                </button>
            </div>
            </div>

            <!-- TABLA (lg+) -->
            <div
            class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
            bg-white dark:bg-neutral-900 shadow-sm max-w-full"
            >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm">
                <thead class="bg-slate-50 dark:bg-neutral-950/60">
                    <tr class="text-left text-slate-600 dark:text-neutral-300">
                    <th class="px-4 py-3 font-semibold w-[46px]">
                        <AppCheckbox
                        :checked="isAllSelectedOnPage"
                        @update:checked="(v) => toggleAllOnPage(!!v)"
                        :label="'Seleccionar todos en la página'"
                        />
                    </th>
                    <th class="px-4 py-3 font-semibold">Empleado</th>
                    <th class="px-4 py-3 font-semibold">Corporativo</th>
                    <th class="px-4 py-3 font-semibold">Sucursal</th>
                    <th class="px-4 py-3 font-semibold">Área</th>
                    <th class="px-4 py-3 font-semibold">Correo</th>
                    <th class="px-4 py-3 font-semibold">Estatus</th>
                    <th class="px-4 py-3 font-semibold text-center w-[160px]">Acciones</th>
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
                        v-model:checked="selectedIdsArray"
                        :value="row.id"
                        :label="`Seleccionar empleado ${fullName(row)}`"
                        />
                    </td>

                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-900 dark:text-neutral-100">
                        {{ fullName(row) }}
                        </div>
                        <div class="text-xs text-slate-600 dark:text-neutral-300">
                        {{ row.puesto ?? '—' }}
                        </div>
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                        {{ corpName(row) }}
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                        {{ sucursalName(row) }}
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                        {{ areaName(row) }}
                    </td>

                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">
                        {{ emailValue(row) }}
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
                        {{ badgeText(!!row.activo) }}
                        </span>
                    </td>

                    <td class="px-4 py-3">
                        <div class="flex justify-center gap-2">
                        <button
                            type="button"
                            @click="openEdit(row)"
                            class="inline-flex items-center justify-center rounded-xl p-2.5 border
                            bg-white text-slate-700 border-slate-200 hover:bg-slate-50
                            dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-white/5
                            transition active:scale-[0.98]"
                            title="Editar"
                            aria-label="Editar"
                        >
                            <img :src="ICON_EDIT" alt="Editar" class="h-5 w-5" />
                        </button>

                        <button
                            v-if="row.activo"
                            type="button"
                            @click="confirmDeactivate(row)"
                            class="inline-flex items-center justify-center rounded-xl p-2.5 border
                            bg-white text-rose-700 border-rose-200 hover:bg-rose-50
                            dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                            transition active:scale-[0.98]"
                            title="Dar de baja"
                            aria-label="Dar de baja"
                        >
                            <img :src="ICON_TRASH" alt="Eliminación" class="h-5 w-5" />
                        </button>

                        <button
                            v-else
                            type="button"
                            @click="confirmActivate(row)"
                            class="inline-flex items-center justify-center rounded-xl p-2.5 border
                            bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100
                            dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20 dark:hover:bg-emerald-500/15
                            transition active:scale-[0.98]"
                            title="Activar"
                            aria-label="Activar"
                        >
                            <img :src="ICON_CHECK" alt="Activar" class="h-5 w-5" />
                        </button>
                        </div>
                    </td>
                    </tr>

                    <tr v-if="!pageRows.length">
                    <td colspan="8" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">
                        No hay empleados con los filtros actuales.
                    </td>
                    </tr>
                </tbody>
                </table>
            </div>

            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t
                border-slate-200/70 dark:border-white/10 px-4 py-3 bg-white dark:bg-neutral-900"
            >
                <div class="text-xs text-slate-600 dark:text-neutral-300">
                <span class="font-semibold">Mostrando {{ from }}</span> a
                <span class="font-semibold">{{ to }}</span> de
                <span class="font-semibold">{{ total }}</span>
                </div>

                <AppPagination :links="paginationLinks" @go="goTo" />
            </div>
            </div>

            <!-- CARDS (< lg) -->
            <div class="lg:hidden grid gap-3 max-w-full">
            <div
                v-for="row in pageRows"
                :key="row.id"
                class="w-full max-w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200/70
                dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4"
            >
                <div class="flex items-start gap-3 min-w-0">
                <div class="pt-1 shrink-0">
                    <AppCheckbox v-model:checked="selectedIdsArray" :value="row.id" :label="`Seleccionar empleado ${fullName(row)}`" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2 min-w-0">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                        {{ fullName(row) }}
                        </div>
                        <div class="text-xs text-slate-600 dark:text-neutral-300 truncate">
                        {{ corpName(row) }} · {{ sucursalName(row) }} · {{ areaName(row) }}
                        </div>
                        <div class="text-xs text-slate-600 dark:text-neutral-300 truncate">
                        {{ emailValue(row) }}
                        </div>
                    </div>

                    <span
                        class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border
                        bg-slate-100 text-slate-700 border-slate-200 dark:bg-white/5 dark:text-neutral-200 dark:border-white/10"
                    >
                        <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'" />
                        {{ badgeText(!!row.activo) }}
                    </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="openEdit(row)"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold border
                        bg-white text-slate-700 border-slate-200 hover:bg-slate-50
                        dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-white/5
                        transition active:scale-[0.98]"
                    >
                        <img :src="ICON_EDIT" alt="Editar" class="h-5 w-5" />
                        Editar
                    </button>

                    <button
                        v-if="row.activo"
                        type="button"
                        @click="confirmDeactivate(row)"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold border
                        bg-white text-rose-700 border-rose-200 hover:bg-rose-50
                        dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                        transition active:scale-[0.98]"
                    >
                        <img :src="ICON_TRASH" alt="Eliminación" class="h-5 w-5" />
                        Eliminar
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="confirmActivate(row)"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold border
                        bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100
                        dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20 dark:hover:bg-emerald-500/15
                        transition active:scale-[0.98]"
                    >
                        <img :src="ICON_CHECK" alt="Activar" class="h-5 w-5" />
                        Activar
                    </button>
                    </div>
                </div>
                </div>
            </div>

            <div
                v-if="!pageRows.length"
                class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900
                shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400"
            >
                No hay empleados con los filtros actuales.
            </div>

            <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4 overflow-hidden">
                <div class="flex items-center justify-between mb-3 gap-2">
                <div class="text-xs text-slate-600 dark:text-neutral-300">
                    <span class="font-semibold">{{ from }}</span> -
                    <span class="font-semibold">{{ to }}</span> /
                    <span class="font-semibold">{{ total }}</span>
                </div>

                <button
                    type="button"
                    class="text-xs font-semibold text-slate-700 hover:text-slate-900 dark:text-neutral-300 dark:hover:text-white shrink-0"
                    @click="toggleAllOnPage(!isAllSelectedOnPage)"
                >
                    {{ isAllSelectedOnPage ? 'Quitar' : 'Seleccionar' }}
                </button>
                </div>

                <div class="flex flex-wrap gap-2 max-w-full">
                <button
                    v-for="(l, idx) in mobileLinks"
                    :key="idx"
                    type="button"
                    @click="goTo(l.url)"
                    :disabled="!l.url"
                    class="px-3 py-2 text-xs font-semibold rounded-xl border max-w-full min-w-0
                    disabled:opacity-50 disabled:cursor-not-allowed transition active:scale-[0.98]"
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

            <!-- Modal Create/Edit -->
            <Modal :show="modalOpen" @close="closeModal">
            <!-- IMPORTANTE: lo hago relative para poder montar overlay de loading -->
            <div class="relative p-4 sm:p-6 rounded-2xl bg-white dark:bg-neutral-900 border border-slate-200/70 dark:border-white/10">
                <!-- Overlay de carga mientras guardo (el correo puede tardar) -->
                <transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div
                    v-if="saving"
                    class="absolute inset-0 z-50 flex items-center justify-center rounded-2xl
                    bg-white/75 dark:bg-neutral-900/75 backdrop-blur-sm"
                >
                    <div class="flex flex-col items-center gap-3 px-6 text-center">
                    <div class="h-10 w-10 rounded-full border-4 border-slate-300 border-t-slate-900 dark:border-white/15 dark:border-t-white animate-spin"></div>
                    <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100">
                        {{ savingText }}
                    </div>
                    <div class="text-xs text-slate-600 dark:text-neutral-300">
                        Puede tardar un poco si se envía correo de acceso.
                    </div>
                    </div>
                </div>
                </transition>

                <div class="flex items-start justify-between gap-3 mb-4">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-neutral-100">
                    {{ isEdit ? 'Editar empleado' : 'Nuevo empleado' }}
                    </h3>

                    <p v-if="modalHasInactiveSelection" class="mt-1 text-xs text-rose-700 dark:text-rose-300">
                    Hay una selección en baja (corporativo/sucursal/área). Reactívala para poder guardar/activar.
                    </p>
                </div>

                <button type="button" @click="() => closeModal()" :disabled="saving"
                    class="rounded-xl px-3 py-2 text-xs font-semibold
                    bg-slate-100 text-slate-800 hover:bg-red-400 hover:text-white
                    disabled:opacity-50 disabled:cursor-not-allowed
                    dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-red-400
                    transition active:scale-[0.98]"
                >
                    X
                </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <SearchableSelect
                    v-model="form.corporativo_id"
                    :options="modalCorporativos"
                    label="Corporativo"
                    label-key="__label"
                    value-key="id"
                    :nullable="false"
                    placeholder="Selecciona corporativo..."
                    class="w-full"
                    :error="errors.corporativo_id ?? null"
                    />
                </div>

                <div class="sm:col-span-2">
                    <SearchableSelect
                    :key="`sucursal-modal-${form.corporativo_id ?? 'none'}`"
                    v-model="form.sucursal_id"
                    :options="modalSucursalesSafe"
                    label="Sucursal"
                    label-key="__label"
                    value-key="id"
                    :nullable="false"
                    placeholder="Selecciona sucursal..."
                    class="w-full"
                    :disabled="!form.corporativo_id"
                    :error="errors.sucursal_id ?? null"
                    />
                </div>

                <div class="sm:col-span-2">
                    <SearchableSelect
                    :key="`area-modal-${form.corporativo_id ?? 'none'}`"
                    v-model="form.area_id"
                    :options="modalAreasSafe"
                    label="Área"
                    label-key="__label"
                    value-key="id"
                    :nullable="true"
                    null-label="Sin área"
                    placeholder="Sin área"
                    class="w-full"
                    :disabled="!form.corporativo_id"
                    />
                </div>

                <AppInput v-model="form.nombre" label="Nombre" placeholder="Ej. Juan" />
                <AppInput v-model="form.apellido_paterno" label="Apellido paterno" placeholder="Ej. Pérez" />
                <AppInput v-model="form.apellido_materno" label="Apellido materno" placeholder="Opcional" />
                <AppInput v-model="form.telefono" label="Teléfono" placeholder="Opcional" />

                <div class="sm:col-span-2">
                    <AppInput v-model="form.puesto" label="Puesto" placeholder="Opcional" />
                </div>

                <div class="sm:col-span-2">
                    <AppInput v-model="form.user_email" label="Email" placeholder="correo@empresa.com" />
                    <div v-if="errors.user_email" class="mt-1 text-xs text-rose-600 dark:text-rose-300">
                    {{ errors.user_email }}
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <AppSelect v-model="form.user_rol" label="Rol">
                    <option value="ADMIN">ADMIN</option>
                    <option value="CONTADOR">CONTADOR</option>
                    <option value="COLABORADOR">COLABORADOR</option>
                    </AppSelect>
                </div>

                <!-- Solo ADMIN y solo al editar: cambiar contraseña -->
                <div v-if="isEdit && canEditPassword" class="sm:col-span-2 mt-2">
                    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 p-4 bg-slate-50 dark:bg-neutral-950/30">
                    <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100">
                        Cambiar contraseña
                    </div>
                    <div class="text-xs text-slate-600 dark:text-neutral-300 mt-1">
                        Nota: Si se deja vacía, no se modifica.
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                        <AppInput
                            v-model="form.user_password"
                            label="Nueva contraseña"
                            placeholder="Mínimo 8 caracteres"
                            type="password"
                        />
                        <div v-if="errors.user_password" class="mt-1 text-xs text-rose-600 dark:text-rose-300">
                            {{ errors.user_password }}
                        </div>
                        </div>

                        <div>
                        <AppInput
                            v-model="form.user_password_confirmation"
                            label="Confirmar contraseña"
                            placeholder="Repite la nueva contraseña"
                            type="password"
                        />
                        <div v-if="errors.user_password_confirmation" class="mt-1 text-xs text-rose-600 dark:text-rose-300">
                            {{ errors.user_password_confirmation }}
                        </div>
                        </div>
                    </div>
                    </div>
                </div>
                </div>

                <div class="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                <button type="button" @click="() => closeModal()" :disabled="saving"
                    class="rounded-xl px-4 py-2 text-sm font-semibold
                    bg-slate-100 text-slate-800 hover:bg-slate-200
                    disabled:opacity-50 disabled:cursor-not-allowed
                    dark:bg-white/10 dark:text-neutral-100 dark:hover:bg-white/15
                    transition active:scale-[0.98]"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmitFinal"
                    class="rounded-xl px-4 py-2 text-sm font-semibold inline-flex items-center justify-center gap-2
                    bg-slate-900 text-white hover:bg-slate-800
                    dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition active:scale-[0.98]"
                >
                    <span class="inline-flex items-center gap-2">
                    <span
                        v-if="saving"
                        class="h-4 w-4 rounded-full border-2 border-white/60 border-t-white animate-spin dark:border-neutral-900/50 dark:border-t-neutral-900"
                    />
                    <img v-else :src="ICON_CHECK" class="h-5 w-5 opacity-90 brightness-0 invert" alt="" />
                    <span>{{ isEdit ? 'Guardar cambios' : 'Registrar empleado' }}</span>
                    </span>
                </button>
                </div>
            </div>
            </Modal>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
