<script setup lang="ts">
    /**
     * ==========================================================
     * SearchableSelect.vue (UI component)
     * ----------------------------------------------------------
     * Componente “combobox” con input de busqueda (sin dependencias externas):
     * - Botón que abre/cierra un panel dropdown.
     * - Input de búsqueda para filtrar opciones por texto.
     * - Soporta modo oscuro (Tailwind dark:).
     * - Soporta opción nula (Todos / Sin selección).
     * - Cierra al hacer click fuera y al presionar Escape.
     *
     * Caso de uso típico:
     * - Filtros
     * - Formularios
     *
     * Contrato (v-model):
     * - Recibe: modelValue (id seleccionado)
     * - Emite: update:modelValue (nuevo id) + change (opcional)
     * ==========================================================
     */

    import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

    /**
     * Estructura flexible de opción.
     * Requisito mínimo: cada opción debe tener `id`.
     * Además, normalmente tendrá:
     * - nombre
     * - codigo
     */

    type OptionLike = Record<string, any>

    /**
     * Props del componente.
     * - modelValue: el valor seleccionado (id) enlazado con v-model.
     * - options: lista de opciones disponibles.
     *
     * Etiquetas de UX:
     * - label: etiqueta superior del campo.
     * - placeholder: texto cuando no hay selección.
     * - searchPlaceholder: texto del input buscador.
     *
     * Null / Todos:
     * - allowNull: si permite seleccionar null.
     * - nullLabel: texto del botón que selecciona null.
     *
     * Estilos:
     * - buttonClass: clases extra para el botón.
     * - panelClass: clases extra para el panel.
     * - rounded: control de redondeo
     */

    const props = defineProps<{
        modelValue: string | number | null
        options: OptionLike[]
        label?: string
        placeholder?: string
        searchPlaceholder?: string
        error?: string | null

        /** Campos a mostrar (por defecto nombre + codigo) */
        labelKey?: string
        secondaryKey?: string

        /** Permitir "Sin/Todos" */
        allowNull?: boolean
        nullLabel?: string

        /** UI */
        buttonClass?: string
        panelClass?: string
        rounded?: 'xl' | '2xl' | '3xl'
        zIndexClass?: string
    }>()

    /**
     * Eventos que emite el componente.
     * - update:modelValue: obligatorio para que funcione v-model.
     * - change: evento opcional útil si el padre quiere ejecutar lógica extra cuando cambia la selección.
     */
    const emit = defineEmits<{
        (e: 'update:modelValue', v: string | number | null): void
        (e: 'change', v: string | number | null): void
    }>()

    /**
     * Estado interno
     * - open: controla visibilidad del panel dropdown.
     * - query: texto del buscador.
     * - rootRef: referencia al contenedor (para detectar click fuera).
     * - searchRef: referencia al input (para auto-focus).
     */
    const open = ref(false)
    const query = ref('')
    const rootRef = ref<HTMLElement | null>(null)
    const searchRef = ref<HTMLInputElement | null>(null)

    /**
     * Normalizamos llaves de campos para mostrar.
     * Si el padre no pasa labelKey/secondaryKey, usamos valores estándar.
     */
    const labelKey = computed(() => props.labelKey ?? 'nombre')
    const secondaryKey = computed(() => props.secondaryKey ?? 'codigo')

    /**
     * selected:
     * - Resuelve el objeto seleccionado a partir de modelValue.
     * - Convierte ids a Number para evitar mismatches (string vs number).
     * - Retorna null si no hay selección.
     */
    const selected = computed<OptionLike | null>(() => {
        if (props.modelValue === null || props.modelValue === '' || props.modelValue === undefined) return null
        const idNum = Number(props.modelValue)
        return props.options.find(o => Number(o.id) === idNum) ?? null
    })

    /**
     * filtered:
     * - Filtra options por el texto `query`.
     * - Busca en labelKey y secondaryKey.
     * - Si query está vacío, regresa options completo.
     */
    const filtered = computed(() => {
        const q = query.value.trim().toLowerCase()
        if (!q) return props.options

        return props.options.filter(o => {
            const a = String(o[labelKey.value] ?? '').toLowerCase()
            const b = String(o[secondaryKey.value] ?? '').toLowerCase()
            return a.includes(q) || b.includes(q)
        })
    })

    /**
     * setOpen:
     * - Abre o cierra el dropdown.
     * - Al abrir: hace focus al input buscador (nextTick para esperar DOM).
     * - Al cerrar: limpia la query para dejar el componente “fresh” en próximo open.
     */
    function setOpen(v: boolean) {
        open.value = v

        if (v) {
            nextTick(() => searchRef.value?.focus())
        } else {
            query.value = ''
        }
    }

    /**
     * toggle:
     * - Alterna abierto/cerrado.
     * - Centraliza el comportamiento en setOpen para mantener consistencia.
     */
    function toggle() {
        setOpen(!open.value)
    }

    /**
     * pick:
     * - Selecciona un valor (id o null) y:
     *   1) Emite update:modelValue para actualizar v-model en el padre.
     *   2) Emite change por si el padre necesita side-effects.
     *   3) Cierra el dropdown.
     */
    function pick(v: string | number | null) {
        emit('update:modelValue', v)
        emit('change', v)
        setOpen(false)
    }

    /**
     * onKeydown:
     * - Cierra el dropdown si el usuario presiona Escape.
     * - Se escucha a nivel document para que funcione incluso con focus dentro.
     */
    function onKeydown(e: KeyboardEvent) {
        if (e.key === 'Escape') setOpen(false)
    }

    /**
     * onClickOutside:
     * - Cierra el dropdown si el click ocurre fuera del componente.
     * - rootRef contiene el árbol del componente; si el target no está dentro, se cierra.
     */
    function onClickOutside(e: MouseEvent) {
        if (!open.value) return
        const el = rootRef.value
        if (!el) return
        if (!el.contains(e.target as Node)) setOpen(false)
    }

    /**
     * Lifecycle:
     * - onMounted: registramos listeners globales.
     * - onBeforeUnmount: removemos listeners para evitar leaks.
     */
    onMounted(() => {
        document.addEventListener('mousedown', onClickOutside)
        document.addEventListener('keydown', onKeydown)
    })

    onBeforeUnmount(() => {
        document.removeEventListener('mousedown', onClickOutside)
        document.removeEventListener('keydown', onKeydown)
    })

    /**
     * watch options:
     * - Si cambian las opciones (ej: se recarga lista por API),
     *   mantenemos el query estable mientras esté abierto.
     * - Nota: esto es “no-op intencional” para no resetear UX a mitad de interacción.
     */
    watch(
        () => props.options,
        () => {
            if (open.value) query.value = query.value
        }
    )

    /**
     * roundedCls:
     * - Define el nivel de redondeo del botón y del panel.
     * - Default: rounded-3xl (premium)
     */
    const roundedCls = computed(() => {
        if (props.rounded === 'xl') return 'rounded-xl'
        if (props.rounded === '2xl') return 'rounded-2xl'
        return 'rounded-3xl'
    })

    /**
     * baseButton:
     * - Estilos base del botón (light/dark) con foco accesible.
     * - Se permite extender con props.buttonClass.
     */
    const baseButton =
    'mt-1 w-full min-w-0 px-4 py-3 text-sm text-left border border-slate-200 bg-white text-slate-900 ' +
    'hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5 dark:focus:ring-white/10 transition'

    /**
     * basePanel:
     * - Estilos base del panel dropdown.
     * - Se permite extender con props.panelClass.
     * - Mantiene overflow hidden para bordes limpios, y el scroll vive dentro del listado.
     */
    const basePanel =
    'absolute mt-2 w-full overflow-visible p-3 border border-slate-200/70 bg-white shadow-2xl ' +
    'dark:border-white/10 dark:bg-neutral-950'

    /**
     * zCls:
     * - Controla el stacking del panel (z-index).
     * - Útil si se usa dentro de modales o contenedores con overlays.
     */
    const zCls = computed(() => props.zIndexClass ?? 'z-40')

