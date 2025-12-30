<script setup lang="ts">

    import { computed } from 'vue'
    import { Head } from '@inertiajs/vue3'
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

    import SecondaryButton from '@/Components/SecondaryButton.vue'
    import SearchableSelect from '@/Components/ui/SearchableSelect.vue'

    import type { RequisicionesPageProps } from './Requisiciones.types'
    import { useRequisicionesIndex } from './useRequisicionesIndex'

    // Importamos iconos propios
    import ICON_PDF from '@/img/pdf.png'
    import ICON_EXCEL from '@/img/excel.png'

    // Importamos funciones para exportar archivos
    import { toQS, downloadFile } from '@/Utils/exports'

    const props = defineProps<RequisicionesPageProps>()

    const {
        // permisos
        canDelete,
        canPay,
        canUploadComprobantes,

        // data + filtros
        state,
        rows,
        tabs,
        corporativosActive,
        sucursalesActive,
        empleadosActive,
        inputBase,
        selectBase,
        hasActiveFilters,
        clearFilters,
        sortLabel,
        toggleSort,

        // selección
        selectedIds,
        selectedCount,
        isAllSelectedOnPage,
        toggleRow,
        toggleAllOnPage,
        clearSelection,
        destroySelected,

        // navegación
        goTo,
        goShow,
        goCreate,
        goPay,
        goComprobar,
        printReq,

        // helpers UI
        money,
        statusPill,

        // eliminar
        destroyRow,

        // tabs
        setTab,

        // date range popover
        dateOpen,
        dateAnchorRef,
        datePanelRef,
        tempFrom,
        tempTo,
        dateLabel,
        openDate,
        closeDate,
        applyDate,
        clearDate,
        presetToday,
        presetLast7,
        presetThisMonth,

        // paginación
        safePagerLinks,
    } = useRequisicionesIndex(props)

    const exportPdfUrl = computed(() => route('requisiciones.export.pdf') + toQS(state))
    const exportExcelUrl = computed(() => route('requisiciones.export.excel') + toQS(state))
</script>

