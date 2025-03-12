<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
});

</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
<!--        <SectionTitle>-->
<!--            <template #title>-->
<!--                <slot name="title" />-->
<!--            </template>-->
<!--            <template #description>-->
<!--                <slot name="description" />-->
<!--            </template>-->
<!--        </SectionTitle>-->

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
            </form>
        </div>
    </div>
</template>
