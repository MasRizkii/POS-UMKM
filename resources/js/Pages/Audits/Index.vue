<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AuditDetailModal from '@/Modules/Audit/Components/AuditDetailModal.vue';
import ResponsivePagination from '@/Components/Layout/ResponsivePagination.vue';
import { useDateTime } from '@/Composables/useDateTime';
import { 
    History, 
    Search, 
    Calendar, 
    User as UserIcon, 
    Eye, 
    RotateCcw,
    ShieldAlert,
    Clock
} from 'lucide-vue-next';

const { formatDateTime } = useDateTime();

const props = defineProps({
    audits: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedUserId = ref(props.filters.user_id || '');
const selectedPreset = ref(props.filters.date_preset || 'all');
const selectedAudit = ref(null);
const showDetailModal = ref(false);

const applyFilters = () => {
    router.get(
        route('audits.index'),
        {
            search: searchQuery.value,
            user_id: selectedUserId.value,
            date_preset: selectedPreset.value,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedUserId.value = '';
    selectedPreset.value = 'all';
    router.get(route('audits.index'));
};

const openDetail = (audit) => {
    selectedAudit.value = audit;
    showDetailModal.value = true;
};

const getActionBadgeClass = (action) => {
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
    <AuthenticatedLayout>
        <Head title="Audit Log Aktivitas - Foodislice POS" />

        <div class="p-4 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-[1600px] mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-text-primary tracking-tight">
                        Audit Log Aktivitas
                    </h1>
                    <p class="text-xs text-text-muted mt-0.5">
                        Jejak audit permanen (append-only) untuk memantau perubahan krusial, pembatalan transaksi, harga, dan pengaturan.
                    </p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="p-4 rounded-3xl bg-surface border border-border-subtle shadow-xs flex flex-col md:flex-row items-center gap-3">
                <!-- Search Input -->
                <div class="relative w-full md:flex-1">
                    <Search class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari berdasarkan aksi atau entitas (cth: BATAL, Product)..."
                        @keyup.enter="applyFilters"
                        class="w-full h-10 pl-10 pr-4 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                    />
                </div>

                <!-- User Filter -->
                <select
                    v-model="selectedUserId"
                    @change="applyFilters"
                    class="w-full md:w-48 h-10 px-3 rounded-xl bg-surface-container-low border border-border-subtle text-xs font-semibold text-text-primary focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                >
                    <option value="">Semua Pengguna</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">
                        {{ u.name }} ({{ u.role }})
                    </option>
                </select>

                <!-- Date Preset Filter -->
                <select
                    v-model="selectedPreset"
                    @change="applyFilters"
                    class="w-full md:w-44 h-10 px-3 rounded-xl bg-surface-container-low border border-border-subtle text-xs font-semibold text-text-primary focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                >
                    <option value="all">Semua Waktu</option>
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="7days">7 Hari Terakhir</option>
                    <option value="month">Bulan Ini</option>
                </select>

                <!-- Buttons -->
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button
                        type="button"
                        @click="applyFilters"
                        class="flex-1 md:flex-none px-4 h-10 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-dark transition cursor-pointer"
                    >
                        Filter
                    </button>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="p-2.5 h-10 rounded-xl border border-border-subtle text-text-muted hover:bg-surface-container-high transition cursor-pointer"
                        title="Reset Filter"
                    >
                        <RotateCcw class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Content Area: Desktop Table & Mobile Cards -->
            <div class="bg-surface rounded-3xl border border-border-subtle shadow-xs overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-border-subtle text-[11px] font-bold text-text-muted uppercase tracking-wider">
                                <th class="py-3.5 px-6">Waktu Kejadian</th>
                                <th class="py-3.5 px-6">Pelaku (User)</th>
                                <th class="py-3.5 px-6">Tipe Aksi</th>
                                <th class="py-3.5 px-6">Entitas</th>
                                <th class="py-3.5 px-6 text-right">Rincian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            <tr
                                v-for="audit in audits.data"
                                :key="audit.id"
                                class="hover:bg-surface-container-low/50 transition-colors"
                            >
                                <!-- Waktu -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2 text-text-primary font-medium">
                                        <Clock class="w-3.5 h-3.5 text-text-muted shrink-0" />
                                        <span>{{ formatDateTime(audit.created_at) }}</span>
                                    </div>
                                </td>

                                <!-- Pelaku -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-primary-subtle text-primary flex items-center justify-center font-bold text-[11px] shrink-0">
                                            {{ (audit.user?.name || 'S').substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-text-primary">{{ audit.user?.name || 'Sistem' }}</span>
                                            <span class="text-[10px] text-text-muted">{{ audit.user?.role || 'System' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-6">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-black tracking-wide border"
                                        :class="getActionBadgeClass(audit.action)"
                                    >
                                        {{ audit.action }}
                                    </span>
                                </td>

                                <!-- Entitas -->
                                <td class="py-4 px-6 font-medium text-text-primary">
                                    <span>{{ audit.entity }}</span>
                                    <span v-if="audit.entity_id" class="text-text-muted ml-1">#{{ audit.entity_id }}</span>
                                </td>

                                <!-- Rincian Button -->
                                <td class="py-4 px-6 text-right">
                                    <button
                                        type="button"
                                        @click="openDetail(audit)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container-low hover:bg-primary hover:text-white text-text-primary text-xs font-bold transition cursor-pointer"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>Detail</span>
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="audits.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-text-muted">
                                    <History class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                                    <p class="font-bold text-sm">Tidak ada catatan audit log</p>
                                    <p class="text-xs text-text-muted">Belum ada aktivitas yang tercatat atau cocok dengan kriteria filter.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card List (< 768px) -->
                <div class="block md:hidden divide-y divide-border-subtle">
                    <div
                        v-for="audit in audits.data"
                        :key="audit.id"
                        class="p-4 flex flex-col gap-2.5"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wide border"
                                :class="getActionBadgeClass(audit.action)"
                            >
                                {{ audit.action }}
                            </span>
                            <span class="text-[11px] text-text-muted flex items-center gap-1">
                                <Clock class="w-3 h-3" />
                                {{ formatDateTime(audit.created_at) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-primary-subtle text-primary flex items-center justify-center font-bold text-[10px]">
                                    {{ (audit.user?.name || 'S').substring(0, 2).toUpperCase() }}
                                </div>
                                <span class="font-bold text-text-primary">{{ audit.user?.name || 'Sistem' }}</span>
                            </div>

                            <span class="text-text-muted font-medium">
                                {{ audit.entity }} #{{ audit.entity_id || '-' }}
                            </span>
                        </div>

                        <div class="pt-2 border-t border-border-subtle flex justify-end">
                            <button
                                type="button"
                                @click="openDetail(audit)"
                                class="px-3 py-1.5 rounded-xl bg-surface-container-low text-primary font-bold text-xs flex items-center gap-1.5"
                            >
                                <Eye class="w-3.5 h-3.5" />
                                <span>Lihat Perubahan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Empty State -->
                    <div v-if="audits.data.length === 0" class="py-12 text-center text-text-muted px-4">
                        <History class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                        <p class="font-bold text-sm">Tidak ada catatan audit</p>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t border-border-subtle bg-surface-container-low/30">
                    <ResponsivePagination :links="audits.links" />
                </div>
            </div>
        </div>

        <!-- Detail Inspector Modal -->
        <AuditDetailModal
            :show="showDetailModal"
            :audit="selectedAudit"
            @close="showDetailModal = false"
        />
    </AuthenticatedLayout>
</template>
