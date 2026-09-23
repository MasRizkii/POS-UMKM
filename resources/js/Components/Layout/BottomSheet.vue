<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
});

defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
            @click="$emit('close')"
        ></div>

        <!-- Sheet Container -->
        <div class="fixed inset-x-0 bottom-0 max-h-[88vh] bg-white rounded-t-3xl shadow-2xl flex flex-col z-10 transition-transform duration-300">
            <!-- Grab Handle -->
            <div class="w-full flex justify-center pt-3 pb-1">
                <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
            </div>

            <!-- Header -->
            <div v-if="title || $slots.header" class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <slot name="header">
                    <h3 class="text-base font-bold text-gray-900">{{ title }}</h3>
                    <button
                        @click="$emit('close')"
                        class="p-1.5 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </slot>
            </div>

            <!-- Content Area (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-5 py-4 overscroll-contain">
                <slot />
            </div>

            <!-- Footer / Actions (Sticky if provided) -->
            <div v-if="$slots.footer" class="p-4 border-t border-gray-100 bg-gray-50/50">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
