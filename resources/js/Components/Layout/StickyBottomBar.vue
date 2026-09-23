<script setup>
defineProps({
    zIndex: {
        type: String,
        default: 'z-30',
    },
});
</script>

<template>
    <div
        class="fixed inset-x-0 bottom-0 bg-white/95 backdrop-blur-md border-t border-gray-100 p-3 shadow-lg lg:hidden"
        :class="zIndex"
    >
        <div class="max-w-md mx-auto">
            <slot />
        </div>
    </div>
</template>
