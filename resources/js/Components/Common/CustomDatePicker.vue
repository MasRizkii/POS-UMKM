<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';
import { useDateTime } from '@/Composables/useDateTime';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih Tanggal',
    },
    label: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
const { timezone } = useDateTime();

const storeTodayYMD = () => {
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: timezone.value,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).formatToParts(new Date());
    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));
    return `${values.year}-${values.month}-${values.day}`;
};

const localDateFromYMD = (ymd) => new Date(`${ymd}T00:00:00`);

const isOpen = ref(false);
const containerRef = ref(null);

// Tanggal yang sedang aktif dilihat di kalender
const viewDate = ref(localDateFromYMD(props.modelValue || storeTodayYMD()));
if (isNaN(viewDate.value.getTime())) {
    viewDate.value = localDateFromYMD(storeTodayYMD());
}

const currentYear = computed(() => viewDate.value.getFullYear());
const currentMonth = computed(() => viewDate.value.getMonth());

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

// Navigasi Bulan
const prevMonth = () => {
    viewDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
};

const nextMonth = () => {
    viewDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
};

// Menghitung grid hari
const calendarDays = computed(() => {
    const year = currentYear.value;
    const month = currentMonth.value;

    const firstDayIndex = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();
    const prevLastDate = new Date(year, month, 0).getDate();

    const days = [];

    // Hari dari bulan sebelumnya
    for (let i = firstDayIndex - 1; i >= 0; i--) {
        const d = prevLastDate - i;
        const dateObj = new Date(year, month - 1, d);
        days.push({
            date: d,
            isCurrentMonth: false,
            dateString: formatDateYMD(dateObj),
        });
    }

    // Hari bulan saat ini
    const todayYMD = storeTodayYMD();
    for (let d = 1; d <= lastDate; d++) {
        const dateObj = new Date(year, month, d);
        const ymd = formatDateYMD(dateObj);
        days.push({
            date: d,
            isCurrentMonth: true,
            isToday: ymd === todayYMD,
            isSelected: ymd === props.modelValue,
            dateString: ymd,
        });
    }

    // Sisa grid 42 sel (6 baris x 7 hari)
    const remainingDays = 42 - days.length;
    for (let d = 1; d <= remainingDays; d++) {
        const dateObj = new Date(year, month + 1, d);
        days.push({
            date: d,
            isCurrentMonth: false,
            dateString: formatDateYMD(dateObj),
        });
    }

    return days;
});

const formatDateYMD = (date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const formatDisplayDate = (ymd) => {
    if (!ymd) return props.placeholder;
    const parts = ymd.split('-');
    if (parts.length !== 3) return ymd;
    const day = parseInt(parts[2], 10);
    const month = monthNames[parseInt(parts[1], 10) - 1];
    const year = parts[0];
    return `${day} ${month} ${year}`;
};

const selectDate = (dayObj) => {
    emit('update:modelValue', dayObj.dateString);
    isOpen.value = false;
};

const setToday = () => {
    const todayStr = storeTodayYMD();
    viewDate.value = localDateFromYMD(todayStr);
    emit('update:modelValue', todayStr);
    isOpen.value = false;
};

const clearDate = () => {
    emit('update:modelValue', '');
    isOpen.value = false;
};

// Tutup popover jika klik di luar
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative inline-block text-left w-full sm:w-auto">
        <label v-if="label" class="block text-[11px] font-bold text-text-muted uppercase mb-1">
            {{ label }}
        </label>

        <!-- Trigger Button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="h-9 px-3 bg-background border border-border-subtle hover:border-primary/40 rounded-xl text-xs font-semibold text-on-surface flex items-center justify-between gap-2 shadow-2xs transition-all cursor-pointer w-full sm:min-w-[150px]"
            :class="{ 'ring-2 ring-primary/20 border-primary': isOpen }"
        >
            <div class="flex items-center gap-2 truncate">
                <CalendarIcon class="w-4 h-4 text-primary shrink-0" />
                <span :class="modelValue ? 'text-on-surface font-bold' : 'text-text-muted'">
                    {{ formatDisplayDate(modelValue) }}
                </span>
            </div>
            <X
                v-if="modelValue"
                @click.stop="clearDate"
                class="w-3.5 h-3.5 text-text-muted hover:text-error-alert transition shrink-0"
                title="Hapus Tanggal"
            />
        </button>

        <!-- Custom Calendar Popover -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute left-0 sm:left-auto right-0 mt-2 z-50 w-72 bg-surface rounded-2xl shadow-xl border border-border-subtle p-3.5 flex flex-col gap-3 animate-in fade-in"
            >
                <!-- Month / Year Navigation Header -->
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-1 rounded-lg hover:bg-surface-container text-text-muted hover:text-on-surface transition cursor-pointer"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-xs font-bold text-on-surface">
                        {{ monthNames[currentMonth] }} {{ currentYear }}
                    </span>
                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1 rounded-lg hover:bg-surface-container text-text-muted hover:text-on-surface transition cursor-pointer"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

                <!-- Day Headers -->
                <div class="grid grid-cols-7 text-center">
                    <span
                        v-for="d in dayNames"
                        :key="d"
                        class="text-[10px] font-bold text-text-muted uppercase py-1"
                    >
                        {{ d }}
                    </span>
                </div>

                <!-- Days Grid -->
                <div class="grid grid-cols-7 gap-1 text-center">
                    <button
                        v-for="(day, idx) in calendarDays"
                        :key="idx"
                        type="button"
                        @click="selectDate(day)"
                        class="h-8 w-8 mx-auto rounded-lg text-xs font-semibold flex items-center justify-center transition-all cursor-pointer"
                        :class="[
                            day.isSelected
                                ? 'bg-primary text-white font-bold shadow-xs'
                                : day.isToday
                                ? 'bg-primary-subtle text-primary font-bold border border-primary/40'
                                : day.isCurrentMonth
                                ? 'text-on-surface hover:bg-surface-container-low'
                                : 'text-gray-300 hover:bg-surface-container-lowest',
                        ]"
                    >
                        {{ day.date }}
                    </button>
                </div>

                <!-- Quick Action Footer -->
                <div class="pt-2 border-t border-border-subtle flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="setToday"
                        class="text-primary font-bold hover:underline cursor-pointer"
                    >
                        Hari Ini
                    </button>
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-semibold cursor-pointer"
                    >
                        Selesai
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
