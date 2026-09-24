<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import POSHeader from '@/Modules/POS/Components/POSHeader.vue';
import CategoryPills from '@/Modules/POS/Components/CategoryPills.vue';
import ProductCard from '@/Modules/POS/Components/ProductCard.vue';
import InvoicePanel from '@/Modules/POS/Components/InvoicePanel.vue';
import CashPaymentModal from '@/Modules/POS/Components/CashPaymentModal.vue';
import QrisPaymentModal from '@/Modules/POS/Components/QrisPaymentModal.vue';
import ReceiptModal from '@/Modules/POS/Components/ReceiptModal.vue';
import BottomSheet from '@/Components/Layout/BottomSheet.vue';
import StickyBottomBar from '@/Components/Layout/StickyBottomBar.vue';
import { useCartStore } from '@/Modules/POS/Stores/useCartStore';
import { useCurrency } from '@/Composables/useCurrency';
import { ShoppingCart } from 'lucide-vue-next';
import { generateIdempotencyKey } from '@/Services/idempotency';
import { assertOnline } from '@/Services/onlineMutations';

const page = usePage();
const cart = useCartStore();
const { formatRupiah } = useCurrency();

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
    activeShift: {
        type: Object,
        default: null,
    },
    setting: {
        type: Object,
        default: () => ({
            store_name: 'Foodislice POS UMKM',
            tax_enabled: false,
            tax_percentage: 0,
        }),
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

// Search & Category Filters
const searchQuery = ref('');
const selectedCategoryId = ref(null);

const filteredProducts = computed(() => {
    return props.products.filter((product) => {
        const matchesCategory =
            selectedCategoryId.value === null || product.category_id === selectedCategoryId.value;
        const matchesSearch =
            !searchQuery.value ||
            product.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return matchesCategory && matchesSearch;
    });
});

const taxAmount = computed(() => props.setting.tax_enabled ? Math.round(cart.subtotal * Number(props.setting.tax_percentage) * 100) / 10000 : 0);
const serviceChargeAmount = computed(() => props.setting.service_charge_enabled ? Math.round(cart.subtotal * Number(props.setting.service_charge_percentage) * 100) / 10000 : 0);
const totalPayable = computed(() => {
    return cart.subtotal + taxAmount.value + serviceChargeAmount.value;
});

// Modals State
const showMobileCartSheet = ref(false);
const showCashModal = ref(false);
const showQrisModal = ref(false);
const showReceiptModal = ref(false);
const completedTransaction = ref(null);
const isProcessing = ref(false);
const errorMessage = ref('');
const checkoutKey = ref(null);

// Add to Cart
const handleAddToCart = (product) => {
    cart.addItem(product, 1);
};

// Open payment modal according to selected method
const openPayment = () => {
    if (cart.items.length === 0) return;
    showMobileCartSheet.value = false;

    if (cart.paymentMethod === 'cash') {
        showCashModal.value = true;
    } else {
        showQrisModal.value = true;
    }
};

// Process Checkout with Backend
const submitCheckout = async (paymentPayload = {}) => {
    if (isProcessing.value) return;
    try {
        assertOnline();
    } catch (error) {
        errorMessage.value = error.message;
        alert(error.message);
        return;
    }
    isProcessing.value = true;
    errorMessage.value = '';
    checkoutKey.value ||= generateIdempotencyKey();

    try {
        const payload = {
            items: cart.items.map((item) => ({
                product_id: item.product.id,
                quantity: item.quantity,
                note: item.note || '',
            })),
            payment_method: cart.paymentMethod,
            amount_paid: cart.paymentMethod === 'cash' ? paymentPayload.cashReceived : totalPayable.value,
            idempotency_key: checkoutKey.value,
        };

        const response = await axios.post('/pos/checkout', payload);

        // Transaksi Berhasil
        completedTransaction.value = response.data.transaction;
        showCashModal.value = false;
        showQrisModal.value = false;
        showReceiptModal.value = true;
        cart.clearCart();
        checkoutKey.value = null;
    } catch (error) {
        errorMessage.value = error.code === 'OFFLINE_MUTATION'
            ? error.message
            : error.response?.data?.message || 'Koneksi terputus atau transaksi gagal. Keranjang tetap tersimpan. Periksa transaksi sebelum mencoba kembali.';
        alert(errorMessage.value);
    } finally {
        isProcessing.value = false;
    }
};

const handleNewTransaction = () => {
    showReceiptModal.value = false;
    completedTransaction.value = null;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Terminal Kasir POS - Foodislice" />

        <div class="p-3 sm:p-6 lg:p-8 w-full max-w-[1600px] mx-auto">
            <!-- Headline Kasir diatas halaman kasir -->
            <div class="mb-4 sm:mb-6">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">Kasir</h1>
                <p class="text-xs text-text-muted mt-0.5">Terminal operasional kasir dan pemesanan menu gerai</p>
            </div>

            <!-- 12-Column Grid Workspace matching Stitch -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5 sm:gap-6">
                <!-- Left Column (8 cols): Catalog, Filter, Search -->
                <div class="xl:col-span-8 flex flex-col gap-4 sm:gap-5">
                    <!-- Top Search Bar & Quick Metrics Ribbon -->
                    <POSHeader
                        v-model:searchQuery="searchQuery"
                        :today-orders-count="todayOrdersCount"
                        :register-total="registerTotal"
                    />

                    <!-- Category Pills Horizontal Scroll -->
                    <CategoryPills
                        :categories="categories"
                        :selected-category-id="selectedCategoryId"
                        @select-category="selectedCategoryId = $event"
                    />

                    <!-- Product Grid (2 cols on mobile, 3 cols on sm/md/xl) -->
                    <div
                        v-if="filteredProducts.length > 0"
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-3 gap-2.5 sm:gap-4 md:gap-5"
                    >
                        <ProductCard
                            v-for="product in filteredProducts"
                            :key="product.id"
                            :product="product"
                            @add-to-cart="handleAddToCart"
                        />
                    </div>

                    <!-- Empty State if search/filter doesn't match -->
                    <div
                        v-else
                        class="bg-surface rounded-2xl p-8 sm:p-12 text-center border border-border-subtle flex flex-col items-center justify-center gap-2"
                    >
                        <p class="text-sm font-bold text-text-primary">Produk tidak ditemukan</p>
                        <p class="text-xs text-text-muted">Coba kata kunci lain atau pilih semua kategori.</p>
                        <button
                            type="button"
                            @click="searchQuery = ''; selectedCategoryId = null"
                            class="mt-2 text-xs font-bold text-primary underline cursor-pointer"
                        >
                            Reset Pencarian
                        </button>
                    </div>
                </div>

                <!-- Right Column (4 cols on desktop): Sticky Invoice Panel -->
                <div class="hidden xl:block xl:col-span-4">
                    <div class="sticky top-20">
                        <InvoicePanel
                            :active-cashier="{
                                name: page.props.auth?.user?.name || 'Kasir',
                                shiftName: activeShift?.notes || (activeShift ? 'Shift Aktif' : 'Belum Aktif'),
                            }"
                            :tax-rate="0"
                            :setting="setting"
                            @open-payment-modal="openPayment"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Mobile Bottom Cart Bar (Visible on < xl / Smartphone) -->
        <StickyBottomBar z-index="z-30">
            <button
                type="button"
                @click="showMobileCartSheet = true"
                class="w-full py-3 px-4 rounded-xl bg-primary text-white font-bold text-xs sm:text-sm flex items-center justify-between shadow-lg shadow-primary/30 active:scale-98 transition cursor-pointer"
            >
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center text-xs font-black">
                        {{ cart.totalItems }}
                    </div>
                    <span>Keranjang Pesanan</span>
                </div>
                <div class="flex items-center gap-1 font-black text-sm">
                    <span>{{ formatRupiah(totalPayable) }}</span>
                    <span>&rarr;</span>
                </div>
            </button>
        </StickyBottomBar>

        <!-- Mobile Bottom Sheet for Cart Drawer -->
        <BottomSheet
            :show="showMobileCartSheet"
            title="Keranjang Pesanan"
            @close="showMobileCartSheet = false"
        >
            <InvoicePanel
                :active-cashier="{
                    name: page.props.auth?.user?.name || 'Kasir',
                    shiftName: activeShift?.notes || (activeShift ? 'Shift Aktif' : 'Belum Aktif'),
                }"
                :setting="setting"
                @open-payment-modal="openPayment"
            />
        </BottomSheet>

        <!-- Payment Modals -->
        <CashPaymentModal
            :show="showCashModal"
            :total-amount="totalPayable"
            :loading="isProcessing"
            @close="showCashModal = false"
            @confirm-payment="submitCheckout"
        />

        <QrisPaymentModal
            :show="showQrisModal"
            :total-amount="totalPayable"
            :loading="isProcessing"
            @close="showQrisModal = false"
            @confirm-payment="submitCheckout"
        />

        <!-- Success Receipt Modal -->
        <ReceiptModal
            :show="showReceiptModal"
            :transaction="completedTransaction"
            :store-name="setting.store_name"
            @close="showReceiptModal = false"
            @new-transaction="handleNewTransaction"
        />
    </AuthenticatedLayout>
</template>
