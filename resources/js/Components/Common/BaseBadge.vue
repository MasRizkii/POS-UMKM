<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default', // default, success, danger, warning, info
    },
    size: {
        type: String,
        default: 'sm', // sm, md
    },
});

const variantClasses = {
    default: 'bg-gray-100 text-gray-700 border-gray-200',
    success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    danger: 'bg-rose-50 text-rose-700 border-rose-200',
    warning: 'bg-amber-50 text-amber-700 border-amber-200',
    info: 'bg-blue-50 text-blue-700 border-blue-200',
};

const sizeClasses = {
    sm: 'text-[11px] px-2 py-0.5',
    md: 'text-xs px-2.5 py-1',
};
</script>

<template>
    <span
        class="inline-flex items-center font-medium rounded-full border"
        :class="[variantClasses[variant] || variantClasses.default, sizeClasses[size] || sizeClasses.sm]"
    >
        <slot />
    </span>
</template>
