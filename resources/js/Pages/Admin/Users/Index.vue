<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Table from "@/Components/Table.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
</script>

<template>
    <AdminLayout title="Users">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Users
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end">
                    <PrimaryButton type="link" :href="route('admin.users.create')">
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
                        @edit="handleEdit"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import Table from "@/Components/Table.vue";
export default {
    components: {
        Table,
    },
    data() {
        return {
            headers: ['Product name', 'Color', 'Category', 'Price', 'Action'],
            rows: [
                {
                    productName: 'Apple MacBook Pro 17"',
                    color: 'Silver',
                    category: 'Laptop',
                    price: '$2999',
                },
                {
                    productName: 'Microsoft Surface Pro',
                    color: 'White',
                    category: 'Laptop PC',
                    price: '$1999',
                },
                {
                    productName: 'Magic Mouse 2',
                    color: 'Black',
                    category: 'Accessories',
                    price: '$99',
                },
                {
                    productName: 'Google Pixel Phone',
                    color: 'Gray',
                    category: 'Phone',
                    price: '$799',
                },
                {
                    productName: 'Apple Watch 5',
                    color: 'Red',
                    category: 'Wearables',
                    price: '$999',
                },
            ],
        };
    },
    methods: {
        handleEdit(row) {
            console.log('Edit row:', row);
            // Aqui você pode adicionar a lógica para editar o item
        },
    },
};
</script>
