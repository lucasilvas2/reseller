<script setup>
import {ref, computed, onMounted, onUnmounted} from 'vue';
import {useForm, router, Link} from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Sales Components
import ProductSelector from '@/Components/Sales/ProductSelector.vue';
import ClientSelector from '@/Components/Sales/ClientSelector.vue';
import SaleItemRow from '@/Components/Sales/SaleItemRow.vue';
import SaleSummary from '@/Components/Sales/SaleSummary.vue';
import AppSidebarLayout from "@/Layouts/AppSidebarLayout.vue";

// Props
const props = defineProps({
    can: {
        type: Object,
        default: () => ({})
    }
});

// Reactive state
const selectedClient = ref(null);
const saleItems = ref([]);
const isSubmitting = ref(false);
const saleStatus = ref(null);
const statusMessage = ref('');
const processingProgress = ref(0);
const pollingInterval = ref(null);

// Form
const form = useForm({
    client_id: null,
    items: [],
    notes: '',
    status: 'pending'
});

// Computed
const canSubmit = computed(() => {
    return selectedClient.value &&
        saleItems.value.length > 0 &&
        !isSubmitting.value &&
        !hasStockErrors.value;
});

const hasStockErrors = computed(() => {
    return saleItems.value.some(item => {
        const requestedQty = parseFloat(item.quantity) || 0;
        const availableStock = item.product?.stock_quantity || 0;
        return requestedQty > availableStock;
    });
});

const totals = computed(() => {
    const subtotal = saleItems.value.reduce((sum, item) => {
        const quantity = parseFloat(item.quantity) || 0;
        const unitPrice = parseFloat(item.unit_price) || 0;
        return sum + (quantity * unitPrice);
    }, 0);

    const itemCount = saleItems.value.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity) || 0);
    }, 0);

    return {subtotal, itemCount};
});

// Methods
const handleClientSelected = (client) => {
    selectedClient.value = client;
    form.client_id = client.id;
    form.clearErrors('client_id');
};

const handleQuickCreateClient = async (clientData) => {
    try {
        const response = await window.axios.post('/ajax/clients/quick-create', clientData);

        const newClient = response.data.data || response.data;
        handleClientSelected(newClient);
        showMessage('Cliente criado com sucesso!', 'success');
    } catch (error) {
        console.error('Erro ao criar cliente:', error);
        showMessage('Erro ao criar cliente. Tente novamente.', 'error');
    }
};

const handleProductSelected = (product) => {
    const existingIndex = saleItems.value.findIndex(item => item.product_id === product.id);

    if (existingIndex >= 0) {
        saleItems.value[existingIndex].quantity += 1;
    } else {
        const newItem = {
            id: Date.now(),
            product_id: product.id,
            product: product,
            quantity: 1,
            unit_price: parseFloat(product.price) || 0
        };
        saleItems.value.push(newItem);
    }

    updateFormItems();
};

const handleQuantityUpdate = (index, newQuantity) => {
    if (newQuantity > 0 && saleItems.value[index]) {
        saleItems.value[index].quantity = newQuantity;
        updateFormItems();
    }
};

const handlePriceUpdate = (index, newPrice) => {
    if (newPrice >= 0 && saleItems.value[index]) {
        saleItems.value[index].unit_price = newPrice;
        updateFormItems();
    }
};

const handleRemoveItem = (index) => {
    if (saleItems.value[index]) {
        saleItems.value.splice(index, 1);
        updateFormItems();
        form.clearErrors(`items.${index}`);
    }
};

const updateFormItems = () => {
    form.items = saleItems.value.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price
    }));
};

const submitSale = () => {
    if (!canSubmit.value) return;

    updateFormItems();
    isSubmitting.value = true;
    saleStatus.value = 'pending';
    statusMessage.value = 'Enviando venda para processamento...';

    form.post(route('sales.store'), {
        onSuccess: (response) => {
            const saleId = response.props?.flash?.sale_id;
            if (saleId) {
                startStatusPolling(saleId);
            } else {
                router.visit(route('sales.index'));
            }
        },
        onError: (errors) => {
            isSubmitting.value = false;
            saleStatus.value = 'failed';
            statusMessage.value = 'Erro ao processar venda. Verifique os dados.';
            console.error('Validation errors:', errors);
        },
        onFinish: () => {
            if (!pollingInterval.value) {
                isSubmitting.value = false;
            }
        }
    });
};

