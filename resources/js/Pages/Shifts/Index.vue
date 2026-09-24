<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCurrency } from '@/Composables/useCurrency';
import { useDateTime } from '@/Composables/useDateTime';

const props = defineProps({ activeShift: Object, shifts: Object });
const { formatRupiah } = useCurrency();
const { formatDateTime, timeZoneLabel } = useDateTime();
const openForm = useForm({ opening_cash: 0 });
const closeForm = useForm({ actual_cash: 0 });

const openShift = () => openForm.post(route('shifts.open'));
const closeShift = () => closeForm.post(route('shifts.close', props.activeShift.id));
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shift Kasir" />
        <main class="max-w-3xl mx-auto p-4 sm:p-6 space-y-5">
            <section class="bg-surface rounded-2xl border border-border-subtle p-5">
                <h1 class="text-xl font-black">Shift Kasir</h1>
                <form v-if="!activeShift" class="mt-4 space-y-3" @submit.prevent="openShift">
                    <label class="block text-sm font-bold">Kas awal</label>
                    <input v-model.number="openForm.opening_cash" type="number" min="0" class="w-full rounded-xl border p-3" />
                    <button class="rounded-xl bg-primary px-4 py-3 text-white font-bold" :disabled="openForm.processing">Buka Shift</button>
                </form>
                <form v-else class="mt-4 space-y-3" @submit.prevent="closeShift">
                    <p>Kas awal: <b>{{ formatRupiah(activeShift.opening_cash) }}</b></p>
                    <p>Cash sales: <b>{{ formatRupiah(activeShift.cash_sales) }}</b></p>
                    <p>Expected cash: <b>{{ formatRupiah(activeShift.expected_cash) }}</b></p>
                    <label class="block text-sm font-bold">Kas fisik aktual</label>
                    <input v-model.number="closeForm.actual_cash" type="number" min="0" class="w-full rounded-xl border p-3" />
                    <button class="rounded-xl bg-primary px-4 py-3 text-white font-bold" :disabled="closeForm.processing">Tutup Shift</button>
                </form>
            </section>
            <section class="bg-surface rounded-2xl border border-border-subtle p-5">
                <h2 class="font-black">Riwayat Shift</h2>
                <div v-for="shift in shifts.data" :key="shift.id" class="py-3 border-b text-sm flex justify-between">
                    <span>{{ shift.user?.name || 'Kasir' }} - {{ shift.status }}<small class="block text-text-muted">{{ formatDateTime(shift.opened_at) }} {{ timeZoneLabel }}</small></span>
                    <span>{{ formatRupiah(shift.expected_cash) }}</span>
                </div>
            </section>
        </main>
    </AuthenticatedLayout>
</template>
