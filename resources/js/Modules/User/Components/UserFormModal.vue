<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, User, Check, ShieldCheck, Lock } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'cashier',
    status: 'active',
});

watch(
    () => props.user,
    (u) => {
        if (u) {
            form.name = u.name;
            form.email = u.email;
            form.password = '';
            form.role = u.role || 'cashier';
            form.status = u.status || 'active';
        } else {
            form.reset();
            form.role = 'cashier';
            form.status = 'active';
        }
    },
    { immediate: true }
);

const submit = () => {
    if (props.user) {
        form.put(route('users.update', props.user.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => emit('close'),
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
        <div class="bg-surface w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-border-subtle flex flex-col gap-6 animate-in fade-in zoom-in-95 duration-200">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-border-subtle">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-primary-subtle flex items-center justify-center text-primary">
                        <User class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-black text-text-primary tracking-tight">
                            {{ user ? 'Edit Akun Pengguna' : 'Tambah Pengguna Baru' }}
                        </h3>
                        <p class="text-xs text-text-muted">
                            {{ user ? 'Perbarui informasi akun, role, atau kata sandi' : 'Daftarkan kasir baru atau administrator sistem' }}
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

            <!-- Form -->
            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <!-- Nama Lengkap -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-text-primary">Nama Lengkap *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="Contoh: Rian Pratama"
                        class="w-full h-11 px-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-medium focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                    />
                    <p v-if="form.errors.name" class="text-xs text-error-alert">{{ form.errors.name }}</p>
                </div>

                <!-- Email -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-text-primary">Alamat Email *</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="rian@foodislice.com"
                        class="w-full h-11 px-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-medium focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                    />
                    <p v-if="form.errors.email" class="text-xs text-error-alert">{{ form.errors.email }}</p>
                </div>

                <!-- Kata Sandi -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-text-primary flex items-center justify-between">
                        <span>{{ user ? 'Kata Sandi Baru (Opsional)' : 'Kata Sandi *' }}</span>
                        <span v-if="user" class="text-[10px] text-text-muted font-normal">Biarkan kosong jika tidak diubah</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            type="password"
                            :required="!user"
                            placeholder="Minimal 6 karakter..."
                            class="w-full h-11 pl-10 pr-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-medium focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                        />
                        <Lock class="w-4 h-4 text-text-muted absolute left-3.5 top-1/2 -translate-y-1/2" />
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-error-alert">{{ form.errors.password }}</p>
                </div>

                <!-- Role & Status Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Role -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-text-primary">Peran (Role) *</label>
                        <select
                            v-model="form.role"
                            required
                            class="w-full h-11 px-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-bold focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                        >
                            <option value="cashier">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
                        <p v-if="form.errors.role" class="text-xs text-error-alert">{{ form.errors.role }}</p>
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-text-primary">Status Akun *</label>
                        <select
                            v-model="form.status"
                            required
                            class="w-full h-11 px-3.5 rounded-xl bg-surface-container-low border border-border-subtle text-xs text-text-primary font-bold focus:ring-2 focus:ring-primary focus:bg-white outline-none"
                        >
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs text-error-alert">{{ form.errors.status }}</p>
                    </div>
                </div>

                <!-- Info Hak Akses -->
                <div class="p-3 rounded-2xl bg-surface-container-low border border-border-subtle flex items-start gap-2.5">
                    <ShieldCheck class="w-4 h-4 text-primary shrink-0 mt-0.5" />
                    <p class="text-[11px] text-text-muted leading-relaxed">
                        <span class="font-bold text-text-primary">Kasir</span> hanya dapat mengakses terminal POS, transaksi, dan shift kerja.
                        <span class="font-bold text-text-primary">Admin</span> memiliki kontrol penuh termasuk produk, laporan omzet, user, dan audit.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-border-subtle">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2.5 rounded-xl border border-border-subtle text-xs font-bold text-text-muted hover:bg-gray-50 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-dark text-white text-xs font-bold shadow-md active:scale-95 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                    >
                        <Check class="w-4 h-4" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengguna' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
