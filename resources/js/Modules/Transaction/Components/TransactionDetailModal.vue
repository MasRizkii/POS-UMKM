<script setup>
import { useCurrency } from '@/Composables/useCurrency';
import { useDateTime } from '@/Composables/useDateTime';
import { useThermalPrint } from '@/Composables/useThermalPrint';
import { 
    X, 
    Printer, 
    Ban, 
    Receipt, 
    QrCode, 
    Banknote, 
    CheckCircle, 
    FileEdit,
    AlertTriangle
} from 'lucide-vue-next';

const { formatRupiah } = useCurrency();
const { formatDateTime } = useDateTime();
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
});

defineEmits(['close', 'requestVoid']);
</script>

<template>
    <div v-if="show && transaction" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center bg-on-surface/40 backdrop-blur-xs">
        <!-- Card -->
        <div class="relative w-full max-w-xl bg-surface rounded-2xl shadow-2xl border border-border-subtle z-10 flex flex-col overflow-hidden max-h-[92vh] animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="p-4 bg-surface-container-low flex items-center justify-between border-b border-border-subtle shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-subtle text-primary flex items-center justify-center">
                        <Receipt class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-base text-on-surface">Rincian Transaksi</h3>
                            <span class="font-bold text-base text-primary">#{{ transaction.invoice_number }}</span>
                        </div>
                        <span class="text-xs text-text-muted">Terverifikasi otomatis oleh sistem POS</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        v-if="transaction.status === 'completed'"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-secondary-fixed/50 text-secondary text-xs font-bold"
                    >
                        <CheckCircle class="w-3.5 h-3.5" />
                        <span>Selesai</span>
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-error-container text-on-error-container text-xs font-bold"
                    >
                        <Ban class="w-3.5 h-3.5" />
                        <span>Dibatalkan</span>
                    </span>
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="p-1.5 rounded-lg text-text-muted hover:bg-surface-container hover:text-on-surface transition-colors cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Modal Body Scrollable -->
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Cancel Notice Box if cancelled -->
                <div v-if="transaction.status === 'void'" class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-2.5 text-xs text-rose-900">
                    <AlertTriangle class="w-4 h-4 text-error-alert shrink-0 mt-0.5" />
                    <div>
                        <p class="font-bold">Transaksi ini telah dibatalkan:</p>
                        <p class="mt-0.5">Alasan: <span class="font-semibold">{{ transaction.void_log?.reason || 'Pembatalan transaksi' }}</span></p>
                        <p class="text-[11px] text-rose-700 mt-1">
                            Oleh: {{ transaction.void_log?.user?.name || 'Kasir' }} • {{ formatDateTime(transaction.void_log?.void_at) }}
                        </p>
                    </div>
                </div>

                <!-- 4-Grid Metadata Box -->
                <div class="grid grid-cols-2 gap-3 p-3.5 bg-surface-container-lowest rounded-xl shadow-xs border border-border-subtle text-xs">
                    <div>
                        <span class="text-[11px] text-text-muted block">Waktu Transaksi</span>
                        <span class="font-bold text-on-surface">{{ formatDateTime(transaction.created_at) }}</span>
                    </div>
                    <div>
                        <span class="text-[11px] text-text-muted block">Kasir / Shift</span>
                        <span class="font-bold text-on-surface">{{ transaction.user?.name || 'Kasir' }} (Shift #{{ transaction.shift_id || '1' }})</span>
                    </div>
                    <div>
                        <span class="text-[11px] text-text-muted block">Terminal POS</span>
                        <span class="font-bold text-on-surface">Terminal Kasir #01</span>
                    </div>
                    <div>
                        <span class="text-[11px] text-text-muted block">Metode Pembayaran</span>
                        <span
                            v-if="transaction.payment_method === 'qris'"
                            class="font-bold text-tertiary flex items-center gap-1 mt-0.5"
                        >
                            <QrCode class="w-3.5 h-3.5" />
                            <span>QRIS Fisik Toko</span>
                        </span>
                        <span
                            v-else
                            class="font-bold text-secondary flex items-center gap-1 mt-0.5"
                        >
                            <Banknote class="w-3.5 h-3.5" />
                            <span>Tunai (Cash)</span>
                        </span>
                    </div>
                </div>

                <!-- Items Breakdown List -->
                <div>
                    <div class="flex items-center justify-between pb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-text-muted">
                            Daftar Item Pesanan ({{ transaction.items?.length || 0 }})
                        </span>
                        <span class="text-xs text-text-muted">Harga Satuan</span>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="(item, idx) in transaction.items"
                            :key="idx"
                            class="p-3 rounded-xl bg-background flex items-start justify-between border border-border-subtle/50 text-xs"
                        >
                            <div class="flex gap-3 items-start">
                                <span class="w-6 h-6 rounded-md bg-surface-container flex items-center justify-center font-bold text-xs text-on-surface shrink-0">
                                    {{ item.quantity }}x
                                </span>
                                <div>
                                    <span class="font-bold text-on-surface block text-xs sm:text-sm">
                                        {{ item.product_name_snapshot }}
                                    </span>
                                    <span v-if="item.note" class="text-xs text-primary flex items-center gap-1 mt-0.5">
                                        <FileEdit class="w-3.5 h-3.5" />
                                        <span>Catatan: {{ item.note }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-bold text-on-surface block text-xs sm:text-sm">
                                    {{ formatRupiah(item.subtotal) }}
                                </span>
                                <span class="text-[11px] text-text-muted">
                                    @ {{ formatRupiah(item.unit_price_snapshot) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation Box (Pajak Dihapus) -->
                <div class="p-4 bg-surface-container-low rounded-xl space-y-2 border border-border-subtle text-xs">
                    <div class="flex justify-between text-text-muted">
                        <span>Subtotal Item</span>
                        <span class="text-on-surface font-semibold">{{ formatRupiah(transaction.subtotal) }}</span>
                    </div>
                    <div class="pt-2 border-t border-border-subtle flex justify-between items-center">
                        <div>
                            <span class="font-bold text-sm text-on-surface block">Grand Total</span>
                            <span class="text-[11px] text-text-muted">
                                {{ transaction.payment_method === 'cash' ? 'Lunas via Tunai Kasir' : 'Lunas via QRIS Fisik Toko' }}
                            </span>
                        </div>
                        <span class="text-lg font-black text-primary">
                            {{ formatRupiah(transaction.total_amount) }}
                        </span>
                    </div>
                    <div v-if="transaction.payment_method === 'cash'" class="pt-1 border-t border-border-subtle/50 flex justify-between text-[11px] text-text-muted">
                        <span>Uang Diterima / Kembalian</span>
                        <span class="font-semibold text-text-primary">
                            {{ formatRupiah(transaction.amount_paid) }} / {{ formatRupiah(transaction.change_due || 0) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Action Buttons -->
            <div class="p-4 bg-surface border-t border-border-subtle flex items-center justify-between gap-3 shrink-0">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="py-2.5 px-4 rounded-xl bg-surface-container-low text-on-surface hover:bg-surface-container text-xs font-bold transition-colors cursor-pointer"
                >
                    Tutup
                </button>
                <div class="flex items-center gap-2">
                    <button
                        v-if="transaction.status === 'completed'"
                        type="button"
                        @click="$emit('requestVoid', transaction)"
                        class="py-2.5 px-4 rounded-xl bg-error-container text-on-error-container hover:bg-error/20 text-xs font-bold transition-all flex items-center gap-1.5 active:scale-98 cursor-pointer"
                    >
                        <Ban class="w-4 h-4" />
                        <span>Batalkan Transaksi</span>
                    </button>
                    <button
                        type="button"
                        @click="printReceipt"
                        class="py-2.5 px-5 rounded-xl bg-primary text-on-primary text-xs font-bold shadow-md hover:bg-primary-dark transition-all flex items-center gap-1.5 active:scale-98 cursor-pointer"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak Ulang Struk</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
