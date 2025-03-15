<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    role: '',
    errors: {},
});

const submitForm = () => {
    form.post('/admin/users/store', {
        onSuccess: () => {
        },
        onError: (errors) => {
            form.errors = errors;
        },
    });
};
</script>

<template>
    <AdminLayout title="Users">
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
                                <InputLabel for="email" value="Email"/>
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                                <InputError class="mt-2" :message="form.errors.email"/>
                            </div>
                            <div>
                                <InputLabel for="phone_number" value="Phone Number"/>
                                <TextInput
                                    id="phone_number"
                                    v-model="form.phone_number"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    autocomplete="tel"
                                />
                                <InputError class="mt-2" :message="form.errors.phone_number"/>
                            </div>
                            <div>
                                <InputLabel for="role" value="Role"/>
                                <SelectInput
                                    class="w-full"
                                    v-model="form.role"
                                    :options="options"
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.role"/>
                            </div>
                            <div>
                                <InputLabel for="delaership" value="Dealership"/>
                                <SelectInput
                                    class="w-full"
                                    v-model="form.dealership"
                                    :options="optionsDealership"
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.dealership"/>
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
    </AdminLayout>
</template>

<script>


export default {
    components: {},
    props: {
        roles: {
            type: Array,
            required: true,
        },
        dealerships:{
            type: Array,
            required: true
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
        submit(){
            form.post('/admin/login');
        }
    },
    mounted() {
        this.options = this.transformValuesToOptions(this.roles);
        this.optionsDealership = this.transformValuesToOptions(this.dealerships);
    },
};
</script>
