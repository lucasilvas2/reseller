<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

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
    <AppLayout title="Products SKU">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                    <form @submit.prevent="submitForm">
                        <div class="md:grid md:grid-cols-2 md:gap-6">
                            <div>
                                <InputLabel for="barcode" value="Código de barra"/>
                                <TextInput
                                    id="sale_price"
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
                                    autofocus
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
                                    autofocus
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
                                    autofocus
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
    </AppLayout>
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
