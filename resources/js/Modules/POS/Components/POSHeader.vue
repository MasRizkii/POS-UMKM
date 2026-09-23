<script setup>
import { Search, Receipt, Banknote } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';

const { formatRupiah } = useCurrency();

defineProps({
    searchQuery: {
        type: String,
        default: '',
    },
    todayOrdersCount: {
        type: Number,
        default: 0,
    },
    registerTotal: {
        type: Number,
        default: 0,
    },
});

defineEmits(['update:searchQuery']);
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Search Input Bar -->
        <div class="bg-surface p-3 rounded-2xl shadow-sm border border-border-subtle flex items-center gap-3">
            <div class="relative flex-1 flex items-center bg-surface-bright rounded-xl px-4 py-2.5 gap-2 border border-border-subtle">
                <Search class="w-5 h-5 text-text-muted shrink-0" />
                <input
                    :value="searchQuery"
                    @input="$emit('update:searchQuery', $event.target.value)"
                    type="search"
                    placeholder="Cari minuman, es teh, jus, kopi..."
                    class="w-full bg-transparent border-none outline-none text-xs sm:text-sm text-text-primary placeholder:text-text-muted focus:ring-0 p-0"
                />
            </div>
        </div>

        <!-- Quick Metrics Ribbon -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <div class="bg-surface p-4 rounded-2xl shadow-sm border border-border-subtle flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                    <Receipt class="w-5 h-5" />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Today's Orders</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-base sm:text-xl font-black text-text-primary">
                            {{ todayOrdersCount || 148 }}
                        </span>
                        <span class="text-[10px] font-bold text-secondary bg-emerald-50 px-1.5 py-0.5 rounded-md">
                            +12%
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-surface p-4 rounded-2xl shadow-sm border border-border-subtle flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-secondary flex items-center justify-center shrink-0">
                    <Banknote class="w-5 h-5" />
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Register Total</span>
                    <span class="text-base sm:text-xl font-black text-text-primary truncate mt-0.5">
                        {{ formatRupiah(registerTotal || 1850000) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
