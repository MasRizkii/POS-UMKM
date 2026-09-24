<script setup>
import { X, History, User, Calendar, Database, ArrowRight } from 'lucide-vue-next';
import { useDateTime } from '@/Composables/useDateTime';

const { formatDateTime, timeZoneLabel } = useDateTime();

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    audit: {
        type: Object,
        default: null,
    },
});

defineEmits(['close']);

const formatJson = (val) => {
    if (!val) return '-';
    try {
        return typeof val === 'string' ? JSON.parse(val) : val;
    } catch {
        return val;
    }
};

const getActionColor = (action) => {
    if (!action) return 'bg-gray-100 text-gray-700';
    if (action.includes('VOID')) return 'bg-rose-50 text-error-alert border-rose-200';
    if (action.includes('DELETE')) return 'bg-amber-50 text-amber-700 border-amber-200';
    if (action.includes('CREATE')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    if (action.includes('UPDATE')) return 'bg-sky-50 text-sky-700 border-sky-200';
    if (action.includes('SHIFT')) return 'bg-purple-50 text-purple-700 border-purple-200';
    return 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <div v-if="show && audit" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-surface w-full max-w-2xl max-h-[90vh] rounded-3xl p-6 shadow-2xl border border-border-subtle flex flex-col gap-6 animate-in fade-in zoom-in-95 duration-200 overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-border-subtle shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-primary-subtle flex items-center justify-center text-primary">
                        <History class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-black text-text-primary tracking-tight">
                            Detail Audit Log #{{ audit.id }}
                        </h3>
                        <p class="text-xs text-text-muted">
                            Inspeksi jejak audit dan riwayat perubahan data sistem
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="p-2 rounded-xl text-text-muted hover:bg-surface-container-high transition cursor-pointer"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Meta Information Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 shrink-0">
                <!-- User Pelaku -->
                <div class="p-3.5 rounded-2xl bg-surface-container-low border border-border-subtle flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-surface flex items-center justify-center text-primary shadow-xs">
                        <User class="w-4 h-4" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-text-muted uppercase">Pengguna Pelaku</span>
                        <span class="text-xs font-bold text-text-primary">{{ audit.user?.name || 'Sistem / Otomatis' }}</span>
                        <span class="text-[10px] text-text-muted">{{ audit.user?.email || '-' }} ({{ audit.user?.role || '-' }})</span>
                    </div>
                </div>

                <!-- Waktu Kejadian -->
                <div class="p-3.5 rounded-2xl bg-surface-container-low border border-border-subtle flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-surface flex items-center justify-center text-secondary shadow-xs">
                        <Calendar class="w-4 h-4" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-text-muted uppercase">Waktu Aktivitas</span>
                        <span class="text-xs font-bold text-text-primary">{{ formatDateTime(audit.created_at) }}</span>
                        <span class="text-[10px] text-text-muted">Waktu Lokal ({{ timeZoneLabel }})</span>
                    </div>
                </div>

                <!-- Aksi & Entitas -->
                <div class="p-3.5 rounded-2xl bg-surface-container-low border border-border-subtle flex items-center gap-3 sm:col-span-2">
                    <div class="w-9 h-9 rounded-xl bg-surface flex items-center justify-center text-primary shadow-xs">
                        <Database class="w-4 h-4" />
                    </div>
                    <div class="flex items-center justify-between flex-1 gap-2">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-text-muted uppercase">Entitas Target</span>
                            <span class="text-xs font-bold text-text-primary">
                                {{ audit.entity }} (ID: {{ audit.entity_id || '-' }})
                            </span>
                        </div>
                        <span
                            class="px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider border shadow-2xs"
                            :class="getActionColor(audit.action)"
                        >
                            {{ audit.action }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Perubahan Nilai (Old vs New Values) -->
            <div class="flex flex-col gap-3">
                <h4 class="text-xs font-bold text-text-primary uppercase tracking-wider">
                    Snapshot Perubahan Data
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nilai Lama -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">
                                Nilai Sebelumnya (Old)
                            </span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-surface-container-low border border-border-subtle text-xs font-mono overflow-x-auto min-h-[120px]">
                            <pre v-if="audit.old_values && Object.keys(audit.old_values).length > 0" class="text-text-primary whitespace-pre-wrap">{{ JSON.stringify(audit.old_values, null, 2) }}</pre>
                            <p v-else class="text-text-muted italic text-[11px] py-4 text-center">Tidak ada data lama (Entitas baru)</p>
                        </div>
                    </div>

                    <!-- Nilai Baru -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200">
                                Nilai Baru (New)
                            </span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-surface-container-low border border-border-subtle text-xs font-mono overflow-x-auto min-h-[120px]">
                            <pre v-if="audit.new_values && Object.keys(audit.new_values).length > 0" class="text-text-primary whitespace-pre-wrap">{{ JSON.stringify(audit.new_values, null, 2) }}</pre>
                            <p v-else class="text-text-muted italic text-[11px] py-4 text-center">Data dihapus / tidak ada nilai baru</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between pt-4 border-t border-border-subtle shrink-0">
                <span class="text-[11px] text-text-muted">
                    Log ini bersifat permanen & append-only sesuai standar audit PRD.
                </span>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 rounded-xl bg-surface-container-high text-xs font-bold text-text-primary hover:bg-gray-200 transition cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