const startStatusPolling = (saleId) => {
    let attempts = 0;
    const maxAttempts = 60;

    pollingInterval.value = setInterval(async () => {
        attempts++;

        try {
            const response = await window.axios.get(`/ajax/sales/${saleId}/status`);
            const data = response.data;

            saleStatus.value = data.status;
            processingProgress.value = Math.min((attempts / maxAttempts) * 100, 95);

            switch (data.status) {
                case 'pending':
                    statusMessage.value = 'Venda na fila de processamento...';
                    break;
                case 'processing':
                    statusMessage.value = 'Processando venda e atualizando estoque...';
                    break;
                case 'completed':
                    statusMessage.value = 'Venda processada com sucesso!';
                    processingProgress.value = 100;
                    stopPolling();
                    setTimeout(() => {
                        router.visit(route('sales.show', saleId));
                    }, 2000);
                    break;
                case 'failed':
                    statusMessage.value = data.message || 'Falha no processamento da venda.';
                    stopPolling();
                    break;
            }
        } catch (error) {
            console.error('Error polling status:', error);
            if (attempts >= maxAttempts) {
                statusMessage.value = 'Timeout ao verificar status.';
                stopPolling();
            }
        }

        if (attempts >= maxAttempts) {
            statusMessage.value = 'Timeout ao verificar status.';
            stopPolling();
        }
    }, 5000);
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
        isSubmitting.value = false;
    }
};

const showMessage = (message, type = 'info') => {
    statusMessage.value = message;
    setTimeout(() => statusMessage.value = '', 5000);
};

const retrySubmission = () => {
    saleStatus.value = null;
    statusMessage.value = '';
    processingProgress.value = 0;
    submitSale();
};

const cancelAndGoBack = () => {
    stopPolling();
    router.visit(route('sales.index'));
};

// Keyboard shortcuts
const handleKeyboardShortcuts = (event) => {
    if (event.ctrlKey || event.metaKey) {
        switch (event.key) {
            case 's':
                event.preventDefault();
                if (canSubmit.value) submitSale();
                break;
            case 'Escape':
                event.preventDefault();
                cancelAndGoBack();
                break;
        }
    }
};

// Lifecycle
onMounted(() => {
    document.addEventListener('keydown', handleKeyboardShortcuts);
    setTimeout(() => {
        document.querySelector('#client-search')?.focus();
    }, 100);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboardShortcuts);
    stopPolling();
});
</script>

