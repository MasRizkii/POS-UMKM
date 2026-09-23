<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TopOperationalBar from '@/Modules/Dashboard/Components/TopOperationalBar.vue';
import StatCard from '@/Modules/Dashboard/Components/StatCard.vue';
import SalesTrendChart from '@/Modules/Dashboard/Components/SalesTrendChart.vue';
import TopProductsCard from '@/Modules/Dashboard/Components/TopProductsCard.vue';
import RecentTransactionsCard from '@/Modules/Dashboard/Components/RecentTransactionsCard.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { 
    Wallet, 
    Receipt, 
    Banknote, 
    QrCode 
} from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';

const { formatRupiah } = useCurrency();
const page = usePage();

defineProps({
    metrics: {
        type: Object,
        default: () => ({
            omzet: 0,
            count: 0,
            cash: 0,
            qris: 0,
        }),
    },
    activeShift: {
        type: Object,
        default: null,
    },
    topProducts: {
        type: Array,
        default: () => [],
    },
    recentTransactions: {
        type: Array,
        default: () => [],
    },
    hourlyData: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboard POS - Foodislice" />

        <div class="p-4 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-[1600px] mx-auto">
            <!-- Top Operational Header Bar -->
            <TopOperationalBar
                :user-name="page.props.auth?.user?.name || 'Kasir'"
                :active-shift="activeShift"
            />

            <!-- 4 Key Performance Metrics Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
                <!-- Omzet Card -->
                <StatCard
                    title="Omzet Hari Ini"
                    :value="formatRupiah(metrics.omzet)"
                    trend="+14% dari kemarin"
                    :icon="Wallet"
                    variant="primary"
                />

                <!-- Total Transaksi Card -->
                <StatCard
                    title="Total Transaksi"
                    :value="metrics.count"
                    suffix="Pesanan"
                    :icon="Receipt"
                    variant="default"
                />

                <!-- Tunai / Cash Card -->
                <StatCard
                    title="Penerimaan Tunai"
                    :value="formatRupiah(metrics.cash)"
                    :icon="Banknote"
                    variant="secondary"
                />

                <!-- QRIS Fisik Card -->
                <StatCard
                    title="Pembayaran QRIS"
                    :value="formatRupiah(metrics.qris)"
                    :icon="QrCode"
                    variant="tertiary"
                />
            </div>

            <!-- Main Two-Column Workspace (approx 65% Left / 35% Right on Desktop) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Left Column (8 cols): Chart + Recent Transactions -->
                <div class="lg:col-span-8 flex flex-col gap-6">
                    <SalesTrendChart :hourly-data="hourlyData" />
                    <RecentTransactionsCard :transactions="recentTransactions" />
                </div>

                <!-- Right Column (4 cols): Product Leaderboard -->
                <div class="lg:col-span-4 flex flex-col gap-6">
                    <TopProductsCard :products="topProducts" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
