<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

// Props
const props = defineProps({
    modelValue: {
        type: Object,
        default: null
    },
    error: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: 'Buscar produto por nome, SKU ou código...'
    }
});

// Emits
const emit = defineEmits(['update:modelValue', 'product-selected']);

// Reactive data
const searchQuery = ref('');
const isOpen = ref(false);
const isLoading = ref(false);
const products = ref([]);
const searchInput = ref(null);

// Computed
const filteredProducts = computed(() => {
    if (!searchQuery.value || searchQuery.value.length < 2) return [];
    return products.value.filter(product =>
        product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        product.sku.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (product.barcode && product.barcode.includes(searchQuery.value))
    );
});

// Methods
const searchProducts = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        products.value = [];
        isOpen.value = false;
        return;
    }

    isLoading.value = true;

    try {
        const response = await window.axios.get(`/ajax/products/search?q=${encodeURIComponent(searchQuery.value)}`);
        const data = response.data;
        products.value = data.data || [];
        isOpen.value = true;
    } catch (error) {
        console.error('Erro ao buscar produtos:', error);
        products.value = [];
    } finally {
        isLoading.value = false;
    }
};

const selectProduct = (product) => {
    // Emite o produto selecionado
    emit('product-selected', product);

    // Limpa o campo de busca para permitir nova seleção
    searchQuery.value = '';
    isOpen.value = false;
    products.value = [];

    // Foca novamente no input para próxima busca
    setTimeout(() => {
        searchInput.value?.focus();
    }, 100);
};

const clearSelection = () => {
    searchQuery.value = '';
    isOpen.value = false;
    products.value = [];
    searchInput.value?.focus();
};

const handleInputFocus = () => {
    if (searchQuery.value.length >= 2) {
        isOpen.value = true;
    }
};

const handleClickOutside = (event) => {
    if (!event.target.closest('.product-selector-container')) {
        isOpen.value = false;
    }
};

// Watchers
watch(searchQuery, (newVal) => {
    if (newVal && newVal.length >= 2) {
        searchProducts();
    } else {
        isOpen.value = false;
        products.value = [];
    }
});

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Expose focus method
defineExpose({ focus: () => searchInput.value?.focus() });
</script>

<template>
    <div class="product-selector-container relative">
        <!-- Label -->
        <InputLabel for="product-search" value="Buscar e Adicionar Produto *" />

        <!-- Search Input -->
        <div class="mt-1 relative">
            <TextInput
                id="product-search"
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                :placeholder="placeholder"
                :disabled="disabled"
                @focus="handleInputFocus"
                class="w-full"
                autocomplete="off"
            />

            <!-- Loading Spinner -->
            <div v-if="isLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Clear button when there's text -->
            <div v-else-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button
                    type="button"
                    @click="clearSelection"
                    :disabled="disabled"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                    title="Limpar busca"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Helpful hint -->
        <p class="mt-1 text-xs text-gray-500">
            Digite pelo menos 2 caracteres para buscar produtos. Clique em um produto para adicioná-lo à venda.
        </p>

        <!-- Dropdown Results -->
        <div v-if="isOpen && filteredProducts.length > 0"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
            <ul class="py-1">
                <li v-for="product in filteredProducts"
                    :key="product.id"
                    @click="selectProduct(product)"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">{{ product.name }}</div>
                            <div class="text-sm text-gray-500">SKU: {{ product.sku }}</div>
                            <div v-if="product.barcode" class="text-xs text-gray-400">Código: {{ product.barcode }}</div>
                        </div>
                        <div class="text-right ml-4">
                            <div class="text-sm font-medium text-gray-900">R$ {{ product.price }}</div>
                            <div class="text-xs" :class="product.stock_quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                Estoque: {{ product.stock_quantity }}
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- No Results -->
        <div v-else-if="isOpen && searchQuery.length >= 2 && !isLoading && filteredProducts.length === 0"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
            <div class="px-4 py-3 text-gray-500 text-center">
                Nenhum produto encontrado para "{{ searchQuery }}"
            </div>
        </div>

        <!-- Error Message -->
        <InputError :message="error" class="mt-2" />
    </div>
</template>
