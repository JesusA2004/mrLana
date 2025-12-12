<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <!-- CONTENEDOR PRINCIPAL: full width, sin encajonar -->
    <div class="w-full py-4">

      <!-- Card ejemplo -->
      <div
        class="w-full bg-white dark:bg-neutral-900 border border-slate-200 dark:border-neutral-800
          rounded-lg shadow-sm p-6 text-slate-900 dark:text-slate-100">
        You're logged in!
      </div>

    </div>
  </AuthenticatedLayout>
</template>
