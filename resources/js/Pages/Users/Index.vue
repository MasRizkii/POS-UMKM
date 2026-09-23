<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserFormModal from '@/Modules/User/Components/UserFormModal.vue';
import ResponsivePagination from '@/Components/Layout/ResponsivePagination.vue';
import { useDateTime } from '@/Composables/useDateTime';
import { 
    Users, 
    UserPlus, 
    Search, 
    Edit, 
    Trash2, 
    ShieldCheck, 
    User as UserIcon,
    RotateCcw,
    AlertTriangle,
    Mail,
    ChevronRight,
    Ban
} from 'lucide-vue-next';

const { formatDate, formatTime } = useDateTime();
const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');
const selectedStatus = ref(props.filters.status || '');

const showUserModal = ref(false);
const editingUser = ref(null);
const userToDelete = ref(null);
const showDeleteConfirm = ref(false);

const applyFilters = () => {
    router.get(
        route('users.index'),
        {
            search: searchQuery.value,
            role: selectedRole.value,
            status: selectedStatus.value,
        },
        { preserveState: true, replace: true }
    );
};

const filterStatus = (status) => {
    selectedStatus.value = status;
    applyFilters();
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedRole.value = '';
    selectedStatus.value = '';
    router.get(route('users.index'));
};

const openCreateUser = () => {
    editingUser.value = null;
    showUserModal.value = true;
};

const openEditUser = (user) => {
    editingUser.value = user;
    showUserModal.value = true;
};

const promptDelete = (user) => {
    userToDelete.value = user;
    showDeleteConfirm.value = true;
};

