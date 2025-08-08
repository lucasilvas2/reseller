<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

// Props
const props = defineProps({
    modelValue: {
        type: Object,
        default: null
    },
    error: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: 'Buscar cliente por nome ou email...'
    },
    allowQuickCreate: {
        type: Boolean,
        default: true
    }
});

// Emits
const emit = defineEmits(['update:modelValue', 'client-selected', 'quick-create']);

// Reactive data
const searchQuery = ref('');
const isOpen = ref(false);
const isLoading = ref(false);
const clients = ref([]);
const selectedClient = ref(props.modelValue);
const searchInput = ref(null);
const showQuickCreate = ref(false);

// Quick create form data
const quickCreateForm = ref({
    name: '',
    email: '',
    phone: ''
});

// Computed
const filteredClients = computed(() => {
    if (!searchQuery.value || searchQuery.value.length < 2) return [];
    return clients.value.filter(client =>
        client.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        client.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (client.phone && client.phone.includes(searchQuery.value))
    );
});

const displayText = computed(() => {
    if (selectedClient.value) {
        return `${selectedClient.value.name} (${selectedClient.value.email})`;
    }
    return searchQuery.value;
});

// Methods
const searchClients = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        clients.value = [];
        isOpen.value = false;
        return;
    }

    isLoading.value = true;

    try {
        const response = await window.axios.get(`/ajax/clients/search?q=${encodeURIComponent(searchQuery.value)}`);
        clients.value = response.data.data || [];
        isOpen.value = true;
    } catch (error) {
        console.error('Erro ao buscar clientes:', error);
        clients.value = [];
    } finally {
        isLoading.value = false;
    }
};

const selectClient = (client) => {
    selectedClient.value = client;
    searchQuery.value = '';
    isOpen.value = false;
    showQuickCreate.value = false;

    emit('update:modelValue', client);
    emit('client-selected', client);
};

const clearSelection = () => {
    selectedClient.value = null;
    searchQuery.value = '';
    isOpen.value = false;
    showQuickCreate.value = false;

    emit('update:modelValue', null);
    searchInput.value?.focus();
};

const handleInputFocus = () => {
    if (!selectedClient.value && searchQuery.value.length >= 2) {
        isOpen.value = true;
    }
};

const handleClickOutside = (event) => {
    if (!event.target.closest('.client-selector-container')) {
        isOpen.value = false;
        showQuickCreate.value = false;
    }
};

const openQuickCreate = () => {
    showQuickCreate.value = true;
    isOpen.value = false;
    // Pre-fill name if it looks like a name
    if (searchQuery.value && !searchQuery.value.includes('@')) {
        quickCreateForm.value.name = searchQuery.value;
    }
    // Pre-fill email if it looks like an email
    if (searchQuery.value && searchQuery.value.includes('@')) {
        quickCreateForm.value.email = searchQuery.value;
    }
};

const submitQuickCreate = () => {
    const newClient = {
        name: quickCreateForm.value.name,
        email: quickCreateForm.value.email,
        phone: quickCreateForm.value.phone
    };

    emit('quick-create', newClient);

    // Reset form
    quickCreateForm.value = { name: '', email: '', phone: '' };
    showQuickCreate.value = false;
    searchQuery.value = '';
};

const cancelQuickCreate = () => {
    quickCreateForm.value = { name: '', email: '', phone: '' };
    showQuickCreate.value = false;
    searchInput.value?.focus();
};

// Watchers
watch(searchQuery, (newVal) => {
    if (newVal && newVal.length >= 2) {
        searchClients();
    } else {
        isOpen.value = false;
        clients.value = [];
    }
});

watch(() => props.modelValue, (newVal) => {
    selectedClient.value = newVal;
});

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Expose focus method
defineExpose({ focus: () => searchInput.value?.focus() });
</script>

<template>
    <div class="client-selector-container relative">
        <!-- Label -->
        <InputLabel for="client-search" value="Cliente *" />

        <!-- Selected Client Display -->
        <div v-if="selectedClient" class="mt-1 relative">
            <div class="flex items-center justify-between border border-gray-300 rounded-md shadow-sm bg-white p-3">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ selectedClient.name }}</div>
                    <div class="text-sm text-gray-500">
                        {{ selectedClient.email }}
                        <span v-if="selectedClient.phone"> | {{ selectedClient.phone }}</span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="clearSelection"
                    :disabled="disabled"
                    class="ml-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Input -->
        <div v-else class="mt-1 relative">
            <TextInput
                id="client-search"
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                :placeholder="placeholder"
                :disabled="disabled"
                @focus="handleInputFocus"
                class="w-full"
                autocomplete="off"
            />

            <!-- Loading Spinner -->
            <div v-if="isLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown Results -->
        <div v-if="isOpen && filteredClients.length > 0"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
            <ul class="py-1">
                <li v-for="client in filteredClients"
                    :key="client.id"
                    @click="selectClient(client)"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                    <div class="font-medium text-gray-900">{{ client.name }}</div>
                    <div class="text-sm text-gray-500">{{ client.email }}</div>
                    <div v-if="client.phone" class="text-xs text-gray-400">{{ client.phone }}</div>
                </li>
            </ul>

            <!-- Quick Create Option -->
            <div v-if="allowQuickCreate" class="border-t border-gray-200 p-2">
                <button
                    type="button"
                    @click="openQuickCreate"
                    class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md"
                >
                    + Criar novo cliente
                </button>
            </div>
        </div>

        <!-- No Results -->
        <div v-else-if="isOpen && searchQuery.length >= 2 && !isLoading && filteredClients.length === 0"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
            <div class="p-4 text-center">
                <div class="text-gray-500 mb-3">
                    Nenhum cliente encontrado para "{{ searchQuery }}"
                </div>
                <button
                    v-if="allowQuickCreate"
                    type="button"
                    @click="openQuickCreate"
                    class="inline-flex items-center px-3 py-2 text-sm text-blue-600 hover:bg-blue-50 rounded-md border border-blue-300"
                >
                    + Criar novo cliente
                </button>
            </div>
        </div>

        <!-- Quick Create Form -->
        <div v-if="showQuickCreate"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Criar Novo Cliente</h3>

            <div class="space-y-3">
                <div>
                    <InputLabel for="quick-name" value="Nome *" />
                    <TextInput
                        id="quick-name"
                        v-model="quickCreateForm.name"
                        type="text"
                        placeholder="Nome completo"
                        class="w-full mt-1"
                        required
                    />
                </div>

                <div>
                    <InputLabel for="quick-email" value="Email *" />
                    <TextInput
                        id="quick-email"
                        v-model="quickCreateForm.email"
                        type="email"
                        placeholder="email@exemplo.com"
                        class="w-full mt-1"
                        required
                    />
                </div>

                <div>
                    <InputLabel for="quick-phone" value="Telefone" />
                    <TextInput
                        id="quick-phone"
                        v-model="quickCreateForm.phone"
                        type="tel"
                        placeholder="(11) 99999-9999"
                        class="w-full mt-1"
                    />
                </div>
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button
                    type="button"
                    @click="cancelQuickCreate"
                    class="px-3 py-2 text-sm text-gray-600 hover:text-gray-800"
                >
                    Cancelar
                </button>
                <PrimaryButton
                    type="button"
                    @click="submitQuickCreate"
                    :disabled="!quickCreateForm.name || !quickCreateForm.email"
                >
                    Criar Cliente
                </PrimaryButton>
            </div>
        </div>

        <!-- Error Message -->
        <InputError :message="error" class="mt-2" />
    </div>
</template>
