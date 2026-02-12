<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ArrowLeft, Upload, FileText, CheckCircle2, XCircle } from 'lucide-vue-next'
import type { RequisicionComprobarPageProps } from './Comprobar.types'
import { useRequisicionComprobar } from './useRequisicionComprobar'

declare const route: any

const props = defineProps<RequisicionComprobarPageProps>()

const {
  req, rows, money, fmtLong,
  form, onPickFile, submit,
  estatusLabel, estatusPillClass,
  reviewOpenId, reviewComment, openReject, approve, reject
} = useRequisicionComprobar(props)

const inputBase =
  'w-full rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-900 ' +
  'placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500/40 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500'
</script>

<template>
  <Head title="Comprobar requisición" />

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
          <div class="text-xl font-black text-slate-900 dark:text-neutral-100 truncate">Comprobar requisición</div>
          <div class="text-sm text-slate-500 dark:text-neutral-300 truncate">
            Folio: <span class="font-bold">{{ req?.folio }}</span>
          </div>
        </div>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm p-5">
          <div class="text-xs font-black text-slate-500 dark:text-neutral-300">DATOS DE LA REQUISICIÓN</div>
          <div class="mt-3 space-y-2 text-sm">
            <div><span class="font-black text-slate-700 dark:text-neutral-200">Solicitante:</span> <span class="text-slate-900 dark:text-neutral-100">{{ req?.solicitante_nombre }}</span></div>
            <div><span class="font-black text-slate-700 dark:text-neutral-200">Concepto:</span> <span class="text-slate-900 dark:text-neutral-100">{{ req?.concepto || '—' }}</span></div>
            <div><span class="font-black text-slate-700 dark:text-neutral-200">Cantidad a comprobar:</span> <span class="text-slate-900 dark:text-neutral-100">{{ money(req?.monto_total) }}</span></div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm p-5">
          <div class="text-xs font-black text-slate-500 dark:text-neutral-300">DATOS PARA FACTURACIÓN</div>
          <div class="mt-3 space-y-2 text-sm">
            <div><span class="font-black text-slate-700 dark:text-neutral-200">Razón social:</span> <span class="text-slate-900 dark:text-neutral-100">{{ req?.razon_social || '—' }}</span></div>
            <div><span class="font-black text-slate-700 dark:text-neutral-200">RFC:</span> <span class="text-slate-900 dark:text-neutral-100">{{ req?.rfc || '—' }}</span></div>
            <div><span class="font-black text-slate-700 dark:text-neutral-200">Dirección:</span> <span class="text-slate-900 dark:text-neutral-100">{{ req?.direccion || '—' }}</span></div>
            <div><span class="font-black text-slate-700 dark:text-neutral-200">Correo Electrónico:</span> <span class="text-slate-900 dark:text-neutral-100">{{ req?.correo || '—' }}</span></div>
          </div>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200/70 dark:border-white/10">
          <div class="text-lg font-black text-slate-900 dark:text-neutral-100">Relación de comprobaciones de esta requisición</div>
          <div class="text-sm text-slate-500 dark:text-neutral-300">
            Cada comprobante se revisa de forma independiente. Rechazar uno no tumba la requisición.
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[1100px] w-full">
            <thead class="bg-slate-50/80 dark:bg-neutral-950/40">
              <tr class="text-left text-[12px] font-black text-slate-600 dark:text-neutral-300">
                <th class="px-5 py-3 w-[90px]">Id</th>
                <th class="px-5 py-3">Fecha</th>
                <th class="px-5 py-3">Tipo</th>
                <th class="px-5 py-3">Monto</th>
                <th class="px-5 py-3">Archivo</th>
                <th class="px-5 py-3">Estatus</th>
                <th v-if="props.canReview" class="px-5 py-3 text-right">Acciones</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="c in rows"
                :key="c.id"
                class="border-t border-slate-200/70 dark:border-white/10 hover:bg-slate-50/70 dark:hover:bg-white/5 transition"
              >
                <td class="px-5 py-3 text-sm font-black text-slate-900 dark:text-neutral-100">{{ c.id }}</td>

                <td class="px-5 py-3 text-sm text-slate-800 dark:text-neutral-100">
                  {{ fmtLong(c.fecha_emision) }}
                </td>

                <td class="px-5 py-3 text-sm text-slate-800 dark:text-neutral-100">
                  {{ (c.tipo_doc || '').toLowerCase() }}
                </td>

                <td class="px-5 py-3 text-sm font-black text-slate-900 dark:text-neutral-100">
                  {{ money(c.monto) }}
                </td>

                <td class="px-5 py-3 text-sm">
                  <a
                    v-if="c.archivo?.url"
                    :href="c.archivo.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 font-black text-indigo-700 hover:text-indigo-800 dark:text-indigo-300 dark:hover:text-indigo-200"
                  >
                    <FileText class="h-4 w-4" />
                    {{ c.archivo.label }}
                  </a>
                  <span v-else class="text-slate-500 dark:text-neutral-400">—</span>
                </td>

                <td class="px-5 py-3">
                  <div
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-black"
                    :class="estatusPillClass(c.estatus)"
                  >
                    <span
                      class="h-2 w-2 rounded-full"
                      :class="c.estatus === 'APROBADO'
                        ? 'bg-emerald-500'
                        : c.estatus === 'RECHAZADO'
                          ? 'bg-rose-500'
                          : 'bg-slate-400'"
                    />
                    {{ estatusLabel(c.estatus) }}
                  </div>

                  <div
                    v-if="c.estatus === 'RECHAZADO' && c.comentario_revision"
                    class="mt-2 text-[12px] font-semibold text-rose-700 dark:text-rose-200 max-w-[360px]"
                  >
                    Motivo: {{ c.comentario_revision }}
                  </div>
                </td>

                <td v-if="props.canReview" class="px-5 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-emerald-200 bg-emerald-50
                             hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/15 transition"
                      title="Aprobar"
                      @click="approve(c.id)"
                    >
                      <CheckCircle2 class="h-4 w-4 text-emerald-700 dark:text-emerald-200" />
                    </button>

                    <button
                      type="button"
                      class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-rose-200 bg-rose-50
                             hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:hover:bg-rose-500/15 transition"
                      title="Rechazar"
                      @click="openReject(c.id)"
                    >
                      <XCircle class="h-4 w-4 text-rose-700 dark:text-rose-200" />
                    </button>
                  </div>

                  <div v-if="reviewOpenId === c.id" class="mt-3">
                    <label class="block text-[11px] font-black text-slate-600 dark:text-neutral-300">
                      Motivo del rechazo
                    </label>

                    <textarea
                      v-model="reviewComment"
                      rows="2"
                      class="mt-1 w-full rounded-2xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm font-semibold text-slate-900
                             dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 focus:outline-none"
                      placeholder="Ej: comprobante repetido / no corresponde / monto inconsistente…"
                    />

                    <div class="mt-2 flex justify-end gap-2">
                      <button
                        type="button"
                        class="rounded-2xl px-4 py-2 text-xs font-black border border-slate-200 bg-white hover:bg-slate-50
                               dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition"
                        @click="reviewOpenId = null; reviewComment = ''"
                      >
                        Cancelar
                      </button>

                      <button
                        type="button"
                        class="rounded-2xl px-4 py-2 text-xs font-black bg-rose-600 text-white hover:bg-rose-700 transition"
                        @click="reject(c.id)"
                      >
                        Rechazar comprobante
                      </button>
                    </div>
                  </div>
                </td>
              </tr>

              <tr v-if="rows.length === 0">
                <td :colspan="props.canReview ? 7 : 6" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-neutral-400">
                  Aún no hay comprobantes cargados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="p-5 border-t border-slate-200/70 dark:border-white/10">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-4">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Carga el comprobante</label>
              <input type="file" class="mt-1 block w-full" @change="onPickFile" />
              <div v-if="form.errors.archivo" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.archivo }}</div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Monto por comprobar</label>
              <input v-model="form.monto" type="number" step="0.01" :class="inputBase" class="mt-1" placeholder="0.00" />
              <div v-if="form.errors.monto" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.monto }}</div>
            </div>

            <div class="lg:col-span-3">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Tipo de comprobante</label>
              <select v-model="form.tipo_doc" :class="inputBase" class="mt-1">
                <option v-for="t in props.tipoDocOptions" :key="t.id" :value="t.id">{{ t.nombre }}</option>
              </select>
              <div v-if="form.errors.tipo_doc" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.tipo_doc }}</div>
            </div>

            <div class="lg:col-span-2">
              <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">Fecha del comprobante</label>
              <input v-model="form.fecha_emision" type="date" :class="inputBase" class="mt-1" />
              <div v-if="form.errors.fecha_emision" class="mt-1 text-xs font-bold text-rose-600">{{ form.errors.fecha_emision }}</div>
            </div>

            <div class="lg:col-span-1">
              <button
                type="button"
                @click="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black
                       bg-slate-900 text-white hover:bg-slate-950 dark:bg-white dark:text-slate-900 dark:hover:bg-neutral-200
                       transition active:scale-[0.99]"
              >
                <Upload class="h-4 w-4" />
                Subir
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
