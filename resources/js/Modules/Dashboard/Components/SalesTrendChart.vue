<script setup>
import { onMounted, ref } from 'vue';
import { Chart, registerables } from 'chart.js';
import { Calendar } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';

const { formatCurrency, currencySymbol } = useCurrency();

Chart.register(...registerables);

const canvasRef = ref(null);
let chartInstance = null;

const props = defineProps({
    hourlyData: {
        type: Array,
        default: () => [
            { time: '10:00', total: 120000 },
            { time: '11:00', total: 280000 },
            { time: '12:00', total: 850000 },
            { time: '13:00', total: 420000 },
            { time: '14:00', total: 310000 },
            { time: '15:00', total: 260000 },
            { time: '16:00', total: 350000 },
            { time: '17:00', total: 290000 },
            { time: '18:00', total: 180000 },
            { time: '19:00', total: 110000 },
            { time: '20:00', total: 80000 },
        ],
    },
});

onMounted(() => {
    if (!canvasRef.value) return;

    const ctx = canvasRef.value.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(239, 90, 53, 0.35)');
    gradient.addColorStop(0.7, 'rgba(239, 90, 53, 0.05)');
    gradient.addColorStop(1, 'rgba(239, 90, 53, 0)');

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.hourlyData.map(d => d.time),
            datasets: [
                {
                    label: `Penjualan (${currencySymbol.value})`,
                    data: props.hourlyData.map(d => d.total),
                    borderColor: '#EF5A35',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#EF5A35',
                    pointBorderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1F2937',
                    padding: 10,
                    cornerRadius: 12,
                    callbacks: {
                        label: function (context) {
                            return formatCurrency(context.raw);
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#6B7280',
                    },
                },
                y: {
                    grid: {
                        color: '#F3F4F6',
                    },
                    ticks: {
                        font: { size: 10 },
                        color: '#9CA3AF',
                        callback: function (val) {
                            return formatCurrency(val);
                        },
                    },
                },
            },
        },
    });
});
</script>

<template>
    <div class="bg-surface rounded-2xl p-5 sm:p-6 shadow-sm border border-border-subtle flex flex-col gap-4">
        <!-- Chart Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-bold text-text-primary">Tren Penjualan</h2>
                    <span class="px-2.5 py-0.5 rounded-full bg-primary-subtle text-primary text-[11px] font-bold">
                        Live Sync
                    </span>
                </div>
                <span class="text-xs text-text-muted mt-0.5">Tren penjualan per jam mengikuti zona waktu toko</span>
            </div>

            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border-subtle bg-surface text-xs font-semibold text-text-primary shadow-xs">
                <Calendar class="w-3.5 h-3.5 text-text-muted" />
                <span>Hari Ini</span>
            </div>
        </div>

        <!-- Canvas Container -->
        <div class="relative w-full h-64 pt-2">
            <canvas ref="canvasRef"></canvas>
        </div>
    </div>
</template>
