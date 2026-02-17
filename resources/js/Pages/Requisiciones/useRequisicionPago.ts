import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'

import type { RequisicionPagoPageProps } from './Pagar.types'

type UploadPreview =
  | { kind: 'pdf'; url: string; name: string; mime: string; size: number }
  | { kind: 'image'; url: string; name: string; mime: string; size: number }
  | { kind: 'other'; url: string; name: string; mime: string; size: number }

const MAX_FILE_MB = 10
const MAX_FILE_BYTES = MAX_FILE_MB * 1024 * 1024

const toNumber = (v: any) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

const money = (v: any) => {
  const n = toNumber(v ?? 0)
  return n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
}

// YYYY-MM-DD => dd/MM/yyyy (sin timezone shift)
const fmtLong = (iso?: string | null) => {
  if (!iso) return '—'
  const m = String(iso).match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (m) {
    const y = Number(m[1])
    const mo = Number(m[2])
    const d = Number(m[3])
    const js = new Date(y, mo - 1, d)
    return format(js, 'dd/MM/yyyy', { locale: es })
  }
  // fallback seguro
  try {
    return String(iso)
  } catch {
    return '—'
  }
}

export function useRequisicionPago(props: RequisicionPagoPageProps) {
  const req = computed(() => {
    const raw: any = (props as any).requisicion
    return raw?.data ?? raw ?? null
  })

  const pagos = computed<any[]>(() => {
    const raw: any = (props as any).pagos
    return raw?.data ?? raw ?? []
  })

  // Pendiente (usa totales si vienen; si no, lo calcula)
  const pendiente = computed(() => {
    const t: any = (props as any).totales
    if (t && t.pendiente != null) return toNumber(t.pendiente)

    const total = toNumber(req.value?.monto_total)
    const pagado = pagos.value.reduce((acc, p) => acc + toNumber(p?.monto), 0)
    return Math.max(0, total - pagado)
  })

  const todayISO = () => {
    const d = new Date()
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    return `${y}-${m}-${dd}`
  }

  const form = useForm<{
    archivo: File | null
    fecha_pago: string
    monto: string
    tipo_pago: string | number | null
  }>({
    archivo: null,
    fecha_pago: (req.value?.fecha_pago as any) ?? todayISO(),
    monto: '',
    tipo_pago: '',
  })

  const submitting = computed(() => form.processing)

  // File picker / Drag&drop
  const fileKey = ref(0)
  const dragActive = ref(false)
  const pickedName = ref('Sin archivo seleccionado')
  const hasPicked = computed(() => !!form.archivo)

  const uploadPreview = ref<UploadPreview | null>(null)
  const previewUrl = ref<string | null>(null)

  const revokePreview = () => {
    if (previewUrl.value) {
      try {
        URL.revokeObjectURL(previewUrl.value)
      } catch {}
      previewUrl.value = null
    }
  }

  onBeforeUnmount(() => {
    revokePreview()
  })

  const buildPreview = (file: File) => {
    revokePreview()

    const url = URL.createObjectURL(file)
    previewUrl.value = url

    const mime = file.type || 'application/octet-stream'
    const name = file.name
    const size = file.size

    if (mime === 'application/pdf' || name.toLowerCase().endsWith('.pdf')) {
      uploadPreview.value = { kind: 'pdf', url, name, mime, size }
      return
    }

    if (mime.startsWith('image/') || /\.(png|jpe?g|webp)$/i.test(name)) {
      uploadPreview.value = { kind: 'image', url, name, mime, size }
      return
    }

    uploadPreview.value = { kind: 'other', url, name, mime, size }
  }

  const setFile = (file: File | null) => {
    form.clearErrors('archivo')

    if (!file) {
      form.archivo = null
      pickedName.value = 'Sin archivo seleccionado'
      uploadPreview.value = null
      revokePreview()
      return
    }

    if (file.size > MAX_FILE_BYTES) {
      form.archivo = null
      pickedName.value = 'Sin archivo seleccionado'
      uploadPreview.value = null
      revokePreview()
      form.setError('archivo', `El archivo excede ${MAX_FILE_MB}MB.`)
      return
    }

    const name = file.name.toLowerCase()
    const ok =
      file.type === 'application/pdf' ||
      file.type.startsWith('image/') ||
      /\.(pdf|png|jpg|jpeg|webp)$/i.test(name)

    if (!ok) {
      form.archivo = null
      pickedName.value = 'Sin archivo seleccionado'
      uploadPreview.value = null
      revokePreview()
      form.setError('archivo', 'Formato no permitido. Usa PDF/PNG/JPG/WebP.')
      return
    }

    form.archivo = file
    pickedName.value = file.name
    buildPreview(file)
  }

  const clearFile = () => {
    setFile(null)
    fileKey.value++
  }

  const onPickFile = (e: Event) => {
    const input = e.target as HTMLInputElement | null
    const file = input?.files?.[0] ?? null
    setFile(file)
  }

  const onDragEnter = (e: DragEvent) => {
    e.preventDefault()
    dragActive.value = true
  }
  const onDragOver = (e: DragEvent) => {
    e.preventDefault()
    dragActive.value = true
  }
  const onDragLeave = (e: DragEvent) => {
    e.preventDefault()
    dragActive.value = false
  }
  const onDropFile = (e: DragEvent) => {
    e.preventDefault()
    dragActive.value = false
    const file = e.dataTransfer?.files?.[0] ?? null
    setFile(file)
  }

  // Monto: clamp duro (no permite > pendiente)
  const clampMonto = () => {
    const max = pendiente.value
    let n = toNumber(form.monto)

    if (n < 0) n = 0
    if (max >= 0 && n > max) n = max

    // Mantén 2 decimales “de negocio”
    form.monto = n ? n.toFixed(2).replace(/\.00$/, '') : ''
  }

  const onMontoInput = () => {
    form.clearErrors('monto')
    clampMonto()
  }

  watch(
    () => pendiente.value,
    () => {
      // si cambia el pendiente, re-clampa el monto actual
      clampMonto()
    }
  )

  const canSubmit = computed(() => {
    if (submitting.value) return false
    if (!form.archivo) return false
    if (!form.fecha_pago) return false
    if (!form.tipo_pago) return false

    const n = toNumber(form.monto)
    if (!(n > 0)) return false

    // doble candado
    if (n > pendiente.value + 1e-9) return false

    return true
  })

  const submit = () => {
    // Clamp final antes de disparar
    clampMonto()

    if (!canSubmit.value) return
    const id = req.value?.id ?? req.value?._id ?? req.value?.requisicion_id
    if (!id) {
      form.setError('monto', 'No se pudo identificar la requisición.')
      return
    }

    const url = `/requisiciones/${id}/pagar`

    form.post(url, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        clearFile()
        form.monto = ''
        form.clearErrors()
      },
    })
  }

  return {
    req,
    pagos,
    pendiente,
    money,
    fmtLong,

    form,
    submitting,

    fileKey,
    dragActive,
    pickedName,
    hasPicked,
    clearFile,
    onPickFile,
    onDropFile,
    onDragEnter,
    onDragOver,
    onDragLeave,

    uploadPreview,

    onMontoInput,
    canSubmit,
    submit,
  }
}
