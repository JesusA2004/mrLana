<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
        <h2 class="text-xl font-semibold leading-tight text-slate-900 dark:text-slate-100">
            Profile
        </h2>
        </template>

        <div class="py-4">
        <div class="w-full space-y-6">
            <div class="bg-white dark:bg-neutral-900 p-4 shadow sm:rounded-lg sm:p-8 border border-transparent dark:border-neutral-800">
            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-none" />
            </div>

            <div class="bg-white dark:bg-neutral-900 p-4 shadow sm:rounded-lg sm:p-8 border border-transparent dark:border-neutral-800">
            <UpdatePasswordForm class="max-w-none" />
            </div>

            <div class="bg-white dark:bg-neutral-900 p-4 shadow sm:rounded-lg sm:p-8 border border-transparent dark:border-neutral-800">
            <DeleteUserForm class="max-w-none" />
            </div>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
