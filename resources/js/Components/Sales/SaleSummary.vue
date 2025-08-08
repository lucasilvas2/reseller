<script setup>
import { computed } from 'vue';

// Props
const props = defineProps({
    items: {
        type: Array,
        required: true,
        default: () => []
    },
    discount: {
        type: Number,
        default: 0
    },
    tax: {
        type: Number,
        default: 0
    },
    showTax: {
        type: Boolean,
        default: false
    },
    showDiscount: {
        type: Boolean,
        default: false
    },
    currency: {
        type: String,
        default: 'BRL'
    }
});

// Computed
const itemsCount = computed(() => {
    return props.items.reduce((total, item) => {
        return total + (parseFloat(item.quantity) || 0);
    }, 0);
});

const subtotalValue = computed(() => {
    return props.items.reduce((total, item) => {
        const quantity = parseFloat(item.quantity) || 0;
        const unitPrice = parseFloat(item.unit_price) || 0;
        return total + (quantity * unitPrice);
    }, 0);
});

const discountValue = computed(() => {
    return parseFloat(props.discount) || 0;
});

const taxValue = computed(() => {
    const base = subtotalValue.value - discountValue.value;
    return (base * (parseFloat(props.tax) || 0)) / 100;
});

const totalValue = computed(() => {
    return subtotalValue.value - discountValue.value + taxValue.value;
});

// Formatters
const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: props.currency
    }).format(value);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('pt-BR').format(value);
};
</script>

<template>
    <div class="bg-white border border-gray-300 rounded-lg shadow-sm">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 rounded-t-lg">
            <h3 class="text-lg font-semibold text-gray-900">Resumo da Venda</h3>
        </div>

        <!-- Content -->
        <div class="p-4 space-y-4">
            <!-- Items Summary -->
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">Total de itens:</span>
                <span class="font-medium text-gray-900">
                    {{ formatNumber(itemsCount) }}
                    {{ itemsCount === 1 ? 'item' : 'itens' }}
                </span>
            </div>

            <!-- Items List (Brief) -->
            <div v-if="items.length > 0" class="border-t border-gray-100 pt-3">
                <div class="space-y-2">
                    <div v-for="(item, index) in items" :key="index"
                         class="flex justify-between items-center text-xs text-gray-600">
                        <div class="flex-1 truncate">
                            <span class="font-medium">{{ item.product?.name || 'Produto' }}</span>
                            <span class="ml-1">({{ formatNumber(item.quantity) }}x)</span>
                        </div>
                        <div class="ml-2 font-medium">
                            {{ formatCurrency((item.quantity || 0) * (item.unit_price || 0)) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="border-t border-gray-200 pt-4 space-y-3">
                <!-- Subtotal -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium text-gray-900">{{ formatCurrency(subtotalValue) }}</span>
                </div>

                <!-- Discount -->
                <div v-if="showDiscount && discountValue > 0"
                     class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Desconto:</span>
                    <span class="font-medium text-red-600">-{{ formatCurrency(discountValue) }}</span>
                </div>

                <!-- Tax -->
                <div v-if="showTax && taxValue > 0"
                     class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Impostos ({{ tax }}%):</span>
                    <span class="font-medium text-gray-900">{{ formatCurrency(taxValue) }}</span>
                </div>

                <!-- Total -->
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total:</span>
                        <span class="text-xl font-bold text-gray-900">{{ formatCurrency(totalValue) }}</span>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="items.length === 0"
                 class="text-center py-8 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                <p class="text-sm">Nenhum item adicionado</p>
                <p class="text-xs text-gray-400 mt-1">Adicione produtos para ver o resumo</p>
            </div>
        </div>

        <!-- Footer Actions Slot -->
        <div v-if="$slots.actions" class="px-4 py-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
            <slot name="actions"></slot>
        </div>
    </div>
</template>
