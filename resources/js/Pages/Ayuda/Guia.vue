<!-- resources/js/Pages/Ayuda/Guia.vue -->
<script setup lang="ts">
    import { computed, ref } from 'vue'
    import { Head, Link, usePage } from '@inertiajs/vue3'
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import { BookOpen, FileText, LifeBuoy, ShieldCheck, Workflow, Search } from 'lucide-vue-next'

    declare const route: any

    const SUPPORT_URL = 'https://soporte.mr-lana.com/'
    const PDF_URL = '/ayuda/mr-lana-ayuda.pdf'
    const page = usePage<any>()
    const role = computed(() => String(page.props?.auth?.user?.role ?? '').toUpperCase())

    const isAdmin = computed(() => role.value === 'ADMIN')
    const isContador = computed(() => role.value === 'CONTADOR')
    const isColaborador = computed(() => role.value === 'COLABORADOR')

    type Step = { title: string; bullets: string[] }
    type Section = { id: string; title: string; desc: string; steps: Step[] }

    const baseSectionsColaborador: Section[] = [
    {
        id: 'dashboard',
        title: 'Dashboard (solo vista)',
        desc: 'Monitorea indicadores y descarga reportes (si tu rol lo permite).',
        steps: [
        { title: 'Qué ver', bullets: ['KPIs principales', 'Tendencias diarias', 'Accesos rápidos a exportación'] },
        { title: 'Buenas prácticas', bullets: ['Filtra por periodos antes de exportar', 'Usa PDF para compartir, Excel para analizar'] },
        ],
    },
    {
        id: 'proveedores',
        title: 'Proveedores',
        desc: 'Consulta y administra proveedores (según permisos).',
        steps: [
        { title: 'Flujo', bullets: ['Buscar proveedor', 'Crear/editar datos básicos', 'Activar/Inactivar si aplica'] },
        { title: 'Tips', bullets: ['Mantén RFC/razón social consistentes', 'Evita duplicados: busca antes de crear'] },
        ],
    },
    {
        id: 'requisiciones',
        title: 'Requisiciones',
        desc: 'Crea y da seguimiento a requisiciones (lo esencial para operar).',
        steps: [
        { title: 'Crear', bullets: ['Selecciona solicitante/sucursal/corporativo', 'Captura concepto y montos', 'Guarda y valida que quede en el estatus correcto'] },
        { title: 'Seguimiento', bullets: ['Revisa estatus', 'Adjunta pagos/comprobantes si te corresponde', 'Exporta PDF cuando necesites evidencia'] },
        ],
    },
    ]

    const sectionsAdminContador: Section[] = [
    {
        id: 'overview',
        title: 'Panorama del sistema',
        desc: 'Cómo se conectan los módulos para operar sin fricción.',
        steps: [
        { title: 'Cadena operativa', bullets: ['Catálogos (corporativos/sucursales/áreas/empleados)', 'Proveedores y conceptos', 'Requisiciones + pagos + comprobantes + ajustes', 'Reportes y logs'] },
        { title: 'Gobernanza', bullets: ['Roles y permisos', 'Trazabilidad en logs', 'Exportaciones como evidencia'] },
        ],
    },
    ...baseSectionsColaborador,
    {
        id: 'catalogos',
        title: 'Catálogos (Admin/Contador)',
        desc: 'Donde vive la estructura: corporativos, sucursales, áreas, empleados, conceptos.',
        steps: [
        { title: 'Corporativos', bullets: ['Alta/edición', 'Carga de logo', 'Revisión de sucursales/áreas inactivas'] },
        { title: 'Sucursales y áreas', bullets: ['Alta/edición', 'Activar/Inactivar', 'Eliminación masiva si aplica'] },
        { title: 'Empleados', bullets: ['Alta/edición', 'Activación', 'Bulk destroy (si aplica)'] },
        { title: 'Conceptos', bullets: ['Mantén el catálogo limpio', 'Usa activación en vez de borrar si ya hubo movimiento'] },
        ],
    },
    {
        id: 'pagos-comprobantes',
        title: 'Pagos y Comprobantes',
        desc: 'Control financiero y evidencia documental.',
        steps: [
        { title: 'Pagos', bullets: ['Sube comprobante de pago', 'Valida montos', 'Mantén consistencia con el subtotal/total'] },
        { title: 'Comprobantes', bullets: ['Sube archivos', 'Revisión/Aprobación/Rechazo', 'Notificaciones cuando aplique'] },
        ],
    },
    {
        id: 'ajustes',
        title: 'Ajustes',
        desc: 'Cambios controlados que impactan el monto total y quedan auditables.',
        steps: [
        { title: 'Crear', bullets: ['Captura motivo y monto', 'Deja evidencia clara en la descripción'] },
        { title: 'Review', bullets: ['Aprobar/Rechazar con criterio', 'Aplicar ajuste cuando corresponda'] },
        ],
    },
    {
        id: 'plantillas',
        title: 'Plantillas',
        desc: 'Estandariza requisiciones recurrentes y reduce errores humanos.',
        steps: [
        { title: 'Uso recomendado', bullets: ['Crea plantillas por tipo de gasto', 'Reutiliza y ajusta por caso'] },
        { title: 'Operación', bullets: ['Precarga datos desde plantilla', 'Revisa montos antes de guardar'] },
        ],
    },
    {
        id: 'logs',
        title: 'Logs del sistema',
        desc: 'Bitácora para auditoría interna: quién hizo qué y cuándo.',
        steps: [
        { title: 'Qué revisar', bullets: ['Acciones críticas', 'Cambios de estatus', 'Operaciones masivas'] },
        { title: 'Uso ejecutivo', bullets: ['Detecta cuellos de botella', 'Encuentra errores de operación rápidamente'] },
        ],
    },
    ]

    const sections = computed<Section[]>(() => {
    if (isAdmin.value || isContador.value) return sectionsAdminContador
    return baseSectionsColaborador
    })

    const query = ref('')
    const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return sections.value
    return sections.value.filter(s =>
        (s.title + ' ' + s.desc + ' ' + s.steps.map(st => st.title + ' ' + st.bullets.join(' ')).join(' '))
        .toLowerCase()
        .includes(q),
    )
    })
