<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import ImageInput from "@/Components/ImageInput.vue";

const form = useForm({
    name: '',
    image: '',
    description: '',
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
    <AppLayout title="Products">
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
                            <div>
                                <InputLabel for="name" value="Description"/>
                                <TextInput
                                    id="description"
                                    v-model="form.description"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="description"
                                />
                                <InputError class="mt-2" :message="form.errors.description"/>
                            </div>
                            <div>
                                <InputLabel for="brands" value="Brands"/>
                                <SelectInput
                                    class="w-full"
                                    v-model="form.brand_id"
                                    :options="options"
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.brand_id"/>
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
