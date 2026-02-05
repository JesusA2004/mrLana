<!-- resources/js/Pages/Requisiciones/Pagar.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { useRequisicionPagar } from './useRequisicionPagar'

type Props = {
  requisicion: any
  pagos: {
    id: number
    fecha_pago: string
    tipo_pago: string
    monto: string
    url: string
  }[]
}

const props = defineProps<Props>()

const { form, pagos, onFileChange, openFile, upload } = useRequisicionPagar({
  requisicionId: props.requisicion.id,
  pagos: props.pagos,
})
</script>

<template>
  <Head :title="`Pagar · Req ${props.requisicion.folio}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
          Registrar pago (Req {{ props.requisicion.folio }})
        </h2>
        <SecondaryButton @click="$inertia.visit(route('requisiciones.show', props.requisicion.id))" class="rounded-2xl">
          Volver
        </SecondaryButton>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <!-- Formulario de pago -->
      <div class="mb-6 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 mb-4">Registrar nuevo pago</h3>
        <form @submit.prevent="upload" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha de pago</label>
            <input v-model="form.fecha_pago" type="date"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo de pago</label>
            <select v-model="form.tipo_pago"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
              <option value="TRANSFERENCIA">Transferencia</option>
              <option value="EFECTIVO">Efectivo</option>
              <option value="TARJETA">Tarjeta</option>
              <option value="DEPOSITO">Depósito</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Monto</label>
            <input v-model="form.monto" type="number" step="0.01"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Comprobante de pago (PDF/imagen)</label>
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="onFileChange"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div class="sm:col-span-3 flex justify-end">
            <button type="submit"
              class="rounded-2xl px-4 py-2 text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 dark:bg-sky-500 dark:hover:bg-sky-600">
              Registrar pago
            </button>
          </div>
        </form>
      </div>

      <!-- Lista de pagos -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 mb-4">Pagos registrados</h3>
        <div v-if="pagos && pagos.length > 0" class="grid gap-3">
          <div v-for="p in pagos" :key="p.id"
            class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4 flex items-center justify-between">
            <div>
              <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ p.tipo_pago }}</div>
              <div class="text-xs text-slate-600 dark:text-neutral-400">{{ p.fecha_pago }} · {{ p.monto }}</div>
            </div>
            <button @click="openFile(p.url)" type="button"
              class="rounded-2xl px-3 py-1.5 text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
              Ver comprobante
            </button>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500 dark:text-neutral-400">
          No hay pagos registrados.
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