</script>

<template>
    <Head title="Guía del sistema" />

    <AuthenticatedLayout>
        <!-- Header del layout -->
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
                Guía de uso del sistema
            </h2>
        </template>

        <div class="mx-auto w-full max-w-6xl px-4 py-6">
            <div class="rounded-3xl border border-neutral-200
            bg-white p-6 shadow-sm dark:border-neutral-800
            dark:bg-neutral-900">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900">
                                <BookOpen class="h-5 w-5" />
                            </div>
                            <div>
                                <h1 class="text-xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Guía de uso del sistema
                                </h1>
                                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                Contenido adaptado a tu rol:
                                <span class="font-semibold text-neutral-900 dark:text-neutral-100">{{ role || 'N/A' }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a :href="SUPPORT_URL" target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-2xl bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:opacity-90 dark:bg-white dark:text-neutral-900">
                                <LifeBuoy class="h-4 w-4" />
                                Soporte (tickets)
                            </a>

                            <a :href="PDF_URL" target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white px-4 py-2 text-sm font-semibold text-neutral-900 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-800">
                                <FileText class="h-4 w-4" />
                                Abrir PDF de ayuda
                            </a>

                            <Link :href="route('dashboard')"
                            class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white px-4 py-2 text-sm font-semibold text-neutral-900 hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 dark:hover:bg-neutral-800">
                                <Workflow class="h-4 w-4" />
                                Volver al dashboard
                            </Link>
                        </div>
                    </div>

                    <div class="w-full md:max-w-sm">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                            <input v-model="query" type="text"
                            placeholder="Buscar en la guía…"
                            class="w-full rounded-2xl border border-neutral-200 bg-white py-2 pl-10 pr-3 text-sm text-neutral-900 outline-none ring-0 focus:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:border-neutral-700"/>
                        </div>

                        <div class="mt-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-4 text-sm text-neutral-700 dark:border-neutral-800 dark:bg-neutral-950/30 dark:text-neutral-300">
                        <div class="flex items-center gap-2 font-semibold text-neutral-900 dark:text-neutral-100">
                            <ShieldCheck class="h-4 w-4" />
                            Recomendación operativa
                        </div>
                        <p class="mt-1">
                            Si algo no cuadra (monto, estatus, export, etc.), levanta ticket con evidencia (captura + folio).
                            Menos drama, más trazabilidad.
                        </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4">
                <div v-for="s in filtered" :key="s.id"
                class="rounded-3xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <h2 class="text-lg font-extrabold text-neutral-900 dark:text-neutral-100">{{ s.title }}</h2>
                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">{{ s.desc }}</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="(st, idx) in s.steps" :key="idx"
                        class="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/30">
                            <div class="text-sm font-bold text-neutral-900 dark:text-neutral-100">{{ st.title }}</div>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-neutral-700 dark:text-neutral-300">
                                <li v-for="(b, i) in st.bullets" :key="i">{{ b }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
