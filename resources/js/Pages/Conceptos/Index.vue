<script setup lang="ts">
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import { Head } from '@inertiajs/vue3'
    import { computed } from 'vue'

    import Modal from '@/Components/Modal.vue'
    import SecondaryButton from '@/Components/SecondaryButton.vue'
    import DangerButton from '@/Components/DangerButton.vue'

    import type { ConceptosPageProps, ConceptoRow } from './Conceptos.types'
    import { useConceptosIndex } from './useConceptosIndex'

    const props = defineProps<ConceptosPageProps>()

    const {
        state,
        safeLinks,
        goTo,
        hasActiveFilters,
        clearFilters,
        sortLabel,
        toggleSort,

        selectedIds,
        selectedCount,
        isAllSelectedOnPage,
        toggleRow,
        toggleAllOnPage,
        clearSelection,
        destroySelected,

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
    } = useConceptosIndex(props)

    function statusPill(active: boolean) {
        return active
        ? 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
        : 'bg-slate-500/10 text-slate-200 border-white/10'
    }

    const grouped = computed(() => {
        const map = new Map<string, { key: string; label: string; rows: ConceptoRow[] }>()
        for (const r of props.conceptos.data ?? []) {
            const label = r.grupo?.trim() || 'Sin grupo'
            const key = label.toLowerCase()
            if (!map.has(key)) map.set(key, { key, label, rows: [] })
            map.get(key)!.rows.push(r)
        }
        return Array.from(map.values()).sort((a, b) => a.label.localeCompare(b.label, 'es'))
    })

    const selectBase =
    'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
    'border-slate-200 bg-white text-slate-900 focus:ring-slate-200 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:focus:ring-white/10'

    const inputBase =
    'mt-1 w-full rounded-xl px-3 py-2 text-sm border transition focus:outline-none focus:ring-2 ' +
    'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'
</script>

