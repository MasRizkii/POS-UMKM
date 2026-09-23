<script setup>
import { ref, computed, watch } from 'vue';
import { useCartStore } from '@/Modules/POS/Stores/useCartStore';
import { useCurrency } from '@/Composables/useCurrency';
import { Banknote, X, CheckCircle } from 'lucide-vue-next';

const cart = useCartStore();
const { formatRupiah } = useCurrency();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    totalAmount: {
        type: Number,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'confirmPayment']);

const cashInput = ref(props.totalAmount);

watch(() => props.show, (newVal) => {
    if (newVal) {
        cashInput.value = props.totalAmount;
    }
});

const changeDue = computed(() => {
    const received = Number(cashInput.value) || 0;
    return Math.max(0, received - props.totalAmount);
});

const isValid = computed(() => {
    return (Number(cashInput.value) || 0) >= props.totalAmount;
});

const setExactAmount = () => {
    cashInput.value = props.totalAmount;
};

const addDenomination = (val) => {
    cashInput.value = val;
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="$emit('close')"></div>

        <!-- Modal Box -->
        <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-border-subtle z-10 flex flex-col gap-5">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                <div class="flex items-center gap-2 text-secondary font-bold text-base">
                    <Banknote class="w-5 h-5" />
                    <span>Pembayaran Tunai (Cash)</span>
                </div>
                <button @click="$emit('close')" class="p-1 rounded-full text-text-muted hover:text-text-primary">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Total Bill Summary -->
            <div class="bg-surface-bright p-4 rounded-2xl border border-border-subtle flex flex-col items-center justify-center">
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Total Tagihan</span>
                <span class="text-2xl font-black text-primary mt-0.5">{{ formatRupiah(totalAmount) }}</span>
            </div>

            <!-- Cash Input -->
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-text-primary">Nominal Uang Diterima:</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-text-muted text-sm">Rp</span>
                    <input
                        type="number"
                        v-model.number="cashInput"
                        class="w-full h-12 pl-12 pr-4 rounded-xl bg-surface-container-low font-bold text-lg text-text-primary focus:bg-white focus:outline-none focus:ring-2 focus:ring-secondary border border-border-subtle"
                        placeholder="0"
                    />
                </div>
            </div>

            <!-- Quick Denomination Suggestions -->
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="setExactAmount"
                    class="px-3 py-1.5 rounded-lg bg-surface border border-border-subtle text-xs font-bold text-text-primary hover:bg-surface-container-high transition active:scale-95"
                >
                    Uang Pas
                </button>
                <button
                    type="button"
                    @click="addDenomination(20000)"
                    class="px-3 py-1.5 rounded-lg bg-surface border border-border-subtle text-xs font-bold text-text-primary hover:bg-surface-container-high transition active:scale-95"
                >
                    20.000
                </button>
                <button
                    type="button"
                    @click="addDenomination(50000)"
                    class="px-3 py-1.5 rounded-lg bg-surface border border-border-subtle text-xs font-bold text-text-primary hover:bg-surface-container-high transition active:scale-95"
                >
                    50.000
                </button>
                <button
                    type="button"
                    @click="addDenomination(100000)"
                    class="px-3 py-1.5 rounded-lg bg-surface border border-border-subtle text-xs font-bold text-text-primary hover:bg-surface-container-high transition active:scale-95"
                >
                    100.000
                </button>
            </div>

            <!-- Change Calculation Box -->
            <div
                class="p-4 rounded-2xl flex items-center justify-between border"
                :class="[
                    isValid
                        ? 'bg-emerald-50/70 border-emerald-200 text-secondary'
                        : 'bg-rose-50/70 border-rose-200 text-error-alert',
                ]"
            >
                <span class="text-xs font-bold">
                    {{ isValid ? 'Uang Kembalian:' : 'Uang Diterima Kurang!' }}
                </span>
                <span class="text-lg font-black">
                    {{ formatRupiah(changeDue) }}
                </span>
            </div>

            <!-- Confirmation Button -->
            <div class="pt-2">
                <button
                    type="button"
                    :disabled="!isValid || loading"
                    @click="$emit('confirmPayment', { cashReceived: cashInput, changeDue: changeDue })"
                    class="w-full py-3.5 rounded-xl bg-secondary hover:bg-secondary/90 text-white font-bold text-sm shadow-md flex items-center justify-center gap-2 active:scale-95 transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                >
                    <CheckCircle class="w-5 h-5" />
                    <span>{{ loading ? 'Menyimpan Transaksi...' : 'Konfirmasi Pembayaran Selesai' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
