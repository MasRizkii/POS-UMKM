<script setup>
import { useNetworkStatus } from '@/Composables/useNetworkStatus';
import { WifiOff } from 'lucide-vue-next';

const { isOnline } = useNetworkStatus();
</script>

<template>
    <div
        v-if="!isOnline"
        class="bg-rose-600 text-white px-4 py-2 text-xs font-semibold text-center flex items-center justify-center gap-2 sticky top-0 z-50 shadow-md animate-pulse"
    >
        <WifiOff class="w-4 h-4 shrink-0" />
        <span>Koneksi internet terputus. Checkout & mutasi finansial membutuhkan jaringan aktif.</span>
    </div>
</template>
