<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

import SearchableSelect from '@/Components/ui/SearchableSelect.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

type CatalogOption = Record<string, any>

type CreateProps = {
  catalogos: {
    corporativos: CatalogOption[]
    sucursales: CatalogOption[]
    empleados: CatalogOption[]
    conceptos: CatalogOption[]
    proveedores: CatalogOption[]
  }
  defaults?: {
    comprador_corp_id?: number | null
    sucursal_id?: number | null
    solicitante_id?: number | null
  }
}

const props = defineProps<CreateProps>()
const page = usePage<any>()
const role = computed(() => String(page.props?.auth?.user?.rol ?? 'COLABORADOR').toUpperCase())

const corporativosActive = computed(() => (props.catalogos?.corporativos ?? []).filter((c) => c.activo !== false))
const sucursalesActive = computed(() => (props.catalogos?.sucursales ?? []).filter((s) => s.activo !== false))
const empleadosActive = computed(() => (props.catalogos?.empleados ?? []).filter((e) => e.activo !== false))
const conceptosActive = computed(() => (props.catalogos?.conceptos ?? []).filter((c) => c.activo !== false))
const proveedoresActive = computed(() => (props.catalogos?.proveedores ?? []).filter((p) => true))

const form = reactive({
  tipo: 'ANTICIPO' as 'ANTICIPO' | 'REEMBOLSO',
  comprador_corp_id: (props.defaults?.comprador_corp_id ?? null) as number | null,
  sucursal_id: (props.defaults?.sucursal_id ?? null) as number | null,
  solicitante_id: (props.defaults?.solicitante_id ?? null) as number | null,
  concepto_id: null as number | null,
  proveedor_id: null as number | null,
  observaciones: '',
})

type Item = {
  cantidad: number
  descripcion: string
  precio_unitario: number
  sucursal_id: number | null
}
const items = ref<Item[]>([
  { cantidad: 1, descripcion: '', precio_unitario: 0, sucursal_id: form.sucursal_id ?? null },
])

const errors = ref<Record<string, string>>({})
const saving = ref(false)

const subtotal = computed(() =>
  items.value.reduce((acc, it) => acc + (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0), 0),
)
const total = computed(() => subtotal.value)

function money(v: any) {
  const n = Number(v ?? 0)
  try {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)
  } catch {
    return String(v ?? '')
  }
}

function addItem() {
  items.value.push({ cantidad: 1, descripcion: '', precio_unitario: 0, sucursal_id: form.sucursal_id ?? null })
}

function removeItem(idx: number) {
  if (items.value.length === 1) return
  items.value.splice(idx, 1)
}

function validate() {
  const e: Record<string, string> = {}
  if (!form.comprador_corp_id) e.comprador_corp_id = 'Selecciona corporativo.'
  if (!form.sucursal_id) e.sucursal_id = 'Selecciona sucursal que absorbe.'
  if (!form.solicitante_id) e.solicitante_id = 'Selecciona solicitante.'
  if (!form.concepto_id) e.concepto_id = 'Selecciona concepto.'
  if (!items.value.some((it) => String(it.descripcion || '').trim().length > 0)) e.items = 'Agrega al menos 1 detalle.'
  items.value.forEach((it, i) => {
    if (!String(it.descripcion || '').trim()) e[`items.${i}.descripcion`] = 'Descripción requerida.'
    if ((Number(it.cantidad) || 0) <= 0) e[`items.${i}.cantidad`] = 'Cantidad inválida.'
    if ((Number(it.precio_unitario) || 0) < 0) e[`items.${i}.precio_unitario`] = 'Precio inválido.'
  })
  errors.value = e
  return Object.keys(e).length === 0
}

