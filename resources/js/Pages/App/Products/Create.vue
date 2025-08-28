<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {Link, useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import ImageInput from "@/Components/ImageInput.vue";
import AppSidebarLayout from "@/Layouts/AppSidebarLayout.vue";

const form = useForm({
    name: '',
    image: '',
    description: '',
    category: '',
    sku: '',
    barcode: '',
    cost_price: null,
    sale_price: null,
    brand_id: null,
    errors: {},
});

const submitForm = () => {
    form.post('/products/store', {
        onSuccess: () => {
        },
        onError: (errors) => {
            form.errors = errors;
        },
    });
};
</script>

<template>
    <AppSidebarLayout title="Products">
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
                                        <Link :href="route('products.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                            Products
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
                                Create Product
                            </h1>
                        </div>
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
                                <InputLabel for="name" value="Name"/>
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                                <InputError class="mt-2" :message="form.errors.name"/>
                            </div>
                            <div>
                                <InputLabel for="brands" value="Brands"/>
                                <SelectInput
                                    class="w-full"
                                    :required="true"
                                    v-model="form.brand_id"
                                    :options="options"
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.brand_id"/>
                            </div>
                            <div>
                                <InputLabel for="category" value="Category"/>
                                <TextInput
                                    id="category"
                                    v-model="form.category"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="category"
                                />
                                <InputError class="mt-2" :message="form.errors.category"/>
                            </div>
                            <div>
                                <InputLabel for="sku" value="SKU"/>
                                <TextInput
                                    id="sku"
                                    v-model="form.sku"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="sku"
                                />
                                <InputError class="mt-2" :message="form.errors.sku"/>
                            </div>
                            <div>
                                <InputLabel for="barcode" value="Barcode"/>
                                <TextInput
                                    id="barcode"
                                    v-model="form.barcode"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="barcode"
                                />
                                <InputError class="mt-2" :message="form.errors.barcode"/>
                            </div>
                            <div>
                                <InputLabel for="cost_price" value="Cost Price"/>
                                <TextInput
                                    id="cost_price"
                                    v-model="form.cost_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="cost_price"
                                />
                                <InputError class="mt-2" :message="form.errors.cost_price"/>
                            </div>
                            <div>
                                <InputLabel for="sale_price" value="Sale Price"/>
                                <TextInput
                                    id="sale_price"
                                    v-model="form.sale_price"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="sale_price"
                                />
                                <InputError class="mt-2" :message="form.errors.sale_price"/>
                            </div>
                            <div>
                                <InputLabel for="description" value="Description"/>
                                <TextInput
                                    id="description"
                                    v-model="form.description"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="description"
                                />
                                <InputError class="mt-2" :message="form.errors.description"/>
                            </div>
                        </div>
                        <div class="md:grid md:grid-cols-1 md:gap-6 mt-2">
                            <div>
                                <InputLabel for="image" value="Image"/>
                                <ImageInput
                                    id="image"
                                    v-model="form.image"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                    autocomplete="image"
                                />
                                <InputError class="mt-2" :message="form.errors.image"/>
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
        brands: {
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
        this.options = this.transformValuesToOptions(this.brands);
    },
};
</script>
