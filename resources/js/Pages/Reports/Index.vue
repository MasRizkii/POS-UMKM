<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsivePagination from '@/Components/Layout/ResponsivePagination.vue';
import CustomDatePicker from '@/Components/Common/CustomDatePicker.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDateTime } from '@/Composables/useDateTime';
import { 
    BarChart3, 
    Download, 
    Calendar, 
    Receipt, 
    Banknote, 
    QrCode, 
    ShoppingBag, 
    TrendingUp, 
    UtensilsCrossed, 
    CheckCircle
} from 'lucide-vue-next';

const { formatRupiah } = useCurrency();
const { formatDate, formatTime, timeZoneLabel } = useDateTime();

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    hourlySales: {
        type: Array,
        default: () => [],
    },
    topProducts: {
        type: Array,
        default: () => [],
    },
    transactions: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const datePreset = ref(props.filters.date_preset || 'month');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const showCustomDate = ref(props.filters.date_preset === 'custom');

const dateOptions = [
    { value: 'today', label: 'Hari Ini' },
    { value: 'yesterday', label: 'Kemarin' },
    { value: '7days', label: '7 Hari Terakhir' },
    { value: 'month', label: 'Bulan Ini' },
    { value: 'custom', label: 'Kustom' },
];

const applyFilter = (preset) => {
    datePreset.value = preset;
    if (preset === 'custom') {
        showCustomDate.value = true;
        return;
    }
    showCustomDate.value = false;
    router.get(
        route('reports.index'),
        { date_preset: preset },
        { preserveState: true, replace: true }
    );
};

const applyCustomDate = () => {
    if (!startDate.value || !endDate.value) return;
    router.get(
        route('reports.index'),
        {
            date_preset: 'custom',
            start_date: startDate.value,
            end_date: endDate.value,
        },
        { preserveState: true, replace: true }
    );
};

const exportCsv = () => {
    const params = new URLSearchParams({
        date_preset: datePreset.value,
        start_date: startDate.value,
        end_date: endDate.value,
    });
    window.location.href = `/reports/export-csv?${params.toString()}`;
};

// Kalkulasi Persentase Pembayaran untuk Donut Chart
const totalPaymentAmount = computed(() => {
    return (props.metrics.cash || 0) + (props.metrics.qris || 0);
});

const qrisPercentage = computed(() => {
    if (!totalPaymentAmount.value) return 50;
    return Math.round(((props.metrics.qris || 0) / totalPaymentAmount.value) * 100);
});

const cashPercentage = computed(() => {
    if (!totalPaymentAmount.value) return 50;
    return 100 - qrisPercentage.value;
});

// SVG Donut Stroke Dash calculations (Circumference of r=14 is ~88)
const circumference = 88;
const qrisStrokeDash = computed(() => {
    return `${(qrisPercentage.value / 100) * circumference} ${circumference}`;
});
const cashStrokeDash = computed(() => {
    return `${(cashPercentage.value / 100) * circumference} ${circumference}`;
});
const cashStrokeOffset = computed(() => {
    return `-${(qrisPercentage.value / 100) * circumference}`;
});

// Max hourly amount for chart normalisation
const maxHourlyAmount = computed(() => {
    const max = Math.max(...props.hourlySales.map(s => s.amount), 10000);
    return max;
});

// Line Chart Calculations (SVG Coordinates)
const chartWidth = 600;
const chartHeight = 200;
const paddingX = 40;
const paddingTop = 25;
const paddingBottom = 35;

const activeHoverIndex = ref(null);

const chartPoints = computed(() => {
    const data = props.hourlySales;
    if (!data || data.length === 0) return [];
    
    const count = data.length;
    const innerWidth = chartWidth - paddingX * 2;
    const innerHeight = chartHeight - paddingTop - paddingBottom;
    const max = maxHourlyAmount.value;

    return data.map((item, index) => {
        const x = paddingX + (count > 1 ? (index / (count - 1)) * innerWidth : innerWidth / 2);
        const y = (chartHeight - paddingBottom) - (item.amount / max) * innerHeight;
        return {
            x,
            y,
            hour: item.hour,
            amount: item.amount,
        };
    });
});

