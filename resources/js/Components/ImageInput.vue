<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    modelValue: File,
    autofocus: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const input = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);

// Show preview when modelValue changes
watch(() => props.modelValue, (file) => {
    if (file instanceof File) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
});

// Autofocus
onMounted(() => {
    if (props.autofocus && input.value) {
        input.value.focus();
    }
});

// Expose focus method
defineExpose({ focus: () => input.value?.focus() });

// Handle file change
function onFileChange(event: Event) {
    const files = (event.target as HTMLInputElement).files;
    if (files && files[0]) {
        emit('update:modelValue', files[0]);
    }
}
</script>

<template>
    <div>
        <input
            ref="input"
            type="file"
            accept="image/*"
            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
            @change="onFileChange"
        >
        <div v-if="previewUrl" class="mt-2">
            <img :src="previewUrl" alt="Preview" class="max-h-32 rounded" />
        </div>
    </div>
</template>

<style scoped>
/* Add any custom styles if needed */
</style>
