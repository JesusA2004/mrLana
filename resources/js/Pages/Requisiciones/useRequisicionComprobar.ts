// resources/js/Pages/Requisiciones/useRequisicionComprobar.ts

import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { swalOk, swalErr } from '@/lib/swal'

type Comprobante = {
  id: number
  tipo_doc: string
  subtotal: string
  total: string
  fecha_emision: string | null
  url: string
}

type Props = {
  requisicionId: number
  comprobantes: Comprobante[]
}

export function useRequisicionComprobar(props: Props) {
  // Formulario para subir un nuevo comprobante
  const form = reactive({
    tipo_doc: 'FACTURA' as 'FACTURA' | 'TICKET',
    subtotal: '',
    total: '',
    fecha_emision: '',
    archivo: null as File | null,
  })

  // Lista de comprobantes existentes (reactiva)
  const comprobantes = ref<Comprobante[]>(props.comprobantes ?? [])

  // Carga un archivo en el form
  function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target?.files?.length) {
      form.archivo = target.files[0]
    }
  }

  // Previsualización del PDF en nueva pestaña
  function openFile(url: string) {
    window.open(url, '_blank', 'noopener,noreferrer')
  }

  /**
   * Envia el comprobante al backend con FormData.
   */
  async function upload() {
    try {
      const fd = new FormData()
      fd.append('tipo_doc', form.tipo_doc)
      fd.append('subtotal', form.subtotal)
      fd.append('total', form.total)
      fd.append('fecha_emision', form.fecha_emision || '')
      if (form.archivo) {
        fd.append('archivo', form.archivo)
      }

      await router.post(
        route('requisiciones.comprobantes.store', props.requisicionId),
        fd,
        {
          forceFormData: true,
          onSuccess: () => {
            // Limpia el formulario y actualiza lista (recargar con Inertia)
            form.tipo_doc = 'FACTURA'
            form.subtotal = ''
            form.total = ''
            form.fecha_emision = ''
            form.archivo = null
          },
        }
      )

      await swalOk('Comprobante cargado correctamente.', 'Éxito')
      router.visit(route('requisiciones.comprobar', props.requisicionId))
    } catch (e: any) {
      await swalErr(e?.message || 'Ocurrió un problema al subir el comprobante.')
    }
  }

  return {
    form,
    comprobantes,
    onFileChange,
    openFile,
    upload,
  }
}
