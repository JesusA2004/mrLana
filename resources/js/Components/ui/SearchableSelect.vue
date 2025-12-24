<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

type OptionLike = Record<string, any>

const props = defineProps<{
  modelValue: string | number | null
  options: OptionLike[]

  id?: string
  label?: string

  placeholder?: string
  searchPlaceholder?: string
  error?: string | null

  labelKey?: string
  secondaryKey?: string
  valueKey?: string

  allowNull?: boolean
  nullable?: boolean
  nullLabel?: string

  buttonClass?: string
  panelClass?: string
  rounded?: 'xl' | '2xl' | '3xl'
  zIndexClass?: string
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: string | number | null): void
  (e: 'change', v: string | number | null): void
}>()

const open = ref(false)
const query = ref('')
const rootRef = ref<HTMLElement | null>(null)
const searchRef = ref<HTMLInputElement | null>(null)
const buttonRef = ref<HTMLButtonElement | null>(null)

const uid = `ss-${Math.random().toString(36).slice(2, 10)}`
const buttonId = computed(() => props.id ?? uid)

const labelKey = computed(() => props.labelKey ?? 'nombre')
const secondaryKey = computed(() => props.secondaryKey ?? 'codigo')
const valueKey = computed(() => props.valueKey ?? 'id')

const allowNullEff = computed(() => {
  if (typeof props.allowNull === 'boolean') return props.allowNull
  if (typeof props.nullable === 'boolean') return props.nullable
  return false
})

const selected = computed<OptionLike | null>(() => {
  const v = props.modelValue
  if (v === null || v === '' || v === undefined) return null
  const idNum = Number(v)
  return props.options.find((o) => Number(o?.[valueKey.value]) === idNum) ?? null
})

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options

  return props.options.filter((o) => {
    const a = String(o?.[labelKey.value] ?? '').toLowerCase()
    const b = String(o?.[secondaryKey.value] ?? '').toLowerCase()
    return a.includes(q) || b.includes(q)
  })
})

function setOpen(v: boolean) {
  open.value = v
  if (v) {
    nextTick(() => searchRef.value?.focus())
  } else {
    query.value = ''
  }
}

function toggle() {
  setOpen(!open.value)
}

function openFromLabel() {
  setOpen(true)
  nextTick(() => buttonRef.value?.focus())
}

function pick(v: string | number | null) {
  emit('update:modelValue', v)
  emit('change', v)
  setOpen(false)
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') setOpen(false)
}

function onClickOutside(e: MouseEvent) {
  if (!open.value) return
  const el = rootRef.value
  if (!el) return
  if (!el.contains(e.target as Node)) setOpen(false)
}

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside)
  document.removeEventListener('keydown', onKeydown)
})

watch(
  () => props.options,
  () => {
    if (open.value) query.value = query.value
  }
)

const roundedCls = computed(() => {
  if (props.rounded === 'xl') return 'rounded-xl'
  if (props.rounded === '2xl') return 'rounded-2xl'
  return 'rounded-2xl'
})

const baseButton =
  'w-full min-w-0 px-4 py-3 text-sm text-left border border-slate-200 bg-white text-slate-900 ' +
  'hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 ' +
  'dark:border-white/10 dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5 dark:focus:ring-white/10 transition'

const basePanel =
  'absolute mt-2 w-full overflow-hidden border border-slate-200/70 bg-white shadow-2xl ' +
  'dark:border-white/10 dark:bg-neutral-950'

const zCls = computed(() => props.zIndexClass ?? 'z-40')
</script>

<template>
  <div ref="rootRef" class="relative min-w-0">
    <label
      v-if="label"
      class="block text-xs font-semibold text-slate-600 dark:text-neutral-300"
      :for="buttonId"
      @click.prevent="openFromLabel"
    >
      {{ label }}
    </label>

    <button
      ref="buttonRef"
      :id="buttonId"
      type="button"
      @click="toggle"
      class="w-full"
      :class="[roundedCls, baseButton, buttonClass]"
    >
      <span class="flex items-center justify-between gap-3">
        <span class="truncate">
          <template v-if="selected">
            {{ selected[labelKey] }}
            <span v-if="selected[secondaryKey]"> ({{ selected[secondaryKey] }})</span>
          </template>
          <template v-else>
            {{ placeholder ?? 'Selecciona...' }}
          </template>
        </span>

        <svg class="h-4 w-4 opacity-70 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path
            fill-rule="evenodd"
            d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
            clip-rule="evenodd"
          />
        </svg>
      </span>
    </button>

    <div v-if="open" :class="[zCls, basePanel, roundedCls, panelClass]">
      <div class="p-3 border-b border-slate-200/70 dark:border-white/10">
        <input
          ref="searchRef"
          v-model="query"
          type="text"
          :placeholder="searchPlaceholder ?? 'Buscar...'"
          class="w-full rounded-2xl px-4 py-3 text-sm
                 border border-slate-200 bg-white text-slate-900
                 placeholder:text-slate-400 focus:outline-none focus:ring-2
                 focus:ring-slate-300 focus:border-slate-300
                 dark:border-white/10 dark:bg-neutral-900/60 dark:text-neutral-100
                 dark:placeholder:text-neutral-500 dark:focus:ring-white/10"
        />
      </div>

      <div class="max-h-64 overflow-auto p-2 dark:text-neutral-100">
        <button
          v-if="allowNullEff"
          type="button"
          @click="pick(null)"
          class="w-full text-left px-3 py-2 rounded-2xl text-sm font-semibold
                 hover:bg-slate-50 dark:hover:bg-white/5 transition"
        >
          {{ nullLabel ?? 'Sin selección' }}
        </button>

        <button
          v-for="o in filtered"
          :key="o[valueKey] ?? o.id"
          type="button"
          @click="pick(o[valueKey])"
          class="w-full text-left px-3 py-2 rounded-2xl text-sm
                 hover:bg-slate-50 dark:hover:bg-white/5 transition"
          :class="Number(modelValue) === Number(o[valueKey]) ? 'bg-slate-100 dark:bg-white/10 font-semibold' : ''"
        >
          {{ o[labelKey] }}<span v-if="o[secondaryKey]"> ({{ o[secondaryKey] }})</span>
        </button>

        <div v-if="filtered.length === 0" class="px-3 py-3 text-sm text-slate-500 dark:text-neutral-400">
          Sin resultados.
        </div>
      </div>
    </div>

    <p v-if="error" class="mt-1 text-xs text-rose-500">{{ error }}</p>
  </div>
</template>