<template>
    <Head title="Conceptos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Conceptos</h2>
        </template>

        <div class="w-full max-w-full min-w-0 overflow-x-hidden">
            <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm px-4 py-4">
                    <div class="min-w-0">
                        <h1 class="text-base font-bold text-slate-900 dark:text-neutral-100 truncate">
                        Catálogo de conceptos de requisición
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-neutral-400">Orden, control y trazabilidad.</p>
                    </div>

                    <button type="button" @click="openCreate"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                            bg-slate-900 text-white hover:bg-slate-800
                            dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                            transition active:scale-[0.98] w-full sm:w-auto">
                        Nuevo concepto
                    </button>
                </div>

                <div v-if="selectedCount > 0"
                class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-slate-50 dark:bg-white/5 px-4 py-3">

                    <div class="text-sm text-slate-700 dark:text-neutral-200">
                        Seleccionados: <span class="font-extrabold">{{ selectedCount }}</span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <button type="button" @click="clearSelection"
                        class="rounded-xl px-3 py-2 text-sm font-semibold
                            bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                            dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-white/10
                            transition active:scale-[0.98]">
                            Limpiar
                        </button>

                        <button type="button" @click="destroySelected"
                        class="rounded-xl px-3 py-2 text-sm font-extrabold
                                bg-rose-600 text-white hover:bg-rose-500
                                transition active:scale-[0.98]">
                            Eliminar seleccionados
                        </button>
                    </div>
                </div>

                <div class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
                    rounded-2xl border border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm p-4 max-w-full">
                    <div class="lg:col-span-6 min-w-0">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Búsqueda</label>
                        <input v-model="state.q" type="text" placeholder="Grupo o concepto..." :class="inputBase" />
                    </div>

                    <div class="lg:col-span-3 min-w-0">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Grupo</label>
                        <select v-model="state.grupo" :class="selectBase">
                        <option value="">Todos</option>
                        <option v-for="g in props.grupos" :key="g" :value="g">{{ g }}</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2 min-w-0">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Estatus</label>
                        <select v-model="state.activo" :class="selectBase">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                        </select>
                    </div>

                    <div class="lg:col-span-1 min-w-0">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Por página</label>
                        <select v-model="state.perPage" :class="selectBase">
                        <option :value="10">10</option>
                        <option :value="15">15</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        </select>
                    </div>

                    <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="text-sm text-slate-600 dark:text-neutral-300">
                            Mostrando
                            <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.conceptos.from ?? 0 }}</span>
                            a
                            <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.conceptos.to ?? 0 }}</span>
                            de
                            <span class="font-semibold text-slate-900 dark:text-neutral-100">{{ props.conceptos.total }}</span>
                        </div>

                        <SecondaryButton
                            type="button" @click="clearFilters"
                            :disabled="!hasActiveFilters"
                            class="rounded-xl disabled:opacity-50">
                            Limpiar
                        </SecondaryButton>
                    </div>
                </div>

                <div class="hidden lg:block overflow-hidden rounded-2xl border
                    border-slate-200/70 dark:border-white/10
                    bg-white dark:bg-neutral-900 shadow-sm max-w-full">

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-sm">
                        <thead class="bg-slate-50 dark:bg-neutral-950/60">
                            <tr class="text-left text-slate-600 dark:text-neutral-300">
                                <th class="px-4 py-3 font-semibold w-[46px]">
                                    <input type="checkbox" class="h-4 w-4 rounded border-slate-300 dark:border-white/10
                                    bg-white dark:bg-neutral-900" :checked="isAllSelectedOnPage"
                                    @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"/>
                                </th>

                                <th class="px-4 py-3 font-semibold">
                                    <div class="inline-flex items-center gap-2">
                                    <span>Concepto</span>
                                    <button type="button" @click="toggleSort"
                                        class="rounded-lg border px-2 py-1 text-xs font-extrabold
                                            border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                                            dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                                            transition active:scale-[0.98]"
                                        :title="`Ordenar ${sortLabel}`">
                                        {{ sortLabel }}
                                    </button>
                                    </div>
                                </th>

                                <th class="px-4 py-3 font-semibold">Grupo</th>
                                <th class="px-4 py-3 font-semibold">Estatus</th>
                                <th class="px-4 py-3 font-semibold text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="g in grouped" :key="g.key">
                            <tr class="border-t border-slate-200/70 dark:border-white/10">
                                <td colspan="5" class="px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">{{ g.label }}</div>
                                    <div class="text-xs text-slate-500 dark:text-neutral-400">{{ g.rows.length }} concepto(s)</div>
                                </div>
                                </td>
                            </tr>

                            <tr v-for="row in g.rows" :key="row.id"
                                class="border-t border-slate-200/70 dark:border-white/10
                                    hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition">
                                <td class="px-4 py-3 align-middle">
                                    <input type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 dark:border-white/10
                                        bg-white dark:bg-neutral-900" :checked="selectedIds.has(row.id)"
                                        @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"/>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                                        {{ row.nombre }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">
                                    {{ row.grupo }}
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border" :class="statusPill(!!row.activo)">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-400' : 'bg-slate-400'" />
                                        {{ row.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap text-right">
                                    <div class="inline-flex gap-2">
                                        <button type="button"
                                        class="rounded-xl px-3 py-2 text-xs font-extrabold
                                            border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                                            dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                                            transition active:scale-[0.98]"
                                        @click="openEdit(row)">
                                        Editar
                                        </button>
                                        <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
                                    </div>
                                </td>
                            </tr>
                            </template>

                            <tr v-if="props.conceptos.data.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-slate-500 dark:text-neutral-400">
                                    No hay conceptos con los filtros actuales.
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                            border-t border-slate-200/70 dark:border-white/10
                            px-4 py-3 bg-white dark:bg-neutral-900">
                        <div class="text-xs text-slate-600 dark:text-neutral-300">
                            Página
                            <span class="font-semibold">{{ props.conceptos.current_page }}</span> de
                            <span class="font-semibold">{{ props.conceptos.last_page }}</span>
                        </div>

                        <nav class="flex flex-wrap gap-2">
                            <button v-for="(link, i) in safeLinks" :key="i" type="button" @click="goTo(link.url)"
                                :disabled="!link.url"
                                class="rounded-xl px-3 py-1.5 text-sm font-semibold border transition
                                    border-slate-200 bg-white text-slate-800 hover:bg-slate-50
                                    disabled:opacity-50 disabled:cursor-not-allowed
                                    dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5"
                                :class="link.active ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''">
                                {{ link.label }}
                            </button>
                        </nav>
                    </div>
                </div>

                <div class="lg:hidden grid gap-3 max-w-full">
                    <template v-for="g in grouped" :key="g.key">
                        <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-extrabold text-slate-900 dark:text-neutral-100">{{ g.label }}</div>
                                <div class="text-xs text-slate-500 dark:text-neutral-400">{{ g.rows.length }} concepto(s)</div>
                            </div>
                        </div>

                        <div v-for="row in g.rows" :key="row.id"
                        class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900
                            shadow-sm p-4 hover:shadow-md dark:hover:shadow-black/40 transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <input type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"
                                    :checked="selectedIds.has(row.id)" @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"/>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">{{ row.nombre }}</div>
                                    <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">{{ row.grupo }}</div>
                                </div>
                            </div>

                            <span class="shrink-0 inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border" :class="statusPill(!!row.activo)">
                                <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-400' : 'bg-slate-400'" />
                                {{ row.activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <button type="button"
                            class="rounded-xl px-3 py-2 text-xs font-extrabold
                                border border-slate-200 bg-white text-slate-700 hover:bg-slate-50
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                                transition active:scale-[0.98]"
                            @click="openEdit(row)">
                            Editar
                            </button>
                            <DangerButton class="rounded-xl" @click="destroyRow(row)">Eliminar</DangerButton>
                        </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <Modal :show="modalOpen" maxWidth="2xl" @close="closeModal">
            <div class="rounded-3xl border border-slate-200/60 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-2xl">
                <div class="p-6 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-xl font-extrabold text-slate-900 dark:text-neutral-100">
                                {{ isEdit ? 'Editar concepto' : 'Nuevo concepto' }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                                Estandariza el catálogo para requisiciones.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-semibold
                                border border-slate-200 bg-white hover:bg-slate-50
                                dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 dark:text-neutral-100
                                transition active:scale-[0.98]"
                            @click="closeModal">
                            Cerrar
                        </button>
                    </div>

                    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Grupo (Select para evitar inputs diferentes)-->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">
                                Grupo *
                            </label>
                            <select v-model="form.grupo" :class="inputBase">
                                <option value="" disabled>Selecciona un grupo</option>
                                <option value="Administrativo">Administrativo</option>
                                <option value="Comercial">Comercial</option>
                                <option value="Community Manager">Community Manager</option>
                                <option value="Compras">Compras</option>
                                <option value="Contabilidad">Contabilidad</option>
                                <option value="Dirección">Dirección</option>
                                <option value="Diseño">Diseño</option>
                                <option value="General">General</option>
                                <option value="Operación">Operación</option>
                                <option value="Recursos Humanos">Recursos Humanos</option>
                                <option value="Sistemas">Sistemas</option>
                                <option value="Ventas">Ventas</option>
                            </select>
                            <p v-if="errors.grupo" class="mt-1 text-xs text-rose-500">
                                {{ errors.grupo }}
                            </p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Nombre *</label>
                            <input v-model="form.nombre" type="text" placeholder="Ej. Gasolina" :class="inputBase" />
                            <p v-if="errors.nombre" class="mt-1 text-xs text-rose-500">{{ errors.nombre }}</p>
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-3 pt-1">
                            <input id="c-activo" type="checkbox" v-model="form.activo"
                            class="h-4 w-4 rounded border-slate-300 dark:border-white/10 bg-white dark:bg-neutral-900"/>
                            <label for="c-activo" class="text-sm font-semibold text-slate-800 dark:text-neutral-100">
                            Concepto activo
                            </label>
                        </div>
                    </div>

                    <div class="mt-7 flex flex-col sm:flex-row gap-3 sm:justify-end">
                        <SecondaryButton class="rounded-2xl" @click="closeModal">Cancelar</SecondaryButton>

                        <button type="button" @click="submit" :disabled="!canSubmit"
                            class="rounded-2xl px-6 py-3 text-sm font-extrabold tracking-wide
                                bg-slate-900 text-white hover:bg-slate-800
                                dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                                disabled:opacity-50 disabled:cursor-not-allowed
                                transition active:scale-[0.98]">
                            {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear') }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
  </AuthenticatedLayout>
</template>

<style scoped>
    :global(html.dark select option) {
    background: #0a0a0a;
    color: #f5f5f5;
    }
</style>
