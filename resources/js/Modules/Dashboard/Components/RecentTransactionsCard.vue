<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowRight, QrCode, Banknote } from 'lucide-vue-next';
import { useCurrency } from '@/Composables/useCurrency';
import { useDateTime } from '@/Composables/useDateTime';

const { formatRupiah } = useCurrency();
const { timeZoneLabel } = useDateTime();

defineProps({
    transactions: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="bg-surface rounded-2xl p-5 sm:p-6 shadow-sm border border-border-subtle flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-base sm:text-lg font-bold text-text-primary">Transaksi Terakhir</h2>
            <Link
                href="/transactions"
                class="flex items-center gap-1 text-xs font-bold text-primary hover:underline"
            >
                <span>Lihat Semua Riwayat</span>
                <ArrowRight class="w-3.5 h-3.5" />
            </Link>
        </div>

        <!-- Table / Mobile Responsive List -->
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-background rounded-xl text-text-muted text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3 rounded-l-xl">Invoice</th>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3">Detail Pesanan</th>
                        <th class="py-2.5 px-3">Metode Bayar</th>
                        <th class="py-2.5 px-3 text-right">Total</th>
                        <th class="py-2.5 px-3 rounded-r-xl text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y-0 text-xs sm:text-sm">
                    <tr
                        v-for="trx in transactions"
                        :key="trx.id"
                        class="hover:bg-surface-container-low transition-colors group"
                    >
                        <td class="py-3 px-3 font-bold text-primary whitespace-nowrap">
                            #{{ trx.invoice_number }}
                        </td>
                        <td class="py-3 px-3 text-text-muted text-xs whitespace-nowrap">
                            {{ trx.formatted_time ? `${trx.formatted_time} ${timeZoneLabel}` : 'Baru saja' }}
                        </td>
                        <td class="py-3 px-3 font-medium text-text-primary max-w-xs truncate">
                            {{ trx.notes || (trx.items ? trx.items.map(i => i.product_name_snapshot + ' x' + i.quantity).join(', ') : 'Pesanan Minuman') }}
                        </td>
                        <td class="py-3 px-3 whitespace-nowrap">
                            <span
                                v-if="trx.payment_method === 'qris'"
                                class="inline-flex items-center gap-1 text-tertiary font-bold text-xs bg-cyan-50 px-2 py-0.5 rounded-md"
                            >
                                <QrCode class="w-3.5 h-3.5" />
                                QRIS
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-secondary font-bold text-xs bg-emerald-50 px-2 py-0.5 rounded-md"
                            >
                                <Banknote class="w-3.5 h-3.5" />
                                Tunai
                            </span>
                        </td>
                        <td class="py-3 px-3 text-right font-black text-text-primary whitespace-nowrap">
                            {{ formatRupiah(trx.total_amount) }}
                        </td>
                        <td class="py-3 px-3 text-center whitespace-nowrap">
                            <span
                                v-if="trx.status === 'completed'"
                                class="inline-flex items-center gap-1.5 text-secondary text-xs font-bold"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                                Selesai
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 text-error-alert text-xs font-bold"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-error-alert"></span>
                                Dibatalkan
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
