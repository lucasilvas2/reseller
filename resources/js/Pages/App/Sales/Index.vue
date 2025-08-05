<template>
    <AppLayout title="Sales">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Sales
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end">
                    <PrimaryButton type="link" :href="route('sales.create')">
                        Add Sales
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Advanced Filters -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6 p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input
                                v-model="localFilters.name"
                                type="text"
                                placeholder="Search by name..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @input="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                v-model="localFilters.status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Statuses</option>
                                <option
                                    v-for="status in ['pending', 'completed', 'cancelled']"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                            <input
                                v-model="localFilters.dateFrom"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
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
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold rounded"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ServerPaginatedTable
                        title="Sales"
                        :headers="headers"
                        :data="transformedSales"
                        :pagination="pagination"
                        :filters="filters"
                        :actions="actions"
                        @action="handleAction"
                        route-name="sales.index"
                    >
                        <template #cell-client_name="{ row }">
                            <span class="font-medium text-gray-900">{{ row.client_name }}</span>
                        </template>

                        <template #cell-status="{ row }">
                            <span class="text-gray-600">{{ row.status || 'N/A' }}</span>
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
    </AppLayout>
</template>

<script>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ServerPaginatedTable from "@/Components/ServerPaginatedTable.vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

export default {
    components: {
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
        brands: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            headers: [
                { label: 'ID', key: 'id' },
                { label: 'Client', key: 'client_name' },
                { label: 'Status', key: 'status' },
                { label: 'Description', key: 'description' },
                { label: 'Created', key: 'created_at_formatted' }
            ],
            localFilters: {
                client_name: this.filters.client_name || '',
                dateFrom: this.filters.date_from || '',
                dateTo: this.filters.date_to || '',
                status: this.filters.status || ''
            },
            actions: [
                {
                    name: 'edit',
                    label: 'Edit',
                    icon: 'fas fa-edit',
                    type: 'default'
                },
                {
                    name: 'show',
                    label: 'View',
                    icon: 'fas fa-eye',
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
        transformedSales() {
            debugger
            return this.data.map(sale => ({
                id: sale.id,
                client_name: sale.client_name,
                description: sale.description,
                status: sale.status,
                created_at_formatted: sale.created_at_formatted,
                // Keep original for actions
                _original: sale
            }));
        }
    },
    methods: {
        applyFilters() {
            const params = {
                page: 1,
                client_name: this.localFilters.clientName || undefined,
                date_from: this.localFilters.dateFrom || undefined,
                date_to: this.localFilters.dateTo || undefined,
                status: this.localFilters.status || undefined
            };

            // Remove undefined values
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });

            router.get(route('sales.index'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        resetFilters() {
            this.localFilters = {
                clientName: '',
                dateFrom: '',
                dateTo: '',
                status: ''
            };
            this.applyFilters();
        },
        handleAction(payload) {
            const { action, row } = payload;
            const sale = row._original;

            switch (action) {
                case 'edit':
                    this.handleEdit(sale);
                    break;
                case 'show':
                    router.get(route('sales.show', {
                        id: sale.id
                    }));
                    break;
                case 'delete':
                    this.handleDelete(sale);
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },
        handleEdit(sale) {
            router.get(route('sales.edit', {
                id: sale.id
            }));
        },
        handleDelete(sale) {
            if (confirm('Tem certeza que deseja deletar este venda?')) {
                router.delete(route('sales.destroy', {
                    id: sale.id
                }));
            }
        }
    }
};
</script>

