import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import type { RequisicionPagoPageProps, PagoRow } from './Pagar.types'

declare const route: any

export function useRequisicionPago(props: RequisicionPagoPageProps) {
  const req = computed(() => {
    const raw: any = props.requisicion
    return raw?.data ?? raw ?? null
  })

  const pagos = computed<PagoRow[]>(() => {
    const raw: any = props.pagos
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

  const form = useForm<{
    archivo: File | null
    fecha_pago: string
    monto: string
    tipo_pago: string
    referencia: string
  }>({
    archivo: null,
    fecha_pago: new Date().toISOString().slice(0, 10),
    monto: '',
    tipo_pago: 'TRANSFERENCIA',
    referencia: '',
  })

  const submitting = computed(() => form.processing)

  const onPickFile = (e: Event) => {
    const input = e.target as HTMLInputElement
    form.archivo = input.files?.[0] ?? null
  }

  const submit = () => {
    if (!req.value?.id) return

    Swal.fire({
      title: 'Registrando pago…',
      text: 'Un segundo, estamos guardando el comprobante.',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    })

    form.post(route('requisiciones.pagar.store', { requisicion: req.value.id }), {
      forceFormData: true,
      onSuccess: () => {
        Swal.fire({ icon: 'success', title: 'Pago registrado', timer: 1400, showConfirmButton: false })
        form.reset('archivo', 'monto', 'referencia')
      },
      onError: () => {
        Swal.close()
      },
      onFinish: () => {
        if (Swal.isLoading()) Swal.close()
      },
      preserveScroll: true,
    })
  }

  return {
    req,
    pagos,
    money,
    fmtLong,
    form,
    submitting,
    onPickFile,
    submit,
  }
}
