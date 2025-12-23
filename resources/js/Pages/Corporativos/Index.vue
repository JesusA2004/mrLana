<script setup lang="ts">
    /**
     * ==========================================================
     * Corporativos/Index.vue
     * ----------------------------------------------------------
     * Vista de listado con:
     * - Filtros (búsqueda, estatus, perPage)
     * - Selección por fila + selección masiva por página
     * - Bulk actions (limpiar / eliminar)
     * - Responsive: tabla (lg+) y cards (mobile/tablet)
     * - Modo oscuro (Tailwind dark:)
     * ==========================================================
     */

    import { computed } from 'vue'
    import { Head } from '@inertiajs/vue3'

    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

    // Componentes UI personalizados (Desarrollados internamente)
    import AppInput from '@/Components/ui/AppInput.vue'
    import AppSelect from '@/Components/ui/AppSelect.vue'
    import AppPagination from '@/Components/ui/AppPagination.vue'

    // Componentes UI predefinidos (Breeze)
    import AppCheckbox from '@/Components/Checkbox.vue'

    import type { CorporativosProps } from './Corporativos.types'
    import { useCorporativosIndex } from './useCorporativosIndex'

    /**
     * Props tipadas desde Inertia.
     */
    const props = defineProps<CorporativosProps>()

    /**
     * Hook: encapsula toda la lógica de negocio/estado para mantener
     * esta vista enfocada en UI.
     *
     * Reglas clave:
     * - Este componente NO debe “inventar” lógica (solo orquesta).
     * - El hook maneja: filtros, navegación, selección y acciones.
     */
    const {
        // filtros
        q,
        activo,
        perPage,

        // selección
        selectedIds,
        headerCheckbox,
        isAllSelected,
        selectedCount,
        headerAriaChecked,

        // paginación + helpers
        paginationLinks,
        logoSrc,

        // handlers
        toggleRow,
        toggleAllOnPage,
        clearSelection,
        goTo,

        // acciones
        openCreate,
        openEdit,
        confirmDelete,
        confirmBulkDelete,
        confirmActivate,
    } = useCorporativosIndex(props)

    /**
     * En mobile/tablet evitamos componentes de paginación complejos para no romper el layout.
     * Aquí filtramos links válidos que traigan `label` (string) de forma segura.
     */
    type PaginationLink = {
        url: string | null
        label: string
        active?: boolean
    }

    const mobileLinks = computed<PaginationLink[]>(() => {
        const links = (paginationLinks.value ?? []) as unknown as Array<Partial<PaginationLink> | null>
        return links
            .filter((l): l is Partial<PaginationLink> => !!l && typeof l.label === 'string')
            .map((l) => ({
            url: (l.url ?? null) as string | null,
            label: String(l.label ?? ''),
            active: Boolean(l.active),
            }))
    })

    /**
     * Normaliza labels HTML/entidades típicas de Laravel pagination para mobile:
     * - remueve entidades (&laquo; &raquo; &hellip;)
     * - remueve tags HTML (<span>..</span>)
     * - simplifica textos largos
     */
    function linkLabelShort(label: string): string {
        const clean = String(label)
            .replace(/&laquo;|&raquo;|&hellip;/g, '')
            .replace(/<[^>]*>/g, '')
            .trim()

        const low = clean.toLowerCase()

        // Soporta variaciones “Atrás” / “Anterior” / “Siguiente”
        if (low.includes('atrás') || low.includes('anterior')) return 'Atrás'
        if (low.includes('siguiente')) return 'Siguiente'

        // Números tal cual
        if (/^\d+$/.test(clean)) return clean

        // Si es texto largo, lo compactamos
        if (clean.length > 6) return clean.slice(0, 6)

        return clean || '…'
    }

    const selectedIdsArray = computed<number[]>({
        get() {
            return Array.from(selectedIds.value)
        },
        set(values: number[]) {
            selectedIds.value = new Set(values)
        },
    })

</script>

