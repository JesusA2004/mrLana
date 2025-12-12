<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import Swal from 'sweetalert2'

type CorporativoRow = {
  id: number
  nombre: string
  rfc: string | null
  direccion: string | null
  telefono: string | null
  email: string | null
  codigo: string | null
  logo_path: string | null
  activo: boolean
  created_at?: string | null
  updated_at?: string | null
}

type PaginationLink = {
  url: string | null
  label: string
  active: boolean
}

const page = usePage()

const props = defineProps<{
  corporativos: {
    data: CorporativoRow[]
    meta: {
      current_page: number
      last_page: number
      per_page: number
      total: number
      from: number | null
      to: number | null
      links: PaginationLink[]
    }
  }
  filters: {
    q: string
    activo: 'all' | '1' | '0'
    per_page: number
  }
}>()

/**
 * UI State (Filtros)
 */
const q = ref(props.filters.q ?? '')
const activo = ref<'all' | '1' | '0'>((props.filters.activo ?? 'all') as any)
const perPage = ref<number>(props.filters.per_page ?? 10)

/**
 * Selección múltiple
 */
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

onMounted(() => {
  syncHeaderIndeterminate()
})

/**
 * Helpers: dark mode + SweetAlert2 classes
 */
const isDark = computed(() => document.documentElement.classList.contains('dark'))

function swalBaseClasses() {
  return {
    popup:
      'rounded-3xl shadow-2xl border border-slate-200/70 dark:border-white/10 ' +
      'bg-white dark:bg-neutral-900 text-slate-800 dark:text-neutral-100',
    title: 'text-slate-900 dark:text-neutral-100',
    htmlContainer: 'text-slate-700 dark:text-neutral-200 !m-0 overflow-x-hidden',
    actions: 'gap-2',
    confirmButton:
      'rounded-2xl px-4 py-2 font-semibold bg-slate-900 text-white hover:bg-slate-800 ' +
      'dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white transition active:scale-[0.98]',
    cancelButton:
      'rounded-2xl px-4 py-2 font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 ' +
      'dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700 transition active:scale-[0.98]',
  }
}

function toast() {
  return Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
    customClass: {
      popup:
        'rounded-2xl shadow-2xl border border-slate-200/70 dark:border-white/10 ' +
        'bg-white dark:bg-neutral-900 text-slate-800 dark:text-neutral-100',
      title: 'text-sm font-semibold',
    },
    didOpen: (p) => {
      if (p) p.classList.toggle('dark', isDark.value)
    },
  })
}

function csrfToken(): string {
  const el = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
  return el?.content ?? ''
}

