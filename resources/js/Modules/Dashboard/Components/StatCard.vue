<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    suffix: {
        type: String,
        default: '',
    },
    trend: {
        type: String,
        default: '',
    },
    icon: {
        type: Object,
        required: true,
    },
    variant: {
        type: String,
        default: 'primary', // primary, secondary, tertiary, default
    },
});

const variantStyles = {
    primary: 'text-primary bg-primary-subtle',
    secondary: 'text-secondary bg-emerald-50',
    tertiary: 'text-tertiary bg-cyan-50',
    default: 'text-text-primary bg-gray-100',
};
</script>

<template>
    <div class="bg-surface rounded-2xl p-5 shadow-sm border border-border-subtle flex flex-col justify-between gap-3 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex flex-col">
                <span class="text-xs font-semibold text-text-muted">{{ title }}</span>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-xl sm:text-2xl font-black text-text-primary tracking-tight">
                        {{ value }}
                    </span>
                    <span v-if="suffix" class="text-xs font-semibold text-text-muted">
                        {{ suffix }}
                    </span>
                </div>
            </div>

            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                :class="variantStyles[variant] || variantStyles.primary"
            >
                <component :is="icon" class="w-5 h-5" />
            </div>
        </div>

        <div v-if="trend" class="flex items-center gap-1 text-[11px] font-bold text-secondary">
            <span class="px-2 py-0.5 rounded-full bg-secondary-container/30 border border-secondary-container">
                {{ trend }}
            </span>
        </div>
    </div>
</template>
