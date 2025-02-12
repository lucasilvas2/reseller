<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th
                    v-for="(header, index) in headers"
                    :key="index"
                    scope="col"
                    class="px-6 py-3"
                >
                    {{ header }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr
                v-for="(row, rowIndex) in rows"
                :key="rowIndex"
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
            >
                <th
                    scope="row"
                    class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                >
                    {{ row.productName }}
                </th>
                <td class="px-6 py-4">
                    {{ row.color }}
                </td>
                <td class="px-6 py-4">
                    {{ row.category }}
                </td>
                <td class="px-6 py-4">
                    {{ row.price }}
                </td>
                <td class="px-6 py-4">
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