<template>
    <Head title="Requisiciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3 min-w-0">
                <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100 truncate">
                Requisiciones
                </h2>
            </div>
        </template>

        <div class="w-full max-w-full min-w-0">
            <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
                <div class="mb-4 w-full max-w-full min-w-0 relative z-50
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-4 }
                    py-4 flex flex-col gap-3 sm:flex-row sm:items-center
                    sm:justify-between">
                    <div class="min-w-0">
                        <h1 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                            Control y seguimiento de requisiciones
                        </h1>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0 w-full sm:w-auto">
                        <div v-if="selectedCount > 0 && canDelete"
                        class="flex flex-wrap items-center gap-2 rounded-2xl
                        border border-slate-200/70 dark:border-white/10
                        bg-slate-50/80 dark:bg-neutral-950/40 px-3 py-2 min-w-0
                        max-w-full">
                            <div class="text-xs text-slate-700 dark:text-neutral-200">
                                Seleccionados: <span class="font-semibold">{{ selectedCount }}</span>
                            </div>

                            <button type="button" @click="clearSelection"
                            class="rounded-xl px-3 py-1.5 text-xs font-semibold
                            bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                            dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                            transition active:scale-[0.98]">
                                Limpiar
                            </button>

                            <button type="button" @click="destroySelected"
                            class="rounded-xl px-3 py-1.5 text-xs font-semibold
                            bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                            dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                            transition active:scale-[0.98]">
                                Eliminar
                            </button>
                        </div>

                        <button type="button" @click="goCreate"
                        class="inline-flex items-center justify-center gap-2
                        rounded-xl px-4 py-2 text-sm font-semibold
                        bg-emerald-600 text-white hover:bg-emerald-700
                        dark:bg-emerald-500 dark:hover:bg-emerald-600
                        shadow-sm hover:shadow transition active:scale-[0.98] w-full sm:w-auto">
                            <span class="inline-block h-2 w-2 rounded-full bg-white/80"></span>
                            Nueva requisición
                        </button>
                    </div>
                </div>

                <div class="mb-4 w-full max-w-full min-w-0 relative z-50
                rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/90 dark:bg-neutral-900/80 backdrop-blur
                shadow-sm px-3 py-3">
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <button v-for="t in tabs" :key="t.key" type="button"
                        @click="setTab(t.key as any)"
                        class="inline-flex items-center gap-2 rounded-xl px-3 py-2
                        text-sm font-semibold border
                        transition active:scale-[0.98] min-w-0"
                        :class="
                        state.tab === t.key
                        ? 'bg-slate-900 text-white border-slate-900 shadow-sm dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:-translate-y-[1px] dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'">
                            <span class="truncate">{{ t.label }}</span>
                            <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full text-xs font-extrabold
                            shrink-0"
                            :class=" state.tab === t.key
                            ? 'bg-white/15 text-white dark:bg-neutral-900 dark:text-neutral-100'
                            : 'bg-slate-100 text-slate-800 dark:bg-neutral-950/40 dark:text-neutral-100'">
                                {{ t.count }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="mb-4 w-full max-w-full min-w-0 relative z-50
                rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-12 gap-3 min-w-0">
                        <div class="2xl:col-span-4 min-w-0">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
                            <input v-model="state.q" type="text"
                                placeholder="Folio, proveedor, observaciones..."
                                :class="inputBase + ' hover:border-slate-300 dark:hover:border-white/20'"/>
                        </div>

                        <div class="2xl:col-span-3 min-w-0">
                            <SearchableSelect
                                v-model="state.comprador_corp_id"
                                :options="corporativosActive"
                                label="Corporativo"  placeholder="Todos"
                                searchPlaceholder="Buscar corporativo..."
                                :allowNull="true" nullLabel="Todos"
                                rounded="xl" zIndexClass="z-40" />
                        </div>

                        <div class="2xl:col-span-3 min-w-0">
                            <SearchableSelect v-model="state.sucursal_id"
                                :options="sucursalesActive"
                                label="Sucursal" placeholder="Todas"
                                searchPlaceholder="Buscar sucursal..."
                                :allowNull="true" nullLabel="Todas"
                                rounded="xl" zIndexClass="z-40"
                                labelKey="nombre" secondaryKey="codigo"/>
                        </div>

                        <div class="2xl:col-span-1 min-w-0">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo</label>
                            <select v-model="state.tipo" :class="selectBase + ' hover:border-slate-300 dark:hover:border-white/20'">
                                <option value="">Todos</option>
                                <option value="ANTICIPO">Anticipo</option>
                                <option value="REEMBOLSO">Reembolso</option>
                            </select>
                        </div>

                        <div class="2xl:col-span-1 min-w-0">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
                            <select v-model.number="state.perPage" :class="selectBase + ' hover:border-slate-300 dark:hover:border-white/20'">
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>

                        <div class="2xl:col-span-2 min-w-0">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
                            <select v-model="state.status" :class="selectBase + ' hover:border-slate-300 dark:hover:border-white/20'">
                                <option value="">Todos</option>
                                <option value="BORRADOR">BORRADOR</option>
                                <option value="CAPTURADA">CAPTURADA</option>
                                <option value="ACEPTADA">ACEPTADA</option>
                                <option value="RECHAZADA">RECHAZADA</option>
                                <option value="PAGADA">PAGADA</option>
                                <option value="POR_COMPROBAR">POR COMPROBAR</option>
                                <option value="COMPROBADA">COMPROBADA</option>
                            </select>
                        </div>

                        <div class="2xl:col-span-3 min-w-0">
                            <SearchableSelect v-model="state.solicitante_id"
                                :options="empleadosActive"
                                label="Solicitante" placeholder="Todos"
                                searchPlaceholder="Buscar empleado..."
                                :allowNull="true" nullLabel="Todos" rounded="xl"
                                zIndexClass="z-40" labelKey="nombre"
                                secondaryKey="puesto"/>
                        </div>

                        <div class="2xl:col-span-4 min-w-0 relative">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fechas</label>

                            <button ref="dateAnchorRef" type="button"
                                @click="dateOpen ? closeDate() : openDate()"
                                class="mt-1 w-full rounded-xl px-3 py-2 text-left
                                text-sm font-semibold border
                                border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:border-slate-300
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60 dark:hover:border-white/20
                                transition active:scale-[0.99]">
                                <div class="flex items-center justify-between gap-3 min-w-0">
                                    <span class="truncate">{{ dateLabel }}</span>
                                    <span class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-500 dark:text-neutral-300 shrink-0">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500/80"></span>
                                        Rango
                                    </span>
                                </div>
                            </button>

                            <transition name="fadeUp">
                                <div v-if="dateOpen" ref="datePanelRef"
                                class="absolute left-0 top-full mt-2 w-full
                                max-w-[520px] rounded-2xl border
                                border-slate-200/70 dark:border-white/10
                                bg-white dark:bg-neutral-900
                                shadow-2xl p-3 z-[9999]">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" @click="presetToday"
                                        class="rounded-xl px-3 py-1.5 text-xs
                                        font-semibold bg-slate-100 text-slate-800
                                        hover:bg-slate-200 dark:bg-neutral-800
                                        dark:text-neutral-100 dark:hover:bg-neutral-700
                                        transition active:scale-[0.98]">
                                            Hoy
                                        </button>

                                        <button type="button" @click="presetLast7"
                                        class="rounded-xl px-3 py-1.5 text-xs
                                        font-semibold bg-slate-100 text-slate-800
                                        hover:bg-slate-200
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                        transition active:scale-[0.98]">
                                            Últimos 7 días
                                        </button>

                                        <button type="button" @click="presetThisMonth"
                                        class="rounded-xl px-3 py-1.5 text-xs font-semibold
                                        bg-slate-100 text-slate-800 hover:bg-slate-200
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                        transition active:scale-[0.98]">
                                            Mes actual
                                        </button>
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div class="min-w-0">
                                            <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-300">Desde</label>
                                            <input v-model="tempFrom" type="date" :class="inputBase" />
                                        </div>
                                        <div class="min-w-0">
                                            <label class="block text-[11px] font-semibold text-slate-500 dark:text-neutral-300">Hasta</label>
                                            <input v-model="tempTo" type="date" :class="inputBase" />
                                        </div>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between gap-2">
                                        <button type="button" @click="clearDate"
                                        class="rounded-xl px-3 py-2 text-xs
                                        font-semibold bg-white text-rose-700 border
                                        border-rose-200 hover:bg-rose-50
                                        dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20
                                        dark:hover:bg-rose-500/10
                                        transition active:scale-[0.98]">
                                            Limpiar
                                        </button>

                                        <div class="flex gap-2">
                                            <button type="button" @click="closeDate"
                                            class="rounded-xl px-3 py-2 text-xs
                                            font-semibold bg-white text-slate-800
                                            border border-slate-200 hover:bg-slate-50
                                            dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                                            transition active:scale-[0.98]">
                                                Cancelar
                                            </button>

                                            <button type="button"
                                            @click="applyDate"
                                            class="rounded-xl px-3 py-2 text-xs }font-semibold
                                            bg-emerald-600 text-white hover:bg-emerald-700
                                            dark:bg-emerald-500 dark:hover:bg-emerald-600
                                            shadow-sm hover:shadow transition active:scale-[0.98]">
                                                Aplicar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-2 text-[11px] text-slate-500 dark:text-neutral-400">
                                        Tip: Presiona Escape o haz clic afuera para cerrar.
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <div class="lg:col-span-3 min-w-0 flex flex-wrap items-end gap-x-6 gap-y-2 ml-2">
                            <!-- PDF -->
                            <button type="button" @click="downloadFile(exportPdfUrl)" class="group flex flex-col items-center gap-1 py-2 ...">
                                <img :src="ICON_PDF" alt="PDF" class="h-6 w-6 transition-transform group-hover:scale-125"/>
                                <span class="relative text-[11px] leading-none ...">Descargar</span>
                            </button>

                            <!-- EXCEL -->
                            <button type="button" @click="downloadFile(exportExcelUrl)" class="group flex flex-col items-center gap-1 py-2 ...">
                                <img :src="ICON_EXCEL" alt="EXCEL" class="h-6 w-6 transition-transform group-hover:scale-125"/>
                                <span class="relative text-[11px] leading-none ...">Descargar</span>
                            </button>

                            <button type="button" @click="toggleSort"
                                class="ml-0 inline-flex items-center
                                justify-center rounded-full px-2 py-2
                                text-xs font-semibold
                                bg-slate-100 text-slate-800
                                dark:bg-white/10 dark:text-neutral-100
                                hover:bg-slate-200 dark:hover:bg-white/
                                15 transition whitespace-nowrap"
                                    title="Cambiar orden">
                                    Orden: {{ sortLabel }}
                            </button>

                            <SecondaryButton v-if="hasActiveFilters"
                                type="button"
                                @click="clearFilters"
                                :disabled="!hasActiveFilters"
                                class="rounded-xl disabled:opacity-50 shrink-0">
                                Limpiar
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <div class="hidden 2xl:block w-full max-w-full min-w-0 overflow-visible
                rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-slate-50 dark:bg-neutral-950/60">
                        <tr class="text-left text-slate-600 dark:text-neutral-300">
                            <th class="px-4 py-3 font-semibold w-12">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                                :checked="isAllSelectedOnPage"
                                @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"
                            />
                            </th>
                            <th class="px-4 py-3 font-semibold">Folio</th>
                            <th class="px-4 py-3 font-semibold">Tipo</th>
                            <th class="px-4 py-3 font-semibold">Corporativo</th>
                            <th class="px-4 py-3 font-semibold">Sucursal</th>
                            <th class="px-4 py-3 font-semibold">Solicitante</th>
                            <th class="px-4 py-3 font-semibold">Estatus</th>
                            <th class="px-4 py-3 font-semibold text-right">Total</th>
                            <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr
                            v-for="row in rows"
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

                            <td class="px-4 py-3 min-w-0">
                            <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                                {{ row.folio }}
                            </div>
                            <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">
                                ID: {{ row.id }} · {{ row.fecha_captura ? String(row.fecha_captura).slice(0, 10) : '—' }}
                            </div>
                            </td>

                            <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold border
                                    border-slate-200 bg-slate-50 text-slate-700
                                    dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100"
                            >
                                {{ row.tipo === 'ANTICIPO' ? 'Anticipo' : 'Reembolso' }}
                            </span>
                            </td>

                            <td class="px-4 py-3 min-w-0">
                            <div class="text-slate-900 dark:text-neutral-100 truncate">
                                {{ row.comprador?.nombre ?? '—' }}
                            </div>
                            </td>

                            <td class="px-4 py-3 min-w-0">
                            <div class="text-slate-900 dark:text-neutral-100 truncate">
                                {{ row.sucursal?.nombre ?? '—' }}
                            </div>
                            </td>

                            <td class="px-4 py-3 min-w-0">
                            <div class="text-slate-900 dark:text-neutral-100 truncate">
                                {{ row.solicitante?.nombre ?? '—' }}
                            </div>
                            </td>

                            <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                                :class="statusPill(row.status)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                                <span class="truncate">{{ row.status }}</span>
                            </span>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap font-extrabold text-slate-900 dark:text-neutral-100">
                            {{ money(row.monto_total) }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="inline-flex gap-2">
                                <button
                                type="button"
                                @click="goShow(row.id)"
                                class="rounded-xl px-3 py-2 text-xs font-semibold
                                        bg-slate-100 text-slate-800 hover:bg-slate-200 hover:-translate-y-[1px]
                                        dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                        transition active:scale-[0.98]"
                                >
                                Ver
                                </button>

                                <button
                                type="button"
                                @click="printReq(row.id)"
                                class="rounded-xl px-3 py-2 text-xs font-semibold
                                        bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 hover:-translate-y-[1px]
                                        dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                                        transition active:scale-[0.98]"
                                title="Imprimir PDF"
                                >
                                Imprimir
                                </button>

                                <button
                                v-if="canPay"
                                type="button"
                                @click="goPay(row.id)"
                                class="rounded-xl px-3 py-2 text-xs font-semibold
                                        bg-sky-600 text-white hover:bg-sky-700 hover:-translate-y-[1px]
                                        shadow-sm hover:shadow transition active:scale-[0.98]"
                                >
                                Pagar
                                </button>

                                <button
                                v-if="canUploadComprobantes"
                                type="button"
                                @click="goComprobar(row.id)"
                                class="rounded-xl px-3 py-2 text-xs font-semibold
                                        bg-indigo-600 text-white hover:bg-indigo-700 hover:-translate-y-[1px]
                                        shadow-sm hover:shadow transition active:scale-[0.98]"
                                >
                                Comprobar
                                </button>

                                <button
                                v-if="canDelete"
                                type="button"
                                @click="destroyRow(row)"
                                class="rounded-xl px-3 py-2 text-xs font-semibold
                                        bg-white text-rose-700 border border-rose-200 hover:bg-rose-50 hover:-translate-y-[1px]
                                        dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                        transition active:scale-[0.98]"
                                >
                                Eliminar
                                </button>
                            </div>
                            </td>
                        </tr>

                        <tr v-if="rows.length === 0">
                            <td colspan="9" class="px-4 py-12 text-center text-slate-500 dark:text-neutral-400">
                            No hay requisiciones con los filtros actuales.
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                            border-t border-slate-200/70 dark:border-white/10
                            px-4 py-3 bg-white/90 dark:bg-neutral-900/80"
                    >
                        <div class="text-xs text-slate-600 dark:text-neutral-300">
                        Página <span class="font-semibold">{{ props.requisiciones?.current_page ?? 1 }}</span> de
                        <span class="font-semibold">{{ props.requisiciones?.last_page ?? 1 }}</span>
                        </div>

                        <nav class="flex flex-wrap gap-2 max-w-full">
                        <button
                            v-for="(link, i) in safePagerLinks"
                            :key="`${i}-${link.cleanLabel}`"
                            type="button"
                            @click="link.url ? goTo(link.url) : null"
                            :disabled="!link.url"
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold border transition
                                border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:-translate-y-[1px]
                                disabled:opacity-50 disabled:cursor-not-allowed
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60"
                            :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
                            v-html="link.label"
                        />
                        </nav>
                    </div>
                </div>

                <div class="2xl:visible grid gap-3">
                    <transition-group name="list" tag="div" class="grid gap-3">
                        <div
                        v-for="row in rows"
                        :key="row.id"
                        class="w-full max-w-full min-w-0 overflow-visible
                                rounded-2xl border border-slate-200/70 dark:border-white/10
                                bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm p-4"
                        >
                        <div class="flex items-start justify-between gap-3 min-w-0">
                            <div class="flex items-start gap-3 min-w-0">
                            <input
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900 shrink-0"
                                :checked="selectedIds.has(row.id)"
                                @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
                            />

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 min-w-0">
                                <div class="font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                                    {{ row.folio }}
                                </div>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold border shrink-0
                                        border-slate-200 bg-slate-50 text-slate-700
                                        dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100"
                                >
                                    {{ row.tipo === 'ANTICIPO' ? 'Anticipo' : 'Reembolso' }}
                                </span>
                                </div>

                                <div class="mt-0.5 text-xs text-slate-500 dark:text-neutral-400 truncate">
                                {{ row.comprador?.nombre ?? '—' }} · {{ row.sucursal?.nombre ?? '—' }}
                                </div>
                            </div>
                            </div>

                            <span
                            class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border"
                            :class="statusPill(row.status)"
                            >
                            <span class="h-1.5 w-1.5 rounded-full bg-current opacity-40"></span>
                            <span class="truncate">{{ row.status }}</span>
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-700 dark:text-neutral-200">
                            <div class="rounded-xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 p-3 min-w-0">
                            <div class="opacity-70">Solicitante</div>
                            <div class="font-semibold truncate">{{ row.solicitante?.nombre ?? '—' }}</div>
                            </div>

                            <div class="rounded-xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-neutral-950/40 p-3 min-w-0">
                            <div class="opacity-70">Total</div>
                            <div class="font-extrabold truncate">{{ money(row.monto_total) }}</div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button
                            type="button"
                            @click="goShow(row.id)"
                            class="rounded-xl px-3 py-2 text-xs font-semibold
                                    bg-slate-100 text-slate-800 hover:bg-slate-200
                                    dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                    transition active:scale-[0.98]"
                            >
                            Ver
                            </button>

                            <button
                            type="button"
                            @click="printReq(row.id)"
                            class="rounded-xl px-3 py-2 text-xs font-semibold
                                    bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                                    dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                                    transition active:scale-[0.98]"
                            >
                            Imprimir
                            </button>

                            <button
                            v-if="canPay"
                            type="button"
                            @click="goPay(row.id)"
                            class="rounded-xl px-3 py-2 text-xs font-semibold
                                    bg-sky-600 text-white hover:bg-sky-700
                                    shadow-sm hover:shadow transition active:scale-[0.98]"
                            >
                            Pagar
                            </button>

                            <button
                            v-if="canUploadComprobantes"
                            type="button"
                            @click="goComprobar(row.id)"
                            class="rounded-xl px-3 py-2 text-xs font-semibold
                                    bg-indigo-600 text-white hover:bg-indigo-700
                                    shadow-sm hover:shadow transition active:scale-[0.98]"
                            >
                            Comprobar
                            </button>

                            <button
                            v-if="canDelete"
                            type="button"
                            @click="destroyRow(row)"
                            class="col-span-2 rounded-xl px-3 py-2 text-xs font-semibold
                                    bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                                    dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                    transition active:scale-[0.98]"
                            >
                            Eliminar
                            </button>
                        </div>
                        </div>
                    </transition-group>

                    <div
                        class="w-full max-w-full min-w-0 overflow-hidden
                            rounded-2xl border border-slate-200/70 dark:border-white/10
                            bg-white/90 dark:bg-neutral-900/80 backdrop-blur shadow-sm px-4 py-3"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-xs text-slate-600 dark:text-neutral-300">
                            Página <span class="font-semibold">{{ props.requisiciones?.current_page ?? 1 }}</span> de
                            <span class="font-semibold">{{ props.requisiciones?.last_page ?? 1 }}</span>
                        </div>

                        <nav class="flex flex-wrap gap-2 max-w-full">
                            <button
                            v-for="(link, i) in safePagerLinks"
                            :key="`${i}-${link.cleanLabel}`"
                            type="button"
                            @click="link.url ? goTo(link.url) : null"
                            :disabled="!link.url"
                            class="rounded-xl px-3 py-1.5 text-sm font-semibold border transition
                                    border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                                    disabled:opacity-50 disabled:cursor-not-allowed
                                    dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-neutral-950/60"
                            :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
                            v-html="link.label"
                            />
                        </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:global(html),
:global(body) {
  max-width: 100%;
  overflow-x: hidden;
}

:global(html.dark select option) {
  background: #0a0a0a;
  color: #f5f5f5;
}

.fadeUp-enter-active,
.fadeUp-leave-active {
  transition: all 160ms ease;
}
.fadeUp-enter-from,
.fadeUp-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

.list-enter-active,
.list-leave-active {
  transition: all 180ms ease;
}
.list-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.list-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
