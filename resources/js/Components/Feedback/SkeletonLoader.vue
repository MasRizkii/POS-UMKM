<script setup>
defineProps({
    count: {
        type: Number,
        default: 3,
    },
    type: {
        type: String,
        default: 'card', // card, list, text
    },
});
</script>

<template>
    <div class="w-full space-y-3">
        <div
            v-for="i in count"
            :key="i"
            class="animate-pulse bg-white p-4 rounded-2xl border border-gray-100 shadow-sm"
        >
            <div v-if="type === 'card'" class="space-y-3">
                <div class="h-28 bg-gray-100 rounded-xl w-full"></div>
                <div class="h-4 bg-gray-100 rounded w-3/4"></div>
                <div class="h-3 bg-gray-100 rounded w-1/2"></div>
            </div>
            <div v-else-if="type === 'list'" class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gray-100 rounded-xl flex-shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3.5 bg-gray-100 rounded w-3/5"></div>
                    <div class="h-3 bg-gray-100 rounded w-2/5"></div>
                </div>
            </div>
            <div v-else class="space-y-2">
                <div class="h-3.5 bg-gray-100 rounded w-full"></div>
                <div class="h-3.5 bg-gray-100 rounded w-5/6"></div>
            </div>
        </div>
    </div>
</template>
