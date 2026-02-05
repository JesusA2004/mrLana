// resources/js/Pages/Plantillas/usePlantillasIndex.ts
import { computed, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import type { PlantillasPageProps, PlantillaRow } from './Plantillas.types'
import { swalOk, swalErr, swalLoading, swalClose } from '@/lib/swal'

type PagerLink = {
  url: string | null
  label: string
  active: boolean
  cleanLabel?: string
}

function debounce<T extends (...args: any[]) => void>(fn: T, wait = 350) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), wait)
  }
}

function normalizeLinks(raw: any): PagerLink[] {
  const links = Array.isArray(raw) ? raw : raw?.links
  if (!Array.isArray(links)) return []
  return links
    .filter((l: any) => l && typeof l.label === 'string')
    .map((l: any) => ({
      ...l,
      cleanLabel: String(l.label).replace(/<[^>]*>/g, '').trim(),
    }))
}

export function usePlantillasIndex(props: PlantillasPageProps) {
  const state = reactive({
    q: props.filters?.q ?? '',
    status: props.filters?.status ?? '',
    perPage: Number(props.filters?.perPage ?? 20),
    sort: props.filters?.sort ?? 'nombre',
    dir: (props.filters?.dir ?? 'asc') as 'asc' | 'desc',
  })

  const rows = computed<PlantillaRow[]>(() => props.plantillas?.data ?? [])
  const pagerLinks = computed<PagerLink[]>(() => normalizeLinks(props.plantillas?.links))

  const runSearch = debounce(() => {
    router.get(
      route('plantillas.index'),
      {
        q: state.q || undefined,
        status: state.status || undefined,
        perPage: state.perPage || undefined,
        sort: state.sort || undefined,
        dir: state.dir || undefined,
      },
      { preserveScroll: true, preserveState: true, replace: true }
    )
  }, 350)

  watch(() => [state.q, state.status, state.perPage, state.sort, state.dir], () => runSearch())

  const sortLabel = computed(() => (state.dir === 'asc' ? 'A-Z' : 'Z-A'))
  function toggleSort() {
    state.dir = state.dir === 'asc' ? 'desc' : 'asc'
  }

  function goCreatePlantilla() {
    router.visit(route('plantillas.create'))
  }

  function goEdit(id: number) {
    // ✅ Edit funciona sí o sí
    router.visit(route('plantillas.edit', id))
  }

  function goNewRequisicion(id: number) {
    router.visit(route('requisiciones.registrar', { plantilla: id }))
  }

  function goToUrl(url: string | null) {
    if (!url) return
    router.visit(url, { preserveScroll: true, preserveState: true })
  }

  function money(v: any) {
    const n = Number(v ?? 0)
    try {
      return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
    } catch {
      return String(v ?? '')
    }
  }

  async function destroyRow(row: PlantillaRow) {
    // ✅ Confirm con SweetAlert2 (tu wrapper)
    // Si tu swalOk/swalErr son wrappers, aquí usamos window.confirm? NO.
    // Como no me pasaste swalConfirm, uso Swal directo NO.
    // Entonces lo hago con swalErr/swalOk? NO.
    // Mejor: si tu lib/swal ya expone confirm, úsalo. Si no, te dejo el mínimo usando Swal.
    // ----
    // Para no inventar funciones, aquí uso SweetAlert2 via dynamic import (sin romper tu estándar)
    const Swal = (await import('sweetalert2')).default

    const res = await Swal.fire({
      title: '¿Eliminar plantilla?',
      text: `Se marcará como ELIMINADA: "${row.nombre}"`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      customClass: {
        popup: 'rounded-3xl',
        confirmButton: 'rounded-2xl',
        cancelButton: 'rounded-2xl',
      },
    })

    if (!res.isConfirmed) return

    swalLoading('Eliminando...')

    router.delete(route('plantillas.destroy', row.id), {
      preserveScroll: true,
      onError: (errors) => {
        swalClose()
        const first = Object.values(errors ?? {})[0]
        swalErr(String(first || 'No se pudo eliminar la plantilla.'))
      },
      onSuccess: () => {
        swalClose()
        swalOk('Plantilla eliminada.', 'Listo')
      },
      onFinish: () => {
        // por si algo se queda colgado
        try { swalClose() } catch {}
      },
    })
  }

  return {
    state,
    rows,
    pagerLinks,
    sortLabel,
    toggleSort,
    goCreatePlantilla,
    goEdit,
    goNewRequisicion,
    destroyRow,
    goToUrl,
    money,
  }
}
