<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {Link, useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import AppSidebarLayout from "@/Layouts/AppSidebarLayout.vue";

const form = useForm({
    barcode: '',
    sku: '',
    quantity: '',
    cost_price: null,
    sale_price: null,
    product_id: null,
    type: 'in', // Default to 'in' (entrada)
    errors: {},
});

const submitForm = () => {
    form.post('/stock-movements', {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
        onError: (errors) => {
            form.errors = errors;
        },
    });
};
</script>

<template>
    <AppSidebarLayout title="Movements Create">
        <template #header>
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4">
                    <!-- Breadcrumb e Título -->
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <!-- Breadcrumb -->
                            <nav class="flex mb-2" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li class="inline-flex items-center">
                                        <Link :href="route('stocks.movements.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            Movements
                                        </Link>
                                    </li>
                                    <li aria-current="page">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Create</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Movement Create
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">

                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submitForm">
                        <div class="md:grid md:grid-cols-2 md:gap-6">
                            <div>
                                <InputLabel for="cost_price" value="Preço Compra"/>
                                <TextInput
                                    id="cost_price"
                                    v-model="form.cost_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="cost_price"
                                />
                                <InputError class="mt-2" :message="form.errors.cost_price"/>
                            </div>
                            <div>
                                <InputLabel for="quantity" value="Quantidade"/>
                                <TextInput
                                    id="quantity"
                                    v-model="form.quantity"
                                    type="number"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="quantity"
                                />
                                <InputError class="mt-2" :message="form.errors.quantity"/>
                            </div>
                            <div>
                                <InputLabel for="type" value="Tipo de Movimentação"/>
                                <SelectInput
                                    class="w-full"
                                    v-model="form.type"
                                    :options="[
                                        { value: 'in', label: 'Entrada' },
                                        { value: 'out', label: 'Saída' }
                                    ]"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.type"/>
                            </div>
                            <div>
                                <InputLabel for="products" value="Products"/>
                                <SelectInput
                                    class="w-full"
                                    v-model="form.product_id"
                                    :options="options"
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.product_id"/>
                            </div>
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }"
                                           :disabled="form.processing">
                                Saved
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>

<script>


export default {
    components: {},
    props: {
        products: {
            type: Array,
            required: true,
        },
    },
    data() {
        return {
            options: [],
        };
    },
    methods: {
        transformValuesToOptions(value) {
            return value.map(value => ({
                value: value.id,
                label: `${value.id} - ${value.name.charAt(0).toUpperCase()
                + value.name.slice(1)}` ,
            }));
        },
    },
    mounted() {
        this.options = this.transformValuesToOptions(this.products);
    },
};
</script>
