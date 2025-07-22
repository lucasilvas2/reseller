<template>
    <AppLayout title="Stock Inventory">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Stock Inventory
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end space-x-3">
                    <a :href="route('stocks.dashboard')"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                        Dashboard
                    </a>
                    <PrimaryButton type="link" :href="route('stocks.movements.create')">
                        Add Movement
                    </PrimaryButton>
                    <PrimaryButton type="link" :href="route('stocks.movements.index')" variant="secondary">
                        View Movements
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-boxes text-white text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Total Products</div>
                                <div class="text-2xl font-bold text-gray-900">{{ summary.totalProducts }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-warehouse text-white text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Total Stock</div>
                                <div class="text-2xl font-bold text-gray-900">{{ summary.totalStock }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-dollar-sign text-white text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Stock Value</div>
                                <div class="text-2xl font-bold text-gray-900">${{ summary.totalValue }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-line text-white text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500">Potential Revenue</div>
                                <div class="text-2xl font-bold text-gray-900">${{ summary.potentialRevenue }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Product</label>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Product name, SKU, or barcode..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @input="filterInventory"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stock Status</label>
                            <select
                                v-model="filters.stockStatus"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="filterInventory"
                            >
                                <option value="">All Stock</option>
                                <option value="in-stock">In Stock</option>
                                <option value="low-stock">Low Stock (≤ 10)</option>
                                <option value="out-of-stock">Out of Stock</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select
                                v-model="filters.sortBy"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="filterInventory"
                            >
                                <option value="current_stock">Stock Quantity</option>
                                <option value="product_name">Product Name</option>
                                <option value="stock_value">Stock Value</option>
                                <option value="last_movement">Last Movement</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="resetFilters"
                                class="w-full px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded"
                            >
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Table
                        :headers="headers"
                        :rows="filteredInventory"
                        :actions="actions"
                        @action="handleAction"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Table from "@/Components/Table.vue";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        AppLayout,
        PrimaryButton,
        Table,
    },
    props: {
        inventory: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            headers: ['Product', 'SKU', 'Current Stock', 'Cost Price', 'Sale Price', 'Stock Value', 'Last Movement'],
            filteredInventory: [],
            filters: {
                search: '',
                stockStatus: '',
                sortBy: 'current_stock',
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
        }
    },
    methods: {
        transformInventoryToRows(inventory) {
            return inventory.map(item => ({
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
        },
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
        filterInventory() {
            let filtered = [...this.inventory];

            // Filter by search
            if (this.filters.search) {
                const search = this.filters.search.toLowerCase();
                filtered = filtered.filter(item =>
                    item.product_name.toLowerCase().includes(search) ||
                    (item.sku && item.sku.toLowerCase().includes(search)) ||
                    (item.barcode && item.barcode.toLowerCase().includes(search))
                );
            }

            // Filter by stock status
            if (this.filters.stockStatus) {
                switch (this.filters.stockStatus) {
                    case 'in-stock':
                        filtered = filtered.filter(item => item.current_stock > 10);
                        break;
                    case 'low-stock':
                        filtered = filtered.filter(item => item.current_stock > 0 && item.current_stock <= 10);
                        break;
                    case 'out-of-stock':
                        filtered = filtered.filter(item => item.current_stock <= 0);
                        break;
                }
            }

            // Sort
            filtered.sort((a, b) => {
                switch (this.filters.sortBy) {
                    case 'product_name':
                        return a.product_name.localeCompare(b.product_name);
                    case 'stock_value':
                        return b.stock_value - a.stock_value;
                    case 'last_movement':
                        return new Date(b.last_movement || 0) - new Date(a.last_movement || 0);
                    default: // current_stock
                        return b.current_stock - a.current_stock;
                }
            });

            this.filteredInventory = this.transformInventoryToRows(filtered);
        },
        resetFilters() {
            this.filters = {
                search: '',
                stockStatus: '',
                sortBy: 'current_stock',
            };
            this.filterInventory();
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
                product_sku_id: item.id
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
    mounted() {
        this.filterInventory();
    },
};
</script>
