<script setup>
import { useCurrency } from '@/Composables/useCurrency';
import { QrCode, X, CheckCircle, AlertCircle } from 'lucide-vue-next';

const { formatRupiah } = useCurrency();

defineProps({
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

defineEmits(['close', 'confirmPayment']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="$emit('close')"></div>

        <!-- Modal Box -->
        <div class="relative w-full max-w-md bg-white rounded-3xl p-6 shadow-2xl border border-border-subtle z-10 flex flex-col gap-5">
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b border-border-subtle">
                <div class="flex items-center gap-2 text-tertiary font-bold text-base">
                    <QrCode class="w-5 h-5" />
                    <span>Verifikasi QRIS Fisik Toko</span>
                </div>
                <button @click="$emit('close')" class="p-1 rounded-full text-text-muted hover:text-text-primary">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Amount Display -->
            <div class="bg-cyan-50/70 p-5 rounded-2xl border border-cyan-100 flex flex-col items-center justify-center text-center">
                <span class="text-xs font-semibold text-text-muted uppercase tracking-wider">Total Harus Dibayar</span>
                <span class="text-3xl font-black text-primary mt-1">{{ formatRupiah(totalAmount) }}</span>
            </div>

            <!-- Instructions Notice (PRD Section 7.3) -->
            <div class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200/80 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                <div class="flex flex-col text-xs text-amber-900 leading-relaxed">
                    <span class="font-bold">Langkah Konfirmasi Kasir:</span>
                    <ol class="list-decimal list-inside space-y-1 mt-1">
                        <li>Arahkan pelanggan scan QRIS fisik di meja kasir.</li>
                        <li>Pastikan notifikasi uang masuk telah berhasil di aplikasi rekening / EDC toko Anda.</li>
                        <li>Tekan tombol di bawah untuk menyelesaikan pesanan.</li>
                    </ol>
                </div>
            </div>

            <!-- Confirm Button -->
            <div class="pt-2">
                <button
                    type="button"
                    :disabled="loading"
                    @click="$emit('confirmPayment')"
                    class="w-full py-3.5 rounded-xl bg-primary hover:bg-primary-dark text-white font-black text-sm shadow-lg shadow-primary/25 flex items-center justify-center gap-2 active:scale-95 transition cursor-pointer disabled:opacity-50"
                >
                    <CheckCircle class="w-5 h-5" />
                    <span>{{ loading ? 'Menyimpan Transaksi...' : 'Pembayaran Diterima (Selesai)' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
