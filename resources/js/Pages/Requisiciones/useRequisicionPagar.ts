// resources/js/Pages/Requisiciones/useRequisicionPagar.ts

import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { swalOk, swalErr } from '@/lib/swal'

type Pago = {
  id: number
  fecha_pago: string
  tipo_pago: string
  monto: string
  url: string
}

type Props = {
  requisicionId: number
  pagos: Pago[]
}

export function useRequisicionPagar(props: Props) {
  const form = reactive({
    fecha_pago: '',
    tipo_pago: 'TRANSFERENCIA',
    monto: '',
    archivo: null as File | null,
  })

  const pagos = ref<Pago[]>(props.pagos ?? [])

  function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target?.files?.length) {
      form.archivo = target.files[0]
    }
  }

  function openFile(url: string) {
    window.open(url, '_blank', 'noopener,noreferrer')
  }

  async function upload() {
    try {
      const fd = new FormData()
      fd.append('fecha_pago', form.fecha_pago)
      fd.append('tipo_pago', form.tipo_pago)
      fd.append('monto', form.monto)
      if (form.archivo) {
        fd.append('archivo', form.archivo)
      }

      await router.post(
        route('requisiciones.pagar.store', props.requisicionId),
        fd,
        {
          forceFormData: true,
          onSuccess: () => {
            form.fecha_pago = ''
            form.tipo_pago = 'TRANSFERENCIA'
            form.monto = ''
            form.archivo = null
          },
        }
      )

      await swalOk('Pago registrado correctamente.', 'Éxito')
      router.visit(route('requisiciones.pagar', props.requisicionId))
    } catch (e: any) {
      await swalErr(e?.message || 'Ocurrió un problema al registrar el pago.')
    }
  }

  return {
    form,
    pagos,
    onFileChange,
    openFile,
    upload,
  }
}
