<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransactionDetailModal from '@/Modules/Transaction/Components/TransactionDetailModal.vue';
import VoidModal from '@/Modules/Transaction/Components/VoidModal.vue';
import ResponsivePagination from '@/Components/Layout/ResponsivePagination.vue';
import CustomDatePicker from '@/Components/Common/CustomDatePicker.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDateTime } from '@/Composables/useDateTime';
import { 
    Receipt, 
    Eye, 
    QrCode, 
    Banknote, 
    Ban, 
    Search, 
    Download, 
    RotateCcw,
    CheckCircle
} from 'lucide-vue-next';

const { formatRupiah } = useCurrency();
const { formatDate, formatTime, timeZoneLabel } = useDateTime();

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({
            today_sales_count: 0,
            today_sales_amount: 0,
            today_void_count: 0,
            today_void_percentage: 0,
        }),
    },
    counts: {
        type: Object,
        default: () => ({
            all: 0,
            cash: 0,
            qris: 0,
            completed: 0,
            void: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedPayment = ref(props.filters.payment_method || '');
const selectedStatus = ref(props.filters.status || '');
const selectedDatePreset = ref(props.filters.date_preset || 'all');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const showCustomDate = ref(props.filters.date_preset === 'custom');

const selectedTransaction = ref(null);
const showDetailModal = ref(false);
const showVoidModal = ref(false);

const applyFilters = () => {
    router.get(
        route('transactions.index'),
        {
            search: searchQuery.value,
            payment_method: selectedPayment.value,
            status: selectedStatus.value,
            date_preset: selectedDatePreset.value,
            start_date: selectedDatePreset.value === 'custom' ? startDate.value : undefined,
            end_date: selectedDatePreset.value === 'custom' ? endDate.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const handlePresetChange = () => {
    if (selectedDatePreset.value === 'custom') {
        showCustomDate.value = true;
    } else {
        showCustomDate.value = false;
        applyFilters();
    }
};

const applyCustomDate = () => {
    if (!startDate.value || !endDate.value) return;
    applyFilters();
};

const filterPayment = (method) => {
    selectedPayment.value = method;
    applyFilters();
};

const filterStatus = (status) => {
    selectedStatus.value = status;
    applyFilters();
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedPayment.value = '';
    selectedStatus.value = '';
    selectedDatePreset.value = 'all';
    startDate.value = '';
    endDate.value = '';
    showCustomDate.value = false;
    router.get(route('transactions.index'));
};

const viewDetail = (trx) => {
    selectedTransaction.value = trx;
    showDetailModal.value = true;
};

const openVoid = (trx) => {
    selectedTransaction.value = trx;
    showDetailModal.value = false;
    showVoidModal.value = true;
};

const exportCsv = () => {
    const params = new URLSearchParams({
        date_preset: selectedDatePreset.value,
        start_date: startDate.value,
        end_date: endDate.value,
    });
    window.location.href = `/reports/export-csv?${params.toString()}`;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Transaksi - Foodislice POS" />

        <div class="p-3 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-[1600px] mx-auto">
            <!-- Header Section with KPI Summary Cards matching Stitch -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-bold tracking-tight text-on-surface">Transaksi</h1>
                    <p class="text-xs text-text-muted mt-1">
                        Pantau riwayat penjualan nota kasir, cetak ulang struk, dan audit pembatalan transaksi
                    </p>
                </div>

                <!-- KPI Summary Cards on Right -->
                <div class="flex flex-wrap items-center gap-3 self-start lg:self-auto">
                    <!-- Penjualan Hari Ini -->
                    <div class="bg-surface shadow-xs border border-border-subtle rounded-xl px-4 py-2 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-secondary-fixed/40 text-secondary flex items-center justify-center">
                            <Receipt class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-text-muted uppercase">Penjualan Hari Ini</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="font-bold text-sm text-on-surface">{{ summary.today_sales_count }}</span>
                                <span class="text-xs text-text-muted">({{ formatRupiah(summary.today_sales_amount) }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Transaksi Dibatalkan -->
                    <div class="bg-surface shadow-xs border border-border-subtle rounded-xl px-4 py-2 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-error-container text-on-error-container flex items-center justify-center">
                            <Ban class="w-4 h-4 stroke-[2.2]" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold text-text-muted uppercase">Transaksi Dibatalkan</span>
                            <div class="flex items-baseline gap-1.5">
                                <span class="font-bold text-sm text-error">{{ summary.today_void_count }} Transaksi</span>
                                <span class="text-xs text-text-muted">({{ summary.today_void_percentage }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Toolbar matching Stitch -->
            <div class="bg-surface rounded-xl shadow-xs border border-border-subtle p-4 flex flex-col gap-4">
                <!-- Row 1: Search, Date Preset, Export & Refresh -->
                <div class="flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-3">
                    <div class="flex flex-col sm:flex-row flex-1 items-stretch sm:items-center gap-2 max-w-2xl">
                        <!-- Search Box -->
                        <div class="relative w-full">
                            <Search class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                @keyup.enter="applyFilters"
                                placeholder="Cari nomor invoice (contoh: #POS-0024)..."
                                class="w-full pl-10 pr-3 py-2 bg-background rounded-xl text-xs text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>

                        <!-- Date Preset Dropdown -->
                        <div class="relative shrink-0">
                            <select
                                v-model="selectedDatePreset"
                                @change="handlePresetChange"
                                class="h-9 px-3 pr-8 bg-background border border-border-subtle rounded-xl text-xs font-semibold text-on-surface cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary w-full sm:w-auto"
                            >
                                <option value="all">Semua Waktu</option>
                                <option value="today">Hari Ini</option>
                                <option value="yesterday">Kemarin</option>
                                <option value="7days">7 Hari Terakhir</option>
                                <option value="month">Bulan Ini</option>
                                <option value="custom">Kustom Tanggal</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right actions: Export CSV & Refresh -->
                    <div class="flex items-center gap-2 justify-end">
                        <button
                            type="button"
                            @click="exportCsv"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-background border border-border-subtle text-on-surface text-xs font-semibold hover:bg-surface-container-low transition-colors cursor-pointer"
                        >
                            <Download class="w-4 h-4 text-primary" />
                            <span>Export CSV</span>
                        </button>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="p-2 rounded-xl bg-background border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer"
                            title="Reset Filter"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Row 1.5: Custom Date Picker Popover (Only when Kustom Tanggal is selected) -->
                <div v-if="showCustomDate" class="pt-2 border-t border-border-subtle/50 flex flex-wrap items-center gap-2 animate-in fade-in">
                    <span class="text-xs font-bold text-text-muted">Rentang Tanggal:</span>
                    <CustomDatePicker v-model="startDate" placeholder="Tanggal Mulai" />
                    <span class="text-xs text-text-muted">s/d</span>
                    <CustomDatePicker v-model="endDate" placeholder="Tanggal Sampai" />
                    <button
                        type="button"
                        @click="applyCustomDate"
                        class="h-9 px-4 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-dark shadow-xs transition active:scale-95 cursor-pointer"
                    >
                        Terapkan
                    </button>
                </div>

                <!-- Row 2: Method and Status Filter Chips matching Stitch -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-border-subtle/40">
                    <!-- Metode Pembayaran Chips -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted mr-1">Metode:</span>
                        <button
                            type="button"
                            @click="filterPayment('')"
                            class="px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 transition-all cursor-pointer"
                            :class="selectedPayment === '' ? 'bg-primary-subtle text-primary font-bold shadow-2xs' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            <span>Semua</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="selectedPayment === '' ? 'bg-primary text-white' : 'bg-surface-container text-text-muted'">
                                {{ counts.all }}
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="filterPayment('cash')"
                            class="px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 transition-all cursor-pointer"
                            :class="selectedPayment === 'cash' ? 'bg-primary-subtle text-primary font-bold shadow-2xs' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            <span>Tunai</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="selectedPayment === 'cash' ? 'bg-primary text-white' : 'bg-surface-container text-text-muted'">
                                {{ counts.cash }}
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="filterPayment('qris')"
                            class="px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1 transition-all cursor-pointer"
                            :class="selectedPayment === 'qris' ? 'bg-primary-subtle text-primary font-bold shadow-2xs' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            <span>QRIS</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="selectedPayment === 'qris' ? 'bg-primary text-white' : 'bg-surface-container text-text-muted'">
                                {{ counts.qris }}
                            </span>
                        </button>
                    </div>

                    <!-- Status Chips (Void diganti jadi Batal/Dibatalkan) -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted mr-1">Status:</span>
                        <button
                            type="button"
                            @click="filterStatus('')"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                            :class="selectedStatus === '' ? 'bg-surface-container-high text-on-surface font-bold' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            Semua ({{ counts.all }})
                        </button>
                        <button
                            type="button"
                            @click="filterStatus('completed')"
                            class="px-3 py-1 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="selectedStatus === 'completed' ? 'bg-secondary-fixed/50 text-secondary font-bold' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            <span class="w-2 h-2 rounded-full bg-secondary"></span>
                            <span>Selesai ({{ counts.completed }})</span>
                        </button>
                        <button
                            type="button"
                            @click="filterStatus('void')"
                            class="px-3 py-1 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
                            :class="selectedStatus === 'void' ? 'bg-error-container text-on-error-container font-bold' : 'bg-background border border-border-subtle text-text-muted hover:text-on-surface'"
                        >
                            <span class="w-2 h-2 rounded-full bg-error"></span>
                            <span>Dibatalkan ({{ counts.void }})</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Card Container matching Stitch -->
            <div class="bg-surface rounded-xl shadow-xs border border-border-subtle overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-surface-container-low text-text-muted text-[11px] font-bold uppercase tracking-wider border-b border-border-subtle">
                                <th class="px-4 py-3.5">Nomor Invoice</th>
                                <th class="px-4 py-3.5">Waktu &amp; Tanggal</th>
                                <th class="px-4 py-3.5">Kasir / Shift</th>
                                <th class="px-4 py-3.5">Item Pesanan</th>
                                <th class="px-4 py-3.5">Metode Bayar</th>
                                <th class="px-4 py-3.5 text-right">Total Tagihan</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle/60 text-on-surface">
                            <tr
                                v-for="trx in transactions.data"
                                :key="trx.id"
                                class="hover:bg-surface-container-low/60 transition-colors"
                                :class="{ 'bg-error-container/10': trx.status === 'void' }"
                            >
                                <!-- Invoice -->
                                <td class="px-4 py-3 font-bold text-primary">
                                    <div class="flex items-center gap-1.5">
                                        <Receipt class="w-4 h-4 text-primary" />
                                        <span>#{{ trx.invoice_number }}</span>
                                    </div>
                                </td>

                                <!-- Waktu & Tanggal (2 lines) -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-on-surface font-semibold text-xs block">{{ formatDate(trx.created_at) }}</span>
                                    <span class="text-text-muted text-[11px] block">{{ formatTime(trx.created_at) }} {{ timeZoneLabel }}</span>
                                </td>

                                <!-- Kasir -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-on-surface font-semibold block">{{ trx.user?.name || 'Kasir' }}</span>
                                    <span class="text-[10px] text-text-muted block">Shift #{{ trx.shift_id || '1' }}</span>
                                </td>

                                <!-- Item Pesanan -->
                                <td class="px-4 py-3 max-w-[200px]">
                                    <div class="truncate text-on-surface font-medium" :title="trx.items?.map(i => `${i.quantity}x ${i.product_name_snapshot}`).join(', ')">
                                        {{ trx.items?.map(i => `${i.quantity}x ${i.product_name_snapshot}`).join(', ') }}
                                    </div>
                                </td>

                                <!-- Metode Bayar -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        v-if="trx.payment_method === 'qris'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-tertiary-fixed text-tertiary font-bold text-[11px]"
                                    >
                                        <QrCode class="w-3.5 h-3.5" />
                                        <span>QRIS</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-container text-on-surface-variant font-bold text-[11px]"
                                    >
                                        <Banknote class="w-3.5 h-3.5" />
                                        <span>Tunai</span>
                                    </span>
                                </td>

                                <!-- Total Tagihan -->
                                <td class="px-4 py-3 text-right font-bold text-on-surface whitespace-nowrap">
                                    {{ formatRupiah(trx.total_amount) }}
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span
                                        v-if="trx.status === 'completed'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-fixed/50 text-secondary font-bold text-[11px]"
                                    >
                                        <CheckCircle class="w-3.5 h-3.5" />
                                        <span>Selesai</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-bold text-[11px]"
                                    >
                                        <Ban class="w-3.5 h-3.5" />
                                        <span>Dibatalkan</span>
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <button
                                        type="button"
                                        @click="viewDetail(trx)"
                                        class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-bold shadow-xs hover:bg-primary-dark transition-all flex items-center gap-1 mx-auto active:scale-95 cursor-pointer"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Detail</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="py-12 text-center text-text-muted">
                                    <Receipt class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                                    <p class="font-bold text-sm">Tidak ada transaksi ditemukan</p>
                                    <p class="text-xs text-text-muted">Coba ubah filter pencarian atau tanggal transaksi.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Responsive Card View (< md) -->
                <div class="block md:hidden divide-y divide-border-subtle">
                    <div
                        v-for="trx in transactions.data"
                        :key="trx.id"
                        class="p-4 flex flex-col gap-2.5"
                        :class="{ 'bg-rose-50/40': trx.status === 'void' }"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <Receipt class="w-4 h-4 text-primary" />
                                <span class="font-bold text-sm text-primary">#{{ trx.invoice_number }}</span>
                            </div>
                            <span
                                v-if="trx.status === 'completed'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-fixed/50 text-secondary font-bold text-[10px]"
                            >
                                <CheckCircle class="w-3 h-3" />
                                <span>Selesai</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-bold text-[10px]"
                            >
                                <Ban class="w-3 h-3" />
                                <span>Dibatalkan</span>
                            </span>
                        </div>

                        <div class="text-xs text-text-muted">
                            {{ formatDate(trx.created_at) }}, {{ formatTime(trx.created_at) }} {{ timeZoneLabel }} • Oleh: {{ trx.user?.name || 'Kasir' }}
                        </div>

                        <div class="text-xs text-on-surface line-clamp-2">
                            {{ trx.items?.map(i => `${i.quantity}x ${i.product_name_snapshot}`).join(', ') }}
                        </div>

                        <div class="pt-2 border-t border-border-subtle flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="trx.payment_method === 'qris'"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-tertiary-fixed text-tertiary font-bold text-[10px]"
                                >
                                    <QrCode class="w-3 h-3" />
                                    <span>QRIS</span>
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-container text-on-surface-variant font-bold text-[10px]"
                                >
                                    <Banknote class="w-3 h-3" />
                                    <span>Tunai</span>
                                </span>
                                <span class="font-bold text-sm text-on-surface">
                                    {{ formatRupiah(trx.total_amount) }}
                                </span>
                            </div>

                            <button
                                type="button"
                                @click="viewDetail(trx)"
                                class="px-3 py-1.5 rounded-lg bg-primary text-white text-xs font-bold flex items-center gap-1"
                            >
                                <Eye class="w-3.5 h-3.5" />
                                <span>Detail</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="transactions.data.length === 0" class="py-12 text-center text-text-muted px-4">
                        <Receipt class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                        <p class="font-bold text-sm">Tidak ada transaksi ditemukan</p>
                    </div>
                </div>

                <!-- Pagination Footer matching Stitch -->
                <div class="p-4 border-t border-border-subtle bg-surface flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-text-muted">
                        Menampilkan <strong class="text-on-surface">{{ transactions.from || 0 }} - {{ transactions.to || 0 }}</strong> dari <strong class="text-on-surface">{{ transactions.total }}</strong> transaksi
                    </span>
                    <ResponsivePagination :links="transactions.links" />
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <TransactionDetailModal
            :show="showDetailModal"
            :transaction="selectedTransaction"
            @close="showDetailModal = false"
            @request-void="openVoid"
        />

        <!-- Void Modal -->
        <VoidModal
            :show="showVoidModal"
            :transaction="selectedTransaction"
            @close="showVoidModal = false"
            @void-success="showVoidModal = false"
        />
    </AuthenticatedLayout>
</template>