function escapeHtml(value: string): string {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

/**
 * Normaliza rutas para poder mostrar logos:
 * - "/storage/..." ok
 * - "storage/..." -> "/storage/..."
 * - "http(s)://..." ok
 */
function logoSrc(path: string | null): string | null {
  if (!path) return null
  const p = String(path).trim()
  if (!p) return null
  if (/^https?:\/\//i.test(p)) return p
  if (p.startsWith('/')) return p
  return `/${p}`
}

/**
 * Navegación con filtros (Inertia)
 */
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

/**
 * UI: traduce Previous/Next a Atrás/Siguiente
 */
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

/**
 * Upload logo (endpoint fijo para evitar Ziggy route list issues)
 * POST /corporativos/logo  (auth)
 * Respuesta: { logo_path: "/storage/..." } ó { logo_path: "storage/..." }
 */
async function uploadLogo(file: File): Promise<string> {
  const form = new FormData()
  form.append('logo', file)

  const res = await fetch('/corporativos/logo', {
    method: 'POST',
    credentials: 'same-origin', // ✅ evita 419 (sesión/cookie)
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: form,
  })

  if (!res.ok) {
    let msg = 'No se pudo subir el logo.'
    try {
      const j = await res.json()
      msg = j?.message ?? msg
    } catch {}
    throw new Error(msg)
  }

  const data = await res.json()
  return String(data.logo_path || '')
}

/**
 * Validaciones front (no perder datos por validación backend)
 */
function isValidEmail(v: string): boolean {
  const s = v.trim()
  if (!s) return true
  // suficiente para UI (backend sigue validando)
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s)
}

/**
 * MODAL HTML (Tailwind, responsive móvil)
 */
function buildModalHtml(mode: 'create' | 'edit', row?: CorporativoRow) {
  const title =
    mode === 'create' ? 'Alta de corporativo' : `Editando #${row?.id ?? ''} — ${escapeHtml(row?.nombre ?? '')}`

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
            <div id="m_email_hint" class="mt-1 text-[11px] text-slate-500 dark:text-neutral-400">
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
                (La ruta se guarda al presionar <span class="font-semibold">Guardar/Actualizar</span>.)
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

/**
 * Wire del logo dentro del modal:
 * - NO lanza toast de "logo cargado"
 * - Solo actualiza preview + ruta + status
 */
function wireModalLogo(initialLogoPath: string | null) {
  let currentLogoPath: string | null = initialLogoPath

  const file = document.getElementById('m_logo_file') as HTMLInputElement | null
  const btn = document.getElementById('m_btn_upload') as HTMLButtonElement | null
  const remove = document.getElementById('m_btn_remove') as HTMLButtonElement | null
  const preview = document.getElementById('m_logo_preview') as HTMLImageElement | null
  const badge = document.getElementById('m_logo_badge') as HTMLDivElement | null
  const pathText = document.getElementById('m_logo_path_text') as HTMLSpanElement | null
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
    if (pathText) pathText.textContent = path || '—'
    if (status) status.textContent = note
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

      const path = await uploadLogo(f)
      setPreview(path, 'Logo subido. Pendiente de guardar el registro.')

      if (btn) btn.innerText = 'Cambiar logo'
    } catch (e: any) {
      Swal.showValidationMessage(e?.message ?? 'Error al subir el logo.')
    } finally {
      if (btn) btn.disabled = false
      if (file) file.value = ''
    }
  })

  remove?.addEventListener('click', () => {
    setPreview(null, 'Logo removido. Pendiente de guardar el registro.')
  })

  return () => currentLogoPath
}

/**
 * Errores backend -> texto usable
 */
function firstError(errors: Record<string, any>): string {
  const v = Object.values(errors ?? {})[0]
  if (Array.isArray(v)) return String(v[0] ?? 'Error de validación.')
  return String(v ?? 'Error de validación.')
}

/**
 * CRUD
 */
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
      const popup = Swal.getPopup()
      if (popup) popup.classList.toggle('dark', isDark.value)
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
        // ✅ este valor debe ser permitido en tu FormRequest (logo_path nullable string)
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
        didOpen: () => {
          const popup = Swal.getPopup()
          if (popup) popup.classList.toggle('dark', isDark.value)
        },
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
      const popup = Swal.getPopup()
      if (popup) popup.classList.toggle('dark', isDark.value)
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
        // ✅ guardar ruta final al presionar Actualizar
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
        didOpen: () => {
          const popup = Swal.getPopup()
          if (popup) popup.classList.toggle('dark', isDark.value)
        },
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
    didOpen: () => {
      const popup = Swal.getPopup()
      if (popup) popup.classList.toggle('dark', isDark.value)
    },
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
    didOpen: () => {
      const popup = Swal.getPopup()
      if (popup) popup.classList.toggle('dark', isDark.value)
    },
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
      const popup = Swal.getPopup()
      if (popup) popup.classList.toggle('dark', isDark.value)
      Swal.showLoading()
    },
  })

  let ok = 0
  let fail = 0

  for (const id of ids) {
    // eslint-disable-next-line no-await-in-loop
    await new Promise<void>((resolve) => {
      router.delete(route('corporativos.destroy', id), {
        preserveScroll: true,
        onSuccess: () => {
          ok++
          resolve()
        },
        onError: () => {
          fail++
          resolve()
        },
      })
    })
  }

  Swal.close()
  clearSelection()

  if (fail === 0) toast().fire({ icon: 'success', title: `Eliminados ${ok}` })
  else toast().fire({ icon: 'warning', title: `Eliminados ${ok}, fallaron ${fail}` })
}

