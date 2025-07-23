<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SummaryCard from '@/Components/Stock/SummaryCard.vue';
import MovementsTrendChart from '@/Components/Charts/MovementsTrendChart.vue';
import StockDistributionChart from '@/Components/Charts/StockDistributionChart.vue';
import TopProductsChart from '@/Components/Charts/TopProductsChart.vue';
import StockLevelGauge from '@/Components/Charts/StockLevelGauge.vue';
import PaginatedTable from '@/Components/PaginatedTable.vue';

// Props recebidas do controller
defineProps({
    totalProducts: Number,
    lowStockCount: Number,
    outOfStockCount: Number,
    totalMovements: Number,
    recentMovements: Array,
    trendData: Array,
    stockDistribution: Array,
    topProducts: Array,
    currentStockLevel: Number,
    maxStockCapacity: Number,
    isDealer: {
        type: Boolean,
        default: false
    }
});

// Métodos para lidar com ações da tabela
const handleMovementAction = ({ action, row }) => {
    if (action === 'view') {
        // Redirecionar para página de detalhes do movimento
        window.location.href = `/stocks/movements/show/${row.id}`;
    }
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ isDealer ? 'Stock Dashboard' : 'Dashboard' }}
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Conteúdo para Dealers -->
                <div v-if="isDealer">
                    <!-- Cards de Resumo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <SummaryCard
                            title="Total Products"
                            :value="totalProducts"
                            icon="📦"
                            color="blue"
                        />
                        <SummaryCard
                            title="Low Stock Items"
                            :value="lowStockCount"
                            icon="⚠️"
                            color="yellow"
                        />
                        <SummaryCard
                            title="Out of Stock"
                            :value="outOfStockCount"
                            icon="🚫"
                            color="red"
                        />
                        <SummaryCard
                            title="Total Movements"
                            :value="totalMovements"
                            icon="📊"
                            color="green"
                        />
                    </div>

                    <!-- Grid de Gráficos -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                        <!-- Gráfico de Tendência de Movimentos -->
                        <div class="xl:col-span-2">
                            <MovementsTrendChart
                                :data="trendData"
                                title="Stock Movements Trend"
                                height="400px"
                            />
                        </div>

                        <!-- Gauge de Nível de Estoque -->
                        <div>
                            <StockLevelGauge
                                :current-stock="currentStockLevel"
                                :max-stock="maxStockCapacity"
                                title="Overall Stock Level"
                                height="400px"
                            />
                        </div>
                    </div>

                    <!-- Segunda linha de gráficos -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                        <!-- Distribuição de Estoque -->
                        <div>
                            <StockDistributionChart
                                :data="stockDistribution"
                                title="Stock Distribution by Category"
                                chart-type="pie"
                                height="350px"
                            />
                        </div>

                        <!-- Top Produtos -->
                        <div>
                            <TopProductsChart
                                :data="topProducts"
                                title="Top Products by Stock"
                                height="350px"
                            />
                        </div>
                    </div>

                    <!-- Tabela de Movimentos Recentes com Paginação -->
                    <PaginatedTable
                        title="Recent Stock Movements"
                        :headers="[
                            { label: 'Product', key: 'product_name' },
                            { label: 'Type', key: 'type' },
                            { label: 'Quantity', key: 'quantity' },
                            { label: 'Date', key: 'created_at' }
                        ]"
                        :data="recentMovements"
                        :default-items-per-page="5"
                        empty-message="No recent movements found"
                        @action="handleMovementAction"
                    >
                        <!-- Custom cell for Type column -->
                        <template #cell-type="{ value }">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                  :class="{
                                      'bg-green-100 text-green-800': value === 'in',
                                      'bg-red-100 text-red-800': value === 'out'
                                  }">
                                {{ value === 'in' ? 'IN' : 'OUT' }}
                            </span>
                        </template>

                        <!-- Custom cell for Date column -->
                        <template #cell-created_at="{ value }">
                            {{ new Date(value).toLocaleDateString() }}
                        </template>
                    </PaginatedTable>
                </div>

                <!-- Conteúdo para Usuários Não-Dealers -->
                <div v-else>
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
                            <div class="mt-8 text-2xl">
                                Welcome to the Dealer Management System
                            </div>

                            <div class="mt-6 text-gray-500">
                                <p>This is your dashboard. Since you're not assigned to a dealership, you won't see stock management features.</p>
                                <p class="mt-2">Please contact an administrator to be assigned to a dealership to access stock management.</p>
                            </div>
                        </div>

                        <div class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8">
                            <div>
                                <div class="flex items-center">
                                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 text-gray-500">
                                        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <div class="ml-4 text-lg leading-7 font-semibold"><a href="#" class="underline text-gray-900 dark:text-white">Documentation</a></div>
                                </div>

                                <div class="ml-12">
                                    <div class="mt-2 text-gray-600 text-sm">
                                        Learn more about the system and how to use it effectively.
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center">
                                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-8 h-8 text-gray-500">
                                        <path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                        <path d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <div class="ml-4 text-lg leading-7 font-semibold"><a href="#" class="underline text-gray-900 dark:text-white">Contact Support</a></div>
                                </div>

                                <div class="ml-12">
                                    <div class="mt-2 text-gray-600 text-sm">
                                        Need help? Get in touch with our support team.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
