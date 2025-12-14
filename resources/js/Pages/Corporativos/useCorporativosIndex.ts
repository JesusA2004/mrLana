import { router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import type { CorporativoRow, CorporativosProps } from './Corporativos.types'
import { escapeHtml, isValidEmail, logoSrc } from '@/Utils/dom'
import { uploadCorporativoLogo } from '@/Services/corporativos.service'
import { useSwalTheme } from '@/Utils/swal'

export function useCorporativosIndex(props: CorporativosProps) {
  const page = usePage()
  const { Swal, isDark, toast, swalBaseClasses, ensurePopupDark } = useSwalTheme()

  const q = ref(props.filters.q ?? '')
  const activo = ref<'all' | '1' | '0'>((props.filters.activo ?? 'all') as any)
  const perPage = ref<number>(props.filters.per_page ?? 10)

  const selectedIds = ref<Set<number>>(new Set())
  const headerCheckbox = ref<HTMLInputElement | null>(null)

  const pageIds = computed(() => props.corporativos.data.map((r) => r.id))
  const isAllSelected = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => selectedIds.value.has(id)))
  const isSomeSelected = computed(() => pageIds.value.some((id) => selectedIds.value.has(id)) && !isAllSelected.value)
  const selectedCount = computed(() => selectedIds.value.size)

  const headerAriaChecked = computed<true | false | 'mixed'>(() => {
    if (isSomeSelected.value) return 'mixed'
    return isAllSelected.value ? true : false
  })

  function syncHeaderIndeterminate() {
    if (!headerCheckbox.value) return
    headerCheckbox.value.indeterminate = isSomeSelected.value
  }

  function toggleRow(id: number, checked: boolean) {
    const s = new Set(selectedIds.value)
    if (checked) s.add(id)
    else s.delete(id)
    selectedIds.value = s
    syncHeaderIndeterminate()
  }

  function toggleAllOnPage(checked: boolean) {
    const s = new Set(selectedIds.value)
    for (const id of pageIds.value) {
      if (checked) s.add(id)
      else s.delete(id)
    }
    selectedIds.value = s
    syncHeaderIndeterminate()
  }

  function clearSelection() {
    selectedIds.value = new Set()
    syncHeaderIndeterminate()
  }

  onMounted(() => syncHeaderIndeterminate())

  let t: number | null = null
  watch([q, activo, perPage], () => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => {
      router.get(
        route('corporativos.index'),
        { q: q.value, activo: activo.value, per_page: perPage.value },
        {
          preserveState: true,
          preserveScroll: true,
          replace: true,
          onFinish: () => nextTick(syncHeaderIndeterminate),
        }
      )
    }, 250)
  })

  const paginationLinks = computed(() => {
    const raw = (props.corporativos as any)?.meta?.links ?? (props.corporativos as any)?.links ?? []
    if (!Array.isArray(raw)) return []
    return raw.map((l: any) => {
      const labelRaw = String(l.label ?? '').toLowerCase()
      let label = l.label
      if (labelRaw.includes('previous')) label = 'Atrás'
      if (labelRaw.includes('next')) label = 'Siguiente'
      return { ...l, label }
    })
  })

  function goTo(url: string | null) {
    if (!url) return
    router.visit(url, { preserveState: true, preserveScroll: true, onFinish: () => nextTick(syncHeaderIndeterminate) })
  }

  function firstError(errors: Record<string, any>): string {
    const v = Object.values(errors ?? {})[0]
    if (Array.isArray(v)) return String(v[0] ?? 'Error de validación.')
    return String(v ?? 'Error de validación.')
  }

  function buildModalHtml(mode: 'create' | 'edit', row?: CorporativoRow) {
    const nombre = escapeHtml(row?.nombre ?? '')
    const rfc = escapeHtml(row?.rfc ?? '')
    const codigo = escapeHtml(row?.codigo ?? '')
    const direccion = escapeHtml(row?.direccion ?? '')
    const telefono = escapeHtml(row?.telefono ?? '')
    const email = escapeHtml(row?.email ?? '')
    const logoPath = escapeHtml(row?.logo_path ?? '')
    const activoChecked = row?.activo ?? true

    return `
      <div class="text-left">
        <div class="grid gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Nombre <span class="text-rose-500">*</span></label>
            <input id="m_nombre" value="${nombre}"
              class="w-full rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                     px-3 py-2 text-sm text-slate-900 dark:text-neutral-100 outline-none
                     focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
              placeholder="Ej. Corporativo MR-Lana" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">RFC</label>
              <input id="m_rfc" value="${rfc}"
                class="w-full rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                       px-3 py-2 text-sm text-slate-900 dark:text-neutral-100 outline-none
                       focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
                placeholder="Opcional" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Código</label>
              <input id="m_codigo" value="${codigo}"
                class="w-full rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                       px-3 py-2 text-sm text-slate-900 dark:text-neutral-100 outline-none
                       focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
                placeholder="Opcional" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Dirección</label>
            <input id="m_direccion" value="${direccion}"
              class="w-full rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                     px-3 py-2 text-sm text-slate-900 dark:text-neutral-100 outline-none
                     focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
              placeholder="Opcional" />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Teléfono</label>
              <input id="m_telefono" value="${telefono}"
                class="w-full rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                       px-3 py-2 text-sm text-slate-900 dark:text-neutral-100 outline-none
                       focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
                placeholder="Opcional" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Email</label>
              <input id="m_email" value="${email}"
                class="w-full rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                       px-3 py-2 text-sm text-slate-900 dark:text-neutral-100 outline-none
                       focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
                placeholder="Opcional" />
              <div class="mt-1 text-[11px] text-slate-500 dark:text-neutral-400">
                Si lo capturas, debe ser válido (ej. nombre@dominio.com).
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950/40 p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
              <div>
                <div class="text-sm font-semibold text-slate-900 dark:text-neutral-100">Logo</div>
                <div class="text-xs text-slate-600 dark:text-neutral-300">PNG / JPG / WEBP (máx 2MB)</div>
              </div>

              <div class="flex items-center gap-2">
                <input id="m_logo_file" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" />
                <button id="m_btn_upload" type="button"
                  class="rounded-2xl px-3 py-2 text-xs font-semibold
                         bg-slate-900 text-white hover:bg-slate-800
                         dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white transition active:scale-[0.98]">
                  ${mode === 'create' ? 'Subir logo' : 'Cambiar logo'}
                </button>
                <button id="m_btn_remove" type="button"
                  class="${logoPath ? '' : 'hidden'} rounded-2xl px-3 py-2 text-xs font-semibold
                         bg-rose-50 text-rose-700 hover:bg-rose-100
                         dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20 transition active:scale-[0.98]">
                  Quitar
                </button>
              </div>
            </div>

            <div class="mt-3 flex items-center gap-3">
              <div class="h-14 w-14 rounded-2xl border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-neutral-900 grid place-items-center overflow-hidden">
                <img id="m_logo_preview" class="${logoPath ? '' : 'hidden'} h-full w-full object-cover" src="${logoSrc(logoPath) ?? ''}" />
                <div id="m_logo_badge" class="${logoPath ? 'hidden' : ''} text-[10px] font-semibold text-slate-500 dark:text-neutral-400">
                  Sin logo
                </div>
              </div>

              <div class="min-w-0">
                <div id="m_logo_status" class="text-[11px] text-slate-500 dark:text-neutral-400">
                  (La ruta se guarda al presionar Guardar/Actualizar.)
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <input id="m_activo" type="checkbox" ${activoChecked ? 'checked' : ''} class="h-4 w-4 rounded border-slate-300 dark:border-white/10" />
            <label for="m_activo" class="text-sm text-slate-700 dark:text-neutral-200">Activo</label>
          </div>
        </div>
      </div>
    `
  }

  function wireModalLogo(initialLogoPath: string | null) {
    let currentLogoPath: string | null = initialLogoPath

    const file = document.getElementById('m_logo_file') as HTMLInputElement | null
    const btn = document.getElementById('m_btn_upload') as HTMLButtonElement | null
    const remove = document.getElementById('m_btn_remove') as HTMLButtonElement | null
    const preview = document.getElementById('m_logo_preview') as HTMLImageElement | null
    const badge = document.getElementById('m_logo_badge') as HTMLDivElement | null
    const status = document.getElementById('m_logo_status') as HTMLDivElement | null

    const setPreview = (path: string | null, note?: string) => {
      currentLogoPath = path
      const src = logoSrc(path)
      if (preview) {
        preview.src = src || ''
        preview.classList.toggle('hidden', !src)
      }
      if (badge) badge.classList.toggle('hidden', !!src)
      if (remove) remove.classList.toggle('hidden', !src)
      if (status) status.textContent = note ?? ''
    }

    setPreview(currentLogoPath)

    btn?.addEventListener('click', () => file?.click())

    file?.addEventListener('change', async () => {
      const f = file.files?.[0]
      if (!f) return

      if (f.size > 2 * 1024 * 1024) {
        Swal.showValidationMessage('El archivo supera 2MB.')
        if (file) file.value = ''
        return
      }

      try {
        if (btn) {
          btn.disabled = true
          btn.innerText = 'Subiendo...'
        }

        const path = await uploadCorporativoLogo(f)
        setPreview(path, 'Logo subido. Pendiente de guardar el registro.')

        if (btn) btn.innerText = 'Cambiar logo'
      } catch (e: any) {
        Swal.showValidationMessage(e?.message ?? 'Error al subir el logo.')
      } finally {
        if (btn) btn.disabled = false
        if (file) file.value = ''
      }
    })

    remove?.addEventListener('click', () => setPreview(null, 'Logo removido. Pendiente de guardar el registro.'))

    return () => currentLogoPath
  }

  async function openCreate() {
    let getLogoPath: (() => string | null) | null = null

    const result = await Swal.fire({
      title: 'Nuevo corporativo',
      showCancelButton: true,
      confirmButtonText: 'Guardar',
      cancelButtonText: 'Cancelar',
      focusConfirm: false,
      customClass: swalBaseClasses(),
      html: buildModalHtml('create'),
      didOpen: () => {
        ensurePopupDark()
        getLogoPath = wireModalLogo(null)
      },
      preConfirm: () => {
        const nombre = (document.getElementById('m_nombre') as HTMLInputElement)?.value?.trim()
        const email = (document.getElementById('m_email') as HTMLInputElement)?.value?.trim() || ''

        if (!nombre) {
          Swal.showValidationMessage('El nombre es obligatorio.')
          return
        }
        if (!isValidEmail(email)) {
          Swal.showValidationMessage('El email no es válido.')
          return
        }

        return {
          nombre,
          rfc: (document.getElementById('m_rfc') as HTMLInputElement)?.value?.trim() || null,
          codigo: (document.getElementById('m_codigo') as HTMLInputElement)?.value?.trim() || null,
          direccion: (document.getElementById('m_direccion') as HTMLInputElement)?.value?.trim() || null,
          telefono: (document.getElementById('m_telefono') as HTMLInputElement)?.value?.trim() || null,
          email: email || null,
          logo_path: getLogoPath ? getLogoPath() : null,
          activo: (document.getElementById('m_activo') as HTMLInputElement)?.checked ?? true,
        }
      },
    })

    if (!result.isConfirmed) return

    router.post(route('corporativos.store'), result.value, {
      preserveScroll: true,
      onSuccess: () => {
        toast().fire({ icon: 'success', title: 'Corporativo creado' })
        clearSelection()
      },
      onError: (errors) => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo guardar',
          text: firstError(errors as any),
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  async function openEdit(row: CorporativoRow) {
    let getLogoPath: (() => string | null) | null = null

    const result = await Swal.fire({
      title: `Editar: ${row.nombre}`,
      showCancelButton: true,
      confirmButtonText: 'Actualizar',
      cancelButtonText: 'Cancelar',
      focusConfirm: false,
      customClass: swalBaseClasses(),
      html: buildModalHtml('edit', row),
      didOpen: () => {
        ensurePopupDark()
        getLogoPath = wireModalLogo(row.logo_path ?? null)
      },
      preConfirm: () => {
        const nombre = (document.getElementById('m_nombre') as HTMLInputElement)?.value?.trim()
        const email = (document.getElementById('m_email') as HTMLInputElement)?.value?.trim() || ''

        if (!nombre) {
          Swal.showValidationMessage('El nombre es obligatorio.')
          return
        }
        if (!isValidEmail(email)) {
          Swal.showValidationMessage('El email no es válido.')
          return
        }

        return {
          nombre,
          rfc: (document.getElementById('m_rfc') as HTMLInputElement)?.value?.trim() || null,
          codigo: (document.getElementById('m_codigo') as HTMLInputElement)?.value?.trim() || null,
          direccion: (document.getElementById('m_direccion') as HTMLInputElement)?.value?.trim() || null,
          telefono: (document.getElementById('m_telefono') as HTMLInputElement)?.value?.trim() || null,
          email: email || null,
          logo_path: getLogoPath ? getLogoPath() : row.logo_path ?? null,
          activo: (document.getElementById('m_activo') as HTMLInputElement)?.checked ?? true,
        }
      },
    })

    if (!result.isConfirmed) return

    router.put(route('corporativos.update', row.id), result.value, {
      preserveScroll: true,
      onSuccess: () => {
        toast().fire({ icon: 'success', title: 'Corporativo actualizado' })
        clearSelection()
      },
      onError: (errors) => {
        Swal.fire({
          icon: 'error',
          title: 'No se pudo actualizar',
          text: firstError(errors as any),
          confirmButtonText: 'OK',
          customClass: swalBaseClasses(),
          didOpen: ensurePopupDark,
        })
      },
    })
  }

  async function confirmDelete(row: CorporativoRow) {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Eliminar corporativo',
      text: `Se eliminará "${row.nombre}". Esta acción no se puede deshacer.`,
      showCancelButton: true,
      confirmButtonText: 'Eliminar',
      cancelButtonText: 'Cancelar',
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })

    if (!result.isConfirmed) return

    router.delete(route('corporativos.destroy', row.id), {
      preserveScroll: true,
      onSuccess: () => {
        toast().fire({ icon: 'success', title: 'Corporativo eliminado' })
        const s = new Set(selectedIds.value)
        s.delete(row.id)
        selectedIds.value = s
        syncHeaderIndeterminate()
      },
    })
  }

  async function confirmBulkDelete() {
    if (selectedIds.value.size === 0) return

    const ids = Array.from(selectedIds.value)
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Eliminar seleccionados',
      html: `<div class="text-sm">Se eliminarán <b>${ids.length}</b> corporativos. Esta acción no se puede deshacer.</div>`,
      showCancelButton: true,
      confirmButtonText: `Eliminar (${ids.length})`,
      cancelButtonText: 'Cancelar',
      customClass: swalBaseClasses(),
      didOpen: ensurePopupDark,
    })

    if (!result.isConfirmed) return

    Swal.fire({
      title: 'Eliminando...',
      html: `<div class="text-sm">Procesando <b>${ids.length}</b> registros</div>`,
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      customClass: swalBaseClasses(),
      didOpen: () => {
        ensurePopupDark()
        Swal.showLoading()
      },
    })

    let ok = 0
    let fail = 0

    for (const id of ids) {
      await new Promise<void>((resolve) => {
        router.delete(route('corporativos.destroy', id), {
          preserveScroll: true,
          onSuccess: () => { ok++; resolve() },
          onError: () => { fail++; resolve() },
        })
      })
    }

    Swal.close()
    clearSelection()

    if (fail === 0) toast().fire({ icon: 'success', title: `Eliminados ${ok}` })
    else toast().fire({ icon: 'warning', title: `Eliminados ${ok}, fallaron ${fail}` })
  }

  watch(
    () => (page.props as any)?.flash,
    (f: any) => {
      const msg = f?.success || f?.message
      if (msg) toast().fire({ icon: 'success', title: String(msg) })
    },
    { deep: true }
  )

  watch(
    () => props.corporativos.data,
    () => nextTick(syncHeaderIndeterminate),
    { deep: true }
  )

  return {
    q, activo, perPage,
    selectedIds, headerCheckbox,
    isAllSelected, isSomeSelected, selectedCount, headerAriaChecked,
    paginationLinks,
    logoSrc,
    toggleRow, toggleAllOnPage, clearSelection,
    goTo,
    openCreate, openEdit, confirmDelete, confirmBulkDelete,
    isDark,
  }
}
