<script setup>
import { computed } from 'vue';

const props = defineProps({
    userName: {
        type: String,
        default: 'User',
    },
    activeShift: {
        type: Object,
        default: null,
    },
});

const todayDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date());
});
</script>

<template>
    <div class="bg-surface rounded-2xl p-5 sm:p-6 shadow-sm border border-border-subtle flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="text-xl sm:text-2xl font-black text-text-primary tracking-tight">
                    Halo, {{ userName }} 👋
                </h1>

                <!-- Active Shift Pill -->
                <div v-if="activeShift" class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-surface-container-high text-secondary border border-border-subtle">
                    <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                    <span class="text-xs font-bold">
                        {{ activeShift.notes || '1' }} • Kasir: {{ activeShift.user?.name || userName }}
                    </span>
                </div>
                <div v-else class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-text-muted text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    <span>Tidak ada shift aktif</span>
                </div>
            </div>

            <p class="text-xs sm:text-sm text-text-muted">
                Laporan performa kasir &amp; penjualan hari ini •
                <span class="font-bold text-text-primary">{{ todayDate }}</span>
            </p>
        </div>
    </div>
</template>
