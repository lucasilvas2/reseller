<template>
    <AppLayout title="Brands">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Products
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end">
                    <PrimaryButton type="link" :href="route('products.create')">
                        Add
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
                        :hasActions="hasActions"
                        @edit="handleEdit"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
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
        products: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            headers: ['Id', 'Name', 'Description', 'Created'],
            rows: [],
            hasActions: true
        };
    },
    methods: {
        handleEdit(product) {
            router.get(route('products.edit', {
                id: product.id
            }));
        },
        transformBrandsToRows(products) {
            return products.map(product => ({
                id: product.id,
                name: product.name,
                description: product.description,
                created_at: product.created_at,
            }));
        },
    },
    mounted() {
        this.rows = this.transformBrandsToRows(this.products);
    },
};
</script>

