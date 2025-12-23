<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SecondaryButton from '@/Components/SecondaryButton.vue'

type AnyObj = Record<string, any>

type ShowProps = {
  requisicion: AnyObj
  detalles: AnyObj[]
  comprobantes?: AnyObj[]
  pagos?: AnyObj[]
  pdf?: {
    // PDF de requisición impresa (logo del corporativo)
    requisicion_url?: string | null
    // PDFs relacionados (comprobantes, pago, etc.)
    files?: Array<{ label: string; url: string }>
  }
}

const props = defineProps<ShowProps>()
const page = usePage<any>()
const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())

const canDelete = computed(() => role.value === 'ADMIN' || role.value === 'CONTADOR')
const canPay = computed(() => role.value === 'CONTADOR')
const canUploadComprobantes = computed(() => ['ADMIN', 'CONTADOR', 'COLABORADOR'].includes(role.value))

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
  if (s === 'ACEPTADA') return 'bg-emerald-500/10 text-emerald-200 border-emerald-500/20'
  if (s === 'PAGADA') return 'bg-sky-500/10 text-sky-200 border-sky-500/20'
  if (s === 'COMPROBADA') return 'bg-indigo-500/10 text-indigo-200 border-indigo-500/20'
  if (s === 'POR_COMPROBAR') return 'bg-amber-500/10 text-amber-200 border-amber-500/20'
  if (s === 'CAPTURADA') return 'bg-slate-500/10 text-slate-200 border-white/10'
  if (s === 'RECHAZADA') return 'bg-rose-500/10 text-rose-200 border-rose-500/20'
  if (s === 'BORRADOR') return 'bg-zinc-500/10 text-zinc-200 border-white/10'
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

        <!-- Grid: resumen + pdf -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
          <!-- Resumen -->
          <div
            class="lg:col-span-5 rounded-3xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
          >
            <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Resumen</h3>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
              <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
                <div class="text-xs text-slate-500 dark:text-neutral-400">Tipo</div>
                <div class="font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ props.requisicion?.tipo === 'ANTICIPO' ? 'Anticipo' : 'Reembolso' }}
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
                <div class="text-xs text-slate-500 dark:text-neutral-400">Total</div>
                <div class="font-extrabold text-slate-900 dark:text-neutral-100">
                  {{ money(props.requisicion?.monto_total) }}
                </div>
              </div>

              <div class="col-span-2 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
                <div class="text-xs text-slate-500 dark:text-neutral-400">Proveedor</div>
                <div class="font-semibold text-slate-900 dark:text-neutral-100">
                  {{ props.requisicion?.proveedor?.nombre_comercial ?? 'Sin proveedor' }}
                </div>
                <div class="text-xs text-slate-600 dark:text-neutral-300">
                  {{ props.requisicion?.proveedor?.banco ?? '—' }} · {{ props.requisicion?.proveedor?.clabe ?? '—' }}
                </div>
              </div>

              <div class="col-span-2 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-950/40 p-4">
                <div class="text-xs font-semibold text-slate-600 dark:text-neutral-300">Observaciones</div>
                <p class="mt-2 text-sm text-slate-800 dark:text-neutral-100 whitespace-pre-wrap">
                  {{ props.requisicion?.observaciones ?? '—' }}
                </p>
              </div>
            </div>

            <!-- Detalles table -->
            <div class="mt-5">
              <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Detalles</div>
              <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[520px] text-sm">
                  <thead class="bg-slate-50 dark:bg-neutral-950/60">
                    <tr class="text-left text-slate-600 dark:text-neutral-300">
                      <th class="px-3 py-2 font-semibold w-[90px]">Cant</th>
                      <th class="px-3 py-2 font-semibold">Descripción</th>
                      <th class="px-3 py-2 font-semibold text-right w-[130px]">Importe</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="d in props.detalles" :key="d.id" class="border-t border-slate-200/70 dark:border-white/10">
                      <td class="px-3 py-2">{{ d.cantidad }}</td>
                      <td class="px-3 py-2">
                        <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ d.descripcion }}</div>
                        <div class="text-xs text-slate-500 dark:text-neutral-400">
                          {{ d.sucursal?.nombre ?? '—' }}
                        </div>
                      </td>
                      <td class="px-3 py-2 text-right font-extrabold text-slate-900 dark:text-neutral-100">
                        {{ money(d.total ?? d.subtotal) }}
                      </td>
                    </tr>
                    <tr v-if="!props.detalles?.length">
                      <td colspan="3" class="px-3 py-8 text-center text-slate-500 dark:text-neutral-400">
                        Sin detalles.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PDF Panel -->
          <div
            class="lg:col-span-7 rounded-3xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
          >
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
              <div>
                <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">PDFs</h3>
                <p class="text-sm text-slate-600 dark:text-neutral-300">
                  Preview embebido + opción de abrir aparte.
                </p>
              </div>

              <div class="flex gap-2">
                <button
                  type="button"
                  @click="openPdfNewTab"
                  :disabled="!activePdfUrl"
                  class="rounded-2xl px-4 py-3 text-sm font-extrabold
                         bg-slate-900 text-white hover:bg-slate-800
                         dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                         disabled:opacity-50 disabled:cursor-not-allowed
                         transition active:scale-[0.99]"
                >
                  Abrir
                </button>
              </div>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
              <button
                v-for="(f, idx) in pdfFiles"
                :key="idx"
                type="button"
                @click="pickPdf(f.url)"
                class="rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition
                       border-slate-200 bg-slate-50 hover:bg-slate-100
                       dark:border-white/10 dark:bg-neutral-950/40 dark:hover:bg-white/5"
                :class="activePdfUrl === f.url ? 'ring-2 ring-slate-300 dark:ring-white/10' : ''"
              >
                <div class="truncate text-slate-900 dark:text-neutral-100">{{ f.label }}</div>
                <div class="text-xs text-slate-500 dark:text-neutral-400 truncate">{{ f.url }}</div>
              </button>
            </div>

            <div class="mt-4 rounded-3xl overflow-hidden border border-slate-200/70 dark:border-white/10 bg-neutral-50 dark:bg-neutral-950/40">
              <div v-if="activePdfUrl" class="w-full">
                <iframe :src="activePdfUrl" class="w-full h-[640px]" />
              </div>
              <div v-else class="p-8 text-center text-slate-500 dark:text-neutral-400">
                No hay PDF seleccionado.
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