/**
 * Flash backend (with('success'))
 */
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
</script>

<template>
  <Head title="Corporativos" />

  <AuthenticatedLayout>
    <div class="p-4 sm:p-6">
      <!-- Header -->
      <div
        class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
               rounded-2xl border border-slate-200/70 dark:border-white/10
               bg-white dark:bg-neutral-900 shadow-sm px-4 py-4"
      >
        <div class="min-w-0">
          <h1 class="text-xl font-bold text-slate-900 dark:text-neutral-100 truncate">Corporativos</h1>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
          <div
            v-if="selectedCount > 0"
            class="flex items-center justify-between sm:justify-start gap-2
                   rounded-2xl border border-slate-200/70 dark:border-white/10
                   bg-slate-50 dark:bg-neutral-950/40 px-3 py-2"
          >
            <div class="text-xs text-slate-700 dark:text-neutral-200">
              Seleccionados: <span class="font-semibold">{{ selectedCount }}</span>
            </div>
            <button
              type="button"
              @click="clearSelection"
              class="rounded-xl px-3 py-1.5 text-xs font-semibold
                     bg-white text-slate-800 border border-slate-200 hover:bg-slate-50
                     dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-neutral-950/40
                     transition active:scale-[0.98]"
            >
              Limpiar
            </button>
            <button
              type="button"
              @click="confirmBulkDelete"
              class="rounded-xl px-3 py-1.5 text-xs font-semibold
                     bg-rose-50 text-rose-700 hover:bg-rose-100
                     dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20
                     transition active:scale-[0.98]"
            >
              Eliminar
            </button>
          </div>

          <button
            type="button"
            @click="openCreate"
            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold
                   bg-slate-900 text-white hover:bg-slate-800
                   dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                   transition active:scale-[0.98]"
          >
            Nuevo
          </button>
        </div>
      </div>

      <!-- Filtros -->
      <div
        class="mb-4 grid grid-cols-1 lg:grid-cols-12 gap-3
               rounded-2xl border border-slate-200/70 dark:border-white/10
               bg-white dark:bg-neutral-900 shadow-sm p-4"
      >
        <div class="lg:col-span-6">
          <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Búsqueda</label>
          <input
            v-model="q"
            type="text"
            placeholder="Buscar por nombre, RFC, email, teléfono o código..."
            class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                   px-3 py-2 text-sm text-slate-900 dark:text-neutral-100
                   outline-none focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
          />
        </div>

        <div class="lg:col-span-3">
          <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Estatus</label>
          <select
            v-model="activo"
            class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                   px-3 py-2 text-sm text-slate-900 dark:text-neutral-100
                   outline-none focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
          >
            <option value="all">Todos</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        <div class="lg:col-span-3">
          <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">Registros por página</label>
          <select
            v-model="perPage"
            class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
                   px-3 py-2 text-sm text-slate-900 dark:text-neutral-100
                   outline-none focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>

      <!-- Tabla (desktop/tablet) -->
      <div
        class="hidden sm:block overflow-hidden rounded-2xl border border-slate-200/70 dark:border-white/10
               bg-white dark:bg-neutral-900 shadow-sm"
      >
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-neutral-950/60">
              <tr class="text-left text-slate-600 dark:text-neutral-300">
                <th class="px-4 py-3 font-semibold w-[46px]">
                  <input
                    ref="headerCheckbox"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 dark:border-white/10"
                    :checked="isAllSelected"
                    :aria-checked="headerAriaChecked"
                    @change="toggleAllOnPage(($event.target as HTMLInputElement).checked)"
                  />
                </th>
                <th class="px-4 py-3 font-semibold">Corporativo</th>
                <th class="px-4 py-3 font-semibold">RFC</th>
                <th class="px-4 py-3 font-semibold">Contacto</th>
                <th class="px-4 py-3 font-semibold">Código</th>
                <th class="px-4 py-3 font-semibold">Estatus</th>
                <th class="px-4 py-3 font-semibold text-right">Acciones</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="row in corporativos.data"
                :key="row.id"
                class="border-t border-slate-200/70 dark:border-white/10
                       hover:bg-slate-50/70 dark:hover:bg-neutral-950/40 transition"
              >
                <td class="px-4 py-3 align-middle">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 dark:border-white/10"
                    :checked="selectedIds.has(row.id)"
                    @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
                  />
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div
                      class="h-10 w-10 rounded-2xl border border-slate-200/70 dark:border-white/10 overflow-hidden
                             bg-slate-50 dark:bg-neutral-950 grid place-items-center shrink-0"
                    >
                      <img
                        v-if="row.logo_path"
                        :src="logoSrc(row.logo_path)!"
                        class="h-full w-full object-cover"
                        alt="logo"
                        loading="lazy"
                      />
                      <span v-else class="text-[10px] font-bold text-slate-500 dark:text-neutral-400">
                        {{ row.nombre?.slice(0, 2)?.toUpperCase() }}
                      </span>
                    </div>
                    <div class="min-w-0">
                      <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                        {{ row.nombre }}
                      </div>
                      <div class="text-xs text-slate-500 dark:text-neutral-400">ID: {{ row.id }}</div>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">{{ row.rfc ?? '—' }}</td>

                <td class="px-4 py-3">
                  <div class="text-slate-700 dark:text-neutral-200">{{ row.email ?? '—' }}</div>
                  <div class="text-xs text-slate-500 dark:text-neutral-400">{{ row.telefono ?? '—' }}</div>
                </td>

                <td class="px-4 py-3 text-slate-700 dark:text-neutral-200">{{ row.codigo ?? '—' }}</td>

                <td class="px-4 py-3">
                  <span
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                    :class="
                      row.activo
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20'
                        : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/20'
                    "
                  >
                    <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500' : 'bg-rose-500'" />
                    {{ row.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex justify-end gap-2">
                    <button
                      type="button"
                      @click="openEdit(row)"
                      class="rounded-xl px-3 py-2 text-xs font-semibold
                             bg-slate-100 text-slate-800 hover:bg-slate-200
                             dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                             transition active:scale-[0.98]"
                    >
                      Editar
                    </button>

                    <button
                      type="button"
                      @click="confirmDelete(row)"
                      class="rounded-xl px-3 py-2 text-xs font-semibold
                             bg-rose-50 text-rose-700 hover:bg-rose-100
                             dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20
                             transition active:scale-[0.98]"
                    >
                      Eliminar
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!corporativos.data?.length">
                <td colspan="7" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">
                  No hay corporativos con los filtros actuales.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer / paginación -->
        <div
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                 border-t border-slate-200/70 dark:border-white/10
                 px-4 py-3 bg-white dark:bg-neutral-900"
        >
          <div class="text-xs text-slate-600 dark:text-neutral-300">
            Pagina <span class="font-semibold">{{ corporativos.meta.from ?? 0 }}</span> -
            Registros <span class="font-semibold">{{ corporativos.meta.to ?? 0 }}</span> de
            <span class="font-semibold">{{ corporativos.meta.total }}</span>
          </div>

          <div class="flex flex-wrap gap-2 justify-start sm:justify-end">
            <button
              v-for="(l, idx) in paginationLinks"
              :key="idx"
              type="button"
              @click="goTo(l.url)"
              :disabled="!l.url"
              class="rounded-xl px-3 py-2 text-xs font-semibold border transition
                     disabled:opacity-50 disabled:cursor-not-allowed"
              :class="
                l.active
                  ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'
              "
              v-html="l.label"
            />
          </div>
        </div>
      </div>

      <!-- Cards (móvil) -->
      <div class="sm:hidden grid gap-3">
        <div
          v-for="row in corporativos.data"
          :key="row.id"
          class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4"
        >
          <div class="flex items-start gap-3">
            <div class="pt-1">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 dark:border-white/10"
                :checked="selectedIds.has(row.id)"
                @change="toggleRow(row.id, ($event.target as HTMLInputElement).checked)"
              />
            </div>

            <div
              class="h-12 w-12 rounded-2xl border border-slate-200/70 dark:border-white/10 overflow-hidden
                     bg-slate-50 dark:bg-neutral-950 grid place-items-center shrink-0"
            >
              <img
                v-if="row.logo_path"
                :src="logoSrc(row.logo_path)!"
                class="h-full w-full object-cover"
                alt="logo"
                loading="lazy"
              />
              <span v-else class="text-xs font-black text-slate-500 dark:text-neutral-400">
                {{ row.nombre?.slice(0, 2)?.toUpperCase() }}
              </span>
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                <div class="font-semibold text-slate-900 dark:text-neutral-100 truncate">
                  {{ row.nombre }}
                </div>

                <span
                  class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold border"
                  :class="
                    row.activo
                      ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/20'
                      : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/20'
                  "
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="row.activo ? 'bg-emerald-500' : 'bg-rose-500'" />
                  {{ row.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </div>

              <div class="mt-1 text-xs text-slate-500 dark:text-neutral-400">ID: {{ row.id }}</div>

              <div class="mt-3 grid gap-1 text-sm text-slate-700 dark:text-neutral-200">
                <div class="text-xs"><span class="font-semibold">RFC:</span> {{ row.rfc ?? '—' }}</div>
                <div class="text-xs"><span class="font-semibold">Código:</span> {{ row.codigo ?? '—' }}</div>
                <div class="text-xs"><span class="font-semibold">Email:</span> {{ row.email ?? '—' }}</div>
                <div class="text-xs"><span class="font-semibold">Tel:</span> {{ row.telefono ?? '—' }}</div>
              </div>

              <div class="mt-4 flex gap-2">
                <button
                  type="button"
                  @click="openEdit(row)"
                  class="flex-1 rounded-xl px-3 py-2 text-xs font-semibold
                         bg-slate-100 text-slate-800 hover:bg-slate-200
                         dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700
                         transition active:scale-[0.98]"
                >
                  Editar
                </button>

                <button
                  type="button"
                  @click="confirmDelete(row)"
                  class="flex-1 rounded-xl px-3 py-2 text-xs font-semibold
                         bg-rose-50 text-rose-700 hover:bg-rose-100
                         dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20
                         transition active:scale-[0.98]"
                >
                  Eliminar
                </button>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="!corporativos.data?.length"
          class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-6 text-center text-slate-500 dark:text-neutral-400"
        >
          No hay corporativos con los filtros actuales.
        </div>

        <!-- Paginación móvil -->
        <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="text-xs text-slate-600 dark:text-neutral-300">
              Mostrando <span class="font-semibold">{{ corporativos.meta.from ?? 0 }}</span> -
              <span class="font-semibold">{{ corporativos.meta.to ?? 0 }}</span> de
              <span class="font-semibold">{{ corporativos.meta.total }}</span>
            </div>

            <button
              type="button"
              class="text-xs font-semibold text-slate-700 hover:text-slate-900 dark:text-neutral-300 dark:hover:text-white"
              @click="toggleAllOnPage(!isAllSelected)"
            >
              {{ isAllSelected ? 'Quitar todo' : 'Seleccionar página' }}
            </button>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              v-for="(l, idx) in paginationLinks"
              :key="idx"
              type="button"
              @click="goTo(l.url)"
              :disabled="!l.url"
              class="rounded-xl px-3 py-2 text-xs font-semibold border transition
                     disabled:opacity-50 disabled:cursor-not-allowed"
              :class="
                l.active
                  ? 'bg-slate-900 text-white border-slate-900 dark:bg-neutral-100 dark:text-neutral-900 dark:border-neutral-100'
                  : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-neutral-900 dark:text-neutral-200 dark:border-white/10 dark:hover:bg-neutral-950/40'
              "
              v-html="l.label"
            />
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
