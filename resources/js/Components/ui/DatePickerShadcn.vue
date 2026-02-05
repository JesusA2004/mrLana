<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { format } from 'date-fns'
import { es } from 'date-fns/locale'
import { Calendar as CalendarIcon } from 'lucide-vue-next'

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { Calendar } from '@/Components/ui/calendar'

import { parseDate, type DateValue } from '@internationalized/date'

type Model = string | null | undefined // guardamos YYYY-MM-DD (cero bugs de timezone)

const props = defineProps<{
  modelValue: Model
  label?: string
  placeholder?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: Model): void
}>()

const open = ref(false)
const selected = ref<DateValue | undefined>(undefined)

const toDateValue = (v: Model): DateValue | undefined => {
  if (!v) return undefined
  // Espera 'YYYY-MM-DD'
  try {
    return parseDate(v)
  } catch {
    return undefined
  }
}

watch(
  () => props.modelValue,
  (v) => (selected.value = toDateValue(v)),
  { immediate: true }
)

// display dd/MM/yyyy sin desfase (nunca uses new Date('YYYY-MM-DD') aquí)
const display = computed(() => {
  const d = selected.value
  if (!d) return ''
  const js = new Date(d.year, d.month - 1, d.day) // local date, sin shift
  return format(js, 'dd/MM/yyyy', { locale: es })
})

const onPick = (v: DateValue | undefined) => {
  if (!v) return
  selected.value = v
  emit('update:modelValue', v.toString()) // 'YYYY-MM-DD'
  open.value = false // cierra al seleccionar
}
</script>

<template>
  <div class="w-full">
    <label v-if="label" class="block text-xs font-semibold text-slate-600 dark:text-neutral-300">
      {{ label }}
    </label>

    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <button
          type="button"
          :disabled="disabled"
          class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm
                 text-slate-900 shadow-sm transition
                 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/25
                 dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                 disabled:opacity-60 disabled:cursor-not-allowed"
        >
          <div class="flex items-center justify-between gap-2">
            <span :class="display ? 'font-semibold' : 'text-slate-400 dark:text-neutral-500'">
              {{ display || (placeholder ?? 'Selecciona fecha') }}
            </span>
            <CalendarIcon class="h-4 w-4 opacity-70" />
          </div>
        </button>
      </PopoverTrigger>

      <PopoverContent
        align="start"
        class="w-auto p-0 overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl
               dark:border-white/10 dark:bg-neutral-950
               data-[state=open]:animate-in data-[state=closed]:animate-out
               data-[state=open]:fade-in-0 data-[state=closed]:fade-out-0
               data-[state=open]:zoom-in-95 data-[state=closed]:zoom-out-95
               duration-200"
      >
        <!-- Calendar shadcn real: DateValue -->
        <Calendar
          v-model="selected"
          mode="single"
          locale="es"
          class="p-3"
          @update:modelValue="onPick"
        />
      </PopoverContent>
    </Popover>
  </div>
</template>
