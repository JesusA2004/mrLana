<script setup lang="ts">
    import { ref, onMounted } from 'vue'
    import Navbar from '@/Layouts/partials/navbar.vue'
    import Sidebar from '@/Layouts/partials/sidebar.vue'
    import { Head } from '@inertiajs/vue3'

    const showingNavigationDropdown = ref(false)

    /** Animación ligera al montar */
    onMounted(() => {
        const el = document.getElementById('auth-layout')
        el?.animate(
            [{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'translateY(0)' }],
            { duration: 240, easing: 'ease-out' }
        )
    })
</script>

<template>
    <Head>
        <link rel="icon" href="/favicon.ico" />
    </Head>

    <div id="auth-layout" class="min-h-screen flex bg-slate-100 dark:bg-neutral-950 transition-colors">
        <Sidebar />
        <div class="flex-1 flex flex-col min-h-screen">
            <Navbar>
                <!-- Se recibe lo que se envie en #header -->
                <template #title>
                <slot name="header">Dashboard</slot>
                </template>
            </Navbar>

            <!-- Aqui se insertan las demas paginas en forma de "Paneles"  -->
            <main class="flex-1 min-w-0">
                <div class="w-full px-4 sm:px-6 lg:px-8 py-0">
                <slot />
                </div>
            </main>
        </div>
    </div>
</template>
