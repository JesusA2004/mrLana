<!-- resources/js/Pages/Requisiciones/Ajustes.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { useRequisicionAjustes } from './useRequisicionAjustes'

type Props = {
  requisicion: any
  ajustes: {
    id: number
    tipo: string
    monto: string
    descripcion: string
    fecha: string
  }[]
}

const props = defineProps<Props>()

const { form, ajustes, save } = useRequisicionAjustes({
  requisicionId: props.requisicion.id,
  ajustes: props.ajustes,
})
</script>

<template>
  <Head :title="`Ajustes · Req ${props.requisicion.folio}`" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">
          Ajustes de la requisición {{ props.requisicion.folio }}
        </h2>
        <SecondaryButton @click="$inertia.visit(route('requisiciones.show', props.requisicion.id))" class="rounded-2xl">
          Volver
        </SecondaryButton>
      </div>
    </template>

    <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
      <!-- Formulario de ajuste -->
      <div class="mb-6 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 mb-4">Registrar ajuste</h3>
        <form @submit.prevent="save" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo de ajuste</label>
            <select v-model="form.tipo"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
              <option value="DEVOLUCION">Devolución</option>
              <option value="FALTANTE">Faltante</option>
              <option value="INCREMENTO">Incremento</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Monto</label>
            <input v-model="form.monto" type="number" step="0.01"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Fecha</label>
            <input v-model="form.fecha" type="date"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
          </div>

          <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Descripción</label>
            <textarea v-model="form.descripcion" rows="2"
              class="mt-1 w-full rounded-2xl px-3 py-2 text-sm border border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100"></textarea>
          </div>

          <div class="sm:col-span-3 flex justify-end">
            <button type="submit"
              class="rounded-2xl px-4 py-2 text-sm font-semibold bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600">
              Guardar ajuste
            </button>
          </div>
        </form>
      </div>

      <!-- Lista de ajustes -->
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6">
        <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100 mb-4">Ajustes registrados</h3>
        <div v-if="ajustes && ajustes.length > 0" class="grid gap-3">
          <div v-for="a in ajustes" :key="a.id"
            class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
            <div class="font-semibold text-slate-900 dark:text-neutral-100">{{ a.tipo }}</div>
            <div class="text-xs text-slate-600 dark:text-neutral-400">{{ a.fecha }} · {{ a.monto }}</div>
            <div class="text-xs text-slate-600 dark:text-neutral-400">{{ a.descripcion }}</div>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500 dark:text-neutral-400">
          No hay ajustes registrados.
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
