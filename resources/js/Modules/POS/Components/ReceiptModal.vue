<script setup>
import { Printer, X, CheckCircle2 } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';
import { useThermalPrint } from '@/Composables/useThermalPrint';

const { formatRupiah } = useCurrency();
const { printReceipt } = useThermalPrint();

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    transaction: {
        type: Object,
        default: null,
    },
    storeName: {
        type: String,
        default: 'Foodislice POS UMKM',
    },
});

defineEmits(['close', 'newTransaction']);
</script>

<template>
    <div v-if="show && transaction" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="$emit('close')"></div>

        <!-- Receipt Card -->
        <div class="relative w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-border-subtle z-10 flex flex-col gap-4 animate-in fade-in zoom-in-95 duration-150">
            <!-- Success Icon -->
            <div class="flex flex-col items-center text-center gap-1.5 pt-2">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-secondary flex items-center justify-center">
                    <CheckCircle2 class="w-7 h-7" />
                </div>
                <h3 class="text-lg font-black text-text-primary">Transaksi Berhasil!</h3>
                <span class="text-xs text-text-muted">Invoice: #{{ transaction.invoice_number }}</span>
            </div>

            <!-- Printable Receipt Area -->
            <div id="printable-receipt" class="bg-surface-bright rounded-2xl p-4 border border-dashed border-border-subtle text-xs font-mono text-text-primary flex flex-col gap-3">
                <div class="text-center pb-2 border-b border-dashed border-border-subtle">
                    <p class="font-bold text-sm">{{ storeName }}</p>
                    <p class="text-[10px] text-text-muted mt-0.5">Struk Pembelian Kasir</p>
                    <p class="text-[10px] text-text-muted mt-0.5">{{ new Date().toLocaleString('id-ID') }}</p>
                </div>

                <!-- Items -->
                <div class="flex flex-col gap-1.5 py-1">
                    <div
                        v-for="(item, idx) in transaction.items"
                        :key="idx"
                        class="flex justify-between items-start"
                    >
                        <div class="flex flex-col">
                            <span class="font-bold">{{ item.product_name_snapshot }} x{{ item.quantity }}</span>
                            <span v-if="item.note" class="text-[10px] text-text-muted italic">Note: {{ item.note }}</span>
                        </div>
                        <span class="font-bold">{{ formatRupiah(item.subtotal) }}</span>
                    </div>
                </div>

                <!-- Financials (Pajak Dihapus) -->
                <div class="pt-2 border-t border-dashed border-border-subtle flex flex-col gap-1 text-[11px]">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>{{ formatRupiah(transaction.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-xs pt-1 border-t border-border-subtle">
                        <span>TOTAL:</span>
                        <span class="text-primary font-black">{{ formatRupiah(transaction.total_amount) }}</span>
                    </div>
                    <div class="flex justify-between pt-1 text-[10px] text-text-muted">
                        <span>Metode:</span>
                        <span class="uppercase font-bold">{{ transaction.payment_method }}</span>
                    </div>
                    <div v-if="transaction.payment_method === 'cash'" class="flex justify-between text-[10px] text-text-muted">
                        <span>Kembalian:</span>
                        <span>{{ formatRupiah(transaction.change_due || 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons: Cetak Struk & Tutup -->
            <div class="grid grid-cols-2 gap-3 pt-2">
                <button
                    type="button"
                    @click="printReceipt"
                    class="py-3 rounded-xl border border-border-subtle text-text-primary text-xs font-bold hover:bg-gray-50 flex items-center justify-center gap-1.5 cursor-pointer active:scale-95 transition"
                >
                    <Printer class="w-4 h-4" />
                    <span>Cetak Struk</span>
                </button>
                <button
                    type="button"
                    @click="$emit('newTransaction')"
                    class="py-3 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition cursor-pointer"
                >
                    <X class="w-4 h-4" />
                    <span>Tutup</span>
                </button>
            </div>
        </div>
    </div>
</template>
