<script setup>
import { ref, computed, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

// Props
const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    index: {
        type: Number,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    },
    disabled: {
        type: Boolean,
        default: false
    },
    showRemoveButton: {
        type: Boolean,
        default: true
    }
});

// Emits
const emit = defineEmits(['update:quantity', 'update:unit-price', 'remove-item']);

// Reactive data
const localQuantity = ref(String(props.item.quantity));
const localUnitPrice = ref(String(props.item.unit_price));

// Computed
const subtotal = computed(() => {
    const qty = parseFloat(localQuantity.value) || 0;
    const price = parseFloat(localUnitPrice.value) || 0;
    return qty * price;
});

const formattedSubtotal = computed(() => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(subtotal.value);
});

const formattedUnitPrice = computed({
    get() {
        return String(localUnitPrice.value);
    },
    set(value) {
        localUnitPrice.value = String(value);
    }
});

const stockWarning = computed(() => {
    const requestedQty = parseFloat(localQuantity.value) || 0;
    const availableStock = props.item.product?.stock_quantity || 0;

    if (requestedQty > availableStock) {
        return `Quantidade solicitada (${requestedQty}) excede o estoque disponível (${availableStock})`;
    }
    return null;
});

const hasStockError = computed(() => {
    return stockWarning.value !== null;
});

// Methods
const updateQuantity = (value) => {
    const numericValue = parseInt(value) || 1;
    if (numericValue >= 1) {
        localQuantity.value = String(numericValue);
        emit('update:quantity', props.index, numericValue);
    }
};

const updateUnitPrice = (value) => {
    const numericValue = parseFloat(value) || 0;
    if (numericValue >= 0) {
        localUnitPrice.value = String(numericValue);
        emit('update:unit-price', props.index, numericValue);
    }
};

const removeItem = () => {
    emit('remove-item', props.index);
};

const incrementQuantity = () => {
    updateQuantity(parseInt(localQuantity.value) + 1);
};

const decrementQuantity = () => {
    const currentQty = parseInt(localQuantity.value);
    if (currentQty > 1) {
        updateQuantity(currentQty - 1);
    }
};

// Watchers
watch(() => props.item.quantity, (newVal) => {
    localQuantity.value = String(newVal);
});

watch(() => props.item.unit_price, (newVal) => {
    localUnitPrice.value = String(newVal);
});
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
        <!-- Product Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-medium text-gray-900">{{ item.product.name }}</h3>
                </div>
                <div class="mt-1 text-sm text-gray-600 space-y-1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <span>SKU: {{ item.product.sku }}</span>
                        <span>Estoque: {{ item.product.stock_quantity }} unidades</span>
                        <span>Preço: {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(item.product.price) }}</span>
                    </div>
                </div>
            </div>

            <!-- Remove Button -->
            <button
                v-if="showRemoveButton && !disabled"
                type="button"
                @click="removeItem"
                class="ml-4 p-1 text-gray-400 hover:text-red-600 focus:outline-none focus:text-red-600"
                title="Remover item"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>

        <!-- Quantity and Price Controls -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Quantity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Quantidade *
                </label>
                <div class="flex items-center">
                    <button
                        type="button"
                        @click="decrementQuantity"
                        :disabled="disabled || parseInt(localQuantity) <= 1"
                        class="flex-shrink-0 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed text-gray-600 hover:text-gray-700 h-10 w-10 rounded-l-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </button>

                    <TextInput
                        v-model="localQuantity"
                        type="number"
                        min="1"
                        step="1"
                        :disabled="disabled"
                        @input="updateQuantity($event.target.value)"
                        class="text-center border-l-0 border-r-0 rounded-none h-10 w-20"
                    />

                    <button
                        type="button"
                        @click="incrementQuantity"
                        :disabled="disabled"
                        class="flex-shrink-0 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed text-gray-600 hover:text-gray-700 h-10 w-10 rounded-r-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </button>
                </div>

                <!-- Stock Warning -->
                <div v-if="hasStockError" class="text-xs text-red-600 mt-1">
                    {{ stockWarning }}
                </div>

                <!-- Quantity Error -->
                <InputError :message="errors[`items.${index}.quantity`]" class="mt-1" />
            </div>

            <!-- Unit Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Preço Unitário
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">R$</span>
                    <TextInput
                        v-model="formattedUnitPrice"
                        type="number"
                        min="0"
                        step="0.01"
                        :disabled="disabled"
                        @input="updateUnitPrice($event.target.value)"
                        class="pl-8 w-full"
                        placeholder="0,00"
                    />
                </div>
                <InputError :message="errors[`items.${index}.unit_price`]" class="mt-1" />
            </div>

            <!-- Subtotal -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Subtotal
                </label>
                <div class="flex items-center h-10 px-3 bg-gray-50 border border-gray-300 rounded-md">
                    <span class="font-medium text-gray-900">{{ formattedSubtotal }}</span>
                </div>
            </div>
        </div>

        <!-- Additional Product Info (Mobile) -->
        <div class="md:hidden mt-4 pt-4 border-t border-gray-200">
            <div class="text-xs text-gray-500 space-y-1">
                <div>Estoque disponível: {{ item.product.stock_quantity }} unidades</div>
                <div>Preço sugerido: {{ new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(item.product.price) }}</div>
            </div>
        </div>
    </div>
</template>
