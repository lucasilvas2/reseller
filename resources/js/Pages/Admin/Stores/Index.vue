<template>
    <AdminLayout title="Stores">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Stores
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end">
                    <PrimaryButton type="link" :href="route('admin.stores.create')">
                        Add
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

                <!-- Stores Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ServerPaginatedTable
                        title="Stores"
                        :headers="headers"
                        :data="transformedStores"
                        :pagination="pagination"
                        :filters="filters"
                        :actions="actions"
                        @action="handleAction"
                        route-name="admin.stores.index"
                    >
                        <template #cell-name="{ row }">
                            <span class="font-medium text-gray-900">{{ row.name }}</span>
                        </template>

                        <template #cell-email="{ row }">
                            <span class="text-gray-600">{{ row.email }}</span>
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
    </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Table from "@/Components/Table.vue";
import {router} from "@inertiajs/vue3";
import ServerPaginatedTable from "@/Components/ServerPaginatedTable.vue";

export default {
    components: {
        ServerPaginatedTable,
        AdminLayout,
        PrimaryButton,
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
                    name: 'edit',
                    label: 'Edit',
                    icon: 'fas fa-edit',
                    type: 'primary'
                },
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
        transformedStores() {
            return this.data.map(store => ({
                id: store.id,
                name: store.name,
                email: store.email,
                created_at_formatted: store.created_at_formatted,
                // Keep original for actions
                _original: store
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

            router.get(route('admin.stores.index'), params, {
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
            const store = row._original;

            switch (action) {
                case 'edit':
                    this.handleEdit(store);
                    break;
                case 'view':
                    this.handleView(store);
                    break;
                case 'delete':
                    this.handleDelete(store);
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },
        handleEdit(row) {
            router.get(route('admin.stores.edit', {
                id: row.id
            }));
        },
        handleView(row) {
            router.get(route('admin.stores.show', {
                id: row.id
            }));
        },
        handleDelete(row) {
            if (confirm(`Are you sure you want to delete the store "${row.name}"?`)) {
                router.delete(route('admin.stores.destroy', {
                    id: row.id
                }), {
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        }
    },
};
</script>

