<template>
    <AppSidebarLayout title="Products">
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
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Products</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Product List
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">
                            <PrimaryButton type="link" :href="route('products.create')">
                                Add Product
                            </PrimaryButton>
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                            <select
                                v-model="localFilters.brandId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Brands</option>
                                <option
                                    v-for="brand in brands"
                                    :key="brand.id"
                                    :value="brand.id"
                                >
                                    {{ brand.name }}
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

                <!-- Products Table -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <ServerPaginatedTable
                        title="Products"
                        :headers="headers"
                        :data="transformedProducts"
                        :pagination="pagination"
                        :filters="filters"
                        :actions="actions"
                        @action="handleAction"
                        route-name="products.index"
                    >
                        <template #cell-name="{ row }">
                            <span class="font-medium text-gray-900">{{ row.name }}</span>
                        </template>

                        <template #cell-description="{ row }">
                            <span class="text-gray-600">{{ row.description || 'N/A' }}</span>
                        </template>

                        <template #cell-brand_name="{ row }">
                            <span class="text-gray-600">{{ row.brand_name }}</span>
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
        brands: {
            type: Array,
            default: () => []
        },
        pageTitle: {
            type: String,
            default: 'Products'
        }
    },
    data() {
        return {
            headers: [
                { label: 'ID', key: 'id' },
                { label: 'Name', key: 'name' },
                { label: 'Description', key: 'description' },
                { label: 'Brand', key: 'brand_name' },
                { label: 'Created', key: 'created_at_formatted' }
            ],
            localFilters: {
                name: this.filters.name || '',
                brandId: this.filters.brand_id || '',
                dateFrom: this.filters.date_from || '',
                dateTo: this.filters.date_to || '',
            },
            actions: [
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
            ],
            pageTitle: 'Products',
        };
    },
    computed: {
        transformedProducts() {
            return this.data.map(product => ({
                id: product.id,
                name: product.name,
                description: product.description,
                brand_name: product.brand_name,
                created_at_formatted: product.created_at_formatted,
                _original: product
            }));
        }
    },
    methods: {
        applyFilters() {
            const params = {
                page: 1,
                name: this.localFilters.name || undefined,
                brand_id: this.localFilters.brandId || undefined,
                date_from: this.localFilters.dateFrom || undefined,
                date_to: this.localFilters.dateTo || undefined,
            };

            // Remove undefined values
            Object.keys(params).forEach(key => {
                if (params[key] === undefined) {
                    delete params[key];
                }
            });

            router.get(route('products.index'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        resetFilters() {
            this.localFilters = {
                name: '',
                brandId: '',
                dateFrom: '',
                dateTo: '',
            };
            this.applyFilters();
        },
        handleAction(payload) {
            const { action, row } = payload;
            const product = row._original;

            switch (action) {
                case 'edit':
                    this.handleEdit(product);
                    break;
                case 'delete':
                    this.handleDelete(product);
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        },
        handleEdit(product) {
            router.get(route('products.edit', {
                id: product.id
            }));
        },
        handleDelete(product) {
            if (confirm('Tem certeza que deseja deletar este produto?')) {
                router.delete(route('products.destroy', {
                    id: product.id
                }));
            }
        }
    }
};
</script>

