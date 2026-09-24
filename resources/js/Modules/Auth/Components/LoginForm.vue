<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useDateTime } from '@/Composables/useDateTime';
import { 
    Lock, 
    User as UserIcon, 
    KeyRound, 
    Eye, 
    EyeOff, 
    Store, 
    Clock, 
    LogIn 
} from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);
const currentTime = ref('');
const { formatTime, timeZoneLabel } = useDateTime();

let timer = null;

const updateClock = () => {
    currentTime.value = `${formatTime(new Date(), { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })} ${timeZoneLabel.value}`;
};

onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillQuickLogin = (email, pass) => {
    form.email = email;
    form.password = pass;
};
</script>

<template>
    <div class="mx-auto w-full max-w-lg">
        <div class="w-full bg-surface rounded-3xl shadow-xl border border-border-subtle p-6 sm:p-8">
            <!-- Header Section -->
            <div class="flex flex-col items-center text-center pb-6 border-b border-border-subtle gap-3">
                <div class="inline-flex items-center justify-center gap-1.5 text-primary text-xs font-bold uppercase tracking-wider bg-primary-subtle px-3 py-1 rounded-full">
                    <Lock class="w-3.5 h-3.5" />
                    Otentikasi Kasir & Terminal POS
                </div>

                <h1 class="text-2xl font-extrabold text-text-primary tracking-tight">
                    Masuk ke Terminal Kasir
                </h1>

                <p class="text-xs sm:text-sm text-text-muted max-w-sm">
                    Silakan masukkan identitas karyawan, outlet aktif, dan kata sandi untuk membuka sesi kasir.
                </p>

                <!-- Live Digital Clock Box -->
                <div class="flex flex-col items-center bg-surface-container-low px-5 py-2 rounded-2xl border border-border-subtle mt-1">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-text-muted flex items-center gap-1">
                        <Clock class="w-3 h-3" />
                        Waktu Kasir
                    </span>
                    <span class="text-lg font-black text-primary tracking-wide">
                        {{ currentTime }}
                    </span>
                </div>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="flex flex-col gap-4 mt-6">
                <!-- Email / Username Field -->
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-xs font-bold text-text-primary flex items-center gap-1.5">
                        <UserIcon class="w-4 h-4 text-primary" />
                        Email / Username
                    </label>
                    <div class="relative">
                        <input
                            id="email"
                            v-model="form.email"
                            type="text"
                            required
                            autofocus
                            placeholder="Contoh: kasir@foodislice.com"
                            class="w-full h-12 px-4 rounded-xl bg-surface-container-low text-text-primary text-sm focus:bg-surface focus:outline-none focus:ring-2 focus:ring-primary border border-border-subtle transition shadow-xs"
                            :class="{ 'border-red-400': form.errors.email }"
                        />
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-error-alert font-medium">
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Password Field -->
                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-xs font-bold text-text-primary flex items-center gap-1.5">
                        <KeyRound class="w-4 h-4 text-primary" />
                        Kata Sandi Akun
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            placeholder="Masukkan kata sandi akun"
                            class="w-full h-12 pl-4 pr-11 rounded-xl bg-surface-container-low text-text-primary text-sm focus:bg-surface focus:outline-none focus:ring-2 focus:ring-primary border border-border-subtle transition shadow-xs"
                            :class="{ 'border-red-400': form.errors.password }"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition"
                        >
                            <EyeOff v-if="showPassword" class="w-5 h-5" />
                            <Eye v-else class="w-5 h-5" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-error-alert font-medium">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Options -->
                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded text-primary focus:ring-primary border-border-subtle cursor-pointer"
                        />
                        <span class="text-xs text-text-primary">Ingat sesi di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-3 border-t border-border-subtle">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full h-12 bg-primary hover:bg-primary-dark text-white rounded-xl font-bold text-sm shadow-lg shadow-primary/25 active:scale-[0.98] transition-all flex items-center justify-center gap-2 tracking-wide disabled:opacity-50 cursor-pointer"
                    >
                        <LogIn class="w-5 h-5" />
                        <span>{{ form.processing ? 'Memverifikasi...' : 'Masuk ke Terminal' }}</span>
                    </button>
                </div>

                <!-- Development Quick Login Helper -->
                <div class="mt-4 p-3 rounded-2xl bg-surface-container-low/70 border border-border-subtle text-xs">
                    <span class="font-bold text-text-primary block mb-2">Akun Uji Coba Cepat (Seeder):</span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="fillQuickLogin('kasir@foodislice.com', 'password123')"
                            class="px-2.5 py-1.5 bg-white border border-border-subtle rounded-lg text-primary font-semibold hover:bg-primary-subtle transition active:scale-95"
                        >
                            🧑‍🍳 Kasir (Sarah)
                        </button>
                        <button
                            type="button"
                            @click="fillQuickLogin('admin@foodislice.com', 'password123')"
                            class="px-2.5 py-1.5 bg-white border border-border-subtle rounded-lg text-text-primary font-semibold hover:bg-gray-100 transition active:scale-95"
                        >
                            👑 Admin (Owner)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
