<template>
    <AppLayout title="Products Stock">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Products
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end space-x-3">
                    <a :href="route('stocks.dashboard')"
                       class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                        Dashboard
                    </a>
                    <PrimaryButton type="link" :href="route('stocks.inventory.index')" variant="secondary">
                        Stock Inventory
                    </PrimaryButton>
                    <PrimaryButton type="link" :href="route('stocks.movements.create')">
                        Add Movement
                    </PrimaryButton>
                </div>
            </div>

        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Table
                        :headers="headers"
                        :rows="rows"
                        :actions="actions"
                        @action="handleAction"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Table from "@/Components/Table.vue";
import {router} from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

export default {
    components: {
        AppLayout,
        PrimaryButton,
        Table,
    },
    props: {
        movements: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            headers: ['Id', 'Product', 'Type', 'Quantity', 'Cost', 'Sale','Created'],
            rows: [],
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
    methods: {
        handleAction(payload) {
            const { action, row } = payload;

            switch (action) {
                case 'view':
                    this.handleView(row);
                    break;
                case 'edit':
                    this.handleEdit(row);
                    break;
                case 'delete':
                    this.handleDelete(row);
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
        },
        transformBrandsToRows(movements) {
            return movements.map(movement => ({
                id: movement.id,
                product: movement.product_sku.products ? movement.product_sku.products.name : 'N/A',
                type: movement.type,
                quantity: movement.quantity,
                cost: movement.product_sku.cost_price ? parseFloat(movement.product_sku.cost_price).toFixed(2) : 'N/A',
                sale: movement.product_sku.sale_price ? parseFloat(movement.product_sku.sale_price).toFixed(2) : 'N/A',
                created: movement.created_at,
            }));
        },
    },
    mounted() {
        this.rows = this.transformBrandsToRows(this.movements);
        console.log(this.movements, this.rows);
    },
};
</script>

