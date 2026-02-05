<!-- resources/js/Pages/Requisiciones/Comprobar.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { useRequisicionComprobar } from './useRequisicionComprobar'

type AnyObj = Record<string, any>;

type Props = {
  requisicion: AnyObj
  comprobantes: {
    id: number
    tipo_doc: string
    subtotal: string
    total: string
    fecha_emision: string | null
    url: string
  }[]
}

const props = defineProps<Props>()

const { form, comprobantes, onFileChange, openFile, upload } = useRequisicionComprobar({
  requisicionId: props.requisicion.id,
  comprobantes: props.comprobantes,
})
</script>

<template>
  <Head :title="`Comprobantes · Req ${props.requisicion.folio}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
          Comprobantes de la requisición {{ props.requisicion.folio }}
        </h2>
        <SecondaryButton @click="$inertia.visit(route('requisiciones.show', props.requisicion.id))" class="rounded-2xl">
          Volver
        </SecondaryButton>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <!-- Formulario de carga -->
      <div class="mb-6 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 mb-4">Subir nuevo comprobante</h3>
        <form @submit.prevent="upload" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo de documento</label>
            <select v-model="form.tipo_doc"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
              <option value="FACTURA">Factura</option>
              <option value="TICKET">Ticket</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Subtotal</label>
            <input v-model="form.subtotal" type="number" step="0.01"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Total</label>
            <input v-model="form.total" type="number" step="0.01"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha de emisión</label>
            <input v-model="form.fecha_emision" type="date"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Archivo (PDF/imagen)</label>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div class="sm:col-span-3 flex justify-end">
            <button type="submit"
              class="rounded-2xl px-4 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600">
              Cargar comprobante
            </button>
          </div>
        </form>
      </div>

      <!-- Lista de comprobantes -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 mb-4">Comprobantes existentes</h3>
        <div v-if="comprobantes && comprobantes.length > 0" class="grid gap-3">
          <div v-for="c in comprobantes" :key="c.id"
            class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4 flex items-center justify-between">
            <div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ c.tipo_doc }}</div>
              <div class="text-xs text-slate-600 dark:text-neutral-400">{{ c.subtotal }} → {{ c.total }}</div>
              <div class="text-xs text-slate-600 dark:text-neutral-400">Fecha: {{ c.fecha_emision ?? '—' }}</div>
            </div>
            <button @click="openFile(c.url)" type="button"
              class="rounded-2xl px-3 py-1.5 text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
              Ver
            </button>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500 dark:text-neutral-400">
          No hay comprobantes registrados.
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
