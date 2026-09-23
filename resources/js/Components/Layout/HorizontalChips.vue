<script setup>
defineProps({
    items: {
        type: Array,
        default: () => [], // Array of { id, name }
    },
    modelValue: {
        type: [String, Number, null],
        default: null,
    },
    allLabel: {
        type: String,
        default: 'Semua',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex items-center gap-2 overflow-x-auto py-2 px-4 no-scrollbar -mx-4 scroll-smooth">
        <button
            type="button"
            @click="$emit('update:modelValue', null)"
            class="whitespace-nowrap px-4 py-2 rounded-full text-xs font-semibold transition-all shrink-0 active:scale-95"
            :class="[
                modelValue === null
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            ]"
        >
            {{ allLabel }}
        </button>

        <button
            v-for="item in items"
            :key="item.id"
            type="button"
            @click="$emit('update:modelValue', item.id)"
            class="whitespace-nowrap px-4 py-2 rounded-full text-xs font-semibold transition-all shrink-0 active:scale-95"
            :class="[
                modelValue === item.id
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            ]"
        >
            {{ item.name }}
        </button>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
