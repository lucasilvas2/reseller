<script setup>
import { onMounted, ref } from 'vue';

// Define props
const props = defineProps({
    modelValue: {
        type: String,
        required: true
    },
    pattern: {
        type: String,
        default: ''
    },
    typeInput: {
        type: String,
        default: 'text',
        validator: (value) => {
            return ['text', 'search', 'url', 'tel', 'email', 'password'].includes(value);
        }
    },
    autofocus: {
        type: Boolean,
        default: false
    }
});

// Define emits
defineEmits(['update:modelValue']);

// Create a ref for the input element
const input = ref(null);

// Handle autofocus on mount
onMounted(() => {
    if (props.autofocus && input.value) {
        input.value.focus();
    }
});

// Expose the focus method
defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        ref="input"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        :value="modelValue"
        :type="typeInput"
        v-bind="pattern ? { pattern } : {}"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
