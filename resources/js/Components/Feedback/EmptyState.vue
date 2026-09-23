<script setup>
defineProps({
    title: {
        type: String,
        default: 'Tidak ada data',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center p-8 text-center my-6">
        <div class="w-16 h-16 mb-4 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100">
            <slot name="icon">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </slot>
        </div>
        <h4 class="text-base font-semibold text-gray-800">{{ title }}</h4>
        <p v-if="description" class="mt-1 text-xs text-gray-500 max-w-xs leading-relaxed">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
