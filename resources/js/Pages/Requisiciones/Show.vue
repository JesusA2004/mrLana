<!-- resources/js/Pages/Requisiciones/Show.vue -->
<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

// Se usa any para simplificar; puedes crear un tipo estricto
type AnyObj = Record<string, any>

type ShowProps = {
  requisicion: AnyObj
  detalles: AnyObj[]
  comprobantes?: AnyObj[]
  pagos?: AnyObj[]
  pdf?: {
    requisicion_url?: string | null
    files?: Array<{ label: string; url: string }>
  }
}

const props = defineProps<ShowProps>()

const page = usePage<any>()
const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
const canDelete = computed(() => ['ADMIN','CONTADOR'].includes(role.value))
const canPay = computed(() => role.value === 'CONTADOR')
const canUploadComprobantes = computed(() => ['ADMIN','CONTADOR','COLABORADOR'].includes(role.value))

const activePdfUrl = ref<string | null>(props.pdf?.requisicion_url ?? null)

function money(v: any) {
  const n = Number(v ?? 0)
  try {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
  } catch {
    return String(v ?? '')
  }
}

function printReq() {
  const url = route('requisiciones.print', props.requisicion.id)
  const w = window.open(url, '_blank', 'noopener,noreferrer')
  w?.focus()
}

function openPdfNewTab() {
  if (!activePdfUrl.value) return
  window.open(activePdfUrl.value, '_blank', 'noopener,noreferrer')
}

function goPay() {
  router.visit(route('requisiciones.pagar', props.requisicion.id))
}

function goComprobar() {
  router.visit(route('requisiciones.comprobar', props.requisicion.id))
}

function destroyReq() {
  if (!canDelete.value) return
  if (!confirm(`¿Eliminar requisición ${props.requisicion.folio}?`)) return
  router.delete(route('requisiciones.destroy', props.requisicion.id))
}

function pickPdf(url: string) {
  activePdfUrl.value = url
}

function statusPill(status: string) {
  const s = String(status || '').toUpperCase()
  // Map de estados actualizado a los nuevos
  if (s === 'BORRADOR')              return 'bg-zinc-500/10 text-zinc-200 border-white/10'
  if (s === 'ELIMINADA')             return 'bg-rose-500/10 text-rose-200 border-rose-500/20'
  if (s === 'CAPTURADA')             return 'bg-slate-500/10 text-slate-200 border-white/10'
  if (s === 'PAGO_AUTORIZADO')       return 'bg-sky-500/10 text-sky-200 border-sky-500/20'
  if (s === 'PAGO_RECHAZADO')        return 'bg-rose-500/10 text-rose-200 border-rose-500/20'
  if (s === 'PAGADA')               return 'bg-cyan-600/10 text-cyan-200 border-cyan-600/20'
  if (s === 'POR_COMPROBAR')         return 'bg-amber-500/10 text-amber-200 border-amber-500/20'
  if (s === 'COMPROBACION_ACEPTADA') return 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
  if (s === 'COMPROBACION_RECHAZADA')return 'bg-rose-500/10 text-rose-200 border-rose-500/20'
  return 'bg-slate-500/10 text-slate-200 border-white/10'
}

const pdfFiles = computed(() => {
  const base = props.pdf?.files ?? []
  const req = props.pdf?.requisicion_url ? [{ label: 'Requisición (PDF)', url: props.pdf.requisicion_url }] : []
  return [...req, ...base]
})
</script>

<template>
  <Head :title="`Requisición ${props.requisicion?.folio ?? props.requisicion?.id}`" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
        Requisición {{ props.requisicion?.folio ?? `#${props.requisicion?.id}` }}
      </h2>
    </template>

    <div class="w-full max-w-full min-w-0 overflow-x-hidden">
      <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Header actions -->
        <div
          class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
        >
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-lg font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ props.requisicion?.folio ?? `REQ-${props.requisicion?.id}` }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold border"
                      :class="statusPill(props.requisicion?.status)">
                  <span class="h-1.5 w-1.5 rounded-full bg-white/50"></span>
                  {{ props.requisicion?.status }}
                </span>
              </div>

              <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                {{ props.requisicion?.comprador?.nombre ?? '—' }} · {{ props.requisicion?.sucursal?.nombre ?? '—' }} ·
                {{ props.requisicion?.solicitante?.nombre ?? '—' }}
              </p>
            </div>

            <div class="flex flex-wrap gap-2">
              <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.index'))">
                Volver
              </SecondaryButton>

              <button
                type="button"
                @click="printReq"
                class="rounded-2xl px-4 py-3 text-sm font-extrabold
                       bg-white text-slate-900 border border-slate-200 hover:bg-slate-50
                       dark:bg-neutral-900 dark:text-neutral-100 dark:border-white/10 dark:hover:bg-white/5
                       transition active:scale-[0.99]"
              >
                Imprimir PDF
              </button>

              <button
                v-if="canPay"
                type="button"
                @click="goPay"
                class="rounded-2xl px-4 py-3 text-sm font-extrabold
                       bg-sky-600 text-white hover:bg-sky-700
                       transition active:scale-[0.99]"
              >
                Pagar
              </button>

              <button
                v-if="canUploadComprobantes"
                type="button"
                @click="goComprobar"
                class="rounded-2xl px-4 py-3 text-sm font-extrabold
                       bg-indigo-600 text-white hover:bg-indigo-700
                       transition active:scale-[0.99]"
              >
                Subir comprobantes
              </button>

              <button
                v-if="canDelete"
                type="button"
                @click="destroyReq"
                class="rounded-2xl px-4 py-3 text-sm font-extrabold
                       bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                       dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                       transition active:scale-[0.99]"
              >
                Eliminar
              </button>
            </div>
          </div>
        </div>

        <!-- Aquí continúa la estructura del detalle, lista de items, comprobantes, pagos, PDF viewer, etc.
             Puedes reutilizar la base del archivo original, actualizando los estados de color y textos.
        -->
      </div>
    </div>
  </AuthenticatedLayout>
</template>
