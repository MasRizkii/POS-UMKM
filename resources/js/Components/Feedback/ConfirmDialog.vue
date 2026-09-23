<script setup>
import BaseButton from '../Common/BaseButton.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi',
    },
    message: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    },
    confirmText: {
        type: String,
        default: 'Ya, Lanjutkan',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    danger: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['confirm', 'cancel']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('cancel')"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-sm bg-white rounded-3xl p-6 shadow-xl border border-gray-100 z-10">
            <h3 class="text-lg font-bold text-gray-900">{{ title }}</h3>
            <p class="mt-2 text-sm text-gray-600 leading-relaxed">{{ message }}</p>

            <div class="mt-6 flex gap-3 justify-end">
                <BaseButton variant="secondary" size="md" @click="$emit('cancel')" :disabled="loading">
                    {{ cancelText }}
                </BaseButton>
                <BaseButton
                    :variant="danger ? 'danger' : 'primary'"
                    size="md"
                    :loading="loading"
                    @click="$emit('confirm')"
                >
                    {{ confirmText }}
                </BaseButton>
            </div>
        </div>
    </div>
</template>
