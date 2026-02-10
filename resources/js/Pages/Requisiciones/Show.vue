<script setup lang="ts">
    import { computed, ref } from 'vue'
    import { Head, router } from '@inertiajs/vue3'
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

    import {
    ArrowLeft,
    Copy,
    Printer,
    FileText,
    Paperclip,
    ExternalLink,
    Building2,
    Store,
    User,
    Calendar,
    Clock,
    BadgeCheck,
    Link2,
    LayoutGrid,
    List,
    ChevronRight,
    } from 'lucide-vue-next'

    declare const route: any

    type Money = number | string | null | undefined

    const props = defineProps<{
    requisicion: any
    detalles?: any[]
    comprobantes?: any[]
    pdf?: { requisicion_url?: string | null; files?: { label: string; url: string }[] }
    }>()

    /** Normalizo para aceptar raw o {data: ...} (según controlador) */
    const req = computed(() => {
    const raw = props.requisicion
    return raw?.data ?? raw ?? null
    })

    const detalles = computed(() => (Array.isArray(props.detalles) ? props.detalles : []))
    const comprobantes = computed(() => (Array.isArray(props.comprobantes) ? props.comprobantes : []))
    const pagosFiles = computed(() => (Array.isArray(props.pdf?.files) ? props.pdf?.files : []))

    const title = computed(() => (req.value?.folio ? `Requisición ${req.value.folio}` : 'Requisición'))

    /** Tabs para NO saturar la pantalla */
    const mainTab = ref<'items' | 'comprobantes'>('items')
    const previewTab = ref<'comprobantes' | 'pagos'>('comprobantes')

    const money = (v: Money) => {
    const n = typeof v === 'string' ? Number(String(v).replace(/,/g, '')) : Number(v ?? 0)
    const safe = Number.isFinite(n) ? n : 0
    return safe.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
    }

    const fmtDate = (iso?: string | null) => {
    if (!iso) return '—'
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleString('es-MX', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    })
    }

    const onlyDate = (iso?: string | null) => {
    if (!iso) return '—'
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: '2-digit' })
    }

    /** Orden del flujo operativo (backend manda req.status) */
    const statusOrder = [
    'BORRADOR',
    'CAPTURADA',
    'PAGO_AUTORIZADO',
    'PAGADA',
    'POR_COMPROBAR',
    'COMPROBACION_ACEPTADA',
    'COMPROBACION_RECHAZADA',
    ] as const

    /** Steps “friendly” para UI final */
    const steps = computed(() => [
    { key: 'BORRADOR', label: 'Borrador', hint: 'En captura' },
    { key: 'CAPTURADA', label: 'Capturada', hint: 'En revisión' },
    { key: 'PAGO_AUTORIZADO', label: 'Pago autorizado', hint: 'Listo para pago' },
    { key: 'PAGADA', label: 'Pagada', hint: 'Pago realizado' },
    { key: 'POR_COMPROBAR', label: 'Por comprobar', hint: 'Pendiente de evidencias' },
    { key: 'COMPROBACION_ACEPTADA', label: 'Comprobación aceptada', hint: 'Cerrada' },
    ] as const)

    const currentIndex = computed(() => {
    const s = String(req.value?.status ?? '')
    const idx = statusOrder.indexOf(s as any)
    return idx >= 0 ? idx : 0
    })

    const currentStep = computed(() => steps.value[Math.min(currentIndex.value, steps.value.length - 1)])
    const nextStep = computed(() => steps.value[Math.min(currentIndex.value + 1, steps.value.length - 1)])

    const statusLabel = computed(() => currentStep.value?.label ?? (req.value?.status ?? '—'))
    const statusHint = computed(() => currentStep.value?.hint ?? '—')

    /** Color de badge por estatus (más evidente y usable) */
    const badgeClass = computed(() => {
    const s = String(req.value?.status ?? '')
    if (s === 'PAGADA' || s === 'COMPROBACION_ACEPTADA') {
        return 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-200 border-emerald-500/30'
    }
    if (s === 'COMPROBACION_RECHAZADA') {
        return 'bg-rose-500/15 text-rose-800 dark:text-rose-200 border-rose-500/30'
    }
    if (s === 'CAPTURADA' || s === 'PAGO_AUTORIZADO' || s === 'POR_COMPROBAR') {
        return 'bg-indigo-500/15 text-indigo-800 dark:text-indigo-200 border-indigo-500/30'
    }
    return 'bg-slate-500/15 text-slate-800 dark:text-slate-200 border-slate-500/30'
    })

    /** Acciones de productividad */
    const copyFolio = async () => {
    const folio = String(req.value?.folio ?? '').trim()
    if (!folio) return
    try {
        await navigator.clipboard.writeText(folio)
    } catch {
        // no-op
    }
    }

    const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(String(window.location.href))
    } catch {
        // no-op
    }
    }

    const goBack = () => {
    router.visit(route('requisiciones.index'))
    }

    /** Un solo botón: imprime la pantalla y el usuario puede “Guardar como PDF” */
    const printView = () => {
    window.print()
    }

    /** Helper: bajar fricción de navegación */
    const scrollToId = (id: string) => {
    const el = document.getElementById(id)
    if (!el) return
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }

    /** Totales (si backend no los manda, aquí se calculan desde detalles) */
    const subtotalCalc = computed(() => detalles.value.reduce((acc, d) => acc + Number(d?.subtotal ?? 0), 0))
    const ivaCalc = computed(() => detalles.value.reduce((acc, d) => acc + Number(d?.iva ?? 0), 0))
    const totalCalc = computed(() => detalles.value.reduce((acc, d) => acc + Number(d?.total ?? 0), 0))

    /** Para mostrar totales consistentes (preferir req si existe) */
    const subtotalShown = computed(() => req.value?.monto_subtotal ?? subtotalCalc.value)
    const totalShown = computed(() => req.value?.monto_total ?? totalCalc.value)
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout>
        <template #header>
        <div class="flex items-center justify-between gap-3 min-w-0">
            <div class="min-w-0">
            <h2 class="text-xl font-black leading-tight text-slate-900 dark:text-zinc-100 truncate">
                {{ title }}
            </h2>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-neutral-300">
                <span class="inline-flex items-center gap-2">
                <span class="font-semibold">Estado:</span>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-bold" :class="badgeClass">
                    <BadgeCheck class="h-3.5 w-3.5" />
                    {{ statusLabel }}
                </span>
                <span class="text-xs opacity-80">({{ statusHint }})</span>
                </span>

                <span class="hidden sm:inline opacity-50">•</span>

                <span class="inline-flex items-center gap-2">
                <Calendar class="h-4 w-4 opacity-80" />
                <span class="font-semibold">Capturada:</span>
                <span>{{ fmtDate(req?.created_at ?? null) }}</span>
                </span>

                <span class="hidden sm:inline opacity-50">•</span>

                <span class="inline-flex items-center gap-2">
                <Clock class="h-4 w-4 opacity-80" />
                <span class="font-semibold">Actualizada:</span>
                <span>{{ fmtDate(req?.updated_at ?? null) }}</span>
                </span>
            </div>
            </div>

            <!-- Action bar (compacta, útil, sin duplicados) -->
            <div class="hidden md:flex items-center gap-2 shrink-0">
            <button
                type="button"
                @click="goBack"
                class="btn-soft"
                title="Volver"
            >
                <ArrowLeft class="h-4 w-4" />
                Volver
            </button>

            <button
                type="button"
                @click="copyFolio"
                class="btn-soft"
                title="Copiar folio"
            >
                <Copy class="h-4 w-4" />
                Copiar folio
            </button>

            <button
                type="button"
                @click="copyLink"
                class="btn-soft"
                title="Copiar enlace"
            >
                <Link2 class="h-4 w-4" />
                Copiar enlace
            </button>

            <button
                type="button"
                @click="printView"
                class="btn-primary"
                title="Imprimir o guardar como PDF"
            >
                <Printer class="h-4 w-4" />
                Imprimir / PDF
            </button>
            </div>
        </div>
        </template>

        <!-- Wrapper full width con márgenes laterales razonables en TODOS los breakpoints -->
        <div class="w-full overflow-x-hidden">
        <div class="mx-auto w-full max-w-[1920px] min-w-0 px-3 sm:px-4 md:px-6 lg:px-7 xl:px-8 2xl:px-8 py-4 sm:py-6">
            <!-- Mobile action bar -->
            <div class="md:hidden mb-3 grid grid-cols-2 gap-2">
            <button type="button" @click="goBack" class="btn-soft justify-center">
                <ArrowLeft class="h-4 w-4" />
                Volver
            </button>
            <button type="button" @click="printView" class="btn-primary justify-center">
                <Printer class="h-4 w-4" />
                Imprimir / PDF
            </button>
            <button type="button" @click="copyFolio" class="btn-soft justify-center col-span-1">
                <Copy class="h-4 w-4" />
                Copiar folio
            </button>
            <button type="button" @click="copyLink" class="btn-soft justify-center col-span-1">
                <Link2 class="h-4 w-4" />
                Copiar enlace
            </button>
            </div>

            <!-- Resumen (lo importante primero) -->
            <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm overflow-hidden">
            <!-- Header visual (menos plano, más “producto”) -->
            <div class="px-4 sm:px-6 py-4 sm:py-5 bg-gradient-to-r from-slate-50/80 via-white/70 to-indigo-50/50 dark:from-neutral-900 dark:via-neutral-900/70 dark:to-indigo-950/25 border-b border-slate-200/70 dark:border-white/10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 min-w-0">
                <!-- Identidad / folio -->
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-12 w-12 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-900 grid place-items-center shrink-0 overflow-hidden shadow-sm">
                    <img
                        v-if="req?.comprador?.logo_url"
                        :src="req.comprador.logo_url"
                        alt="Logo"
                        class="h-full w-full object-contain p-2"
                    />
                    <FileText v-else class="h-6 w-6 text-slate-700 dark:text-neutral-200" />
                    </div>

                    <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="text-lg sm:text-xl font-black text-slate-900 dark:text-neutral-100 truncate">
                        {{ req?.folio ?? '—' }}
                        </div>

                        <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-black" :class="badgeClass">
                        <BadgeCheck class="h-3.5 w-3.5" />
                        {{ statusLabel }}
                        </span>

                        <span class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-neutral-900/60 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-neutral-200">
                        <ChevronRight class="h-3.5 w-3.5 opacity-80" />
                        Siguiente: {{ nextStep?.label ?? '—' }}
                        </span>
                    </div>

                    <div class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                        {{ currentStep?.hint ?? '—' }}
                    </div>
                    </div>
                </div>

                <!-- Quick nav (reduce fricción: saltos y tabs) -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="chip" @click="scrollToId('bloque-flujo')">
                    <LayoutGrid class="h-4 w-4" />
                    Flujo
                    </button>
                    <button
                    type="button"
                    class="chip"
                    @click="() => { mainTab = 'items'; scrollToId('bloque-contenido') }"
                    >
                    <List class="h-4 w-4" />
                    Items
                    </button>
                    <button
                    type="button"
                    class="chip"
                    @click="() => { mainTab = 'comprobantes'; previewTab = 'comprobantes'; scrollToId('bloque-contenido') }"
                    >
                    <Paperclip class="h-4 w-4" />
                    Comprobantes
                    </button>
                </div>
                </div>
            </div>

            <!-- Flujo operativo: más claro, más visual -->
            <div id="bloque-flujo" class="border-t border-slate-200/70 dark:border-white/10 p-4 sm:p-6">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                <div>
                    <div class="text-sm font-black text-slate-900 dark:text-neutral-100">
                    Flujo operativo
                    </div>
                    <div class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                    Estás en <span class="font-extrabold text-slate-900 dark:text-neutral-100">{{ statusLabel }}</span>:
                    <span class="font-semibold">{{ statusHint }}</span>
                    </div>
                </div>

                <div class="text-xs text-slate-500 dark:text-neutral-400">
                    Los pasos marcados como completados ya pasaron; el resaltado es el actual.
                </div>
                </div>

                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 min-w-0">
                <button
                    v-for="(s, i) in steps"
                    :key="s.key"
                    type="button"
                    class="step"
                    :class="[
                    i < currentIndex ? 'step-done' : '',
                    i === currentIndex ? 'step-now' : '',
                    i > currentIndex ? 'step-next' : '',
                    ]"
                >
                    <div class="text-sm font-black truncate">{{ s.label }}</div>
                    <div class="text-xs mt-0.5 opacity-80 truncate">
                    {{ i < currentIndex ? 'Completado' : (i === currentIndex ? 'Actual' : 'Pendiente') }}
                    <span class="mx-1 opacity-60">•</span>
                    {{ s.hint }}
                    </div>
                </button>
                </div>
            </div>

            <!-- Grid: todo lo “Datos clave” junto, ordenado (no duplicado) -->
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 min-w-0">
                <!-- Panel principal de datos (izquierda) -->
                <div class="lg:col-span-8 min-w-0">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Partes -->
                    <div class="card-soft">
                        <div class="card-title">
                        <Building2 class="h-4 w-4" />
                        Partes
                        </div>

                        <div class="mt-3 grid gap-3">
                        <div class="row-kv">
                            <div class="k">Comprador</div>
                            <div class="v truncate">{{ req?.comprador?.nombre ?? '—' }}</div>
                        </div>

                        <div class="row-kv">
                            <div class="k">Sucursal</div>
                            <div class="v truncate">{{ req?.sucursal?.nombre ?? '—' }}</div>
                        </div>

                        <div class="row-kv">
                            <div class="k">Solicitante</div>
                            <div class="v truncate">{{ req?.solicitante?.nombre ?? '—' }}</div>
                        </div>
                        </div>
                    </div>

                    <!-- Concepto/Proveedor -->
                    <div class="card-soft">
                        <div class="card-title">
                        <FileText class="h-4 w-4" />
                        Datos clave
                        </div>

                        <div class="mt-3 grid gap-3">
                        <div class="row-kv">
                            <div class="k">Concepto</div>
                            <div class="v truncate">{{ req?.concepto?.nombre ?? '—' }}</div>
                        </div>

                        <div class="row-kv">
                            <div class="k">Proveedor</div>
                            <div class="v truncate">{{ req?.proveedor?.razon_social ?? req?.proveedor?.nombre ?? '—' }}</div>
                        </div>

                        <div class="row-kv">
                            <div class="k">Observaciones</div>
                            <div class="v break-words">{{ req?.observaciones ?? '—' }}</div>
                        </div>
                        </div>
                    </div>

                    <!-- Totales -->
                    <div class="card-soft md:col-span-2">
                        <div class="card-title">
                        <BadgeCheck class="h-4 w-4" />
                        Totales
                        </div>

                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="stat">
                            <div class="stat-k">Subtotal</div>
                            <div class="stat-v">{{ money(subtotalShown) }}</div>
                        </div>
                        <div class="stat">
                            <div class="stat-k">IVA</div>
                            <div class="stat-v">{{ money(ivaCalc) }}</div>
                        </div>
                        <div class="stat stat-strong">
                            <div class="stat-k">Total</div>
                            <div class="stat-v">{{ money(totalShown) }}</div>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Panel derecho: Fechas + Vista previa (solo comprobantes y pagos) -->
                <div class="lg:col-span-4 min-w-0">
                    <div class="card-soft">
                    <div class="card-title">
                        <Calendar class="h-4 w-4" />
                        Fechas
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3">
                        <div class="stat">
                        <div class="stat-k">Capturada</div>
                        <div class="stat-v">{{ onlyDate(req?.created_at ?? null) }}</div>
                        </div>

                        <div class="stat">
                        <div class="stat-k">Solicitud</div>
                        <div class="stat-v">{{ onlyDate(req?.fecha_solicitud ?? null) }}</div>
                        </div>

                        <div class="stat">
                        <div class="stat-k">Autorización</div>
                        <div class="stat-v">{{ onlyDate(req?.fecha_autorizacion ?? null) }}</div>
                        </div>

                        <div class="stat">
                        <div class="stat-k">Pago</div>
                        <div class="stat-v">{{ onlyDate(req?.fecha_pago ?? null) }}</div>
                        </div>
                    </div>
                    </div>

                </div>
                </div>
            </div>
            </div>

            <!-- Contenido principal: tab Items / Comprobantes (menos ruido visual) -->
            <div id="bloque-contenido" class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-4 min-w-0">
            <div class="lg:col-span-12 min-w-0">
                <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                    <div class="seg">
                        <button
                        type="button"
                        class="seg-btn"
                        :class="mainTab === 'items' ? 'seg-btn-active' : ''"
                        @click="mainTab = 'items'"
                        >
                        Items ({{ detalles.length }})
                        </button>
                        <button
                        type="button"
                        class="seg-btn"
                        :class="mainTab === 'comprobantes' ? 'seg-btn-active' : ''"
                        @click="mainTab = 'comprobantes'"
                        >
                        Comprobantes ({{ comprobantes.length }})
                        </button>
                    </div>
                    </div>

                    <div class="text-sm text-slate-500 dark:text-neutral-400">
                    Subtotal: <span class="font-black text-slate-900 dark:text-neutral-100">{{ money(subtotalShown) }}</span>
                    <span class="mx-2 opacity-50">•</span>
                    Total: <span class="font-black text-slate-900 dark:text-neutral-100">{{ money(totalShown) }}</span>
                    </div>
                </div>

                <!-- ITEMS -->
                <div v-if="mainTab === 'items'" class="px-4 sm:px-6 pb-6">
                    <!-- Mobile cards -->
                    <div class="lg:hidden">
                    <div v-if="detalles.length === 0" class="empty">
                        No hay items capturados.
                    </div>

                    <div v-else class="grid gap-3">
                        <div
                        v-for="d in detalles"
                        :key="d.id"
                        class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-900/50 p-4 hover:bg-white/90 dark:hover:bg-neutral-900/70 transition min-w-0"
                        >
                        <div class="flex items-start justify-between gap-3 min-w-0">
                            <div class="min-w-0">
                            <div class="text-sm font-black text-slate-900 dark:text-neutral-100 break-words">
                                {{ d.descripcion ?? '—' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500 dark:text-neutral-400">
                                Cantidad:
                                <span class="font-semibold text-slate-700 dark:text-neutral-200">{{ d.cantidad ?? '—' }}</span>
                                <span class="mx-2">•</span>
                                Sucursal:
                                <span class="font-semibold text-slate-700 dark:text-neutral-200">{{ d.sucursal?.nombre ?? '—' }}</span>
                            </div>
                            </div>

                            <div class="text-right shrink-0">
                            <div class="text-xs text-slate-500 dark:text-neutral-400">Total</div>
                            <div class="text-sm font-black text-slate-900 dark:text-neutral-100">{{ money(d.total) }}</div>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                            <div class="mini">
                            <div class="mini-k">Importe</div>
                            <div class="mini-v">{{ money(d.subtotal) }}</div>
                            </div>
                            <div class="mini">
                            <div class="mini-k">IVA</div>
                            <div class="mini-v">{{ money(d.iva) }}</div>
                            </div>
                            <div class="mini mini-strong">
                            <div class="mini-k">Total</div>
                            <div class="mini-v">{{ money(d.total) }}</div>
                            </div>
                        </div>
                        </div>
                    </div>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden lg:block">
                    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-900/50 overflow-hidden">
                        <table class="w-full table-fixed">
                        <thead class="bg-slate-50/80 dark:bg-neutral-900/80 sticky top-0">
                            <tr class="text-left text-xs uppercase tracking-wider text-slate-500 dark:text-neutral-400">
                            <th class="px-4 py-3 w-24">Cantidad</th>
                            <th class="px-4 py-3 w-48">Sucursal</th>
                            <th class="px-4 py-3">Descripción</th>
                            <th class="px-4 py-3 w-36 text-right">Importe</th>
                            <th class="px-4 py-3 w-28 text-right">IVA</th>
                            <th class="px-4 py-3 w-36 text-right">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="detalles.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-600 dark:text-neutral-300">
                                No hay items capturados.
                            </td>
                            </tr>

                            <tr
                            v-for="(d, i) in detalles"
                            :key="d.id"
                            class="border-t border-slate-200/70 dark:border-white/10 hover:bg-white/90 dark:hover:bg-neutral-900/70 transition"
                            :class="i % 2 === 0 ? 'bg-white/40 dark:bg-neutral-900/30' : ''"
                            >
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900 dark:text-neutral-100">
                                {{ d.cantidad ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-neutral-200 truncate">
                                {{ d.sucursal?.nombre ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-neutral-200 truncate">
                                {{ d.descripcion ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-neutral-100">
                                {{ money(d.subtotal) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-slate-900 dark:text-neutral-100">
                                {{ money(d.iva) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-black text-slate-900 dark:text-neutral-100">
                                {{ money(d.total) }}
                            </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-6 text-sm">
                        <div class="text-slate-500 dark:text-neutral-400">
                        Subtotal: <span class="font-black text-slate-900 dark:text-neutral-100">{{ money(subtotalShown) }}</span>
                        </div>
                        <div class="text-slate-500 dark:text-neutral-400">
                        IVA: <span class="font-black text-slate-900 dark:text-neutral-100">{{ money(ivaCalc) }}</span>
                        </div>
                        <div class="text-slate-500 dark:text-neutral-400">
                        Total: <span class="font-black text-slate-900 dark:text-neutral-100">{{ money(totalShown) }}</span>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- COMPROBANTES -->
                <div v-else class="px-4 sm:px-6 pb-6">
                    <div v-if="comprobantes.length === 0" class="empty">
                    No hay comprobantes cargados.
                    </div>

                    <div v-else class="grid gap-3">
                    <div
                        v-for="c in comprobantes"
                        :key="c.id"
                        class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-neutral-900/50 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 hover:bg-white/90 dark:hover:bg-neutral-900/70 transition min-w-0"
                    >
                        <div class="min-w-0">
                        <div class="text-sm font-black text-slate-900 dark:text-neutral-100 truncate">
                            {{ c.tipo_doc ?? 'OTRO' }} #{{ c.id }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-neutral-400">
                            Cargado por:
                            <span class="font-semibold text-slate-700 dark:text-neutral-200">Usuario #{{ c.user_carga_id ?? '—' }}</span>
                            <span class="mx-2 opacity-60">•</span>
                            {{ fmtDate(c.created_at ?? null) }}
                        </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 shrink-0">
                        <div class="mini">
                            <div class="mini-k">Subtotal</div>
                            <div class="mini-v">{{ money(c.subtotal) }}</div>
                        </div>
                        <div class="mini mini-strong">
                            <div class="mini-k">Total</div>
                            <div class="mini-v">{{ money(c.total) }}</div>
                        </div>
                        </div>
                    </div>
                    </div>

                    <!-- CTA para que el usuario haga lo lógico -->
                    <div class="mt-4 flex flex-wrap gap-2 justify-end">
                    <button type="button" class="btn-soft" @click="previewTab = 'comprobantes'">
                        <Paperclip class="h-4 w-4" />
                        Ver en vista previa
                    </button>
                    <button type="button" class="btn-primary" @click="printView">
                        <Printer class="h-4 w-4" />
                        Imprimir / PDF
                    </button>
                    </div>

                </div>
                </div>
            </div>
            </div>

            <!-- Vista previa: Comprobantes / Pagos -->
                    <div class="mt-4 card-soft">
                    <div class="flex items-center justify-between gap-3">
                        <div class="card-title !mb-0">
                        <ExternalLink class="h-4 w-4" />
                        Vista previa
                        </div>

                        <div class="seg">
                        <button
                            type="button"
                            class="seg-btn"
                            :class="previewTab === 'comprobantes' ? 'seg-btn-active' : ''"
                            @click="previewTab = 'comprobantes'"
                        >
                            Comprobantes
                        </button>
                        <button
                            type="button"
                            class="seg-btn"
                            :class="previewTab === 'pagos' ? 'seg-btn-active' : ''"
                            @click="previewTab = 'pagos'"
                        >
                            Pagos
                        </button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <!-- Preview comprobantes -->
                        <div v-if="previewTab === 'comprobantes'">
                        <div v-if="comprobantes.length === 0" class="empty">
                            No hay comprobantes cargados.
                        </div>

                        <div v-else class="grid gap-2">
                            <div
                            v-for="c in comprobantes.slice(0, 4)"
                            :key="c.id"
                            class="preview-row"
                            >
                            <div class="min-w-0">
                                <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100 truncate">
                                {{ c.tipo_doc ?? 'OTRO' }} #{{ c.id }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-neutral-400 mt-0.5 truncate">
                                {{ fmtDate(c.created_at ?? null) }}
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="text-xs text-slate-500 dark:text-neutral-400">Total</div>
                                <div class="text-sm font-black text-slate-900 dark:text-neutral-100">
                                {{ money(c.total) }}
                                </div>
                            </div>
                            </div>

                            <button
                            type="button"
                            class="btn-soft w-full justify-center"
                            @click="() => { mainTab = 'comprobantes'; scrollToId('bloque-contenido') }"
                            >
                            Ver todos
                            </button>
                        </div>
                        </div>

                        <!-- Preview pagos/archivos -->
                        <div v-else>
                        <div v-if="pagosFiles.length === 0" class="empty">
                            No hay archivos de pago disponibles.
                        </div>

                        <div v-else class="grid gap-2">
                            <a
                            v-for="(f, idx) in pagosFiles.slice(0, 6)"
                            :key="`${f.label}-${idx}`"
                            :href="f.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="file-link"
                            >
                            <span class="truncate font-semibold">{{ f.label ?? 'Archivo' }}</span>
                            <ExternalLink class="h-4 w-4 opacity-80" />
                            </a>
                        </div>
                        </div>
                    </div>
                    </div>
        </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Utilidades locales (sin pelear con tu Tailwind global) */
.btn-soft{
  display:inline-flex;align-items:center;gap:.5rem;
  border-radius:1rem;
  border:1px solid rgba(148,163,184,.35);
  background:rgba(255,255,255,.75);
  padding:.55rem .85rem;
  font-weight:700;font-size:.875rem;
  color:rgb(15 23 42);
  box-shadow:0 1px 0 rgba(0,0,0,.04);
  transition:transform .12s ease, background .12s ease, opacity .12s ease;
}
.dark .btn-soft{
  background:rgba(23,23,23,.55);
  border-color:rgba(255,255,255,.10);
  color:rgb(245 245 245);
}
.btn-soft:hover{background:rgba(255,255,255,.95);transform:translateY(-1px)}
.dark .btn-soft:hover{background:rgba(23,23,23,.75)}
.btn-soft:active{transform:translateY(0)}

.btn-primary{
  display:inline-flex;align-items:center;gap:.5rem;
  border-radius:1rem;
  border:1px solid rgba(99,102,241,.35);
  background:linear-gradient(135deg, rgb(79 70 229), rgb(99 102 241));
  padding:.55rem .95rem;
  font-weight:800;font-size:.875rem;
  color:white;
  box-shadow:0 10px 24px rgba(79,70,229,.18);
  transition:transform .12s ease, filter .12s ease, opacity .12s ease;
}
.btn-primary:hover{filter:brightness(1.05);transform:translateY(-1px)}
.btn-primary:active{transform:translateY(0)}

.chip{
  display:inline-flex;align-items:center;gap:.5rem;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.35);
  background:rgba(255,255,255,.65);
  padding:.45rem .75rem;
  font-weight:800;font-size:.75rem;
  color:rgb(51 65 85);
  transition:background .12s ease, transform .12s ease;
}
.dark .chip{
  background:rgba(23,23,23,.45);
  border-color:rgba(255,255,255,.10);
  color:rgb(229 229 229);
}
.chip:hover{background:rgba(255,255,255,.95);transform:translateY(-1px)}
.dark .chip:hover{background:rgba(23,23,23,.7)}

.card-soft{
  border-radius:1.5rem;
  border:1px solid rgba(148,163,184,.25);
  background:rgba(255,255,255,.65);
  padding:1rem;
  min-width:0;
  transition:background .12s ease, transform .12s ease;
}
.dark .card-soft{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
}
.card-soft:hover{background:rgba(255,255,255,.85)}
.dark .card-soft:hover{background:rgba(23,23,23,.60)}
.card-title{
  display:flex;align-items:center;gap:.5rem;
  font-weight:900;
  color:rgb(15 23 42);
  margin-bottom:.25rem;
}
.dark .card-title{color:rgb(245 245 245)}

.row-kv{
  display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;
}
.row-kv .k{
  font-size:.75rem;
  text-transform:uppercase;
  letter-spacing:.08em;
  color:rgb(100 116 139);
  font-weight:800;
}
.dark .row-kv .k{color:rgb(163 163 163)}
.row-kv .v{
  font-size:.95rem;
  font-weight:800;
  color:rgb(15 23 42);
  text-align:right;
  min-width:0;
}
.dark .row-kv .v{color:rgb(245 245 245)}

.stat{
  border-radius:1rem;
  border:1px solid rgba(148,163,184,.22);
  background:rgba(255,255,255,.60);
  padding:.75rem .85rem;
}
.dark .stat{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
}
.stat-k{
  font-size:.7rem;
  text-transform:uppercase;
  letter-spacing:.08em;
  color:rgb(100 116 139);
  font-weight:900;
}
.dark .stat-k{color:rgb(163 163 163)}
.stat-v{
  margin-top:.2rem;
  font-size:1.05rem;
  font-weight:900;
  color:rgb(15 23 42);
}
.dark .stat-v{color:rgb(245 245 245)}
.stat-strong{
  border-color:rgba(99,102,241,.25);
  background:rgba(99,102,241,.06);
}
.dark .stat-strong{
  border-color:rgba(99,102,241,.25);
  background:rgba(99,102,241,.10);
}

.seg{
  display:inline-flex;
  border:1px solid rgba(148,163,184,.28);
  background:rgba(255,255,255,.55);
  border-radius:999px;
  padding:.25rem;
  gap:.25rem;
}
.dark .seg{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
}
.seg-btn{
  border-radius:999px;
  padding:.4rem .75rem;
  font-size:.8rem;
  font-weight:900;
  color:rgb(51 65 85);
  transition:background .12s ease, transform .12s ease;
}
.dark .seg-btn{color:rgb(229 229 229)}
.seg-btn:hover{background:rgba(148,163,184,.16)}
.dark .seg-btn:hover{background:rgba(255,255,255,.08)}
.seg-btn-active{
  background:rgba(99,102,241,.16);
  color:rgb(49 46 129);
}
.dark .seg-btn-active{
  background:rgba(99,102,241,.22);
  color:rgb(224 231 255);
}

.empty{
  border-radius:1rem;
  border:1px dashed rgba(148,163,184,.35);
  background:rgba(255,255,255,.55);
  padding:1rem;
  color:rgb(71 85 105);
  font-weight:700;
}
.dark .empty{
  border-color:rgba(255,255,255,.12);
  background:rgba(23,23,23,.45);
  color:rgb(212 212 212);
}

.mini{
  border-radius:.9rem;
  border:1px solid rgba(148,163,184,.22);
  background:rgba(255,255,255,.60);
  padding:.55rem .65rem;
}
.dark .mini{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
}
.mini-k{font-size:.7rem;color:rgb(100 116 139);font-weight:900;text-transform:uppercase;letter-spacing:.08em}
.dark .mini-k{color:rgb(163 163 163)}
.mini-v{margin-top:.15rem;font-weight:900;color:rgb(15 23 42)}
.dark .mini-v{color:rgb(245 245 245)}
.mini-strong{border-color:rgba(99,102,241,.25);background:rgba(99,102,241,.06)}
.dark .mini-strong{border-color:rgba(99,102,241,.25);background:rgba(99,102,241,.10)}

.step{
  width:100%;
  text-align:left;
  border-radius:1.25rem;
  border:1px solid rgba(148,163,184,.25);
  background:rgba(255,255,255,.60);
  padding:1rem;
  transition:transform .12s ease, background .12s ease, border-color .12s ease;
}
.dark .step{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
}
.step:hover{transform:translateY(-1px);background:rgba(255,255,255,.85)}
.dark .step:hover{background:rgba(23,23,23,.60)}
.step-now{
  border-color:rgba(99,102,241,.45);
  background:rgba(99,102,241,.08);
  box-shadow:0 10px 26px rgba(99,102,241,.12);
}
.dark .step-now{background:rgba(99,102,241,.12)}
.step-done{
  border-color:rgba(16,185,129,.35);
  background:rgba(16,185,129,.06);
}
.dark .step-done{background:rgba(16,185,129,.10)}
.step-next{
  opacity:.95;
}

.preview-row{
  display:flex;align-items:center;justify-content:space-between;gap:1rem;
  border-radius:1rem;
  border:1px solid rgba(148,163,184,.22);
  background:rgba(255,255,255,.55);
  padding:.75rem .85rem;
}
.dark .preview-row{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
}

.file-link{
  display:flex;align-items:center;justify-content:space-between;gap:.75rem;
  border-radius:1rem;
  border:1px solid rgba(148,163,184,.22);
  background:rgba(255,255,255,.55);
  padding:.7rem .85rem;
  transition:transform .12s ease, background .12s ease;
  color:rgb(15 23 42);
  font-weight:800;
}
.dark .file-link{
  border-color:rgba(255,255,255,.10);
  background:rgba(23,23,23,.45);
  color:rgb(245 245 245);
}
.file-link:hover{transform:translateY(-1px);background:rgba(255,255,255,.85)}
.dark .file-link:hover{background:rgba(23,23,23,.60)}

/* Impresión:
  - el navegador arma el PDF.
  - escondo elementos “de interacción” si quieres; aquí lo mantengo simple y limpio.
*/
@media print{
  .btn-soft, .btn-primary, .chip, .seg { display:none !important; }
  body{ background:white !important; }
}
</style>
