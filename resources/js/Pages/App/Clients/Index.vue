<template>
    <AppSidebarLayout title="Clients">
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
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Clients</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Clients List
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">
                            <Link
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                :href="route('clients.create')"
                            >
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create
                            </Link>
                        </div>
                    </div>
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                v-model="localFilters.email"
                                type="email"
                                placeholder="Search by email..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @input="applyFilters"
                            />
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

                <!-- Clients Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ServerPaginatedTable
                        title="Clients"
                        :headers="headers"
                        :data="transformedClients"
                        :pagination="pagination"
                        :filters="filters"
                        :actions="actions"
                        @action="handleAction"
                        route-name="clients.index"
                    >
                        <template #cell-name="{ row }">
                            <span class="font-medium text-gray-900">{{ row.name }}</span>
                        </template>

                        <template #cell-email="{ row }">
                            <span class="text-gray-600">{{ row.email }}</span>
                        </template>

                        <template #cell-phone_number="{ row }">
                            <span class="text-gray-600">{{ row.phone_number || 'N/A' }}</span>
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
import {Link, router} from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import AppSidebarLayout from "@/Layouts/AppSidebarLayout.vue";

export default {
    components: {
        Link,
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
        }
    },
    data() {
        return {
            headers: [
                { label: 'ID', key: 'id' },
                { label: 'Name', key: 'name' },
                { label: 'Email', key: 'email' },
                { label: 'Phone', key: 'phone_number' },
                { label: 'Created', key: 'created_at_formatted' }
            ],
            localFilters: {
                name: this.filters.name || '',
                email: this.filters.email || '',
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
                    name: 'delete',
                    label: 'Delete',
                    icon: 'fas fa-trash',
                    type: 'danger'
                }
            ]
        };
    },
    computed: {
        transformedClients() {
            return this.data.map(client => ({
                id: client.id,
                name: client.name,
                email: client.email,
                phone_number: client.phone_number,
                created_at_formatted: client.created_at_formatted,
                // Keep original for actions
                _original: client
            }));
        }
    },
    methods: {
        applyFilters() {
            const params = {
                page: 1,
                name: this.localFilters.name || undefined,
                email: this.localFilters.email || undefined,
                date_from: this.localFilters.dateFrom || undefined,
                date_to: this.localFilters.dateTo || undefined,
            };

            // Remove undefined values
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });

            router.get(route('clients.index'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        resetFilters() {
            this.localFilters = {
                name: '',
                email: '',
                dateFrom: '',
                dateTo: '',
            };
            this.applyFilters();
        },
        handleAction(payload) {
            const { action, row } = payload;
            const client = row._original;

            switch (action) {
                case 'view':
                    this.handleView(client);
                    break;
                case 'delete':
                    this.handleDelete(client);
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },
        handleView(client) {
            router.get(route('clients.show', {
                id: client.id
            }));
        },
        handleDelete(client) {
            if (confirm('Tem certeza que deseja deletar este cliente?')) {
                router.delete(route('clients.destroy', {
                    id: client.id
                }));
            }
        }
    }
};
</script>