<template>

    <Head title="Corporativos" />

    <AuthenticatedLayout>
        <!-- Header del layout -->
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
                Corporativos
            </h2>
        </template>

        <!-- Contenedor anti-overflow horizontal: evita que grids/tablas “rompan” la vista -->
        <div class="w-full max-w-full min-w-0 overflow-x-hidden">
            <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">

                <!-- =========================
                    Header operativo
                    ========================= -->
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm px-4 py-4">
                    <div class="min-w-0">
                        <h1 class="text-l font-bold text-slate-900 dark:text-neutral-100 truncate">
                            Administra todos tus coporativos
                        </h1>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 min-w-0">
                        <!-- Barra de acciones masivas (solo si hay selección) -->
                        <div v-if="selectedCount > 0" class="flex flex-wrap items-center gap-2
                            rounded-2xl border border-slate-200/70 dark:border-white/10
                            bg-slate-50 dark:bg-neutral-950/40 px-3 py-2 min-w-0 max-w-full">
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

                            <button type="button" @click="confirmBulkDelete"
                                class="rounded-xl px-3 py-1.5 text-xs font-semibold
                                bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                                dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                transition active:scale-[0.98]">
                                    Eliminar
                            </button>
                        </div>

                        <!-- CTA principal -->
                        <button type="button" @click="openCreate"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                            bg-slate-900 text-white hover:bg-slate-800
                            dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                            transition active:scale-[0.98] w-full sm:w-auto">
                            Nuevo
                        </button>
                    </div>
                </div>

                <!-- =========================
                    Filtros
                    ========================= -->
                <div class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full">
                    <div class="lg:col-span-6 min-w-0">
                        <AppInput v-model="q" label="Búsqueda"
                        placeholder="Buscar por nombre, RFC, email, teléfono o alias..."/>
                    </div>

                    <div class="lg:col-span-3 min-w-0">
                        <AppSelect v-model="activo" label="Estatus">
                        <option value="all">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Eliminados</option>
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

                <!-- =========================
                    TABLA (Desktop lg+)
                    ========================= -->
                <div class="hidden lg:block overflow-hidden rounded-2xl border  border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm max-w-full">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[920px] text-sm">
                            <thead class="bg-slate-50 dark:bg-neutral-950/60">
                                <!-- Fila de encabezados -->
                                <tr class="text-left text-slate-600 dark:text-neutral-300">
                                    <!-- Columnas -->
                                    <th class="px-4 py-3 font-semibold w-[46px]">
                                        <!-- Checkbox -->
                                        <AppCheckbox
                                            :checked="isAllSelected"
                                            @update:checked="(v) => toggleAllOnPage(!!v)"
                                        />
                                    </th>
                                    <th class="px-4 py-3 font-semibold">
                                        Corporativo
                                    </th>
                                    <th class="px-4 py-3 font-semibold">
                                        Alias
                                    </th>
                                    <th class="px-4 py-3 font-semibold">RFC</th>
                                    <th class="px-4 py-3 font-semibold">Contacto</th>
                                    <th class="px-4 py-3 font-semibold">Dirección</th>
                                    <th class="px-4 py-3 font-semibold">Estatus</th>
                                    <th class="px-4 py-3 font-semibold text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="row in corporativos.data" :key="row.id"
                                class="border-t border-slate-200/70 dark:border-white/10
                                    hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition">
                                    <td class="px-4 py-3 align-middle">
                                        <AppCheckbox
                                            v-model:checked="selectedIdsArray"
                                            :value="row.id"
                                            :label="`Seleccionar corporativo ${row.nombre}`"
                                        />
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <!-- Logo -->
                                            <div class="h-10 w-10 rounded-2xl border border-slate-200/70 dark:border-white/10 overflow-hidden
                                                bg-slate-50 dark:bg-neutral-950 grid place-items-center shrink-0">
                                                <img v-if="row.logo_path"
                                                :src="logoSrc(row.logo_path)!"
                                                class="h-full w-full object-contain"
                                                width="logo"loading="lazy"/>

                                                <span v-else class="text-[10px] font-bold text-slate-500 dark:text-neutral-400">
                                                {{ row.nombre?.slice(0, 2)?.toUpperCase() }}
                                                </span>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                                                {{ row.nombre }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">
                                        {{ row.codigo ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">
                                        {{ row.rfc ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 min-w-0">
                                        <div class="text-slate-700 dark:text-neutral-200 break-all">{{ row.email ?? '—' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-neutral-400 break-all">{{ row.telefono ?? '—' }}</div>
                                    </td>

                                    <td class="px-4 py-3 text-slate-700 dark:text-neutral-200 break-all">
                                        {{ row.direccion ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border
                                            bg-slate-100 text-slate-700 border-slate-200
                                            dark:bg-white/5 dark:text-neutral-200 dark:border-white/10">
                                        <span class="h-1.5 w-1.5 rounded-full"
                                            :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'"/>
                                        {{ row.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center gap-2">
                                            <!-- Editar (siempre) -->
                                            <button type="button" @click="openEdit(row)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                bg-slate-100 text-slate-800 hover:bg-slate-200
                                                dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                                transition active:scale-[0.98]"
                                            title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 3.487a2.1 2.1 0 013.651 1.486c0 .56-.222 1.096-.617 1.492L7.5 18.86l-4 1 1-4L16.862 3.487z" />
                                                </svg>
                                                Editar
                                            </button>

                                            <!-- Si está ACTIVO -> mostrar Eliminar -->
                                            <button v-if="row.activo" type="button" @click="confirmDelete(row)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                    bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                                                    dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                                    transition active:scale-[0.98]"
                                            title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                </svg>
                                                Eliminar
                                            </button>

                                            <!-- Si está en BAJA -> mostrar Activar -->
                                            <button v-else type="button" @click="confirmActivate(row)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                    bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100
                                                    dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20 dark:hover:bg-emerald-500/15
                                                    transition active:scale-[0.98]"
                                            title="Activar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Activar
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

                    <!-- Footer tabla -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                        border-t border-slate-200/70 dark:border-white/10
                        px-4 py-3 bg-white dark:bg-neutral-900">
                        <div class="text-xs text-slate-600 dark:text-neutral-300">
                            <span class="font-semibold">Mostrando {{ corporativos.meta.from ?? 0 }}</span> a
                            <span class="font-semibold">{{ corporativos.meta.to ?? 0 }} registros por página</span> de
                            <span class="font-semibold">{{ corporativos.meta.total }} registros</span>
                        </div>

                        <AppPagination :links="paginationLinks" @go="goTo" />
                    </div>
                </div>

                <!-- =========================
                    CARDS (Mobile/Tablet < lg)
                    ========================= -->
                <div class="lg:hidden grid gap-3 max-w-full">
                    <div v-for="row in corporativos.data" :key="row.id"
                        class="w-full max-w-full min-w-0 overflow-hidden
                        rounded-2xl border border-slate-200/70 dark:border-white/10
                        bg-white dark:bg-neutral-900 shadow-sm p-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="pt-1 shrink-0">
                                <AppCheckbox
                                    v-model:checked="selectedIdsArray"
                                    :value="row.id"
                                    :label="`Seleccionar corporativo ${row.nombre}`"
                                />
                            </div>

                            <div class="h-12 w-12 rounded-2xl border border-slate-200/70 dark:border-white/10 overflow-hidden
                                bg-slate-50 dark:bg-neutral-950 grid place-items-center shrink-0">
                                <img v-if="row.logo_path"
                                :src="logoSrc(row.logo_path)!"
                                class="h-full w-full object-contain"
                                alt="logo" loading="lazy"/>
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
                                    </div>

                                    <span class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border
                                        bg-slate-100 text-slate-700 border-slate-200
                                        dark:bg-white/5 dark:text-neutral-200 dark:border-white/10">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500/80' : 'bg-slate-400/80'" />
                                        {{ row.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>

                                <div class="mt-3 grid gap-1 text-sm text-slate-700 dark:text-neutral-200">
                                    <div class="text-xs break-all"><span class="font-semibold">RFC:</span> {{ row.rfc ?? '—' }}</div>
                                    <div class="text-xs break-all"><span class="font-semibold">Alias:</span> {{ row.codigo ?? '—' }}</div>
                                    <div class="text-xs break-all"><span class="font-semibold">Email:</span> {{ row.email ?? '—' }}</div>
                                    <div class="text-xs break-all"><span class="font-semibold">Teléfono:</span> {{ row.telefono ?? '—' }}</div>
                                </div>

                                <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center gap-2">
                                            <!-- Editar (siempre) -->
                                            <button type="button" @click="openEdit(row)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                bg-slate-100 text-slate-800 hover:bg-slate-200
                                                dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                                                transition active:scale-[0.98]"
                                            title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M16.862 3.487a2.1 2.1 0 013.651 1.486c0 .56-.222 1.096-.617 1.492L7.5 18.86l-4 1 1-4L16.862 3.487z" />
                                                </svg>
                                                Editar
                                            </button>

                                            <!-- Si está ACTIVO -> mostrar Eliminar -->
                                            <button v-if="row.activo" type="button" @click="confirmDelete(row)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                    bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                                                    dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                                                    transition active:scale-[0.98]"
                                            title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 7h12M9 7V4h6v3M10 11v6M14 11v6M5 7l1 13a2 2 0 002 2h8a2 2 0 002-2l1-13" />
                                                </svg>
                                                Eliminar
                                            </button>

                                            <!-- Si está en BAJA -> mostrar Activar -->
                                            <button v-else type="button" @click="confirmActivate(row)"
                                            class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold
                                                    bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100
                                                    dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20 dark:hover:bg-emerald-500/15
                                                    transition active:scale-[0.98]"
                                            title="Activar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Activar
                                            </button>
                                        </div>
                                    </td>
                            </div>
                        </div>
                    </div>

                    <div v-if="!corporativos.data?.length"
                        class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400">
                        No hay corporativos con los filtros actuales.
                    </div>

                    <!-- Paginación simple (mobile/tablet) -->
                    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10
                        bg-white dark:bg-neutral-900 shadow-sm p-4 overflow-hidden">
                        <div class="flex items-center justify-between mb-3 gap-2">
                            <div class="text-xs text-slate-600 dark:text-neutral-300">
                                <span class="font-semibold">{{ corporativos.meta.from ?? 0 }}</span> -
                                <span class="font-semibold">{{ corporativos.meta.to ?? 0 }}</span> /
                                <span class="font-semibold">{{ corporativos.meta.total }}</span>
                            </div>

                            <button type="button"
                                class="text-xs font-semibold text-slate-700 hover:text-slate-900 dark:text-neutral-300 dark:hover:text-white shrink-0"
                                @click="toggleAllOnPage(!isAllSelected)">
                                {{ isAllSelected ? 'Quitar' : 'Seleccionar' }}
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2 max-w-full">
                            <button v-for="(l, idx) in mobileLinks" :key="idx"
                                type="button" @click="goTo(l.url)" :disabled="!l.url"
                                class="px-3 py-2 text-xs font-semibold rounded-xl border
                                max-w-full min-w-0
                                disabled:opacity-50 disabled:cursor-not-allowed
                                transition active:scale-[0.98]"
                                :class=" l.active
                                    ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'">
                                {{ linkLabelShort(l.label) }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
