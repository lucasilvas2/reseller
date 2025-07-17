<template>
    <AppLayout title="Clients">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Dealers
                    </h2>
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
        dealerships: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            headers: ['Id', 'Name', 'Phone', 'Created'],
            rows: [],
            hasActions: false
        };
    },
    methods: {
        transformBrandsToRows(dealerships) {
            return dealerships.map(dealer => ({
                id: dealer.id,
                name: dealer.name,
                phone: dealer.user.phone_number,
                created: dealer.created_at,
            }));
        },
    },
    mounted() {
        debugger
        console.log(this.dealerships);
        this.rows = this.transformBrandsToRows(this.dealerships);
    },
};
</script>

