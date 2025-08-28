<template>
    <AppSidebarLayout title="Stock Inventory">
        <!-- Header com Actions Bar -->
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
                                        <Link  :href="route('dashboard')" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            Dashboard
                                        </Link>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400">Estoque</span>
                                        </div>
                                    </li>
                                    <li aria-current="page">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Inventário</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Inventário de Estoque
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">
                            <Link :href="route('dashboard')" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                                </svg>
                                Dashboard
                            </Link>
                            <Link  :href="route('stocks.movements.index')" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                                <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2-2V7a2 2 0 012-2h2a2 2 0 002 2v2a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 00-2 2h-2a2 2 0 00-2 2v6a2 2 0 01-2 2H11a2 2 0 01-2-2z" />
                                </svg>
                                Ver Movimentações
                            </Link>

                            <Link :href="route('stocks.movements.create')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nova Movimentação
                            </Link>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Total Produtos</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ summary.totalProducts }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Estoque Total</p>
                                    <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">{{ summary.totalStock }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Valor Total</p>
                                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ summary.totalValue }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Receita Potencial</p>
                                    <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">{{ summary.potentialRevenue }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Conteúdo Principal -->
        <div class="space-y-6">
            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        Filtros
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Status do Estoque
                            </label>
                            <select
                                v-model="localFilters.stockStatus"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Todos</option>
                                <option value="in_stock">Em Estoque</option>
                                <option value="low_stock">Estoque Baixo</option>
                                <option value="out_of_stock">Sem Estoque</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ordenar por
                            </label>
                            <select
                                v-model="localFilters.sortBy"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="current_stock">Estoque Atual</option>
                                <option value="product_name">Nome do Produto</option>
                                <option value="stock_value">Valor do Estoque</option>
                                <option value="last_movement">Última Movimentação</option>
                            </select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <button
                                @click="applyFilters"
                                type="button"
                                class="btn-action-primary"
                            >
                                Aplicar
                            </button>
                            <button
                                @click="resetFilters"
                                type="button"
                                class="btn-action-secondary"
                            >
                                Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela do Inventário -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <ServerPaginatedTable
                    route-name="stocks.inventory.index"
                    :data="transformedInventory"
                    :headers="headers"
                    :pagination="pagination"
                    :actions="actions"
                    @action="handleAction"
                />
            </div>
        </div>
    </AppSidebarLayout>
</template>

<script>
import AppSidebarLayout from "@/Layouts/AppSidebarLayout.vue";
import ServerPaginatedTable from "@/Components/ServerPaginatedTable.vue";
import {Link, router} from "@inertiajs/vue3";

export default {
    components: {
        Link,
        AppSidebarLayout,
        ServerPaginatedTable,
    },
    props: {
        inventory: {
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
        }
    },
    data() {
        return {
            headers: [
                { label: 'Product', key: 'product' },
                { label: 'SKU', key: 'sku' },
                { label: 'Current Stock', key: 'current stock' },
                { label: 'Cost Price', key: 'cost price' },
                { label: 'Sale Price', key: 'sale price' },
                { label: 'Stock Value', key: 'stock value' },
                { label: 'Last Movement', key: 'last movement' }
            ],
            localFilters: {
                stockStatus: this.filters.stock_status || '',
                sortBy: this.filters.sort_by || 'current_stock',
            },
            actions: [
                {
                    name: 'view-movements',
                    label: 'View Movements',
                    icon: 'fas fa-history',
                    type: 'default'
                },
                {
                    name: 'add-stock',
                    label: 'Add Stock',
                    icon: 'fas fa-plus',
                    type: 'success'
                },
                {
                    name: 'remove-stock',
                    label: 'Remove Stock',
                    icon: 'fas fa-minus',
                    type: 'warning'
                }
            ]
        };
    },
    computed: {
        summary() {
            const totalProducts = this.inventory.length;
            const totalStock = this.inventory.reduce((sum, item) => sum + item.current_stock, 0);
            const totalValue = this.inventory.reduce((sum, item) => sum + item.stock_value, 0);
            const potentialRevenue = this.inventory.reduce((sum, item) => sum + item.potential_revenue, 0);

            return {
                totalProducts,
                totalStock,
                totalValue: this.formatPrice(totalValue),
                potentialRevenue: this.formatPrice(potentialRevenue),
            };
        },
        transformedInventory() {
            return this.inventory.map(item => ({
                id: item.id,
                product: item.product_name,
                sku: item.sku || 'N/A',
                'current stock': this.getStockDisplay(item.current_stock),
                'cost price': `$${this.formatPrice(item.cost_price)}`,
                'sale price': `$${this.formatPrice(item.sale_price)}`,
                'stock value': `$${this.formatPrice(item.stock_value)}`,
                'last movement': item.last_movement ? this.formatDate(item.last_movement) : 'Never',
                // Mantém dados originais para ações
                _original: item
            }));
        }
    },
    methods: {
        getStockDisplay(stock) {
            let display = stock.toString();
            if (stock <= 0) {
                display += ' (Out of Stock)';
            } else if (stock <= 10) {
                display += ' (Low Stock)';
            }
            return display;
        },
        formatPrice(price) {
            return price ? parseFloat(price).toFixed(2) : '0.00';
        },
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        },
        applyFilters() {
            const params = {
                page: 1,
                stock_status: this.localFilters.stockStatus || undefined,
                sort_by: this.localFilters.sortBy || undefined,
                sort_order: this.localFilters.sortBy === 'product_name' ? 'asc' : 'desc',
            };

            // Remove undefined values
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });

            router.get(route('stocks.inventory.index'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        resetFilters() {
            this.localFilters = {
                stockStatus: '',
                sortBy: 'current_stock',
            };
            this.applyFilters();
        },
        handleAction(payload) {
            const { action, row } = payload;
            const item = row._original;

            switch (action) {
                case 'view-movements':
                    this.viewMovements(item);
                    break;
                case 'add-stock':
                    this.addStock(item);
                    break;
                case 'remove-stock':
                    this.removeStock(item);
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },
        viewMovements(item) {
            // Redirect to movements page with filter by product
            router.get(route('stocks.movements.index', {
                product_id: item.id
            }));
        },
        addStock(item) {
            // Redirect to create movement with pre-filled product
            router.get(route('stocks.movements.create', {
                product_id: item.product_id,
                type: 'IN'
            }));
        },
        removeStock(item) {
            // Redirect to create movement with pre-filled product for OUT movement
            router.get(route('stocks.movements.create', {
                product_id: item.product_id,
                type: 'OUT'
            }));
        }
    },
};
</script>
