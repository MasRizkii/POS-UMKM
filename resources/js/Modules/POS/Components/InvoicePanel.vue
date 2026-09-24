<script setup>
import { computed } from 'vue';
import { useCartStore } from '@/Modules/POS/Stores/useCartStore';
import { useCurrency } from '@/Composables/useCurrency';
import { 
    Trash2, 
    QrCode, 
    Banknote, 
    CheckCircle2, 
    Plus, 
    Minus, 
    FileText 
} from 'lucide-vue-next';

const cart = useCartStore();
const { formatRupiah } = useCurrency();

const props = defineProps({
    activeCashier: {
        type: Object,
        default: () => ({ name: 'Kasir', shiftName: 'Belum Aktif' }),
    },
    setting: { type: Object, default: () => ({ tax_enabled: false, service_charge_enabled: false, cash_enabled: true, qris_enabled: true }) },
});

defineEmits(['openPaymentModal']);

const taxAmount = computed(() => props.setting.tax_enabled ? Math.round(cart.subtotal * Number(props.setting.tax_percentage) * 100) / 10000 : 0);
const serviceChargeAmount = computed(() => props.setting.service_charge_enabled ? Math.round(cart.subtotal * Number(props.setting.service_charge_percentage) * 100) / 10000 : 0);
const totalPayable = computed(() => {
    return cart.subtotal + taxAmount.value + serviceChargeAmount.value;
});
</script>