<template>
    <AppSidebarLayout title="Create Sale">
        <template #header>
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4">
                    <!-- Breadcrumb e Título -->
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <!-- Breadcrumb -->
                            <nav class="flex mb-2" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li class="inline-flex items-center">
                                        <Link :href="route('sales.index')"
                                           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            Sales
                                        </Link>
                                    </li>
                                    <li aria-current="page">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                      clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Create</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Create Sale
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">
                            <div class="text-sm text-gray-500">
                                <kbd class="px-2 py-1 bg-gray-100 rounded">Ctrl+S</kbd> salvar •
                                <kbd class="px-2 py-1 bg-gray-100 rounded">Esc</kbd> cancelar
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-4">

                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Status Indicator -->
                <div v-if="saleStatus" class="mb-6">
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-900">Status do Processamento</h3>
                            <button
                                v-if="saleStatus === 'failed'"
                                @click="retrySubmission"
                                class="text-sm text-blue-600 hover:text-blue-800"
                            >
                                Tentar Novamente
                            </button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="{
                                    'bg-blue-600': saleStatus === 'pending' || saleStatus === 'processing',
                                    'bg-green-600': saleStatus === 'completed',
                                    'bg-red-600': saleStatus === 'failed'
                                }"
                                :style="{ width: processingProgress + '%' }"
                            ></div>
                        </div>

                        <p class="text-sm" :class="{
                            'text-blue-600': saleStatus === 'pending' || saleStatus === 'processing',
                            'text-green-600': saleStatus === 'completed',
                            'text-red-600': saleStatus === 'failed'
                        }">
                            {{ statusMessage }}
                        </p>
                    </div>
                </div>

                <!-- Main Form -->
                <form @submit.prevent="submitSale" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <!-- Left Column - Form -->
                        <div class="lg:col-span-2 space-y-6">

                            <!-- Client Selection -->
                            <div class="bg-white rounded-lg shadow-sm border p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Cliente</h3>
                                <ClientSelector
                                    v-model="selectedClient"
                                    :error="form.errors.client_id"
                                    :disabled="isSubmitting"
                                    @client-selected="handleClientSelected"
                                    @quick-create="handleQuickCreateClient"
                                />
                            </div>

                            <!-- Product Selection -->
                            <div class="bg-white rounded-lg shadow-sm border p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Adicionar Produtos</h3>
                                <ProductSelector
                                    :disabled="isSubmitting"
                                    @product-selected="handleProductSelected"
                                />
                            </div>

                            <!-- Sale Items -->
                            <div v-if="saleItems.length > 0" class="bg-white rounded-lg shadow-sm border p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Itens da Venda ({{ saleItems.length }})
                                </h3>

                                <!-- Stock Warning -->
                                <div v-if="hasStockErrors"
                                     class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.732 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <div>
                                            <h4 class="text-sm font-medium text-red-800">Problemas de Estoque</h4>
                                            <p class="text-sm text-red-700 mt-1">
                                                Alguns itens excedem o estoque disponível.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <SaleItemRow
                                        v-for="(item, index) in saleItems"
                                        :key="item.id"
                                        :item="item"
                                        :index="index"
                                        :errors="form.errors"
                                        :disabled="isSubmitting"
                                        @update:quantity="handleQuantityUpdate"
                                        @update:unit-price="handlePriceUpdate"
                                        @remove-item="handleRemoveItem"
                                    />
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="bg-white rounded-lg shadow-sm border p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Observações</h3>
                                <div>
                                    <InputLabel for="notes" value="Observações (opcional)"/>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        rows="3"
                                        :disabled="isSubmitting"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="Observações sobre a venda..."
                                    ></textarea>
                                    <InputError :message="form.errors.notes" class="mt-2"/>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Summary -->
                        <div class="lg:col-span-1">
                            <div class="sticky top-6">
                                <SaleSummary :items="saleItems">
                                    <template #actions>
                                        <div class="space-y-3">
                                            <!-- Submit Button -->
                                            <PrimaryButton
                                                type="submit"
                                                :disabled="!canSubmit"
                                                class="w-full justify-center"
                                            >
                                                <svg v-if="isSubmitting"
                                                     class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                                                     viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                {{ isSubmitting ? 'Processando...' : 'Criar Venda' }}
                                            </PrimaryButton>

                                            <!-- Cancel Button -->
                                            <button
                                                type="button"
                                                @click="cancelAndGoBack"
                                                :disabled="isSubmitting && saleStatus === 'processing'"
                                                class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                            >
                                                {{ isSubmitting ? 'Aguarde...' : 'Cancelar' }}
                                            </button>

                                            <!-- Validation Info -->
                                            <div v-if="!canSubmit && (selectedClient || saleItems.length > 0)"
                                                 class="text-sm text-gray-500 space-y-1">
                                                <p v-if="!selectedClient">• Selecione um cliente</p>
                                                <p v-if="saleItems.length === 0">• Adicione produtos</p>
                                                <p v-if="hasStockErrors">• Corrija problemas de estoque</p>
                                            </div>
                                        </div>
                                    </template>
                                </SaleSummary>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Empty State -->
                <div v-if="saleItems.length === 0 && selectedClient"
                     class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum produto adicionado</h3>
                    <p class="mt-1 text-sm text-gray-500">Comece adicionando produtos à venda.</p>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
