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

const props = defineProps({
    movement: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    barcode: props.movement.product?.barcode || '',
    sku: props.movement.product?.sku || '',
    quantity: props.movement.quantity || '',
    cost_price: props.movement.product?.cost_price || null,
    sale_price: props.movement.product?.sale_price || null,
    product_id: props.movement.product_id || null,
    errors: {},
});

const submitForm = () => {
    form.put(route('stocks.movements.update', props.movement.id), {
        onSuccess: () => {
            // Redirect or show success message
        },
        onError: (errors) => {
            form.errors = errors;
        },
    });
};
</script>

<template>
    <AppSidebarLayout title="Edit Stock Movement">
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
                                            <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">Edit</span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>

                            <!-- Título -->
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Edit Movement #{{ movement.id }}
                            </h1>
                        </div>

                        <!-- Action Buttons -->
                        <div class="ml-4 flex items-center space-x-3">
                            <a :href="route('stocks.movements.index')"
                               class="text-gray-600 hover:text-gray-900 text-sm">
                                ← Back to List
                            </a>
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
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                    <form @submit.prevent="submitForm">
                        <div class="md:grid md:grid-cols-2 md:gap-6">
                            <div>
                                <InputLabel for="barcode" value="Código de barra"/>
                                <TextInput
                                    id="barcode"
                                    v-model="form.barcode"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="barcode"
                                />
                                <InputError class="mt-2" :message="form.errors.barcode"/>
                            </div>
                            <div>
                                <InputLabel for="sku" value="SKU"/>
                                <TextInput
                                    id="sku"
                                    v-model="form.sku"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="sku"
                                />
                                <InputError class="mt-2" :message="form.errors.sku"/>
                            </div>
                            <div>
                                <InputLabel for="cost_price" value="Preço Compra"/>
                                <TextInput
                                    id="cost_price"
                                    v-model="form.cost_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="cost_price"
                                />
                                <InputError class="mt-2" :message="form.errors.cost_price"/>
                            </div>
                            <div>
                                <InputLabel for="sale_price" value="Preço Venda"/>
                                <TextInput
                                    id="sale_price"
                                    v-model="form.sale_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="sale_price"
                                />
                                <InputError class="mt-2" :message="form.errors.sale_price"/>
                            </div>
                            <div>
                                <InputLabel for="quantity" value="Quantidade"/>
                                <TextInput
                                    id="quantity"
                                    v-model="form.quantity"
                                    type="number"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="quantity"
                                />
                                <InputError class="mt-2" :message="form.errors.quantity"/>
                            </div>
                            <div>
                                <InputLabel for="products" value="Products"/>
                                <SelectInput
                                    class="w-full"
                                    v-model="form.product_id"
                                    :options="options"
                                />
                                <InputError class="mt-2" :message="form.errors.product_id"/>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-6">
                            <div class="text-sm text-gray-500">
                                <p>Created: {{ new Date(movement.created_at).toLocaleString() }}</p>
                                <p v-if="movement.updated_at !== movement.created_at">
                                    Last updated: {{ new Date(movement.updated_at).toLocaleString() }}
                                </p>
                            </div>

                            <div class="flex space-x-3">
                                <a :href="route('stocks.movements.index')"
                                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                    Cancel
                                </a>
                                <PrimaryButton
                                    class="ms-4"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing">
                                    Update Movement
                                </PrimaryButton>
                            </div>
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
        movement: {
            type: Object,
            required: true,
        },
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
