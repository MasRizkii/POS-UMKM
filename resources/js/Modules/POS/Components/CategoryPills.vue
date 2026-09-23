<script setup>
defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    selectedCategoryId: {
        type: [Number, String, null],
        default: null,
    },
});

defineEmits(['selectCategory']);
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <div class="flex items-center justify-between">
            <span class="text-sm sm:text-base font-bold text-text-primary">Kategori Produk</span>
            <span
                @click="$emit('selectCategory', null)"
                class="text-xs font-semibold text-primary cursor-pointer hover:underline"
            >
                Semua Kategori
            </span>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar -mx-1 px-1">
            <!-- All Button -->
            <button
                type="button"
                @click="$emit('selectCategory', null)"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap shadow-xs cursor-pointer active:scale-95"
                :class="[
                    selectedCategoryId === null
                        ? 'bg-primary-subtle text-primary border border-primary/20'
                        : 'bg-surface text-text-muted hover:text-text-primary border border-border-subtle hover:bg-gray-50',
                ]"
            >
                <span>🍹 Semua Produk</span>
            </button>

            <!-- Dynamic Category Pills -->
            <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                @click="$emit('selectCategory', cat.id)"
                class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap shadow-xs cursor-pointer active:scale-95"
                :class="[
                    selectedCategoryId === cat.id
                        ? 'bg-primary-subtle text-primary border border-primary/20'
                        : 'bg-surface text-text-muted hover:text-text-primary border border-border-subtle hover:bg-gray-50',
                ]"
            >
                <span>{{ cat.icon || '🥤' }} {{ cat.name }}</span>
            </button>
        </div>
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
