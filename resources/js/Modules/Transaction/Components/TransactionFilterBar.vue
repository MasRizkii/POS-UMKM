<script setup>
import { ref, watch } from 'vue';
import { Search, RotateCcw } from 'lucide-vue-next';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['filterChange', 'resetFilters']);

const search = ref(props.filters.search || '');
const paymentMethod = ref(props.filters.payment_method || '');
const status = ref(props.filters.status || '');
const datePreset = ref(props.filters.date_preset || 'all');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const dateOptions = [
    { value: 'all', label: 'Semua Waktu' },
    { value: 'today', label: 'Hari Ini' },
    { value: 'yesterday', label: 'Kemarin' },
    { value: '7days', label: '7 Hari Terakhir' },
    { value: 'month', label: 'Bulan Ini' },
];

const applyFilters = () => {
    emit('filterChange', {
        search: search.value,
        payment_method: paymentMethod.value,
        status: status.value,
        date_preset: datePreset.value,
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const reset = () => {
    search.value = '';
    paymentMethod.value = '';
    status.value = '';
    datePreset.value = 'all';
    startDate.value = '';
    endDate.value = '';
    emit('resetFilters');
};
</script>

<template>
    <div class="bg-surface rounded-2xl p-4 shadow-sm border border-border-subtle flex flex-col gap-3">
        <!-- Date Preset Horizontal Chips -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
            <button
                v-for="opt in dateOptions"
                :key="opt.value"
                type="button"
                @click="datePreset = opt.value; applyFilters()"
                class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer"
                :class="[
                    datePreset === opt.value
                        ? 'bg-primary text-white shadow-xs'
                        : 'bg-surface-container-low text-text-muted hover:text-text-primary hover:bg-gray-100',
                ]"
            >
                {{ opt.label }}
            </button>
        </div>

        <!-- Filter Controls Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <!-- Search Invoice -->
            <div class="relative flex items-center bg-surface-container-low rounded-xl px-3.5 py-2 border border-border-subtle">
                <Search class="w-4 h-4 text-text-muted shrink-0 mr-2" />
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="search"
                    placeholder="Cari No. Invoice..."
                    class="w-full bg-transparent border-none outline-none text-xs text-text-primary placeholder:text-text-muted focus:ring-0 p-0"
                />
            </div>

            <!-- Payment Method -->
            <div>
                <select
                    v-model="paymentMethod"
                    @change="applyFilters"
                    class="w-full bg-surface-container-low border border-border-subtle rounded-xl text-xs font-semibold text-text-primary py-2 px-3 focus:ring-primary focus:border-primary"
                >
                    <option value="">Semua Metode Pembayaran</option>
                    <option value="cash">Tunai (Cash)</option>
                    <option value="qris">QRIS Fisik</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <select
                    v-model="status"
                    @change="applyFilters"
                    class="w-full bg-surface-container-low border border-border-subtle rounded-xl text-xs font-semibold text-text-primary py-2 px-3 focus:ring-primary focus:border-primary"
                >
                    <option value="">Semua Status</option>
                    <option value="completed">Selesai</option>
                    <option value="void">Dibatalkan</option>
                </select>
            </div>

            <!-- Reset Button -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="applyFilters"
                    class="flex-1 py-2 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold transition active:scale-95 cursor-pointer text-center"
                >
                    Terapkan
                </button>
                <button
                    type="button"
                    @click="reset"
                    class="p-2 rounded-xl bg-surface-container-low hover:bg-gray-200 text-text-muted transition cursor-pointer"
                    title="Reset Filter"
                >
                    <RotateCcw class="w-4 h-4" />
                </button>
            </div>
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
