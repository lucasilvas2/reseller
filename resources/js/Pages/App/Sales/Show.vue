<template>
    <AppLayout title="Sale Details">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Sale Details #{{ sale.id }}
                </h2>
                <div class="flex space-x-3">
                    <a :href="route('sales.index')"
                       class="text-gray-600 hover:text-gray-900 text-sm bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md">
                        ← Back to List
                    </a>
                    <a :href="route('sales.edit', sale.id)"
                       class="text-blue-600 hover:text-blue-900 text-sm bg-blue-100 hover:bg-blue-200 px-3 py-2 rounded-md">
                        Edit Sale
                    </a>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">

                    <!-- Header Card -->
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Sale Information
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Created on {{ formatDate(sale.created_at) }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <StatusBadge
                                    :status="sale.status"
                                    type="sale"
                                    :show-pulse="sale.status === 'processing'"
                                />
                                <span class="text-2xl font-bold text-green-600">
                                    R$ {{ formatPrice(sale.total_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">

                        <!-- Client Information -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Client Information</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Client Name:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.client?.name || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Email:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.client?.email || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Phone:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.client?.phone || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Document:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.client?.document || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Store Information -->
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Store Information</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Store Name:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.store?.name || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Address:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.store?.address || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Phone:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ sale.store?.phone || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sale Details -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Sale Details</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Sale ID:</span>
                                        <span class="text-sm text-gray-900">#{{ sale.id }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Status:</span>
                                        <StatusBadge
                                            :status="sale.status"
                                            type="sale"
                                            :show-pulse="sale.status === 'processing'"
                                        />
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Total Items:</span>
                                        <span class="text-sm text-gray-900">{{ getTotalItems() }} items</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Unique Products:</span>
                                        <span class="text-sm text-gray-900">{{ orderItems?.length || 0 }} products</span>
                                    </div>
                                    <div class="flex justify-between border-t pt-3">
                                        <span class="text-sm font-medium text-gray-700">Total Amount:</span>
                                        <span class="text-lg font-bold text-green-600">
                                            R$ {{ formatPrice(sale.total_amount) }}
                                        </span>
                                    </div>
                                    <div v-if="sale.notes" class="border-t pt-3">
                                        <span class="text-sm font-medium text-gray-500">Notes:</span>
                                        <p class="text-sm text-gray-900 mt-1 bg-white p-2 rounded border">
                                            {{ sale.notes }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Timestamps -->
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Timeline</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Created:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ formatDateTime(sale.created_at) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Last Updated:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ formatDateTime(sale.updated_at) }}
                                        </span>
                                    </div>
                                    <div v-if="sale.updated_at !== sale.created_at"
                                         class="flex justify-between border-t pt-3">
                                        <span class="text-sm font-medium text-gray-500">Modified:</span>
                                        <span class="text-sm text-orange-600">Yes</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Summary -->
                            <div v-if="orderItems && orderItems.length > 0">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Financial Summary</h4>
                                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Total Cost:</span>
                                        <span class="text-sm text-gray-900">
                                            R$ {{ formatPrice(getTotalCost()) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Subtotal:</span>
                                        <span class="text-sm text-gray-900">
                                            R$ {{ formatPrice(getSubtotal()) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Total Profit:</span>
                                        <span class="text-sm font-bold" :class="getTotalProfitClass()">
                                            R$ {{ formatPrice(getTotalProfit()) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Profit Margin:</span>
                                        <span class="text-sm font-bold text-blue-600">
                                            {{ getProfitMarginPercentage() }}%
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-t pt-3">
                                        <span class="text-sm font-medium text-gray-700">Final Total:</span>
                                        <span class="text-lg font-bold text-green-600">
                                            R$ {{ formatPrice(sale.total_amount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items Section -->
                    <div class="p-6 border-t border-gray-200">
                        <div v-if="orderItems && orderItems.length > 0">
                            <div class="flex items-center justify-between mb-6">
                                <h4 class="text-lg font-medium text-gray-900">Order Items</h4>
                                <div class="flex space-x-4 text-sm text-gray-600">
                                    <span>{{ orderItems.length }} {{ orderItems.length === 1 ? 'item' : 'items' }}</span>
                                    <span class="border-l pl-4">Total Qty: {{ getTotalItems() }}</span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product Details
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                SKU / Barcode
                                            </th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Cost Price
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Unit Price
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Profit
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total
                                            </th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in orderItems" :key="item.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center space-x-3">
                                                    <div class="flex-shrink-0">
                                                        <div class="h-10 w-10 bg-gray-200 rounded-lg flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ item.product_sku?.products?.name || 'N/A' }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ item.product_sku?.products?.brand?.name || 'No Brand' }}
                                                        </div>
                                                        <div v-if="item.product_sku?.products?.description" class="text-xs text-gray-400 mt-1 max-w-xs truncate">
                                                            {{ item.product_sku?.products?.description }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <span class="font-medium">SKU:</span> {{ item.product_sku?.sku || 'N/A' }}
                                                </div>
                                                <div v-if="item.product_sku?.barcode" class="text-sm text-gray-500">
                                                    <span class="font-medium">Barcode:</span> {{ item.product_sku?.barcode }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ item.quantity }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-500">
                                                R$ {{ formatPrice(item.product_sku?.cost_price || 0) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                                R$ {{ formatPrice(item.unit_price) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium" :class="getItemProfitClass(item)">
                                                R$ {{ formatPrice(getItemProfit(item)) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                                R$ {{ formatPrice(item.total_price) }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex flex-col items-center space-y-1">
                                                    <StatusBadge
                                                        :status="item.status"
                                                        type="item"
                                                        :show-pulse="item.status === 'processing'"
                                                    />
                                                    <div
                                                        v-if="item.error_message"
                                                        class="text-xs text-red-600 max-w-32 truncate"
                                                        :title="item.error_message"
                                                    >
                                                        {{ item.error_message }}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900">
                                                Totals
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm font-bold text-blue-600">
                                                {{ getTotalItems() }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-600">
                                                R$ {{ formatPrice(getTotalCost()) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-600">
                                                -
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-bold" :class="getTotalProfitClass()">
                                                R$ {{ formatPrice(getTotalProfit()) }}
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-bold text-green-600">
                                                R$ {{ formatPrice(sale.total_amount) }}
                                            </td>
                                            <td class="px-6 py-4"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Items Summary Cards -->
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div class="text-blue-600 text-sm font-medium">Total Items</div>
                                    <div class="text-2xl font-bold text-blue-900">{{ orderItems.length }}</div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div class="text-purple-600 text-sm font-medium">Total Quantity</div>
                                    <div class="text-2xl font-bold text-purple-900">{{ getTotalItems() }}</div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="text-gray-600 text-sm font-medium">Total Cost</div>
                                    <div class="text-2xl font-bold text-gray-900">R$ {{ formatPrice(getTotalCost()) }}</div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div class="text-green-600 text-sm font-medium">Profit Margin</div>
                                    <div class="text-2xl font-bold text-green-900">{{ getProfitMarginPercentage() }}%</div>
                                </div>
                            </div>

                            <!-- Additional Sale Information -->
                            <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                                <h5 class="text-md font-medium text-gray-900 mb-3">Sale Analytics</h5>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Average Item Price:</span>
                                        <span class="font-medium">R$ {{ formatPrice(getAverageItemPrice()) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Highest Item Value:</span>
                                        <span class="font-medium">R$ {{ formatPrice(getHighestItemValue()) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Most Profitable Item:</span>
                                        <span class="font-medium">R$ {{ formatPrice(getMostProfitableItem()) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- No items message -->
                        <div v-else class="text-center py-8">
                            <div class="text-gray-400 mb-2">
                                <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-4.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-2.172a1 1 0 01-.707-.293l-2.414-2.414a1 1 0 00-.707-.293H4"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No Items Found</h3>
                            <p class="text-gray-500">This sale doesn't have any items associated with it.</p>
                        </div>
                    </div>

                    <!-- Retry Section for Failed Items -->
                    <div v-if="hasFailedItems() || sale.status === 'processing'" class="p-6 border-t border-gray-200 bg-yellow-50">
                        <div class="flex justify-between items-center">
                            <div class="text-sm">
                                <span v-if="hasFailedItems()" class="text-red-600 font-medium">
                                    ⚠️ {{ getFailedItemsCount() }} item(s) failed to process
                                </span>
                                <span v-else-if="sale.status === 'processing'" class="text-blue-600 font-medium">
                                    ⏳ Sale is being processed...
                                </span>
                            </div>
                            <div class="flex space-x-3">
                                <button
                                    v-if="hasFailedItems() && canRetry()"
                                    @click="retrySale"
                                    :disabled="retrying"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 disabled:bg-yellow-400 text-white text-sm font-medium rounded-md transition-colors duration-200"
                                >
                                    <LoadingState
                                        v-if="retrying"
                                        :show="true"
                                        type="spinner"
                                        size="xs"
                                        color="gray"
                                        :show-text="false"
                                        class="mr-2"
                                    />
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    {{ retrying ? 'Retrying...' : 'Retry Failed Items' }}
                                </button>

                                <button
                                    v-if="sale.status === 'processing'"
                                    @click="refreshStatus"
                                    :disabled="refreshing"
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white text-sm font-medium rounded-md transition-colors duration-200"
                                >
                                    <LoadingState
                                        v-if="refreshing"
                                        :show="true"
                                        type="spinner"
                                        size="xs"
                                        color="gray"
                                        :show-text="false"
                                        class="mr-2"
                                    />
                                    <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    {{ refreshing ? 'Refreshing...' : 'Refresh Status' }}
                                </button>
                            </div>
                        </div>

                        <!-- Failed Items Details -->
                        <div v-if="hasFailedItems()" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-200">
                            <h5 class="text-sm font-medium text-red-800 mb-2">Failed Items:</h5>
                            <div class="space-y-1">
                                <div
                                    v-for="item in getFailedItems()"
                                    :key="item.id"
                                    class="text-sm text-red-700 flex justify-between"
                                >
                                    <span>{{ item.product_sku?.products?.name || 'N/A' }}</span>
                                    <span class="text-xs text-red-600">{{ item.error_message }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                        <a :href="route('sales.index')"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                        <div class="flex space-x-3">
                            <a :href="route('sales.edit', sale.id)"
                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Edit Sale
                            </a>
                            <button v-if="sale.status === 'pending'" @click="markAsPaid"
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Mark as Paid
                            </button>
                            <button @click="confirmDelete"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete Sale
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import StatusBadge from "@/Components/UI/StatusBadge.vue";
import LoadingState from "@/Components/UI/LoadingState.vue";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        AppLayout,
        StatusBadge,
        LoadingState,
    },
    props: {
        sale: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            retrying: false,
            refreshing: false,
        };
    },
    computed: {
        orderItems() {
            // Compatibilidade com ambas as estruturas: order_items ou items
            const items = this.sale.order_items || this.sale.items || [];

            // Normalizar formato dos dados se necessário
            return items.map(item => {
                // Se o item já tem product_sku, retorna como está
                if (item.product_sku) {
                    return item;
                }

                // Se não tem product_sku, cria a estrutura esperada baseada nos dados disponíveis
                return {
                    ...item,
                    product_sku: {
                        sku: item.sku || 'N/A',
                        barcode: null,
                        cost_price: 0, // Valor padrão pois não está no JSON original
                        sale_price: item.unit_price || 0,
                        products: {
                            name: item.product_name || 'N/A',
                            description: null,
                            brand: {
                                name: 'No Brand'
                            }
                        }
                    }
                };
            });
        }
    },
    methods: {
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('pt-BR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('pt-BR', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        formatPrice(price) {
            return price ? parseFloat(price).toFixed(2).replace('.', ',') : '0,00';
        },
        getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'paid': 'bg-green-100 text-green-800',
                'canceled': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        // Cálculos simples mantidos no frontend para responsividade
        getTotalItems() {
            if (!this.orderItems) return 0;
            return this.orderItems.reduce((total, item) => total + item.quantity, 0);
        },
        getSubtotal() {
            if (!this.orderItems) return 0;
            return this.orderItems.reduce((total, item) => total + (item.quantity * item.unit_price), 0);
        },

        // Cálculos complexos/sensíveis vindos do backend
        getTotalProfit() {
            return this.sale.financial_summary?.total_profit || 0;
        },
        getTotalCost() {
            return this.sale.financial_summary?.total_cost || 0;
        },
        getProfitMarginPercentage() {
            return this.sale.financial_summary?.profit_margin?.toFixed(1) || '0.0';
        },
        getAverageItemPrice() {
            return this.sale.financial_summary?.average_item_price || 0;
        },
        getHighestItemValue() {
            return this.sale.financial_summary?.highest_item_value || 0;
        },
        getMostProfitableItem() {
            return this.sale.financial_summary?.most_profitable_item_value || 0;
        },
        getItemProfit(item) {
            const costPrice = parseFloat(item.product_sku?.cost_price || 0);
            const salePrice = parseFloat(item.unit_price || 0);
            return (salePrice - costPrice) * item.quantity;
        },
        getItemProfitClass(item) {
            const profit = this.getItemProfit(item);
            return profit >= 0 ? 'text-green-600' : 'text-red-600';
        },
        getItemStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'completed': 'bg-green-100 text-green-800',
                'canceled': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        getItemStatusLabel(status) {
            const labels = {
                'pending': 'Pending',
                'completed': 'Completed',
                'canceled': 'Canceled'
            };
            return labels[status] || status || 'Active';
        },
        getProfitMarginPercentage() {
            return this.sale.financial_summary?.profit_margin?.toFixed(1) || '0.0';
        },
        getAverageItemPrice() {
            return this.sale.financial_summary?.average_item_price || 0;
        },
        getHighestItemValue() {
            return this.sale.financial_summary?.highest_item_value || 0;
        },
        getMostProfitableItem() {
            return this.sale.financial_summary?.most_profitable_item_value || 0;
        },
        getTotalProfitClass() {
            const profit = this.getTotalProfit();
            return profit >= 0 ? 'text-green-600' : 'text-red-600';
        },
        markAsPaid() {
            if (confirm('Are you sure you want to mark this sale as paid?')) {
                router.patch(route('sales.update', this.sale.id), {
                    status: 'paid'
                });
            }
        },
        confirmDelete() {
            if (confirm('Are you sure you want to delete this sale? This action cannot be undone.')) {
                router.delete(route('sales.destroy', this.sale.id));
            }
        },

        // Novos métodos para Fase 4
        hasFailedItems() {
            if (!this.orderItems) return false;
            return this.orderItems.some(item => item.status === 'failed');
        },

        getFailedItems() {
            if (!this.orderItems) return [];
            return this.orderItems.filter(item => item.status === 'failed');
        },

        getFailedItemsCount() {
            return this.getFailedItems().length;
        },

        canRetry() {
            return ['failed', 'pending'].includes(this.sale.status);
        },

        async retrySale() {
            if (this.retrying) return;

            this.retrying = true;

            try {
                await router.patch(route('sales.retry', this.sale.id), {}, {
                    onSuccess: (page) => {
                        // Atualizar dados da página
                        this.$page.props.sale = page.props.sale;

                        // Mostrar mensagem de sucesso
                        this.$page.props.flash = {
                            success: 'Sale retry initiated successfully!'
                        };
                    },
                    onError: (errors) => {
                        console.error('Retry failed:', errors);
                        alert('Failed to retry sale. Please try again.');
                    }
                });
            } catch (error) {
                console.error('Retry error:', error);
                alert('An error occurred while retrying the sale.');
            } finally {
                this.retrying = false;
            }
        },

        async refreshStatus() {
            if (this.refreshing) return;

            this.refreshing = true;

            try {
                await router.reload({
                    onSuccess: (page) => {
                        // Dados são automaticamente atualizados
                        console.log('Status refreshed successfully');
                    },
                    onError: (errors) => {
                        console.error('Refresh failed:', errors);
                    }
                });
            } catch (error) {
                console.error('Refresh error:', error);
            } finally {
                this.refreshing = false;
            }
        }
    },
};
</script>
