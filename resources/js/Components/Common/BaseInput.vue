<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative rounded-xl">
            <input
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                @input="$emit('update:modelValue', $event.target.value)"
                class="w-full px-3.5 py-2.5 text-sm bg-white border rounded-xl transition duration-150 focus:outline-none focus:ring-2 disabled:bg-gray-50 disabled:text-gray-500"
                :class="[
                    error
                        ? 'border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-200 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500',
                ]"
            />
        </div>
        <p v-if="error" class="mt-1.5 text-xs text-red-600 font-medium">
            {{ error }}
        </p>
    </div>
</template>
