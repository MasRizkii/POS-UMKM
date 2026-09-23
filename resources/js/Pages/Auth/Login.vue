<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import LoginForm from '@/Modules/Auth/Components/LoginForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <GuestLayout>
        <Head title="Masuk ke Terminal Kasir - Foodislice" />

        <div v-if="status" class="mb-4 text-xs font-semibold text-secondary bg-secondary-container/30 px-4 py-2 rounded-xl text-center max-w-lg mx-auto">
            {{ status }}
        </div>

        <LoginForm />
    </GuestLayout>
</template>
