<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

type PayProps = {
  requisicion: Record<string, any>
  pagos?: Array<Record<string, any>>
  // url del comprobante de pago si existe
  pdf?: { pago_url?: string | null }
}

const props = defineProps<PayProps>()

const page = usePage<any>()
const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())
const canAccess = computed(() => role.value === 'CONTADOR')

const form = reactive({
  fecha_pago: '',
  tipo_pago: 'TRANSFERENCIA' as 'TRANSFERENCIA' | 'EFECTIVO' | 'OTRO',
  monto_pagado: Number(props.requisicion?.monto_total ?? 0),
  archivo: null as File | null,
})

const saving = ref(false)
const error = ref<string | null>(null)

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
  if (!form.fecha_pago) {
    error.value = 'Selecciona fecha de pago.'
    return
  }
  if (!form.archivo) {
    error.value = 'Adjunta el comprobante de pago.'
    return
  }

  saving.value = true
  router.post(
    route('requisiciones.pagar.store', props.requisicion.id),
    {
      fecha_pago: form.fecha_pago,
      tipo_pago: form.tipo_pago,
      monto_pagado: form.monto_pagado,
      archivo: form.archivo,
    },
    {
      forceFormData: true,
      onFinish: () => (saving.value = false),
      onError: (e) => {
        error.value = (e as any)?.message ?? 'No se pudo registrar el pago.'
      },
    },
  )
}
</script>

<template>
  <Head :title="`Pagar requisición ${props.requisicion?.folio ?? props.requisicion?.id}`" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Pagar requisición</h2>
    </template>

    <div class="w-full max-w-full min-w-0 overflow-x-hidden">
      <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div
          v-if="!canAccess"
          class="rounded-3xl border border-rose-200 dark:border-rose-500/20 bg-rose-50 dark:bg-rose-500/10 p-6"
        >
          <div class="font-extrabold text-rose-700 dark:text-rose-200">Acceso restringido</div>
          <div class="mt-1 text-sm text-rose-700/80 dark:text-rose-200/80">Solo CONTADOR puede registrar pagos.</div>
        </div>

        <div
          v-else
          class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
        >
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
            <div class="min-w-0">
              <div class="text-lg font-extrabold text-slate-900 dark:text-neutral-100">
                {{ props.requisicion?.folio ?? `REQ-${props.requisicion?.id}` }}
              </div>
              <div class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                Monto a pagar: <span class="font-extrabold">{{ money(props.requisicion?.monto_total) }}</span>
              </div>
            </div>

            <div class="flex gap-2">
              <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.show', props.requisicion.id))">
                Volver
              </SecondaryButton>
              <button
                type="button"
                @click="submit"
                :disabled="saving"
                class="rounded-2xl px-5 py-3 text-sm font-extrabold
                       bg-sky-600 text-white hover:bg-sky-700
                       disabled:opacity-60 disabled:cursor-not-allowed
                       transition active:scale-[0.99]"
              >
                {{ saving ? 'Registrando...' : 'Registrar pago' }}
              </button>
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Datos -->
            <div class="lg:col-span-6 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-5">
              <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Datos del beneficiario</div>
              <div class="mt-3 text-sm text-slate-800 dark:text-neutral-100 space-y-1">
                <div><span class="font-semibold">Beneficiario:</span> {{ props.requisicion?.proveedor?.beneficiario ?? '—' }}</div>
                <div><span class="font-semibold">Banco:</span> {{ props.requisicion?.proveedor?.banco ?? '—' }}</div>
                <div><span class="font-semibold">Cuenta:</span> {{ props.requisicion?.proveedor?.cuenta ?? '—' }}</div>
                <div><span class="font-semibold">CLABE:</span> {{ props.requisicion?.proveedor?.clabe ?? '—' }}</div>
              </div>
            </div>

            <!-- Form -->
            <div class="lg:col-span-6 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 p-5">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha de pago</label>
                  <input v-model="form.fecha_pago" type="date"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo de pago</label>
                  <select v-model="form.tipo_pago"
                          class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="OTRO">Otro</option>
                  </select>
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Monto pagado</label>
                  <input v-model.number="form.monto_pagado" type="number" step="0.01"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Comprobante de pago (PDF)</label>
                  <input type="file" accept="application/pdf" @change="onFile"
                         class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border border-slate-200 bg-white
                                dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                </div>

                <p v-if="error" class="sm:col-span-2 text-sm text-rose-600 dark:text-rose-300">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Historial pagos -->
          <div class="mt-6">
            <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Historial de pagos</div>
            <div class="mt-3 overflow-x-auto">
              <table class="w-full min-w-[700px] text-sm">
                <thead class="bg-slate-50 dark:bg-neutral-950/60">
                  <tr class="text-left text-slate-600 dark:text-neutral-300">
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Tipo</th>
                    <th class="px-4 py-3 font-semibold text-right">Monto</th>
                    <th class="px-4 py-3 font-semibold">Archivo</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in (props.pagos ?? [])" :key="p.id" class="border-t border-slate-200/70 dark:border-white/10">
                    <td class="px-4 py-3">{{ String(p.fecha ?? p.created_at ?? '').slice(0, 10) }}</td>
                    <td class="px-4 py-3">{{ p.tipo ?? '—' }}</td>
                    <td class="px-4 py-3 text-right font-extrabold text-slate-900 dark:text-neutral-100">{{ money(p.monto) }}</td>
                    <td class="px-4 py-3">
                      <a v-if="p.url" :href="p.url" target="_blank" class="text-sky-600 dark:text-sky-300 font-semibold hover:underline">
                        Ver PDF
                      </a>
                      <span v-else class="text-slate-500 dark:text-neutral-400">—</span>
                    </td>
                  </tr>
                  <tr v-if="!(props.pagos ?? []).length">
                    <td colspan="4" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">Sin pagos registrados.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