function submit() {
  if (!validate()) return

  saving.value = true
  router.post(
    route('requisiciones.store'),
    {
      ...form,
      monto_subtotal: subtotal.value,
      monto_total: total.value,
      detalles: items.value.map((it) => ({
        cantidad: Number(it.cantidad) || 0,
        descripcion: it.descripcion,
        precio_unitario: Number(it.precio_unitario) || 0,
        subtotal: (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0),
        total: (Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0),
        sucursal_id: it.sucursal_id,
      })),
    },
    {
      preserveScroll: true,
      onFinish: () => (saving.value = false),
      onError: (e) => {
        // Si backend manda errors, los mezclamos
        errors.value = { ...errors.value, ...(e as any) }
      },
    },
  )
}

const inputBase =
  'mt-1 w-full rounded-2xl px-4 py-3 text-sm border transition focus:outline-none focus:ring-2 ' +
  'border-slate-200 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-slate-200 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:ring-white/10'
</script>

<template>
  <Head title="Nueva requisición" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-zinc-100">Nueva requisición</h2>
    </template>

    <div class="w-full max-w-full min-w-0 overflow-x-hidden">
      <div class="w-full max-w-full min-w-0 px-3 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Hero -->
        <div
          class="mb-4 rounded-3xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
        >
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
              <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-neutral-100">
                Requisición tipo carrito
              </h1>
              <p class="mt-1 text-sm text-slate-600 dark:text-neutral-300">
                Captura limpia: cabecera + detalles. Después ya se gobierna por flujo (pago, comprobación, etc.).
              </p>
            </div>

            <div class="flex gap-2">
              <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.index'))">
                Volver
              </SecondaryButton>

              <button
                type="button"
                @click="submit"
                :disabled="saving"
                class="rounded-2xl px-5 py-3 text-sm font-extrabold
                       bg-emerald-600 text-white hover:bg-emerald-700
                       dark:bg-emerald-500 dark:hover:bg-emerald-600
                       disabled:opacity-60 disabled:cursor-not-allowed
                       transition active:scale-[0.99]"
              >
                {{ saving ? 'Guardando...' : 'Enviar requisición' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Layout: Cabecera + Resumen -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
          <!-- Cabecera -->
          <div
            class="lg:col-span-8 rounded-3xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
          >
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Tipo</label>
                <select v-model="form.tipo" class="mt-1 w-full rounded-2xl px-4 py-3 text-sm border
                      border-slate-200 bg-white text-slate-900
                      dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100">
                  <option value="ANTICIPO">Anticipo</option>
                  <option value="REEMBOLSO">Reembolso</option>
                </select>
              </div>

              <div>
                <SearchableSelect
                  v-model="form.comprador_corp_id"
                  :options="corporativosActive"
                  label="Corporativo comprador"
                  placeholder="Selecciona..."
                  searchPlaceholder="Buscar corporativo..."
                  :allowNull="true"
                  nullLabel="Selecciona..."
                  rounded="3xl"
                  zIndexClass="z-40"
                />
                <p v-if="errors.comprador_corp_id" class="mt-1 text-xs text-rose-500">{{ errors.comprador_corp_id }}</p>
              </div>

              <div>
                <SearchableSelect
                  v-model="form.sucursal_id"
                  :options="sucursalesActive"
                  label="Sucursal que absorbe"
                  placeholder="Selecciona..."
                  searchPlaceholder="Buscar sucursal..."
                  :allowNull="true"
                  nullLabel="Selecciona..."
                  rounded="3xl"
                  zIndexClass="z-40"
                  labelKey="nombre"
                  secondaryKey="codigo"
                />
                <p v-if="errors.sucursal_id" class="mt-1 text-xs text-rose-500">{{ errors.sucursal_id }}</p>
              </div>

              <div>
                <SearchableSelect
                  v-model="form.solicitante_id"
                  :options="empleadosActive"
                  label="Solicitante"
                  placeholder="Selecciona..."
                  searchPlaceholder="Buscar empleado..."
                  :allowNull="true"
                  nullLabel="Selecciona..."
                  rounded="3xl"
                  zIndexClass="z-40"
                  labelKey="nombre"
                  secondaryKey="puesto"
                />
                <p v-if="errors.solicitante_id" class="mt-1 text-xs text-rose-500">{{ errors.solicitante_id }}</p>
              </div>

              <div class="sm:col-span-2">
                <SearchableSelect
                  v-model="form.concepto_id"
                  :options="conceptosActive"
                  label="Concepto"
                  placeholder="Selecciona..."
                  searchPlaceholder="Buscar concepto..."
                  :allowNull="true"
                  nullLabel="Selecciona..."
                  rounded="3xl"
                  zIndexClass="z-40"
                  labelKey="nombre"
                  secondaryKey="id"
                />
                <p v-if="errors.concepto_id" class="mt-1 text-xs text-rose-500">{{ errors.concepto_id }}</p>
              </div>

              <div class="sm:col-span-2">
                <SearchableSelect
                  v-model="form.proveedor_id"
                  :options="proveedoresActive"
                  label="Proveedor (opcional)"
                  placeholder="Sin proveedor"
                  searchPlaceholder="Buscar proveedor..."
                  :allowNull="true"
                  nullLabel="Sin proveedor"
                  rounded="3xl"
                  zIndexClass="z-40"
                  labelKey="nombre_comercial"
                  secondaryKey="rfc"
                />
              </div>

              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">Observaciones</label>
                <textarea v-model="form.observaciones" rows="3" :class="inputBase" placeholder="Notas para contabilidad / contexto..."></textarea>
              </div>
            </div>
          </div>

          <!-- Resumen -->
          <div
            class="lg:col-span-4 rounded-3xl border border-slate-200/70 dark:border-white/10
                   bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
          >
            <div class="flex items-start justify-between">
              <div>
                <div class="text-sm font-extrabold text-slate-900 dark:text-neutral-100">Resumen</div>
                <div class="mt-1 text-xs text-slate-600 dark:text-neutral-300">
                  Rol actual: <span class="font-semibold">{{ role }}</span>
                </div>
              </div>
              <span class="text-[11px] font-bold px-2 py-1 rounded-full border
                           border-slate-200 bg-slate-50 text-slate-700
                           dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-200">
                Draft inteligente
              </span>
            </div>

            <div class="mt-4 grid gap-3">
              <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
                <div class="text-xs text-slate-500 dark:text-neutral-400">Subtotal</div>
                <div class="text-lg font-extrabold text-slate-900 dark:text-neutral-100">{{ money(subtotal) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50 dark:bg-neutral-950/40 p-4">
                <div class="text-xs text-slate-500 dark:text-neutral-400">Total</div>
                <div class="text-lg font-extrabold text-slate-900 dark:text-neutral-100">{{ money(total) }}</div>
              </div>

              <p v-if="errors.items" class="text-xs text-rose-500">{{ errors.items }}</p>

              <button
                type="button"
                @click="addItem"
                class="rounded-2xl px-4 py-3 text-sm font-extrabold
                       bg-slate-900 text-white hover:bg-slate-800
                       dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white
                       transition active:scale-[0.99]"
              >
                + Agregar detalle
              </button>
            </div>
          </div>
        </div>

        <!-- Detalles -->
        <div
          class="mt-4 rounded-3xl border border-slate-200/70 dark:border-white/10
                 bg-white dark:bg-neutral-900 shadow-sm p-5 sm:p-6"
        >
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-base font-extrabold text-slate-900 dark:text-neutral-100">Detalles</h3>
              <p class="text-sm text-slate-600 dark:text-neutral-300">
                Cada renglón es una partida. Esto alimenta el PDF.
              </p>
            </div>
          </div>

          <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
              <thead class="bg-slate-50 dark:bg-neutral-950/60">
                <tr class="text-left text-slate-600 dark:text-neutral-300">
                  <th class="px-4 py-3 font-semibold w-[110px]">Cantidad</th>
                  <th class="px-4 py-3 font-semibold">Descripción</th>
                  <th class="px-4 py-3 font-semibold w-[220px]">Sucursal (opcional)</th>
                  <th class="px-4 py-3 font-semibold w-[160px] text-right">P. Unit</th>
                  <th class="px-4 py-3 font-semibold w-[160px] text-right">Importe</th>
                  <th class="px-4 py-3 font-semibold w-[120px] text-right">Acción</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(it, idx) in items"
                  :key="idx"
                  class="border-t border-slate-200/70 dark:border-white/10"
                >
                  <td class="px-4 py-3 align-top">
                    <input v-model.number="it.cantidad" type="number" step="0.01" min="0"
                           class="w-full rounded-2xl px-3 py-2 border border-slate-200 bg-white
                                  dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                    <p v-if="errors[`items.${idx}.cantidad`]" class="mt-1 text-xs text-rose-500">
                      {{ errors[`items.${idx}.cantidad`] }}
                    </p>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <input v-model="it.descripcion" type="text" placeholder="Ej: Licencia, cafetería, envío, etc."
                           class="w-full rounded-2xl px-3 py-2 border border-slate-200 bg-white
                                  dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                    <p v-if="errors[`items.${idx}.descripcion`]" class="mt-1 text-xs text-rose-500">
                      {{ errors[`items.${idx}.descripcion`] }}
                    </p>
                  </td>

                  <td class="px-4 py-3 align-top">
                    <SearchableSelect
                      v-model="it.sucursal_id"
                      :options="sucursalesActive"
                      placeholder="Usar sucursal general"
                      searchPlaceholder="Buscar sucursal..."
                      :allowNull="true"
                      nullLabel="Usar sucursal general"
                      rounded="2xl"
                      zIndexClass="z-30"
                      labelKey="nombre"
                      secondaryKey="codigo"
                    />
                  </td>

                  <td class="px-4 py-3 align-top text-right">
                    <input v-model.number="it.precio_unitario" type="number" step="0.01" min="0"
                           class="w-full rounded-2xl px-3 py-2 border border-slate-200 bg-white text-right
                                  dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100" />
                    <p v-if="errors[`items.${idx}.precio_unitario`]" class="mt-1 text-xs text-rose-500 text-left">
                      {{ errors[`items.${idx}.precio_unitario`] }}
                    </p>
                  </td>

                  <td class="px-4 py-3 align-top text-right font-extrabold text-slate-900 dark:text-neutral-100">
                    {{ money((Number(it.cantidad) || 0) * (Number(it.precio_unitario) || 0)) }}
                  </td>

                  <td class="px-4 py-3 align-top text-right">
                    <button
                      type="button"
                      @click="removeItem(idx)"
                      class="rounded-2xl px-3 py-2 text-xs font-bold
                             bg-white text-rose-700 border border-rose-200 hover:bg-rose-50
                             dark:bg-neutral-900 dark:text-rose-300 dark:border-rose-500/20 dark:hover:bg-rose-500/10
                             transition active:scale-[0.99]"
                    >
                      Quitar
                    </button>
                  </td>
                </tr>

                <tr v-if="!items.length">
                  <td colspan="6" class="px-4 py-10 text-center text-slate-500 dark:text-neutral-400">
                    Sin detalles.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-5 flex flex-col sm:flex-row gap-2 sm:justify-end">
            <SecondaryButton class="rounded-2xl" @click="router.visit(route('requisiciones.index'))">Cancelar</SecondaryButton>
            <button
              type="button"
              @click="submit"
              :disabled="saving"
              class="rounded-2xl px-6 py-3 text-sm font-extrabold
                     bg-emerald-600 text-white hover:bg-emerald-700
                     dark:bg-emerald-500 dark:hover:bg-emerald-600
                     disabled:opacity-60 disabled:cursor-not-allowed
                     transition active:scale-[0.99]"
            >
              {{ saving ? 'Guardando...' : 'Enviar requisición' }}
            </button>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
:global(html.dark select option) {
  background: #0a0a0a;
  color: #f5f5f5;
}
</style>
