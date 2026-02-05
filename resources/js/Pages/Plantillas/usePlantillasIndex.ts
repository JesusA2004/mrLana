// resources/js/Pages/Plantillas/usePlantillasIndex.ts

import { computed, reactive, watch, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import type { PlantillasPageProps, PlantillaRow } from './Plantillas.types'

/**
 * Debounce helper reutilizado para búsquedas reactivas.
 */
function debounce<T extends (...args: any[]) => void>(fn: T, wait = 350) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), wait)
  }
}

/**
 * Normaliza links de paginación (igual que en requisiciones).
 */
function normalizeLinks(raw: any): any[] {
  if (Array.isArray(raw)) return raw
  if (raw && Array.isArray(raw.links)) return raw.links

  if (raw && typeof raw === 'object') {
    const values = Object.values(raw)
    if (Array.isArray(values) && values.every((v) => v && typeof v === 'object')) return values
  }

  return []
}

export function usePlantillasIndex(props: PlantillasPageProps) {
    // valores por defecto si props.filters no está presente
  const defaultFilters = {
    q: '',
    status: '',
    perPage: 20,
    sort: 'nombre',
    dir: 'asc' as 'asc' | 'desc',
  };
  // Estado de filtros
  const state = reactive({
    q: props.filters?.q ?? defaultFilters.q,
    status: props.filters?.status ?? defaultFilters.status,
    perPage: Number(props.filters?.perPage ?? defaultFilters.perPage),
    sort: props.filters?.sort ?? defaultFilters.sort,
    dir: (props.filters?.dir ?? defaultFilters.dir) as 'asc' | 'desc',
  });

  const rows = computed<PlantillaRow[]>(() => props.plantillas?.data ?? [])

  const safeLinks = computed(() => props.plantillas?.links ?? [])
  const safePagerLinks = computed(() => {
    const links = normalizeLinks(safeLinks.value)
    return links
      .filter((l: any) => l && typeof l.label === 'string')
      .map((l: any) => ({
        ...l,
        cleanLabel: String(l.label).replace(/<[^>]*>/g, '').trim(),
      }))
  })

  // Ejecutar búsqueda cuando cambien filtros
  const runSearch = debounce(() => {
    router.get(route('plantillas.index'), {
      q: state.q || undefined,
      status: state.status || undefined,
      perPage: state.perPage || undefined,
      sort: state.sort || undefined,
      dir: state.dir || undefined,
    }, { preserveScroll: true, preserveState: true, replace: true })
  }, 350)

  watch(
    () => [state.q, state.status, state.perPage, state.sort, state.dir],
    () => runSearch()
  )

  // Orden asc/desc
  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  // Acciones de navegación
  function goCreatePlantilla() {
    router.visit(route('plantillas.create'))
  }

  function goEdit(id: number) {
    router.visit(route('plantillas.edit', id))
  }

  function goNewRequisicion(id: number) {
    // Crea una requisición basándose en la plantilla. Se pasa el id por query.
    router.visit(route('requisiciones.registrar', { plantilla: id }))
  }

  function destroyRow(row: PlantillaRow) {
    if (!confirm(`¿Eliminar plantilla "${row.nombre}"?`)) return
    router.delete(route('plantillas.destroy', row.id))
  }

  return {
    state,
    rows,
    safePagerLinks,
    sortLabel,
    toggleSort,
    goCreatePlantilla,
    goEdit,
    goNewRequisicion,
    destroyRow,
  }
}
