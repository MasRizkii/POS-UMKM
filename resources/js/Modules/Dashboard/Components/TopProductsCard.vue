<script setup>
import { ref, computed } from 'vue';
import { Award } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';

const { formatRupiah } = useCurrency();

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
});

const activeCategory = ref('all');

const filteredProducts = computed(() => {
    if (activeCategory.value === 'all') return props.products;
    return props.products.filter(p => p.category === activeCategory.value);
});
</script>

<template>
    <div class="bg-surface rounded-2xl p-5 sm:p-6 shadow-sm border border-border-subtle flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex flex-col">
                <h2 class="text-base sm:text-lg font-bold text-text-primary">Produk Terlaris</h2>
                <span class="text-xs text-text-muted">Berdasarkan penjualan hari ini</span>
            </div>
            <div class="w-9 h-9 rounded-xl bg-primary-subtle text-primary flex items-center justify-center">
                <Award class="w-5 h-5" />
            </div>
        </div>

        <!-- Leaderboard List -->
        <div class="flex flex-col gap-2.5">
            <div
                v-for="(product, index) in filteredProducts"
                :key="product.id || index"
                class="flex items-center justify-between p-2.5 rounded-xl transition-colors"
                :class="index === 0 ? 'bg-surface-container-low border border-border-subtle' : 'hover:bg-surface-container-low'"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Rank Badge -->
                    <div
                        class="w-7 h-7 rounded-full text-xs font-black flex items-center justify-center shrink-0"
                        :class="index === 0 ? 'bg-primary text-white shadow-xs' : 'bg-surface-container-high text-text-primary'"
                    >
                        {{ index + 1 }}
                    </div>

                    <!-- Image Thumbnail -->
                    <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0 bg-white border border-border-subtle p-0.5 flex items-center justify-center">
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            class="w-full h-full object-cover rounded-md"
                        />
                        <span v-else class="text-sm font-black text-primary">{{ product.name?.charAt(0) }}</span>
                    </div>

                    <!-- Details -->
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs sm:text-sm font-bold text-text-primary truncate">
                            {{ product.name }}
                        </span>
                        <span class="text-[11px] font-semibold text-text-muted">
                            {{ formatRupiah(product.price) }} / unit
                        </span>
                    </div>
                </div>

                <!-- Sales Info -->
                <div class="flex flex-col text-right shrink-0">
                    <span class="text-xs font-bold" :class="index === 0 ? 'text-primary' : 'text-text-primary'">
                        {{ product.sold_count ?? 0 }}x terjual
                    </span>
                    <span class="text-[11px] text-text-muted font-medium">
                        {{ formatRupiah(product.total_sales ?? 0) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
