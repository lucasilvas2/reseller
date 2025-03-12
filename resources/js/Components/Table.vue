<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <!-- Cabeçalhos dinâmicos -->
                <th
                    v-for="(header, index) in headers"
                    :key="index"
                    scope="col"
                    class="px-6 py-3"
                >
                    {{ header }}
                </th>
                <!-- Coluna de ações (opcional) -->
                <th
                    v-if="hasActions"
                    scope="col"
                    class="px-6 py-3"
                >
                    Action
                </th>
            </tr>
            </thead>
            <tbody>
            <!-- Linhas dinâmicas -->
            <tr
                v-for="(row, rowIndex) in rows"
                :key="rowIndex"
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
            >
                <!-- Células dinâmicas -->
                <td
                    v-for="(header, headerIndex) in headers"
                    :key="headerIndex"
                    class="px-6 py-4"
                >
                    {{ row[header.toLowerCase()] || '-' }}
                </td>
                <!-- Coluna de ações (opcional) -->
                <td
                    v-if="hasActions"
                    class="px-6 py-4"
                >
                    <a
                        href="#"
                        class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                        @click.prevent="onEdit(row)"
                    >
                        Edit
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: 'Table',
    props: {
        headers: {
            type: Array,
            required: true,
        },
        rows: {
            type: Array,
            required: true,
        },
        hasActions: {
            type: Boolean,
            default: false,
        },
    },
    methods: {
        onEdit(row) {
            this.$emit('edit', row);
        },
    },
};
</script>

<style scoped>
/* Estilos específicos do componente, se necessário */
</style>
