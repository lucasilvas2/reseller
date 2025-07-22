<template>
    <AppLayout title="Stock Dashboard">
        <template #header>
            <div class="flex flex-row items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Stock Dashboard
                </h2>
                <div class="flex space-x-3">
                    <PrimaryButton type="link" :href="route('stocks.inventory.index')">
                        View Inventory
                    </PrimaryButton>
                    <PrimaryButton type="link" :href="route('stocks.movements.create')">
                        Add Movement
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <SummaryCard icon="fas fa-box" label="Total Products" :value="summary.totalProducts" color="blue" />
                    <SummaryCard icon="fas fa-barcode" label="Total SKUs" :value="summary.totalSkus" color="indigo" />
                    <SummaryCard icon="fas fa-warehouse" label="Total Stock" :value="summary.totalStock" color="green" />
                    <SummaryCard icon="fas fa-dollar-sign" label="Stock Value" :value="`$${summary.totalValue}`" color="yellow" />
                </div>

                <!-- Recent Movements -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-8 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Movements</h3>
                    <Table
                        :headers="['ID', 'Product', 'Type', 'Quantity', 'User', 'Date']"
                        :rows="recentMovementsRows"
                        :actions="[{ name: 'view', label: 'View', icon: 'fas fa-eye', type: 'default' }]"
                        @action="handleMovementAction"
                    />
                </div>

                <!-- Low Stock Products -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Low Stock Products (≤ 10)</h3>
                    <Table
                        :headers="['Product', 'SKU', 'Current Stock', 'Cost Price', 'Sale Price']"
                        :rows="lowStockRows"
                        :actions="[{ name: 'view-inventory', label: 'View', icon: 'fas fa-eye', type: 'default' }]"
                        @action="handleInventoryAction"
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
import SummaryCard from "@/Pages/App/Stocks/Components/SummaryCard.vue";
import { router } from "@inertiajs/vue3";

export default {
    components: {
        AppLayout,
        PrimaryButton,
        Table,
        SummaryCard,
    },
    props: {
        summary: {
            type: Object,
            required: true,
        },
        recentMovements: {
            type: Array,
            required: true,
        },
        lowStockProducts: {
            type: Array,
            required: true,
        },
    },
    computed: {
        recentMovementsRows() {
            return this.recentMovements.map(m => ({
                id: m.id,
                product: m.product_sku?.products?.name || 'N/A',
                type: m.type,
                quantity: m.quantity,
                user: m.user?.name || 'N/A',
                date: this.formatDateTime(m.created_at),
            }));
        },
        lowStockRows() {
            return this.lowStockProducts.map(p => ({
                product: p.product_name,
                sku: p.sku,
                'current stock': p.current_stock,
                'cost price': `$${this.formatPrice(p.cost_price)}`,
                'sale price': `$${this.formatPrice(p.sale_price)}`,
            }));
        },
    },
    methods: {
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
        handleMovementAction(payload) {
            const { row } = payload;
            router.get(route('stocks.movements.show', row.id));
        },
        handleInventoryAction(payload) {
            const { row } = payload;
            router.get(route('stocks.inventory.index'));
        },
    },
};
</script>