// Generate smooth SVG path string (Catmull-Rom or cubic Bezier)
const linePath = computed(() => {
    const pts = chartPoints.value;
    if (pts.length === 0) return '';
    if (pts.length === 1) return `M ${pts[0].x} ${pts[0].y}`;

    let d = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 0; i < pts.length - 1; i++) {
        const p0 = pts[i === 0 ? 0 : i - 1];
        const p1 = pts[i];
        const p2 = pts[i + 1];
        const p3 = pts[i + 2 < pts.length ? i + 2 : i + 1];

        const cp1x = p1.x + (p2.x - p0.x) / 6;
        const cp1y = p1.y + (p2.y - p0.y) / 6;
        const cp2x = p2.x - (p3.x - p1.x) / 6;
        const cp2y = p2.y - (p3.y - p1.y) / 6;

        d += ` C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2.x} ${p2.y}`;
    }
    return d;
});

// Area path for gradient fill under the line
const areaPath = computed(() => {
    const pts = chartPoints.value;
    if (pts.length === 0) return '';
    const baseLine = chartHeight - paddingBottom;
    const firstX = pts[0].x;
    const lastX = pts[pts.length - 1].x;
    return `${linePath.value} L ${lastX} ${baseLine} L ${firstX} ${baseLine} Z`;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Laporan Penjualan - Foodislice POS" />

        <div class="p-3 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-[1600px] mx-auto">
            <!-- Header Section matching Stitch -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold tracking-tight text-on-surface">Laporan Penjualan</h1>
                        <span class="px-2.5 py-0.5 rounded-full bg-surface-container text-on-surface-variant text-xs font-semibold">
                            Shift 1 &amp; 2
                        </span>
                    </div>
                    <p class="text-xs text-text-muted mt-1">
                        Pantau performa omzet, volume transaksi kasir, tren jam sibuk, dan komposisi pembayaran gerai
                    </p>
                </div>

                <!-- Export Action Button -->
                <div class="flex items-center gap-3 self-start lg:self-auto">
                    <button
                        type="button"
                        @click="exportCsv"
                        class="h-10 px-4 rounded-xl bg-primary text-white shadow-md hover:bg-primary-dark font-bold text-xs flex items-center gap-2 transition-all cursor-pointer active:scale-95"
                    >
                        <Download class="w-4 h-4" />
                        <span>Export CSV / Excel (.csv)</span>
                    </button>
                </div>
            </div>

            <!-- Filter Controls Bar matching Stitch with Custom Date Picker -->
            <div class="bg-surface rounded-xl p-4 shadow-xs border border-border-subtle flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                <!-- Preset Buttons -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 xl:pb-0">
                    <button
                        v-for="opt in dateOptions"
                        :key="opt.value"
                        type="button"
                        @click="applyFilter(opt.value)"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition cursor-pointer"
                        :class="datePreset === opt.value
                            ? 'bg-primary-subtle text-primary font-bold shadow-2xs'
                            : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container'"
                    >
                        {{ opt.label }}
                    </button>
                </div>

                <!-- Active Date Range Label & Custom Date Picker Popup -->
                <div class="flex items-center flex-wrap gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-background rounded-lg border border-border-subtle text-on-surface text-xs font-semibold">
                        <Calendar class="w-4 h-4 text-primary" />
                        <span>{{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}</span>
                    </div>

                    <!-- Custom Date Input Trigger with CustomDatePicker -->
                    <div v-if="showCustomDate" class="flex flex-wrap items-center gap-2 animate-in fade-in">
                        <CustomDatePicker v-model="startDate" placeholder="Mulai" />
                        <span class="text-xs text-text-muted">s/d</span>
                        <CustomDatePicker v-model="endDate" placeholder="Sampai" />
                        <button
                            type="button"
                            @click="applyCustomDate"
                            class="h-9 px-4 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-dark shadow-xs transition active:scale-95 cursor-pointer"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5 KPI Summary Cards matching Stitch -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- 1. Omzet Bersih -->
                <div class="bg-surface p-4 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Omzet Bersih</span>
                            <div class="w-7 h-7 rounded-lg bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                                <Receipt class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 mb-1">
                            <div class="text-lg sm:text-xl font-bold tracking-tight text-on-surface whitespace-nowrap">
                                {{ formatRupiah(metrics.omzet) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-secondary text-xs font-semibold pt-2 border-t border-border-subtle/50">
                        <TrendingUp class="w-3.5 h-3.5" />
                        <span>100% Tercatat (Batal Dikecualikan)</span>
                    </div>
                </div>

                <!-- 2. Jumlah Transaksi -->
                <div class="bg-surface p-4 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Jumlah Transaksi</span>
                            <div class="w-7 h-7 rounded-lg bg-surface-container text-tertiary flex items-center justify-center shrink-0">
                                <BarChart3 class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 mb-1">
                            <div class="text-lg sm:text-xl font-bold tracking-tight text-on-surface">
                                {{ metrics.count }} <span class="text-xs font-normal text-text-muted">Pesanan</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-text-muted pt-2 border-t border-border-subtle/50">
                        Rata-rata {{ formatRupiah(metrics.atv) }}/trx
                    </div>
                </div>

                <!-- 3. Penerimaan Tunai -->
                <div class="bg-surface p-4 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Tunai (Cash)</span>
                            <div class="w-7 h-7 rounded-lg bg-secondary-fixed/40 text-secondary flex items-center justify-center shrink-0">
                                <Banknote class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 mb-1">
                            <div class="text-lg sm:text-xl font-bold tracking-tight text-on-surface whitespace-nowrap">
                                {{ formatRupiah(metrics.cash) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-text-muted pt-2 border-t border-border-subtle/50">
                        {{ metrics.cash_count || 0 }} pesanan lunas
                    </div>
                </div>

                <!-- 4. Pembayaran QRIS (Fisik) -->
                <div class="bg-surface p-4 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">QRIS Fisik</span>
                            <div class="w-7 h-7 rounded-lg bg-primary-subtle text-primary flex items-center justify-center shrink-0">
                                <QrCode class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 mb-1">
                            <div class="text-lg sm:text-xl font-bold tracking-tight text-on-surface whitespace-nowrap">
                                {{ formatRupiah(metrics.qris) }}
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-text-muted pt-2 border-t border-border-subtle/50">
                        {{ metrics.qris_count || 0 }} pesanan terverifikasi
                    </div>
                </div>

                <!-- 5. Total Produk Terjual -->
                <div class="bg-surface p-4 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-text-muted uppercase tracking-wider">Produk Terjual</span>
                            <div class="w-7 h-7 rounded-lg bg-surface-container text-on-surface flex items-center justify-center shrink-0">
                                <ShoppingBag class="w-4 h-4" />
                            </div>
                        </div>
                        <div class="mt-2 mb-1">
                            <div class="text-lg sm:text-xl font-bold tracking-tight text-on-surface">
                                {{ metrics.items_sold }} <span class="text-xs font-normal text-text-muted">Item</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-xs text-text-muted pt-2 border-t border-border-subtle/50">
                        Menu siap konsumsi
                    </div>
                </div>
            </div>

            <!-- Analytics Charts Section (2 Columns: Hourly Line Chart & Payment Ratio) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Column 1: Tren Penjualan & Jam Sibuk Kasir (Line Chart) -->
                <div class="lg:col-span-2 bg-surface p-5 sm:p-6 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted block">Dinamika Waktu Operasional</span>
                                <h3 class="text-base font-bold text-on-surface">Tren Penjualan &amp; Jam Sibuk Kasir</h3>
                            </div>
                            <div class="flex items-center gap-1.5 bg-background px-3 py-1 rounded-lg border border-border-subtle self-start sm:self-auto">
                                <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
                                <span class="text-xs font-semibold text-on-surface">Omzet / Jam ({{ timeZoneLabel }})</span>
                            </div>
                        </div>

                        <!-- Smooth SVG Line Chart -->
                        <div class="relative w-full overflow-hidden">
                            <svg
                                class="w-full h-56"
                                :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                                preserveAspectRatio="none"
                            >
                                <defs>
                                    <linearGradient id="lineChartGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#ad2b08" stop-opacity="0.3" />
                                        <stop offset="100%" stop-color="#ad2b08" stop-opacity="0.0" />
                                    </linearGradient>
                                </defs>

                                <!-- Grid reference lines -->
                                <line
                                    :x1="paddingX"
                                    :y1="paddingTop"
                                    :x2="chartWidth - paddingX"
                                    :y2="paddingTop"
                                    stroke="#e2e8f0"
                                    stroke-dasharray="4 4"
                                    stroke-width="1"
                                />
                                <line
                                    :x1="paddingX"
                                    :y1="(paddingTop + (chartHeight - paddingBottom)) / 2"
                                    :x2="chartWidth - paddingX"
                                    :y2="(paddingTop + (chartHeight - paddingBottom)) / 2"
                                    stroke="#e2e8f0"
                                    stroke-dasharray="4 4"
                                    stroke-width="1"
                                />
                                <line
                                    :x1="paddingX"
                                    :y1="chartHeight - paddingBottom"
                                    :x2="chartWidth - paddingX"
                                    :y2="chartHeight - paddingBottom"
                                    stroke="#cbd5e1"
                                    stroke-width="1.5"
                                />

                                <!-- Gradient Area under line -->
                                <path
                                    :d="areaPath"
                                    fill="url(#lineChartGradient)"
                                />

                                <!-- Main Line Path -->
                                <path
                                    :d="linePath"
                                    fill="none"
                                    stroke="#ad2b08"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <!-- Data Point Circles -->
                                <g v-for="(pt, idx) in chartPoints" :key="idx">
                                    <!-- Interactive larger hit circle -->
                                    <circle
                                        :cx="pt.x"
                                        :cy="pt.y"
                                        r="14"
                                        fill="transparent"
                                        class="cursor-pointer"
                                        @mouseenter="activeHoverIndex = idx"
                                        @mouseleave="activeHoverIndex = null"
                                    />
                                    <!-- Outer ring on active -->
                                    <circle
                                        v-if="activeHoverIndex === idx"
                                        :cx="pt.x"
                                        :cy="pt.y"
                                        r="8"
                                        fill="#ad2b08"
                                        fill-opacity="0.2"
                                    />
                                    <!-- Visible point dot -->
                                    <circle
                                        :cx="pt.x"
                                        :cy="pt.y"
                                        :r="activeHoverIndex === idx ? 6 : 4"
                                        fill="#ffffff"
                                        stroke="#ad2b08"
                                        stroke-width="2.5"
                                        class="transition-all"
                                    />

                                    <!-- X-Axis Labels -->
                                    <text
                                        :x="pt.x"
                                        :y="chartHeight - 12"
                                        text-anchor="middle"
                                        font-size="11"
                                        font-weight="600"
                                        fill="#64748b"
                                    >
                                        {{ pt.hour }}
                                    </text>
                                </g>
                            </svg>

                            <!-- Hover Tooltip Floating -->
                            <div
                                v-if="activeHoverIndex !== null && chartPoints[activeHoverIndex]"
                                class="absolute top-2 left-1/2 -translate-x-1/2 bg-black/85 text-white px-3 py-1.5 rounded-xl shadow-lg text-xs font-semibold flex items-center gap-2 pointer-events-none z-10 animate-in fade-in"
                            >
                                <span class="text-amber-300 font-mono">{{ chartPoints[activeHoverIndex].hour }} {{ timeZoneLabel }}:</span>
                                <span class="font-bold text-white">{{ formatRupiah(chartPoints[activeHoverIndex].amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 text-xs text-text-muted border-t border-border-subtle/50 mt-2">
                        <span>Jam buka operasional: 10:00 - 20:00 {{ timeZoneLabel }}</span>
                        <span class="font-semibold text-on-surface">Grafik garis tren penjualan</span>
                    </div>
                </div>

                <!-- Column 2: Rasio Metode Pembayaran (1 Col on Desktop with SVG Donut) matching Stitch -->
                <div class="bg-surface p-5 sm:p-6 rounded-xl shadow-xs border border-border-subtle flex flex-col justify-between">
                    <div>
                        <div class="mb-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted block">Komposisi Finansial</span>
                            <h3 class="text-base font-bold text-on-surface">Rasio Metode Pembayaran</h3>
                        </div>

                        <!-- Donut Graphic Visualizer matching Stitch -->
                        <div class="flex items-center justify-center my-4">
                            <div class="relative w-36 h-36 flex items-center justify-center">
                                <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                                    <!-- Background ring -->
                                    <circle cx="18" cy="18" fill="transparent" r="14" stroke="#eff4ff" stroke-width="4.5" />
                                    <!-- QRIS Arc -->
                                    <circle
                                        cx="18"
                                        cy="18"
                                        fill="transparent"
                                        r="14"
                                        stroke="#ad2b08"
                                        :stroke-dasharray="qrisStrokeDash"
                                        stroke-dashoffset="0"
                                        stroke-linecap="round"
                                        stroke-width="4.5"
                                    />
                                    <!-- Cash Arc -->
                                    <circle
                                        cx="18"
                                        cy="18"
                                        fill="transparent"
                                        r="14"
                                        stroke="#006c49"
                                        :stroke-dasharray="cashStrokeDash"
                                        :stroke-dashoffset="cashStrokeOffset"
                                        stroke-linecap="round"
                                        stroke-width="4.5"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                    <span class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Total</span>
                                    <span class="text-lg font-black text-on-surface leading-tight">{{ metrics.count }}</span>
                                    <span class="text-[10px] font-semibold text-secondary">Pesanan</span>
                                </div>
                            </div>
                        </div>

                        <!-- Legend & Breakdown Cards matching Stitch -->
                        <div class="space-y-2">
                            <!-- QRIS Card -->
                            <div class="p-2.5 rounded-lg bg-surface-container-low flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-primary shrink-0"></div>
                                    <div>
                                        <div class="font-bold text-on-surface">QRIS</div>
                                        <div class="text-[11px] text-text-muted">{{ metrics.qris_count || 0 }} Pesanan</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-primary">{{ formatRupiah(metrics.qris) }}</div>
                                    <div class="text-[11px] text-text-muted">{{ qrisPercentage }}%</div>
                                </div>
                            </div>

                            <!-- Cash Card -->
                            <div class="p-2.5 rounded-lg bg-surface-container-low flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-secondary shrink-0"></div>
                                    <div>
                                        <div class="font-bold text-on-surface">Tunai (Cash)</div>
                                        <div class="text-[11px] text-text-muted">{{ metrics.cash_count || 0 }} Pesanan</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-secondary">{{ formatRupiah(metrics.cash) }}</div>
                                    <div class="text-[11px] text-text-muted">{{ cashPercentage }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-text-muted mt-3 pt-2 border-t border-border-subtle/50 text-center">
                        *Semua pembayaran QRIS diverifikasi langsung oleh kasir.
                    </p>
                </div>
            </div>

            <!-- Top-Selling Products Section ("5 Produk Terlaris") matching Stitch -->
            <div class="bg-surface rounded-xl p-5 sm:p-6 shadow-xs border border-border-subtle">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-text-muted block">Menu Paling Diminati</span>
                        <h3 class="text-base font-bold text-on-surface">5 Produk Terlaris</h3>
                    </div>
                </div>

                <!-- Clean Ranking Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-surface-container-low text-text-muted font-bold text-[11px] uppercase tracking-wider border-b border-border-subtle">
                                <th class="py-3 px-4 w-16 text-center">Peringkat</th>
                                <th class="py-3 px-4">Menu / Produk</th>
                                <th class="py-3 px-4 text-center">Volume Terjual</th>
                                <th class="py-3 px-4 text-right">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle/60 text-on-surface">
                            <tr
                                v-for="(prod, idx) in topProducts"
                                :key="idx"
                                class="hover:bg-surface-bright transition-colors"
                            >
                                <!-- Peringkat Badge -->
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full font-bold text-xs"
                                        :class="idx === 0 ? 'bg-primary text-white shadow-xs' : 'bg-surface-container text-on-surface'"
                                    >
                                        {{ idx + 1 }}
                                    </span>
                                </td>

                                <!-- Menu / Produk -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0 shadow-xs">
                                            <UtensilsCrossed class="w-5 h-5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-sm text-on-surface">{{ prod.product_name_snapshot }}</span>
                                            <span class="text-[11px] text-text-muted">Menu Favorit Pelanggan</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Volume Terjual -->
                                <td class="py-3.5 px-4 text-center font-bold text-on-surface">
                                    {{ prod.total_qty }}x <span class="text-text-muted font-normal">pcs</span>
                                </td>

                                <!-- Total Omzet -->
                                <td class="py-3.5 px-4 text-right font-bold text-primary text-sm whitespace-nowrap">
                                    {{ formatRupiah(prod.total_amount) }}
                                </td>
                            </tr>

                            <tr v-if="topProducts.length === 0">
                                <td colspan="4" class="py-8 text-center text-text-muted">
                                    Belum ada penjualan produk pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Sales History Table for this period -->
            <div class="bg-surface rounded-xl shadow-xs border border-border-subtle overflow-hidden">
                <div class="p-4 border-b border-border-subtle flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-on-surface">Riwayat Transaksi Periode Ini</h3>
                        <span class="text-xs text-text-muted">Daftar transaksi yang selesai pada rentang tanggal terpilih</span>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-surface-container-low text-text-muted text-[11px] font-bold uppercase tracking-wider border-b border-border-subtle">
                                <th class="px-4 py-3.5">Invoice</th>
                                <th class="px-4 py-3.5">Waktu</th>
                                <th class="px-4 py-3.5">Metode</th>
                                <th class="px-4 py-3.5 text-right">Total Tagihan</th>
                                <th class="px-4 py-3.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle/60 text-on-surface">
                            <tr
                                v-for="trx in transactions.data"
                                :key="trx.id"
                                class="hover:bg-surface-container-low/60 transition-colors"
                            >
                                <td class="px-4 py-3 font-bold text-primary">#{{ trx.invoice_number }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-text-muted">
                                    {{ formatDate(trx.created_at) }}, {{ formatTime(trx.created_at) }} {{ timeZoneLabel }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
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
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-on-surface">
                                    {{ formatRupiah(trx.total_amount) }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span
                                        v-if="trx.status === 'completed'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-fixed/50 text-secondary font-bold text-[10px]"
                                    >
                                        <CheckCircle class="w-3 h-3" />
                                        <span>Selesai</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-bold text-[10px]"
                                    >
                                        Dibatalkan
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="transactions.data.length === 0">
                                <td colspan="5" class="py-8 text-center text-text-muted">
                                    Tidak ada transaksi pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List (< md) -->
                <div class="block md:hidden divide-y divide-border-subtle">
                    <div
                        v-for="trx in transactions.data"
                        :key="trx.id"
                        class="p-4 flex flex-col gap-1.5"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs text-primary">#{{ trx.invoice_number }}</span>
                            <span class="font-bold text-xs text-on-surface">{{ formatRupiah(trx.total_amount) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-text-muted">
                            <span>{{ formatDate(trx.created_at) }}, {{ formatTime(trx.created_at) }} {{ timeZoneLabel }}</span>
                            <span class="font-semibold uppercase">{{ trx.payment_method }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t border-border-subtle bg-surface">
                    <ResponsivePagination :links="transactions.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
