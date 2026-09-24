import { computed, onBeforeUnmount, ref, watch } from 'vue';

export function useImagePreview(file, currentUrl) {
    const objectUrl = ref('');
    const revoke = () => {
        if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = '';
    };

    watch(file, (value) => {
        revoke();
        if (value) objectUrl.value = URL.createObjectURL(value);
    });
    onBeforeUnmount(revoke);

    return computed(() => objectUrl.value || currentUrl() || '');
}
