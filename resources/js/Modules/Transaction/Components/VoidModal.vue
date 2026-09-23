<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, X, Ban, Info } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';

const { formatRupiah } = useCurrency();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    transaction: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'voidSuccess']);

const selectedReasonCategory = ref('Pelanggan salah pilih varian minuman / makanan');
const customReasonDetail = ref('');

const form = useForm({
    reason: '',
});

const submitVoid = () => {
    if (!props.transaction) return;

    form.reason = customReasonDetail.value.trim()
        ? `${selectedReasonCategory.value}: ${customReasonDetail.value.trim()}`
        : selectedReasonCategory.value;

    form.post(route('transactions.void', props.transaction.id), {
        onSuccess: () => {
            form.reset();
            customReasonDetail.value = '';
            emit('close');
            emit('voidSuccess');
        },
    });
};
</script>

<template>
    <div v-if="show && transaction" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center bg-on-surface/40 backdrop-blur-xs">
        <div class="relative w-full max-w-lg bg-surface rounded-2xl shadow-2xl border border-border-subtle z-10 flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Header Pembatalan -->
            <div class="p-4 bg-error-container flex items-center justify-between text-on-error-container shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-error text-white flex items-center justify-center">
                        <AlertTriangle class="w-5 h-5 stroke-[2.2]" />
                    </div>
                    <div>
                        <h4 class="font-bold text-base text-on-error-container">Otorisasi Pembatalan Transaksi</h4>
                        <span class="text-xs text-on-error-container/80 font-medium">
                            Invoice: #{{ transaction.invoice_number }} • {{ formatRupiah(transaction.total_amount) }}
                        </span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-1 rounded-lg text-on-error-container hover:bg-black/10 transition cursor-pointer"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="submitVoid" class="p-5 flex flex-col gap-4">
                <!-- Info Notice Box -->
                <div class="p-3.5 rounded-xl bg-surface-container-low flex items-start gap-2.5 text-xs text-on-surface leading-snug border border-border-subtle/50">
                    <Info class="w-4 h-4 text-error shrink-0 mt-0.5" />
                    <p>
                        Pembatalan nota kasir akan menghapus transaksi dari total omzet berjalan dan dicatat ke dalam <strong>Audit Log Permanen</strong>. Tindakan ini tidak dapat diurungkan.
                    </p>
                </div>

                <!-- Reason Selector -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-on-surface">
                        Alasan Pembatalan <span class="text-error">*</span>
                    </label>
                    <select
                        v-model="selectedReasonCategory"
                        class="w-full px-3.5 py-2.5 bg-background rounded-xl text-xs text-on-surface border border-border-subtle font-medium focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="Pelanggan salah pilih varian minuman / makanan">Pelanggan salah pilih varian minuman / makanan</option>
                        <option value="Nota kasir tercetak ganda (Double Input)">Nota kasir tercetak ganda (Double Input)</option>
                        <option value="Pelanggan membatalkan pesanan sebelum dibuat">Pelanggan membatalkan pesanan sebelum dibuat</option>
                        <option value="Metode pembayaran salah input kasir">Metode pembayaran salah input kasir</option>
                        <option value="Alasan operasional lainnya">Alasan operasional lainnya</option>
                    </select>
                </div>

                <!-- Custom Detail Note -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-on-surface">Catatan Tambahan Kasir</label>
                    <textarea
                        v-model="customReasonDetail"
                        rows="2"
                        placeholder="Tuliskan keterangan detail alasan pembatalan..."
                        class="w-full px-3.5 py-2.5 bg-background rounded-xl text-xs text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                    ></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center justify-end gap-2 border-t border-border-subtle">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2.5 rounded-xl bg-background hover:bg-surface-container-low text-on-surface font-semibold text-xs transition-colors cursor-pointer"
                    >
                        Kembali
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-error text-white hover:bg-red-700 font-bold text-xs shadow-md transition-all active:scale-98 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <Ban class="w-4 h-4" />
                        <span>{{ form.processing ? 'Memproses...' : 'Konfirmasi Batalkan Transaksi' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