<template>
    <div class="bg-surface rounded-2xl p-5 shadow-sm border border-border-subtle flex flex-col gap-4">
        <!-- Cashier Header Profile & Status Info -->
        <div class="flex items-center justify-between bg-surface-bright p-3 rounded-xl border border-border-subtle">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm shadow-xs">
                        {{ (activeCashier.name || 'SJ').substring(0, 2).toUpperCase() }}
                    </div>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-secondary ring-2 ring-surface"></span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs sm:text-sm font-bold text-text-primary leading-tight">
                        {{ activeCashier.name || 'Kasir' }}
                    </span>
                    <span class="text-[11px] font-medium text-text-muted">
                        Kasir • {{ activeCashier.shiftName || 'Belum Aktif' }}
                    </span>
                </div>
            </div>
            <span class="w-2 h-2 rounded-full bg-secondary"></span>
        </div>

        <!-- Invoice Title & Order Meta -->
        <div class="flex items-center justify-between pt-1">
            <div class="flex flex-col">
                <h2 class="text-base sm:text-lg font-black text-text-primary">Invoice Pesanan</h2>
                <span class="text-[11px] text-text-muted mt-0.5">Sesi Kasir Aktif</span>
            </div>
            <button
                v-if="cart.items.length > 0"
                type="button"
                @click="cart.clearCart()"
                class="text-error-alert text-xs font-semibold hover:underline flex items-center gap-1 cursor-pointer"
            >
                <Trash2 class="w-3.5 h-3.5" />
                <span>Bersihkan</span>
            </button>
        </div>

        <!-- Cart Items List Container -->
        <div class="flex flex-col gap-2.5 max-h-[300px] overflow-y-auto pr-1">
            <div v-if="cart.items.length === 0" class="py-10 text-center text-xs text-text-muted flex flex-col items-center justify-center gap-2">
                <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-300">
                    <FileText class="w-6 h-6" />
                </div>
                <span>Keranjang masih kosong. Pilih menu untuk memesan.</span>
            </div>

            <!-- Cart Row -->
            <div
                v-for="(item, index) in cart.items"
                :key="index"
                class="flex flex-col p-2.5 rounded-xl bg-surface-bright border border-border-subtle/80 gap-2"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <img
                            :src="item.product.image_url"
                            :alt="item.product.name"
                            class="w-10 h-10 object-contain rounded-lg bg-white p-1 border border-border-subtle shrink-0"
                        />
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-text-primary truncate">
                                {{ item.product.name }}
                            </span>
                            <span class="text-[11px] text-text-muted">
                                {{ formatRupiah(item.product.price) }} / unit
                            </span>
                        </div>
                    </div>

                    <!-- Qty Stepper & Price -->
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <div class="flex items-center bg-white rounded-lg border border-border-subtle shadow-2xs px-1 py-0.5">
                            <button
                                type="button"
                                @click="cart.decrementQuantity(index)"
                                class="w-6 h-6 flex items-center justify-center text-text-muted hover:text-primary font-bold cursor-pointer"
                            >
                                <Minus class="w-3 h-3" />
                            </button>
                            <span class="px-2 text-xs font-bold text-text-primary min-w-[20px] text-center">
                                {{ item.quantity }}
                            </span>
                            <button
                                type="button"
                                @click="cart.incrementQuantity(index)"
                                class="w-6 h-6 flex items-center justify-center text-text-muted hover:text-primary font-bold cursor-pointer"
                            >
                                <Plus class="w-3 h-3" />
                            </button>
                        </div>
                        <span class="text-xs font-black text-text-primary">
                            {{ formatRupiah(item.product.price * item.quantity) }}
                        </span>
                    </div>
                </div>

                <!-- Custom Note per item -->
                <div class="pt-1 border-t border-border-subtle/40">
                    <input
                        type="text"
                        :value="item.note"
                        @input="cart.updateItemNote(index, $event.target.value)"
                        placeholder="Catatan item (misal: Es sedikit, tanpa gula)..."
                        class="w-full text-[11px] bg-white border border-border-subtle/80 rounded-lg px-2.5 py-1 text-text-primary placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </div>

        <!-- Payment Breakdown Summary (Pajak Dihapus) -->
        <div class="bg-surface-bright rounded-xl p-3.5 border border-border-subtle flex flex-col gap-2">
            <div class="flex items-center justify-between text-xs">
                <span class="text-text-muted">Sub Total</span>
                <span class="font-bold text-text-primary">{{ formatRupiah(cart.subtotal) }}</span>
            </div>
            <div v-if="taxAmount" class="flex items-center justify-between text-xs"><span class="text-text-muted">Pajak</span><span class="font-bold">{{ formatRupiah(taxAmount) }}</span></div>
            <div v-if="serviceChargeAmount" class="flex items-center justify-between text-xs"><span class="text-text-muted">Service charge</span><span class="font-bold">{{ formatRupiah(serviceChargeAmount) }}</span></div>
            <div class="my-0.5 h-[1px] bg-border-subtle w-full"></div>
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-text-primary">Total Pembayaran</span>
                <span class="text-lg font-black text-primary">{{ formatRupiah(totalPayable) }}</span>
            </div>
        </div>

        <!-- Payment Method Selector -->
        <div class="flex flex-col gap-2">
            <span class="text-xs font-bold text-text-primary">Pilih Metode Pembayaran:</span>
            <div class="grid grid-cols-2 gap-2">
                <!-- QRIS Button -->
                <button
                    type="button"
                    @click="cart.paymentMethod = 'qris'"
                    :disabled="!setting.qris_enabled"
                    class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl transition-all cursor-pointer border"
                    :class="[
                        cart.paymentMethod === 'qris'
                            ? 'bg-primary-subtle text-primary border-primary/30 font-bold shadow-xs'
                            : 'bg-surface-bright text-text-muted border-border-subtle hover:bg-gray-50',
                    ]"
                >
                    <QrCode class="w-5 h-5" />
                    <span class="text-xs mt-1">QRIS Fisik</span>
                </button>

                <!-- Cash Button -->
                <button
                    type="button"
                    @click="cart.paymentMethod = 'cash'"
                    :disabled="!setting.cash_enabled"
                    class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl transition-all cursor-pointer border"
                    :class="[
                        cart.paymentMethod === 'cash'
                            ? 'bg-primary-subtle text-primary border-primary/30 font-bold shadow-xs'
                            : 'bg-surface-bright text-text-muted border-border-subtle hover:bg-gray-50',
                    ]"
                >
                    <Banknote class="w-5 h-5" />
                    <span class="text-xs mt-1">Tunai (Cash)</span>
                </button>
            </div>
        </div>

        <!-- Checkout Action Button -->
        <div class="pt-1">
            <button
                type="button"
                :disabled="cart.items.length === 0"
                @click="$emit('openPaymentModal')"
                class="w-full py-3.5 px-4 rounded-xl bg-primary hover:bg-primary-dark text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-primary/25 active:scale-[0.98] transition-all disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            >
                <CheckCircle2 class="w-5 h-5" />
                <span>Proses Pesanan • {{ formatRupiah(totalPayable) }}</span>
            </button>
        </div>
    </div>
</template>