</script>

<template>
    <!-- rootRef:
        - Referencia al contenedor para detectar click fuera.
        - relative para posicionar el panel absoluto debajo del botón.
    -->
    <div ref="rootRef" class="relative">
        <!-- Etiqueta superior -->
        <label v-if="label" class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">
        {{ label }}
        </label>

        <!-- Botón del “select” (abre/cierra) -->
        <button type="button" @click="toggle"
            class="w-full" :class="[roundedCls, baseButton, buttonClass]">
            <span class="flex items-center justify-between gap-3">
                <!-- Texto actual -->
                <span class="truncate">
                    <template v-if="selected">
                        <!-- label principal -->
                        {{ selected[labelKey] }}
                        <!-- label secundario opcional -->
                        <span v-if="selected[secondaryKey]"> ({{ selected[secondaryKey] }})</span>
                    </template>

                    <template v-else>
                        {{ placeholder ?? 'Selecciona...' }}
                    </template>
                </span>

                <!-- Icono caret -->
                <svg class="h-4 w-4 opacity-70 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                        clip-rule="evenodd"/>
                </svg>
            </span>
        </button>

            <!-- Panel dropdown -->
            <div v-if="open" :class="[zCls, basePanel, roundedCls, panelClass]">
                <!-- Buscador -->
                <div class="p-3 border-b border-slate-200/70 dark:border-white/10">
                    <input ref="searchRef" v-model="query"
                    type="text" :placeholder="searchPlaceholder ?? 'Buscar...'"
                    class="w-full rounded-2xl px-4 py-3 text-sm
                        border border-slate-200 bg-white text-slate-900
                        placeholder:text-slate-400 focus:outline-none focus:ring-2
                        focus:ring-slate-300 focus:border-slate-300
                        dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100
                        dark:placeholder:text-neutral-500 dark:focus:ring-white/10"/>
                </div>

                <!-- Lista de opciones (scroll controlado) -->
                <div class="max-h-64 overflow-auto p-2">
                    <!-- Opción null (Todos / Sin selección) -->
                    <button v-if="allowNull" type="button" @click="pick(null)"
                    class="w-full text-left px-3 py-2 rounded-2xl text-sm font-semibold hover:bg-slate-50 dark:hover:bg-white/5 transition">
                    {{ nullLabel ?? 'Sin selección' }}
                    </button>

                    <!-- Opciones filtradas -->
                    <button v-for="o in filtered" :key="o.id" type="button" @click="pick(o.id)"
                    class="w-full text-left px-3 py-2 rounded-2xl text-sm hover:bg-slate-50 dark:hover:bg-white/5 transition"
                    :class="Number(modelValue) === Number(o.id) ? 'bg-slate-100 dark:bg-white/10 font-semibold' : ''">
                    {{ o[labelKey] }}<span v-if="o[secondaryKey]"> ({{ o[secondaryKey] }})</span>
                    </button>

                    <!-- Estado vacío -->
                    <div v-if="filtered.length === 0" class="px-3 py-3 text-sm text-slate-500 dark:text-neutral-400">
                    Sin resultados.
                    </div>
                </div>
            </div>

            <!-- Error de validación (si aplica) -->
            <p v-if="error" class="mt-1 text-xs text-rose-500">{{ error }}</p>
        </div>
</template>