const confirmDelete = () => {
    if (!userToDelete.value) return;
    router.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteConfirm.value = false;
            userToDelete.value = null;
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="User Management - Foodislice POS" />

        <div class="p-4 sm:p-6 lg:p-8 flex flex-col gap-6 max-w-[1600px] mx-auto">
            <!-- Header Section matching Stitch -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-bold tracking-tight text-on-surface">User Management</h1>
                    <p class="text-xs text-text-muted mt-0.5">
                        Kelola akun kasir, supervisor, dan hak akses operasional terminal POS gerai
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="openCreateUser"
                        class="px-5 h-10 rounded-xl bg-primary text-white font-bold text-xs shadow-md shadow-primary/20 hover:bg-primary-dark active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
                    >
                        <UserPlus class="w-4 h-4" />
                        <span>+ Tambah User</span>
                    </button>
                </div>
            </div>

            <!-- Toolbar matching Stitch user_management.html -->
            <div class="bg-surface rounded-xl shadow-xs border border-border-subtle p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <!-- Search & Quick Role Dropdown -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 flex-1 max-w-2xl">
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            @keyup.enter="applyFilters"
                            placeholder="Cari nama karyawan atau email..."
                            class="w-full pl-9 pr-3 py-2 bg-background rounded-xl text-xs text-on-surface border border-border-subtle focus:outline-none focus:ring-1 focus:ring-primary"
                        />
                    </div>

                    <div class="relative shrink-0">
                        <select
                            v-model="selectedRole"
                            @change="applyFilters"
                            class="h-9 px-3 bg-background rounded-xl text-xs font-semibold text-on-surface border border-border-subtle cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option value="">Semua Peran</option>
                            <option value="admin">Admin</option>
                            <option value="cashier">Kasir</option>
                        </select>
                    </div>
                </div>

                <!-- Status Filter Segmented Controls -->
                <div class="flex items-center gap-1.5 self-start lg:self-auto">
                    <div class="flex items-center bg-background p-1 rounded-xl border border-border-subtle text-xs">
                        <button
                            type="button"
                            @click="filterStatus('')"
                            class="px-3 py-1 rounded-lg font-semibold transition cursor-pointer"
                            :class="selectedStatus === '' ? 'bg-surface text-primary font-bold shadow-xs' : 'text-text-muted hover:text-on-surface'"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            @click="filterStatus('active')"
                            class="px-3 py-1 rounded-lg font-semibold transition cursor-pointer"
                            :class="selectedStatus === 'active' ? 'bg-surface text-secondary font-bold shadow-xs' : 'text-text-muted hover:text-on-surface'"
                        >
                            Aktif
                        </button>
                        <button
                            type="button"
                            @click="filterStatus('inactive')"
                            class="px-3 py-1 rounded-lg font-semibold transition cursor-pointer"
                            :class="selectedStatus === 'inactive' ? 'bg-surface text-error font-bold shadow-xs' : 'text-text-muted hover:text-on-surface'"
                        >
                            Nonaktif
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="p-2 rounded-xl border border-border-subtle text-text-muted hover:text-on-surface hover:bg-surface-container-low transition cursor-pointer"
                        title="Reset Filter"
                    >
                        <RotateCcw class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Table Card Container matching Stitch -->
            <div class="bg-surface rounded-xl shadow-xs border border-border-subtle overflow-hidden">
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-surface-container/50 text-text-muted text-[11px] font-bold uppercase tracking-wider border-b border-border-subtle">
                                <th class="py-3.5 px-6">Pengguna</th>
                                <th class="py-3.5 px-6">Email</th>
                                <th class="py-3.5 px-6">Peran &amp; Wewenang</th>
                                <th class="py-3.5 px-6">Status Akun</th>
                                <th class="py-3.5 px-6">Aktivitas Terakhir</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle/60 text-on-surface">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-surface-bright transition-colors group"
                            >
                                <!-- Pengguna (Avatar, Name) -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="relative w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-2xs"
                                            :class="user.role === 'admin' ? 'bg-primary-fixed text-primary' : 'bg-tertiary-fixed text-tertiary'"
                                        >
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                            <span
                                                class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-surface"
                                                :class="user.status === 'active' ? 'bg-secondary' : 'bg-gray-400'"
                                            ></span>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-bold text-sm text-on-surface truncate group-hover:text-primary transition-colors">
                                                {{ user.name }}
                                            </span>
                                            <span class="text-[11px] text-text-muted font-mono">ID #{{ user.id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="flex items-center gap-1.5 text-on-surface">
                                        <Mail class="w-3.5 h-3.5 text-text-muted" />
                                        <span class="text-xs font-medium">{{ user.email }}</span>
                                    </div>
                                </td>

                                <!-- Role & Permissions Badge -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span
                                        v-if="user.role === 'admin'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-subtle text-primary text-[11px] font-bold"
                                    >
                                        <ShieldCheck class="w-3.5 h-3.5" />
                                        <span>Admin</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-[11px] font-bold"
                                    >
                                        <UserIcon class="w-3.5 h-3.5" />
                                        <span>Kasir</span>
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span
                                        v-if="user.status === 'active'"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-fixed/30 text-secondary text-[11px] font-bold"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                                        <span>Aktif</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-[11px] font-bold"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        <span>Nonaktif</span>
                                    </span>
                                </td>

                                <!-- Tanggal Dibuat -->
                                <td class="py-4 px-6 whitespace-nowrap text-text-muted">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-on-surface">{{ formatDate(user.created_at) }}</span>
                                        <span class="text-[10px]">{{ formatTime(user.created_at) }} WIB</span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditUser(user)"
                                            class="px-2.5 py-1.5 rounded-lg bg-surface hover:bg-surface-container text-on-surface text-xs font-semibold shadow-xs transition flex items-center gap-1 cursor-pointer border border-border-subtle"
                                        >
                                            <Edit class="w-3.5 h-3.5 text-text-muted" />
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="promptDelete(user)"
                                            :disabled="user.id === currentUser.id"
                                            class="p-1.5 rounded-lg bg-surface hover:bg-error-container hover:text-on-error-container text-text-muted transition cursor-pointer border border-border-subtle disabled:opacity-30 disabled:cursor-not-allowed"
                                            title="Nonaktifkan Akses"
                                        >
                                            <Ban class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-text-muted">
                                    <Users class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                                    <p class="font-bold text-sm">Tidak ada staf/pengguna ditemukan</p>
                                    <p class="text-xs text-text-muted">Coba ubah kata kunci pencarian atau reset filter peran.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Responsive Card View (< md) -->
                <div class="block md:hidden divide-y divide-border-subtle">
                    <div
                        v-for="user in users.data"
                        :key="user.id"
                        class="p-4 flex flex-col gap-2.5"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
                                    :class="user.role === 'admin' ? 'bg-primary-fixed text-primary' : 'bg-tertiary-fixed text-tertiary'"
                                >
                                    {{ user.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-bold text-sm text-on-surface">{{ user.name }}</span>
                                    <span class="text-xs text-text-muted">{{ user.email }}</span>
                                </div>
                            </div>

                            <span
                                v-if="user.status === 'active'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-secondary-fixed/30 text-secondary"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                                <span>Aktif</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                <span>Nonaktif</span>
                            </span>
                        </div>

                        <div class="pt-2 border-t border-border-subtle flex items-center justify-between text-xs">
                            <span
                                v-if="user.role === 'admin'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary-subtle text-primary font-bold text-[10px]"
                            >
                                <ShieldCheck class="w-3 h-3" />
                                <span>Admin</span>
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-bold text-[10px]"
                            >
                                <UserIcon class="w-3 h-3" />
                                <span>Kasir</span>
                            </span>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="openEditUser(user)"
                                    class="px-2.5 py-1 rounded-lg bg-surface-container-low text-xs font-semibold"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    @click="promptDelete(user)"
                                    :disabled="user.id === currentUser.id"
                                    class="px-2.5 py-1 rounded-lg bg-error-container/40 text-error text-xs font-semibold disabled:opacity-30"
                                >
                                    Nonaktifkan
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="users.data.length === 0" class="py-12 text-center text-text-muted px-4">
                        <Users class="w-10 h-10 mx-auto text-text-muted/40 mb-2" />
                        <p class="font-bold text-sm">Tidak ada staf/pengguna ditemukan</p>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t border-border-subtle bg-surface flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-text-muted">
                        Menampilkan <strong class="text-on-surface">{{ users.from || 0 }} - {{ users.to || 0 }}</strong> dari <strong class="text-on-surface">{{ users.total }}</strong> pengguna
                    </span>
                    <ResponsivePagination :links="users.links" />
                </div>
            </div>
        </div>

        <!-- User Form Modal -->
        <UserFormModal
            :show="showUserModal"
            :user="editingUser"
            @close="showUserModal = false"
        />

        <!-- Soft Delete Confirmation Dialog -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-on-surface/40 backdrop-blur-xs">
            <div class="bg-surface w-full max-w-md rounded-2xl p-6 shadow-2xl border border-border-subtle flex flex-col gap-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="w-12 h-12 rounded-2xl bg-error-container text-on-error-container flex items-center justify-center">
                    <AlertTriangle class="w-6 h-6 stroke-[2.2]" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-on-surface">
                        Nonaktifkan Akses Pengguna?
                    </h3>
                    <p class="text-xs text-text-muted mt-1 leading-relaxed">
                        Akun <strong class="text-on-surface">{{ userToDelete?.name }}</strong> akan dinonaktifkan dari sistem POS (Soft Delete). Histori transaksi kasir masa lalu tetap aman dan tidak akan hilang.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-border-subtle">
                    <button
                        type="button"
                        @click="showDeleteConfirm = false"
                        class="px-4 py-2.5 rounded-xl bg-background hover:bg-surface-container-low text-xs font-semibold text-on-surface transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDelete"
                        class="px-5 py-2.5 rounded-xl bg-error text-white hover:bg-red-700 text-xs font-bold shadow-md transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <Ban class="w-4 h-4" />
                        <span>Ya, Nonaktifkan</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
