import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import type { RequisicionComprobarPageProps, ComprobanteRow } from './Comprobar.types'

declare const route: any

export function useRequisicionComprobar(props: RequisicionComprobarPageProps) {
  const req = computed(() => {
    const raw: any = props.requisicion
    return raw?.data ?? raw ?? null
  })

  const rows = computed<ComprobanteRow[]>(() => {
    const raw: any = props.comprobantes
    return raw?.data ?? raw ?? []
  })

  const money = (v: any) => {
    const n = Number(v ?? 0)
    return n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
  }

  const fmtLong = (iso?: string | null) => {
    if (!iso) return '—'
    const d = new Date(iso + 'T00:00:00')
    return d.toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' })
  }

  const estatusLabel = (e: ComprobanteRow['estatus']) => {
    if (e === 'APROBADO') return 'Aprobado'
    if (e === 'RECHAZADO') return 'Rechazado'
    return 'Pendiente'
  }

  const estatusPillClass = (e: ComprobanteRow['estatus']) => {
    if (e === 'APROBADO') return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    if (e === 'RECHAZADO') return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200'
    return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-neutral-200'
  }

  const form = useForm<{
    archivo: File | null
    monto: string
    tipo_doc: string
    fecha_emision: string
  }>({
    archivo: null,
    monto: '',
    tipo_doc: 'NOTA',
    fecha_emision: new Date().toISOString().slice(0, 10),
  })

  const onPickFile = (e: Event) => {
    const input = e.target as HTMLInputElement
    form.archivo = input.files?.[0] ?? null
  }

  const submit = () => {
    if (!req.value?.id) return

    Swal.fire({
      title: 'Subiendo comprobante…',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    })

    form.post(route('requisiciones.comprobar.store', { requisicion: req.value.id }), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({ icon: 'success', title: 'Comprobante cargado', timer: 1200, showConfirmButton: false })
        form.reset('archivo', 'monto')
      },
      onError: () => Swal.close(),
      onFinish: () => { if (Swal.isLoading()) Swal.close() },
    })
  }

  const reviewOpenId = ref<number | null>(null)
  const reviewComment = ref<string>('')

  const openReject = (id: number) => {
    reviewOpenId.value = id
    reviewComment.value = ''
  }

  const approve = (id: number) => {
    Swal.fire({ title: 'Aprobando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() })

    router.patch(
      route('comprobantes.review', { comprobante: id }),
      { estatus: 'APROBADO', comentario_revision: reviewComment.value || null },
      {
        preserveScroll: true,
        onSuccess: () => Swal.fire({ icon: 'success', title: 'Aprobado', timer: 1000, showConfirmButton: false }),
        onError: () => Swal.close(),
      }
    )
  }

  const reject = (id: number) => {
    if (!reviewComment.value.trim()) {
      Swal.fire({ icon: 'warning', title: 'Falta el motivo', text: 'Escribe el motivo del rechazo.' })
      return
    }

    Swal.fire({ title: 'Rechazando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() })

    router.patch(
      route('comprobantes.review', { comprobante: id }),
      { estatus: 'RECHAZADO', comentario_revision: reviewComment.value.trim() },
      {
        preserveScroll: true,
        onSuccess: () => {
          reviewOpenId.value = null
          reviewComment.value = ''
          Swal.fire({ icon: 'success', title: 'Rechazado', timer: 1000, showConfirmButton: false })
        },
        onError: () => Swal.close(),
      }
    )
  }

  return {
    req,
    rows,
    money,
    fmtLong,
    form,
    onPickFile,
    submit,
    estatusLabel,
    estatusPillClass,
    reviewOpenId,
    reviewComment,
    openReject,
    approve,
    reject,
  }
}
