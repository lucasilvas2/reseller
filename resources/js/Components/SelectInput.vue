<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
    required: Boolean,
    options: {
        type: Array,
        required: true,
    },
});

defineEmits(['update:modelValue']);

const select = ref(null);

onMounted(() => {
    if (select.value.hasAttribute('autofocus')) {
        select.value.focus();
    }
});

defineExpose({ focus: () => select.value.focus() });
</script>

<template>
    <select
        ref="select"
        class="dark:bg-gray-700 dark:text-white border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        :value="modelValue"
        :required="required"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>
