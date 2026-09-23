<script setup>
defineProps({
    padding: {
        type: String,
        default: 'p-4',
    },
});
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-gray-100 shadow-sm transition-all"
        :class="padding"
    >
        <slot />
    </div>
</template>
