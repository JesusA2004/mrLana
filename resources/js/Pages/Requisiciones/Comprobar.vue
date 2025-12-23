<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

type AnyObj = Record<string, any>

type ComprobarProps = {
  requisicion: AnyObj
  comprobantes: AnyObj[]
  pdf?: { requisicion_url?: string | null }
}

const props = defineProps<ComprobarProps>()

const page = usePage<any>()
const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
const canAccess = computed(() => ['ADMIN', 'CONTADOR', 'COLABORADOR'].includes(role.value))

const saving = ref(false)
const error = ref<string | null>(null)

const form = reactive({
  tipo_doc: 'FACTURA' as 'FACTURA' | 'TICKET' | 'NOTA' | 'OTRO',
  folio: '',
  subtotal: 0,
  total: 0,
  archivo: null as File | null,
})

const previewUrl = ref<string | null>(props.pdf?.requisicion_url ?? null)

function money(v: any) {
  const n = Number(v ?? 0)
  try {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
  } catch {
    return String(v ?? '')
  }
}

function onFile(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0] ?? null
  form.archivo = f
}

function submit() {
  if (!canAccess.value) return
  error.value = null
  if (!form.archivo) {
    error.value = 'Adjunta un PDF para el comprobante.'
    return
  }

  saving.value = true
  router.post(
    route('requisiciones.comprobantes.store', props.requisicion.id),
    {
      tipo_doc: form.tipo_doc,
      folio: form.folio || null,
      subtotal: form.subtotal,
      total: form.total,
      archivo: form.archivo,
    },
    {
      forceFormData: true,
      preserveScroll: true,
      onFinish: () => (saving.value = false),
      onError: () => {
        error.value = 'No se pudo subir el comprobante.'
      },
    },
  )
}

function printReq() {
  const url = route('requisiciones.print', props.requisicion.id)
  const w = window.open(url, '_blank', 'noopener,noreferrer')
  w?.focus()
}

function openNewTab() {
  if (!previewUrl.value) return
  window.open(previewUrl.value, '_blank', 'noopener,noreferrer')
}

function setPreview(url: string | null) {
  previewUrl.value = url
}
</script>

<template>
  <Head :title="`Comprobar ${props.requisicion?.folio ?? props.requisicion?.id}`" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Subir comprobaciones</h2>
    </template>

    <div class="w-full max-w-full min-w-0 overflow-x-hidden">
      <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div
          v-if="!canAccess"
          class="rounded-3xl border border-rose-200 dark:border-rose-500/20 bg-rose-50 dark:bg-rose-500/10 p-6"
        >
          <div class="font-extrabold text-rose-700 dark:text-rose-200">Acceso restringido</div>
          <div class="mt-1 text-sm text-rose-700/80 dark:text-rose-200/80">
            Esta pantalla requiere rol ADMIN, CONTADOR o COLABORADOR.
          </div>
        </div>

        <div
          v-else
          class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
        >
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
              <div class="text-lg font-extrabold text-slate-900 dark:text-neutral-100">
                {{ props.requisicion?.folio ?? `REQ-${props.requisicion?.id}` }}
              </div>
              <div class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                Total requisición: <span class="font-extrabold">{{ money(props.requisicion?.monto_total) }}</span>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.show', props.requisicion.id))">
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
                type="button"
                @click="submit"
                :disabled="saving"
                class="rounded-2xl px-5 py-3 text-sm font-extrabold
                       bg-indigo-600 text-white hover:bg-indigo-700
                       disabled:opacity-60 disabled:cursor-not-allowed
                       transition active:scale-[0.99]"
              >
                {{ saving ? 'Subiendo...' : 'Subir comprobante' }}
              </button>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Formulario -->
            <div class="lg:col-span-5 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-5">
              <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Nuevo comprobante</div>

              <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo</label>
                  <select v-model="form.tipo_doc"
                          class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
                    <option value="FACTURA">Factura</option>
                    <option value="TICKET">Ticket</option>
                    <option value="NOTA">Nota</option>
                    <option value="OTRO">Otro</option>
                  </select>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Folio (opcional)</label>
                  <input v-model="form.folio" type="text"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Subtotal</label>
                  <input v-model.number="form.subtotal" type="number" step="0.01"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Total</label>
                  <input v-model.number="form.total" type="number" step="0.01"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Archivo (PDF)</label>
                  <input type="file" accept="application/pdf" @change="onFile"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <p v-if="error" class="sm:col-span-2 text-sm text-rose-600 dark:text-rose-300">{{ error }}</p>
              </div>

              <!-- Lista -->
              <div class="mt-6">
                <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Comprobantes cargados</div>
                <div class="mt-3 space-y-2">
                  <button
                    v-if="props.pdf?.requisicion_url"
                    type="button"
                    @click="setPreview(props.pdf?.requisicion_url ?? null)"
                    class="w-full rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition
                           border-slate-200 bg-white hover:bg-slate-50
                           dark:border-white/10 dark:bg-neutral-900 dark:hover:bg-white/5"
                  >
                    Requisición (PDF)
                  </button>

                  <button
                    v-for="c in props.comprobantes"
                    :key="c.id"
                    type="button"
                    @click="setPreview(c.url ?? null)"
                    class="w-full rounded-2xl border px-4 py-3 text-left text-sm transition
                           border-slate-200 bg-white hover:bg-slate-50
                           dark:border-white/10 dark:bg-neutral-900 dark:hover:bg-white/5"
                  >
                    <div class="font-extrabold text-slate-900 dark:text-neutral-100">
                      {{ c.tipo_doc }} · {{ money(c.total) }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-neutral-400">
                      {{ String(c.fecha_carga ?? c.created_at ?? '').slice(0, 10) }} · Folio: {{ c.folio ?? '—' }}
                    </div>
                  </button>

                  <div v-if="!props.comprobantes?.length" class="text-sm text-slate-500 dark:text-neutral-400">
                    Aún no hay comprobantes.
                  </div>
                </div>
              </div>
            </div>

            <!-- Preview -->
            <div class="lg:col-span-7 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 p-5">
              <div class="flex items-center justify-between gap-2">
                <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Previsualizador</div>
                <button
                  type="button"
                  @click="openNewTab"
                  :disabled="!previewUrl"
                  class="rounded-2xl px-4 py-2 text-xs font-extrabold
                         bg-slate-900 text-white hover:bg-slate-800
                         dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                         disabled:opacity-50 disabled:cursor-not-allowed
                         transition"
                >
                  Abrir
                </button>
              </div>

              <div class="mt-3 rounded-3xl overflow-hidden border border-slate-200/70 dark:border-white/10 bg-neutral-50 dark:bg-neutral-950/40">
                <div v-if="previewUrl" class="w-full">
                  <iframe :src="previewUrl" class="w-full h-[720px]" />
                </div>
                <div v-else class="p-10 text-center text-slate-500 dark:text-neutral-400">
                  Selecciona un PDF para previsualizar.
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
