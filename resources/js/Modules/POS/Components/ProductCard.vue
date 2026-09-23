<script setup>
import { ShoppingBag } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';

const { formatRupiah } = useCurrency();

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

defineEmits(['addToCart']);
</script>

<template>
    <div class="group relative flex flex-col justify-between bg-surface rounded-xl sm:rounded-2xl p-2.5 sm:p-4 shadow-xs sm:shadow-sm hover:shadow-md transition-all duration-300 border border-border-subtle">
        <div>
            <!-- Image Area -->
            <div class="relative w-full aspect-square rounded-lg sm:rounded-xl bg-surface-bright flex items-center justify-center p-1.5 sm:p-2 overflow-hidden border border-border-subtle/60">
                <img
                    :src="product.image_url"
                    :alt="product.name"
                    class="w-full h-full object-contain filter drop-shadow-xs group-hover:scale-105 transition-transform duration-300 rounded-md sm:rounded-lg"
                    loading="lazy"
                />

                <!-- Out of Stock Badge -->
                <span
                    v-if="product.status === 'tidak_tersedia'"
                    class="absolute inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center text-white font-bold text-[10px] sm:text-xs uppercase tracking-wider rounded-lg sm:rounded-xl text-center px-1"
                >
                    Habis
                </span>
            </div>

            <!-- Content Area (Deskripsi dihapus) -->
            <div class="flex flex-col mt-2 sm:mt-3">
                <h3 class="text-xs sm:text-sm font-bold text-text-primary truncate" :title="product.name">
                    {{ product.name }}
                </h3>

                <!-- Price Tag -->
                <div class="mt-1 sm:mt-2">
                    <span class="text-sm sm:text-base font-black text-primary">
                        {{ formatRupiah(product.price) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Order Button -->
        <div class="mt-2.5 sm:mt-4 pt-1">
            <button
                type="button"
                :disabled="product.status === 'tidak_tersedia'"
                @click="$emit('addToCart', product)"
                class="w-full flex items-center justify-center gap-1 sm:gap-2 py-1.5 sm:py-2.5 px-2 rounded-lg sm:rounded-xl bg-primary hover:bg-primary-dark text-white text-[11px] sm:text-xs font-bold shadow-xs active:scale-95 transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            >
                <ShoppingBag class="w-3.5 h-3.5 sm:w-4 sm:h-4 shrink-0" />
                <span class="truncate">Pesan</span>
            </button>
        </div>
    </div>
</template>
