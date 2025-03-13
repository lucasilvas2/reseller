<template>
    <AdminLayout title="Users">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Dealerships
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end">
                    <PrimaryButton type="link" :href="route('admin.dealerships.create')">
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
    </AdminLayout>
</template>

<script>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Table from "@/Components/Table.vue";
import {router} from "@inertiajs/vue3";

export default {
    components: {
        AdminLayout,
        PrimaryButton,
        Table,
    },
    props: {
        dealerships: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            headers: ['Id', 'Name', 'Email'],
            rows: [],
            hasActions: true
        };
    },
    methods: {
        handleEdit(row) {
            router.get(route('admin.dealerships.edit', {
                id: row.id
            }));
        },
        transformDealershipsToRows(dealerships) {
            return dealerships.map(dealership => ({
                id: dealership.id,
                name: dealership.name,
                email: dealership.email,
            }));
        },
    },
    mounted() {
        this.rows = this.transformDealershipsToRows(this.dealerships);
    },
};
</script>

