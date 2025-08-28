<template>
    <AppSidebarLayout title="Stock Movements">
        <template #header>
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4">
                    <!-- Breadcrumb e Título -->
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <!-- Breadcrumb -->
                            <nav class="flex mb-2" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li aria-current="page">
                                        <div class="flex items-center">
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Movements</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Movements List
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">
                            <PrimaryButton type="link" :href="route('stocks.inventory.index')" variant="secondary">
                                Stock Inventory
                            </PrimaryButton>
                            <PrimaryButton type="link" :href="route('stocks.movements.create')">
                                Add Movement
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-4">

                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Advanced Filters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-white mb-2">Movement Type</label>
                            <select
                                v-model="localFilters.type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Types</option>
                                <option value="in">Stock In</option>
                                <option value="out">Stock Out</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-white  mb-2">Product</label>
                            <select
                                v-model="localFilters.productId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Products</option>
                                <option
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="product.id"
                                >
                                    {{ product.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-white  mb-2">Date From</label>
                            <input
                                v-model="localFilters.dateFrom"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-white  mb-2">Date To</label>
                            <input
                                v-model="localFilters.dateTo"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 dark:text-white  font-bold rounded"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>

                <!-- Movements Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ServerPaginatedTable
                        title="Stock Movements"
                        :headers="headers"
                        :data="transformedMovements"
                        :pagination="pagination"
                        :filters="filters"
                        :actions="actions"
                        @action="handleAction"
                        route-name="stocks.movements.index"
                    >
                        <template #cell-type="{ row }">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="{
                                      'bg-green-100 text-green-800': row.type === 'in',
                                      'bg-red-100 text-red-800': row.type === 'out',
                                  }">
                                {{ row.type_label }}
                            </span>
                        </template>

                        <template #cell-quantity="{ row }">
                            <span class="font-medium text-gray-900">{{ row.quantity }}</span>
                        </template>

                        <template #cell-total_value="{ row }">
                            <span class="font-medium text-gray-900">${{ row.total_value }}</span>
                        </template>

                        <template #cell-created_at_formatted="{ row }">
                            <span class="text-gray-500 text-sm">
                                {{ row.created_at_formatted }}
                            </span>
                        </template>
                    </ServerPaginatedTable>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<script>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ServerPaginatedTable from "@/Components/ServerPaginatedTable.vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import AppSidebarLayout from "@/Layouts/AppSidebarLayout.vue";

export default {
    components: {
        AppSidebarLayout,
        AppLayout,
        PrimaryButton,
        ServerPaginatedTable,
    },
    props: {
        data: {
            type: Array,
            required: true,
        },
        pagination: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        products: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            headers: [
                { label: 'ID', key: 'id' },
                { label: 'Product', key: 'product_name' },
                { label: 'SKU', key: 'sku' },
                { label: 'Type', key: 'type' },
                { label: 'Quantity', key: 'quantity' },
                { label: 'Total Value', key: 'total_value' },
                { label: 'User', key: 'user_name' },
                { label: 'Date', key: 'created_at_formatted' }
            ],
            localFilters: {
                type: this.filters.type || '',
                productId: this.filters.product_id || '',
                dateFrom: this.filters.date_from || '',
                dateTo: this.filters.date_to || '',
            },
            actions: [
                {
                    name: 'view',
                    label: 'View Details',
                    icon: 'fas fa-eye',
                    type: 'default'
                },
                {
                    name: 'edit',
                    label: 'Edit',
                    icon: 'fas fa-edit',
                    type: 'default'
                },
                {
                    name: 'delete',
                    label: 'Delete',
                    icon: 'fas fa-trash',
                    type: 'danger'
                }
            ]
        };
    },
    computed: {
        transformedMovements() {
            return this.data.map(movement => ({
                id: movement.id,
                product_name: movement.product_name,
                sku: movement.sku,
                type: movement.type,
                type_label: movement.type_label,
                quantity: movement.quantity,
                total_value: this.formatPrice(movement.total_value),
                user_name: movement.user_name,
                created_at_formatted: movement.created_at_formatted,
                _original: movement
            }));
        }
    },
    methods: {
        formatPrice(price) {
            return price ? parseFloat(price).toFixed(2) : '0.00';
        },
        applyFilters() {
            const params = {
                page: 1,
                type: this.localFilters.type || undefined,
                product_id: this.localFilters.productId || undefined,
                date_from: this.localFilters.dateFrom || undefined,
                date_to: this.localFilters.dateTo || undefined,
            };

            // Remove undefined values
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });

            router.get(route('stocks.movements.index'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        resetFilters() {
            this.localFilters = {
                type: '',
                productId: '',
                dateFrom: '',
                dateTo: '',
            };
            this.applyFilters();
        },
        handleAction(payload) {
            const { action, row } = payload;
            const movement = row._original;

            switch (action) {
                case 'view':
                    this.handleView(movement);
                    break;
                case 'edit':
                    this.handleEdit(movement);
                    break;
                case 'delete':
                    this.handleDelete(movement);
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },
        handleView(movement) {
            router.get(route('stocks.movements.show', {
                id: movement.id
            }));
        },
        handleEdit(movement) {
            router.get(route('stocks.movements.edit', {
                id: movement.id
            }));
        },
        handleDelete(movement) {
            if (confirm('Are you sure you want to delete this movement?')) {
                router.delete(route('stocks.movements.destroy', {
                    id: movement.id
                }));
            }
        }
    }
};
</script>

