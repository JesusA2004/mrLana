<script setup lang="ts">
defineProps<{
  modelValue: string
  label?: string
  placeholder?: string
}>()

defineEmits<{ (e: 'update:modelValue', v: string): void }>()
</script>

<template>
  <div>
    <label v-if="label" class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">{{ label }}</label>
    <input
      :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      type="text"
      :placeholder="placeholder"
      class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
             px-3 py-2 text-sm text-slate-900 dark:text-neutral-100
             outline-none focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition"
    />
  </div>
</template>
