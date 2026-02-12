<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ArrowLeft, Upload, FileText } from 'lucide-vue-next'
import type { RequisicionPagoPageProps } from './Pagar.types'
import { useRequisicionPago } from './useRequisicionPago'

const props = defineProps<RequisicionPagoPageProps>()

const { req, pagos, money, fmtLong, form, submitting, onPickFile, submit } = useRequisicionPago(props)

const inputBase =
  'w-full rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-900 ' +
  'placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500/40 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500'

const tot = computed(() => props.totales)
</script>

<template>
  <Head title="Pagar requisición" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <Link
          :href="route('requisiciones.index')"
          class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50
                 dark:border-white/10 dark:bg-neutral-900 dark:hover:bg-white/10 transition"
          title="Volver"
        >
          <ArrowLeft class="h-5 w-5" />
        </Link>

        <div class="min-w-0">
          <div class="text-xl font-black text-slate-900 dark:text-neutral-100 truncate">Pagar requisición</div>
          <div class="text-sm text-slate-500 dark:text-neutral-300 truncate">
            Folio: <span class="font-bold">{{ req?.folio }}</span>
          </div>
        </div>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4">
      <!-- Resumen -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm p-5">
          <div class="text-xs font-black text-slate-500 dark:text-neutral-300">DATOS DE LA REQUISICIÓN</div>
          <div class="mt-3 space-y-2 text-sm">
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Solicitante:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ req?.solicitante_nombre }}</span>
            </div>
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Concepto:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ req?.concepto || '—' }}</span>
            </div>
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Cantidad a pagar:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ money(req?.monto_total) }}</span>
            </div>

            <div class="pt-2 grid grid-cols-2 gap-3">
              <div class="rounded-2xl border border-slate-200/60 dark:border-white/10 bg-slate-50/70 dark:bg-neutral-950/40 p-3">
                <div class="text-[11px] font-black text-slate-500 dark:text-neutral-400">Pagado</div>
                <div class="text-sm font-black text-slate-900 dark:text-neutral-100">{{ money(tot.pagado) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-200/60 dark:border-white/10 bg-slate-50/70 dark:bg-neutral-950/40 p-3">
                <div class="text-[11px] font-black text-slate-500 dark:text-neutral-400">Pendiente</div>
                <div class="text-sm font-black text-slate-900 dark:text-neutral-100">{{ money(tot.pendiente) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm p-5">
          <div class="text-xs font-black text-slate-500 dark:text-neutral-300">DATOS DEL BENEFICIARIO</div>
          <div class="mt-3 space-y-2 text-sm">
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Beneficiario:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ req?.beneficiario?.nombre || '—' }}</span>
            </div>
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Cuenta:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ req?.beneficiario?.cuenta || '—' }}</span>
            </div>
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Clabe:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ req?.beneficiario?.clabe || '—' }}</span>
            </div>
            <div>
              <span class="font-black text-slate-700 dark:text-neutral-200">Banco:</span>
              <span class="text-slate-900 dark:text-neutral-100"> {{ req?.beneficiario?.banco || '—' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Relación de pagos -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200/70 dark:border-white/10">
          <div class="text-lg font-black text-slate-900 dark:text-neutral-100">Relación de pagos de esta requisición</div>
          <div class="text-sm text-slate-500 dark:text-neutral-300">
            Últimos pagos registrados. Adjunta comprobante por movimiento.
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[900px] w-full">
            <thead class="bg-slate-50/80 dark:bg-neutral-950/40">
              <tr class="text-left text-[12px] font-black text-slate-600 dark:text-neutral-300">
                <th class="px-5 py-3 w-[90px]">Id</th>
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Monto</th>
                <th class="px-5 py-3">Archivo</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="p in pagos"
                :key="p.id"
                class="border-t border-slate-200/70 dark:border-white/10 hover:bg-slate-50/70 dark:hover:bg-white/5 transition"
              >
                <td class="px-5 py-3 text-sm font-black text-slate-900 dark:text-neutral-100">{{ p.id }}</td>
                <td class="px-5 py-3 text-sm text-slate-800 dark:text-neutral-100">{{ fmtLong(p.fecha_pago) }}</td>
                <td class="px-5 py-3 text-sm text-slate-800 dark:text-neutral-100">{{ (p.tipo_pago || '').toLowerCase() }}</td>
                <td class="px-5 py-3 text-sm font-black text-slate-900 dark:text-neutral-100">{{ money(p.monto) }}</td>
                <td class="px-5 py-3 text-sm">
                  <a
                    v-if="p.archivo?.url"
                    :href="p.archivo.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 font-black text-emerald-700 hover:text-emerald-800 dark:text-emerald-300 dark:hover:text-emerald-200"
                  >
                    <FileText class="h-4 w-4" />
                    {{ p.archivo.label }}
                  </a>
                  <span v-else class="text-slate-500 dark:text-neutral-400">—</span>
                </td>
              </tr>

              <tr v-if="pagos.length === 0">
                <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-neutral-400">
                  Aún no hay pagos registrados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Form carga -->
        <div class="p-5 border-t border-slate-200/70 dark:border-white/10">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-4">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Comprobante de pago</label>
              <input type="file" class="mt-1 block w-full" @change="onPickFile" />
              <div v-if="form.errors.archivo" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.archivo }}</div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Fecha de pago</label>
              <input v-model="form.fecha_pago" type="date" :class="inputBase" class="mt-1" />
              <div v-if="form.errors.fecha_pago" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.fecha_pago }}</div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Monto pagado</label>
              <input v-model="form.monto" type="number" step="0.01" :class="inputBase" class="mt-1" placeholder="0.00" />
              <div v-if="form.errors.monto" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.monto }}</div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Tipo de pago</label>
              <select v-model="form.tipo_pago" :class="inputBase" class="mt-1">
                <option v-for="t in props.tipoPagoOptions" :key="t.id" :value="t.id">{{ t.nombre }}</option>
              </select>
              <div v-if="form.errors.tipo_pago" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.tipo_pago }}</div>
            </div>

            <div class="lg:col-span-2">
              <button
                type="button"
                :disabled="submitting"
                @click="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black
                       bg-slate-900 text-white hover:bg-slate-950 dark:bg-white dark:text-slate-900 dark:hover:bg-neutral-200
                       transition active:scale-[0.99] disabled:opacity-60"
              >
                <Upload class="h-4 w-4" />
                Pagar Requisición
              </button>
            </div>
          </div>

          <div class="mt-3 text-[12px] text-slate-500 dark:text-neutral-400">
            Tip: si el comprobante es PDF, mejor. Si es screenshot, súbelo en PNG/JPG sin perder resolución.
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
