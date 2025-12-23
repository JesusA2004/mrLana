<script setup lang="ts">
    import { useId } from 'vue'

    const props = defineProps<{
    modelValue: string | number
    label?: string
    id?: string
    }>()

    defineEmits<{ (e: 'update:modelValue', v: string | number): void }>()

    // ID único, estable y accesible
    const selectId = props.id ?? useId()
</script>

<template>
    <div>
        <label v-if="label" :for="selectId"
        class="block text-xs font-semibold text-slate-600 dark:text-neutral-300 mb-1">
            {{ label }}
        </label>

        <select :id="selectId" :value="modelValue"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        class="w-full rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-neutral-950
            px-3 py-2 text-sm text-slate-900 dark:text-neutral-100
            outline-none focus:ring-2 focus:ring-slate-300 dark:focus:ring-white/10 transition">
            <slot />
        </select>
    </div>
</template>
