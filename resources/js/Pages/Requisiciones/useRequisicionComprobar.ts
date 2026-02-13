import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import type { RequisicionComprobarPageProps, ComprobanteRow } from './Comprobar.types'

declare const route: any

type SubmitOpts = {
  onAfterSuccess?: () => void
}

type ReviewStatus = 'APROBADO' | 'RECHAZADO'

type PreviewKind = 'pdf' | 'image' | 'other'
type PreviewState = { url: string; label: string; kind: PreviewKind } | null

type FolioRow = {
  id: number
  folio: string
  monto_total?: number | string | null
  user_registro_id?: number
  created_at?: string
  updated_at?: string
}

export function useRequisicionComprobar(props: RequisicionComprobarPageProps) {
  const page = usePage<any>()

  /** =========================
   * Core data
   * ========================= */
  const req = computed(() => {
    const raw: any = props.requisicion
    return raw?.data ?? raw ?? null
  })

  const rows = computed<ComprobanteRow[]>(() => {
    const raw: any = props.comprobantes
    return raw?.data ?? raw ?? []
  })

  /** =========================
   * Money / dates
   * ========================= */
  const money = (v: any) => {
    const n = Number(v ?? 0)
    return n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
  }

  const fmtLong = (iso?: string | null) => {
    if (!iso) return '—'
    const s = String(iso)
    const d = s.includes('T') ? new Date(s) : new Date(`${s}T00:00:00`)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' })
  }

  /** =========================
   * Labels / pills
   * ========================= */
  const tipoDocLabel = (tipo?: string | null) => {
    const t = String(tipo || '').toUpperCase()
    const opt = (props.tipoDocOptions || []).find((x: any) => String(x.id).toUpperCase() === t)
    return opt?.nombre ?? (t || '—')
  }

  const estatusLabel = (e: ComprobanteRow['estatus']) => {
    if (e === 'APROBADO') return 'Aprobado'
    if (e === 'RECHAZADO') return 'Rechazado'
    return 'Pendiente'
  }

  const estatusPillClass = (e: ComprobanteRow['estatus']) => {
    if (e === 'APROBADO') {
      return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    }
    if (e === 'RECHAZADO') {
      return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200'
    }
    return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-neutral-200'
  }

  /** =========================
   * Roles / permisos
   * ========================= */
  const role = computed(() => {
    const u = page?.props?.auth?.user
    return String(u?.rol ?? u?.role ?? '').toUpperCase()
  })

  const canDelete = computed(() => ['ADMIN', 'CONTADOR'].includes(role.value))
  const canReview = computed(() => ['ADMIN', 'COLABORADOR', 'CONTADOR'].includes(role.value))
  const canFolios = computed(() => ['ADMIN', 'CONTADOR'].includes(role.value))

  /** =========================
   * Form upload (NO cambiar nombres de campos)
   * ========================= */
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

  /** =========================
   * Helpers cents (anti-float)
   * ========================= */
  const toNumber = (v: any) => {
    const n = typeof v === 'number' ? v : parseFloat(String(v ?? '').replace(',', '.'))
    return Number.isFinite(n) ? n : 0
  }
  const toCents = (v: any) => Math.round((toNumber(v) + Number.EPSILON) * 100)
  const centsToFixed = (c: number) => (c / 100).toFixed(2)

  const totalCents = computed(() => toCents(req.value?.monto_total ?? 0))
  const usedCents = computed(() => rows.value.reduce((acc, c: any) => acc + toCents(c?.monto ?? 0), 0))
  const pendienteRawCents = computed(() => totalCents.value - usedCents.value)
  const pendienteCents = computed(() => Math.max(0, pendienteRawCents.value))

  /** =========================
   * Autocompletar monto con pendiente
   * ========================= */
  const montoTouched = ref(false)
  const lastAutoMonto = ref('')

  const syncMontoToPendiente = () => {
    const v = centsToFixed(pendienteCents.value)
    form.monto = v
    lastAutoMonto.value = v
  }

  watch(
    pendienteCents,
    () => {
      if (!montoTouched.value) syncMontoToPendiente()
    },
    { immediate: true },
  )

  const onMontoInput = () => {
    montoTouched.value = true
  }

  const montoOverLimit = computed(() => {
    const m = String(form.monto ?? '').trim()
    if (m === '') return false
    const mCents = toCents(m)
    return mCents > pendienteCents.value
  })

  /** =========================
   * Drag & Drop + file picker
   * ========================= */
  const fileKey = ref(0)
  const dragActive = ref(false)

  const pickedName = computed(() => form.archivo?.name || 'Sin archivo seleccionado')
  const hasPicked = computed(() => !!form.archivo)

  const clearFile = () => {
    form.archivo = null
    fileKey.value++
  }

  const onPickFile = (e: Event) => {
    const input = e.target as HTMLInputElement
    form.archivo = input.files?.[0] ?? null
  }

  const onDropFile = (e: DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    dragActive.value = false
    const f = e.dataTransfer?.files?.[0] ?? null
    if (f) form.archivo = f
  }

  const onDragEnter = (e: DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    dragActive.value = true
  }
  const onDragOver = (e: DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
  }
  const onDragLeave = (e: DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    dragActive.value = false
  }

  /** =========================
   * Preview del archivo A SUBIR
   * ========================= */
  const uploadPreview = ref<PreviewState>(null)
  const uploadObjectUrl = ref<string | null>(null)

  const extOf = (s: string) => {
    const clean = (s || '').split('?')[0].split('#')[0]
    const parts = clean.split('.')
    return (parts.length > 1 ? parts.pop() : '')?.toLowerCase() ?? ''
  }

  const detectKind = (urlOrName: string, mime?: string | null): PreviewKind => {
    const ext = extOf(urlOrName)
    if (mime?.includes('pdf') || ext === 'pdf') return 'pdf'
    if (mime?.startsWith('image/') || ['png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp'].includes(ext)) return 'image'
    return 'other'
  }

  const revokeUploadUrl = () => {
    if (uploadObjectUrl.value) {
      URL.revokeObjectURL(uploadObjectUrl.value)
      uploadObjectUrl.value = null
    }
  }

  const removeUploadPreview = () => {
    uploadPreview.value = null
    revokeUploadUrl()
  }

  const openUploadPreviewInNewTab = () => {
    if (!uploadPreview.value?.url) return
    window.open(uploadPreview.value.url, '_blank', 'noopener,noreferrer')
  }

  watch(
    () => form.archivo,
    (f) => {
      removeUploadPreview()
      if (!f) return
      const url = URL.createObjectURL(f)
      uploadObjectUrl.value = url
      uploadPreview.value = { url, label: f.name, kind: detectKind(f.name, f.type) }
    },
  )

  onBeforeUnmount(() => {
    revokeUploadUrl()
  })

  /** =========================
   * Tipo comprobante (dropdown actual)
   * ========================= */
  const tipoOpen = ref(false)
  const tipoWrap = ref<HTMLElement | null>(null)

  const tipoOptions = computed(() => (Array.isArray(props.tipoDocOptions) ? props.tipoDocOptions : []))
  const tipoSelected = computed(() => {
    const id = (form.tipo_doc ?? '').toString()
    return tipoOptions.value.find((o: any) => (o?.id ?? '').toString() === id) ?? null
  })

  const setTipo = (id: string) => {
    form.tipo_doc = id
    tipoOpen.value = false
  }

  const closeTipoIfOutside = (ev: MouseEvent) => {
    if (!tipoOpen.value) return
    const el = tipoWrap.value
    const t = ev.target as Node | null
    if (el && t && !el.contains(t)) tipoOpen.value = false
  }

  const closeTipoOnEsc = (ev: KeyboardEvent) => {
    if (ev.key === 'Escape') tipoOpen.value = false
  }

  onMounted(() => {
    document.addEventListener('mousedown', closeTipoIfOutside, { passive: true })
    document.addEventListener('keydown', closeTipoOnEsc)
  })

  onBeforeUnmount(() => {
    document.removeEventListener('mousedown', closeTipoIfOutside as any)
    document.removeEventListener('keydown', closeTipoOnEsc as any)
  })

  /** =========================
   * Validación UX submit
   * ========================= */
  const canSubmit = computed(() => {
    const hasFile = !!form.archivo
    const hasTipo = !!(form.tipo_doc && String(form.tipo_doc).trim())
    const hasFecha = !!(form.fecha_emision && String(form.fecha_emision).trim())
    const m = String(form.monto ?? '').trim()
    const hasMonto = m !== '' && !Number.isNaN(Number(m))
    return hasFile && hasTipo && hasFecha && hasMonto && !montoOverLimit.value && !form.processing
  })

  /** =========================
   * Rutas: helper
   * ========================= */
  const tryRoute = (name: string, params?: any) => {
    try {
      return params ? route(name, params) : route(name)
    } catch (_) {
      return null
    }
  }

  /** =========================
   * Submit upload
   * ========================= */
  const submit = (opts?: SubmitOpts) => {
    if (!req.value?.id) return

    Swal.fire({
      title: 'Subiendo comprobante…',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    })

    const url = tryRoute('requisiciones.comprobar.store', { requisicion: req.value.id })
    if (!url) {
      Swal.fire({ icon: 'error', title: 'Ruta no encontrada', text: 'No existe requisiciones.comprobar.store' })
      return
    }

    form.post(url, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        Swal.fire({ icon: 'success', title: 'Comprobante cargado', timer: 1200, showConfirmButton: false })
        form.reset('archivo', 'monto')
        fileKey.value++
        dragActive.value = false
        montoTouched.value = false
        syncMontoToPendiente()
        removeUploadPreview()
        opts?.onAfterSuccess?.()
      },
      onError: (errors) => {
        console.error('Error al subir comprobante:', errors)
        Swal.fire({
          icon: 'error',
          title: 'No se pudo subir',
          text: 'Revisa los campos y vuelve a intentar.',
        })
      },
      onFinish: () => {
        if (Swal.isLoading()) Swal.close()
      },
    })
  }

  const doSubmit = () => submit()

  /** =========================
   * Preview de comprobantes ya subidos (panel derecho)
   * ========================= */
  const preview = ref<PreviewState>(null)
  const previewWrapRef = ref<HTMLElement | null>(null)

  const openPreview = async (row: any) => {
    const url = row?.archivo?.url
    const label = row?.archivo?.label || 'Archivo'
    if (!url) return
    preview.value = { url, label, kind: detectKind(url, null) }
    await nextTick()
    if (previewWrapRef.value && window.matchMedia('(max-width: 1279px)').matches) {
      previewWrapRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }

  const closePreview = () => {
    preview.value = null
  }

  const previewTitle = computed(() => preview.value?.label || 'Selecciona un archivo')

  /** =========================
   * Review routes
   * ========================= */
  const resolveReviewUrl = (id: number) => {
    const candidates = [
      'comprobantes.review',
      'requisiciones.comprobantes.review',
      'requisiciones.comprobantes.revisar',
    ]
    for (const name of candidates) {
      const url = tryRoute(name, { comprobante: id })
      if (url) return url
    }
    const msg = `No encuentro una ruta de revisión. Probé: ${candidates.join(', ')}`
    console.error(msg)
    throw new Error(msg)
  }

  const patchReview = (id: number, estatus: ReviewStatus, comentario: string | null) => {
    return new Promise<void>((resolve, reject) => {
      let url = ''
      try {
        url = resolveReviewUrl(id)
      } catch (e) {
        Swal.fire({ icon: 'error', title: 'Ruta no encontrada', text: (e as any)?.message ?? 'Error' })
        return reject(e)
      }

      Swal.fire({ title: 'Aplicando revisión…', allowOutsideClick: false, didOpen: () => Swal.showLoading() })

      router.patch(
        url,
        { estatus, comentario_revision: comentario },
        {
          preserveScroll: true,
          onSuccess: () => {
            Swal.fire({
              icon: 'success',
              title: estatus === 'APROBADO' ? 'Aprobado' : 'Rechazado',
              timer: 900,
              showConfirmButton: false,
            })
            resolve()
          },
          onError: (errors) => {
            console.error('Error review:', { id, estatus, errors })
            Swal.fire({
              icon: 'error',
              title: 'No se pudo aplicar',
              text: 'Probable: ruta/permiso/validación.',
            })
            reject(errors)
          },
          onFinish: () => {
            if (Swal.isLoading()) Swal.close()
          },
        },
      )
    })
  }

  const approve = async (id: number) => {
    if (!canReview.value) {
      Swal.fire({ icon: 'warning', title: 'Sin permisos', text: 'Tu rol no puede aprobar/rechazar comprobantes.' })
      return
    }

    const r = await Swal.fire({
      icon: 'question',
      title: 'Aprobar comprobante',
      text: `¿Confirmas aprobar el comprobante con ID ${id}?`,
      showCancelButton: true,
      confirmButtonText: 'Aprobar',
      cancelButtonText: 'Cancelar',
    })

    if (!r.isConfirmed) return
    await patchReview(id, 'APROBADO', null)
  }

  const reject = async (id: number) => {
    if (!canReview.value) {
      Swal.fire({ icon: 'warning', title: 'Sin permisos', text: 'Tu rol no puede aprobar/rechazar comprobantes.' })
      return
    }

    const r = await Swal.fire({
      icon: 'warning',
      title: 'Rechazar comprobante',
      input: 'textarea',
      inputLabel: 'Motivo del rechazo',
      inputPlaceholder: 'Ej: comprobante repetido / no corresponde / monto inconsistente…',
      inputAttributes: { 'aria-label': 'Motivo del rechazo' },
      showCancelButton: true,
      confirmButtonText: 'Rechazar',
      cancelButtonText: 'Cancelar',
      preConfirm: (value) => {
        const v = String(value ?? '').trim()
        if (!v) {
          Swal.showValidationMessage('Escribe el motivo del rechazo.')
          return
        }
        return v
      },
    })

    if (!r.isConfirmed) return
    const motivo = String(r.value ?? '').trim()
    await patchReview(id, 'RECHAZADO', motivo)
  }

  /** =========================
   * Eliminar comprobante (solo ADMIN/CONTADOR)
   * ========================= */
  const destroyComprobante = (id: number) => {
    if (!canDelete.value) {
      Swal.fire({ icon: 'warning', title: 'Sin permisos', text: 'Tu rol no puede eliminar comprobantes.' })
      return
    }

    Swal.fire({
      title: 'Eliminar comprobante',
      text: 'Esto lo borra de la base de datos. No hay undo.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626',
    }).then((r) => {
      if (!r.isConfirmed) return

      const url = tryRoute('comprobantes.destroy', id)
      if (!url) {
        Swal.fire({ icon: 'error', title: 'Ruta no encontrada', text: 'No existe comprobantes.destroy' })
        return
      }

      router.delete(url, {
        preserveScroll: true,
        onError: (errors) => {
          console.error('DELETE comprobante error:', errors)
          Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: 'Revisa permisos o la ruta.' })
        },
        onSuccess: () => {
          Swal.fire({ icon: 'success', title: 'Eliminado', timer: 900, showConfirmButton: false })
        },
      })
    })
  }

  /** =========================
   * Folios (panel inline)
   * - Agregar: ADMIN/CONTADOR
   * - Editar: SOLO ADMIN
   * ========================= */
  const foliosOpen = ref(false)
  const toggleFolios = () => {
    foliosOpen.value = !foliosOpen.value
  }

  const folioSelectedId = ref<string | number | null>(null)

  const foliosOptions = computed<FolioRow[]>(() => {
    const anyProps: any = props as any
    const raw = anyProps?.folios ?? anyProps?.foliosOptions ?? anyProps?.folios_options ?? []
    return Array.isArray(raw?.data) ? raw.data : Array.isArray(raw) ? raw : []
  })

  const resolveFolioStoreUrl = () => {
    const candidates = ['folios.store', 'folio.store']
    for (const n of candidates) {
      const url = tryRoute(n)
      if (url) return url
    }
    return null
  }

  const resolveFolioUpdateUrl = (id: number | string) => {
    const candidates = ['folios.update', 'folio.update']
    for (const n of candidates) {
      const url = tryRoute(n, { folio: id })
      if (url) return url
    }
    return null
  }

  const reloadFoliosIfPossible = () => {
    // si tu backend devuelve folios en props, esto los refresca sin navegar a otra página
    try {
      ;(router as any).reload?.({ only: ['folios', 'foliosOptions', 'folios_options'] })
    } catch (_) {
      // no-op
    }
  }

  const addFolio = async () => {
    if (!canFolios.value) return

    const r = await Swal.fire({
      title: 'Agregar folio',
      html:
        `<div style="text-align:left">` +
        `<label style="display:block;font-weight:700;margin:0 0 6px">Folio</label>` +
        `<input id="swal-folio" class="swal2-input" placeholder="Ej: A-2026-000123" style="margin:0 0 10px">` +
        `<label style="display:block;font-weight:700;margin:0 0 6px">Monto total (opcional)</label>` +
        `<input id="swal-monto" class="swal2-input" placeholder="0.00" style="margin:0">` +
        `</div>`,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Guardar',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const folio = String((document.getElementById('swal-folio') as HTMLInputElement)?.value ?? '').trim()
        const montoStr = String((document.getElementById('swal-monto') as HTMLInputElement)?.value ?? '').trim()
        if (!folio) {
          Swal.showValidationMessage('El folio es obligatorio.')
          return
        }
        const monto_total = montoStr ? Number(montoStr) : null
        if (montoStr && Number.isNaN(monto_total)) {
          Swal.showValidationMessage('Monto inválido.')
          return
        }
        return { folio, monto_total }
      },
    })

    if (!r.isConfirmed) return

    const url = resolveFolioStoreUrl()
    if (!url) {
      Swal.fire({ icon: 'error', title: 'Ruta no encontrada', text: 'Crea folios.store en Laravel.' })
      return
    }

    Swal.fire({ title: 'Guardando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() })

    router.post(
      url,
      {
        folio: r.value.folio,
        monto_total: r.value.monto_total,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({ icon: 'success', title: 'Folio agregado', timer: 900, showConfirmButton: false })
          reloadFoliosIfPossible()
        },
        onError: (errors) => {
          console.error('Folio store error:', errors)
          Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: 'Revisa validación/ruta.' })
        },
        onFinish: () => {
          if (Swal.isLoading()) Swal.close()
        },
      },
    )
  }

  const editFolio = async () => {
    if (role.value !== 'ADMIN') {
      Swal.fire({ icon: 'warning', title: 'Solo admin', text: 'El contador no puede editar folios.' })
      return
    }
    if (!folioSelectedId.value) {
      Swal.fire({ icon: 'info', title: 'Selecciona un folio', text: 'Primero elige uno para editar.' })
      return
    }

    const selIdNum = Number(folioSelectedId.value)
    const current = foliosOptions.value.find((f) => Number(f.id) === selIdNum)
    if (!current) {
      Swal.fire({ icon: 'error', title: 'No encontrado', text: 'No pude ubicar el folio seleccionado.' })
      return
    }

    const r = await Swal.fire({
      title: 'Editar folio',
      html:
        `<div style="text-align:left">` +
        `<label style="display:block;font-weight:700;margin:0 0 6px">Folio</label>` +
        `<input id="swal-folio" class="swal2-input" value="${String(current.folio ?? '').replace(/"/g, '&quot;')}" style="margin:0 0 10px">` +
        `<label style="display:block;font-weight:700;margin:0 0 6px">Monto total (opcional)</label>` +
        `<input id="swal-monto" class="swal2-input" value="${current.monto_total ?? ''}" placeholder="0.00" style="margin:0">` +
        `</div>`,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Actualizar',
      cancelButtonText: 'Cancelar',
      preConfirm: () => {
        const folio = String((document.getElementById('swal-folio') as HTMLInputElement)?.value ?? '').trim()
        const montoStr = String((document.getElementById('swal-monto') as HTMLInputElement)?.value ?? '').trim()
        if (!folio) {
          Swal.showValidationMessage('El folio es obligatorio.')
          return
        }
        const monto_total = montoStr ? Number(montoStr) : null
        if (montoStr && Number.isNaN(monto_total)) {
          Swal.showValidationMessage('Monto inválido.')
          return
        }
        return { folio, monto_total }
      },
    })

    if (!r.isConfirmed) return

    const url = resolveFolioUpdateUrl(current.id)
    if (!url) {
      Swal.fire({ icon: 'error', title: 'Ruta no encontrada', text: 'Crea folios.update en Laravel.' })
      return
    }

    Swal.fire({ title: 'Actualizando…', allowOutsideClick: false, didOpen: () => Swal.showLoading() })

    router.patch(
      url,
      {
        folio: r.value.folio,
        monto_total: r.value.monto_total,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          Swal.fire({ icon: 'success', title: 'Folio actualizado', timer: 900, showConfirmButton: false })
          reloadFoliosIfPossible()
        },
        onError: (errors) => {
          console.error('Folio update error:', errors)
          Swal.fire({ icon: 'error', title: 'No se pudo actualizar', text: 'Revisa validación/ruta.' })
        },
        onFinish: () => {
          if (Swal.isLoading()) Swal.close()
        },
      },
    )
  }

  /** =========================
   * Input base (mismo look premium)
   * ========================= */
  const inputBase =
    'w-full rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-900 ' +
    'placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500/40 ' +
    'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500'

  return {
    // core
    req,
    rows,
    money,
    fmtLong,

    // roles
    role,
    canDelete,
    canReview,
    canFolios,

    // upload
    form,
    onPickFile,
    submit,
    doSubmit,
    canSubmit,
    inputBase,

    // monto/pendiente
    pendienteCents,
    centsToFixed,
    onMontoInput,
    montoOverLimit,

    // file picker
    fileKey,
    dragActive,
    pickedName,
    hasPicked,
    clearFile,
    onDropFile,
    onDragEnter,
    onDragOver,
    onDragLeave,

    // preview upload
    uploadPreview,
    openUploadPreviewInNewTab,
    removeUploadPreview,

    // tipo
    tipoOpen,
    tipoWrap,
    tipoOptions,
    tipoSelected,
    setTipo,

    // preview list
    preview,
    previewWrapRef,
    openPreview,
    closePreview,
    previewTitle,

    // labels
    tipoDocLabel,
    estatusLabel,
    estatusPillClass,

    // review actions
    approve,
    reject,

    // delete
    destroyComprobante,

    // folios
    foliosOpen,
    toggleFolios,
    folioSelectedId,
    foliosOptions,
    addFolio,
    editFolio,
  }
}
