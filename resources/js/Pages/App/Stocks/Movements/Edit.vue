<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

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
    barcode: props.movement.product_sku?.barcode || '',
    sku: props.movement.product_sku?.sku || '',
    quantity: props.movement.quantity || '',
    cost_price: props.movement.product_sku?.cost_price || null,
    sale_price: props.movement.product_sku?.sale_price || null,
    product_id: props.movement.product_sku?.product_id || null,
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
    <AppLayout title="Edit Stock Movement">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Movement #{{ movement.id }}
                </h2>
                <a :href="route('stocks.movements.index')"
                   class="text-gray-600 hover:text-gray-900 text-sm">
                    ← Back to List
                </a>
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
    </AppLayout>
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
