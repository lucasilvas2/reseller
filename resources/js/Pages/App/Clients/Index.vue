<template>
    <AppLayout title="Clients">
        <template #header>
            <div class="flex flex-row">
                <div class="basis-1/2">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Clients
                    </h2>
                </div>
                <div class="basis-1/2 flex justify-end">
                    <PrimaryButton type="link" :href="route('clients.create')">
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
                        :actions="tableActions"
                        @action="handleAction"
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
        clients: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            headers: ['Id', 'Name', 'Phone', 'Created'],
            rows: [],
            tableActions: [
                {
                    name: 'view',
                    label: 'Visualizar',
                    icon: 'fas fa-eye',
                    type: 'default'
                },
                {
                    name: 'delete',
                    label: 'Deletar',
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
                case 'delete':
                    this.handleDelete(row);
                    break;
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
        },
        transformClientsToRows(clients) {
            return clients.map(client => ({
                id: client.id,
                name: client.user.name,
                phone: client.user.phone_number,
                created: client.created_at,
            }));
        },
    },
    mounted() {
        this.rows = this.transformClientsToRows(this.clients);
    },
};
</script>
