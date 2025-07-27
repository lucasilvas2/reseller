<template>
    <AppLayout title="Movement Details">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Movement Details #{{ movement.id }}
                </h2>
                <div class="flex space-x-3">
                    <a :href="route('stocks.movements.index')"
                       class="text-gray-600 hover:text-gray-900 text-sm bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-md">
                        ← Back to List
                    </a>
                    <a :href="route('stocks.movements.edit', movement.id)"
                       class="text-blue-600 hover:text-blue-900 text-sm bg-blue-100 hover:bg-blue-200 px-3 py-2 rounded-md">
                        Edit Movement
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
                                    Stock Movement Information
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Created on {{ formatDate(movement.created_at) }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span :class="getTypeClass(movement.type)"
                                      class="px-3 py-1 rounded-full text-sm font-medium">
                                    {{ movement.type }}
                                </span>
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ movement.quantity }} units
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">

                        <!-- Product Information -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Product Information</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Product Name:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ movement.product_sku?.products?.name || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">SKU:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ movement.product_sku?.sku || 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Barcode:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ movement.product_sku?.barcode || 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Pricing Information</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Cost Price:</span>
                                        <span class="text-sm text-gray-900">
                                            ${{ formatPrice(movement.product_sku?.cost_price) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Sale Price:</span>
                                        <span class="text-sm text-gray-900">
                                            ${{ formatPrice(movement.product_sku?.sale_price) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-t pt-3">
                                        <span class="text-sm font-medium text-gray-700">Total Cost:</span>
                                        <span class="text-sm font-bold text-gray-900">
                                            ${{ formatPrice(movement.product_sku?.cost_price * movement.quantity) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Total Sale Value:</span>
                                        <span class="text-sm font-bold text-green-600">
                                            ${{ formatPrice(movement.product_sku?.sale_price * movement.quantity) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Movement Details -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Movement Details</h4>
                                <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Movement ID:</span>
                                        <span class="text-sm text-gray-900">#{{ movement.id }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Type:</span>
                                        <span :class="getTypeClass(movement.type)"
                                              class="px-2 py-1 rounded text-xs font-medium">
                                            {{ movement.type }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Quantity:</span>
                                        <span class="text-sm text-gray-900">{{ movement.quantity }} units</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Created By:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ movement.user?.name || 'Unknown' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Description:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ movement.description || 'No description' }}
                                        </span>
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
                                            {{ formatDateTime(movement.created_at) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-500">Last Updated:</span>
                                        <span class="text-sm text-gray-900">
                                            {{ formatDateTime(movement.updated_at) }}
                                        </span>
                                    </div>
                                    <div v-if="movement.updated_at !== movement.created_at"
                                         class="flex justify-between border-t pt-3">
                                        <span class="text-sm font-medium text-gray-500">Modified:</span>
                                        <span class="text-sm text-orange-600">Yes</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Profit Analysis -->
                            <div v-if="movement.product_sku?.cost_price && movement.product_sku?.sale_price">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Profit Analysis</h4>
                                <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Unit Profit:</span>
                                        <span class="text-sm font-bold" :class="getUnitProfitClass()">
                                            ${{ formatPrice(getUnitProfit()) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Total Profit Potential:</span>
                                        <span class="text-lg font-bold" :class="getTotalProfitClass()">
                                            ${{ formatPrice(getTotalProfit()) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-700">Margin:</span>
                                        <span class="text-sm font-bold text-blue-600">
                                            {{ getMarginPercentage() }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                        <a :href="route('stocks.movements.index')"
                           class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Back to List
                        </a>
                        <div class="flex space-x-3">
                            <a :href="route('stocks.movements.edit', movement.id)"
                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Edit Movement
                            </a>
                            <button @click="confirmDelete"
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                Delete Movement
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
import { router } from "@inertiajs/vue3";

export default {
    components: {
        AppLayout,
    },
    props: {
        movement: {
            type: Object,
            required: true,
        },
    },
    methods: {
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
        formatDateTime(dateString) {
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        formatPrice(price) {
            return price ? parseFloat(price).toFixed(2) : '0.00';
        },
        getTypeClass(type) {
            const classes = {
                'IN': 'bg-green-100 text-green-800',
                'OUT': 'bg-red-100 text-red-800',
                'ADJUSTMENT': 'bg-yellow-100 text-yellow-800'
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },
        getUnitProfit() {
            const cost = parseFloat(this.movement.product_sku?.cost_price || 0);
            const sale = parseFloat(this.movement.product_sku?.sale_price || 0);
            return sale - cost;
        },
        getTotalProfit() {
            return this.getUnitProfit() * this.movement.quantity;
        },
        getUnitProfitClass() {
            const profit = this.getUnitProfit();
            return profit >= 0 ? 'text-green-600' : 'text-red-600';
        },
        getTotalProfitClass() {
            const profit = this.getTotalProfit();
            return profit >= 0 ? 'text-green-600' : 'text-red-600';
        },
        getMarginPercentage() {
            const cost = parseFloat(this.movement.product_sku?.cost_price || 0);
            const sale = parseFloat(this.movement.product_sku?.sale_price || 0);
            if (cost === 0) return '0';
            return ((sale - cost) / cost * 100).toFixed(1);
        },
        confirmDelete() {
            if (confirm('Are you sure you want to delete this movement? This action cannot be undone.')) {
                router.delete(route('stocks.movements.destroy', this.movement.id));
            }
        }
    },
};
</script>
