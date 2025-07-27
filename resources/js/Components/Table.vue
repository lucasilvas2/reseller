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
                <th
                    v-if="actions && actions.length > 0"
                    scope="col"
                    class="px-6 py-3"
                >
                    Actions
                </th>
            </tr>
            </thead>
            <tbody>
            <tr
                v-for="(row, rowIndex) in rows"
                :key="rowIndex"
                class="odd:bg-white odd:dark:bg-gray-900 even:bg-gray-50 even:dark:bg-gray-800 border-b dark:border-gray-700 border-gray-200"
            >
                <td
                    v-for="(header, headerIndex) in headers"
                    :key="headerIndex"
                    class="px-6 py-4"
                >
                    {{ row[header.toLowerCase()] || '-' }}
                </td>
                <td
                    v-if="actions && actions.length > 0"
                    class="px-6 py-4"
                >
                    <div class="relative">
                        <button
                            :ref="`actionButton-${rowIndex}`"
                            @click="toggleDropdown(rowIndex)"
                            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                            type="button"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 3">
                                <path d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM10.082 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Dropdown posicionado fora da table -->
        <Teleport to="body">
            <div
                v-if="openDropdown !== null && dropdownPosition"
                class="fixed z-50 bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700 dark:divide-gray-600"
                :style="{
                    top: dropdownPosition.top + 'px',
                    left: dropdownPosition.left + 'px'
                }"
            >
                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                    <li v-for="action in actions" :key="action.name">
                        <button
                            @click="executeAction(action, rows[openDropdown])"
                            :class="getActionClasses(action.type)"
                            class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                        >
                            <i :class="action.icon" class="mr-2"></i>
                            {{ action.label }}
                        </button>
                    </li>
                </ul>
            </div>
        </Teleport>
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
        actions: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            openDropdown: null,
            dropdownPosition: null,
        };
    },
    mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        toggleDropdown(index) {
            if (this.openDropdown === index) {
                this.closeDropdown();
                return;
            }

            this.openDropdown = index;
            this.$nextTick(() => {
                this.calculateDropdownPosition(index);
            });
        },
        calculateDropdownPosition(rowIndex) {
            const buttonRef = this.$refs[`actionButton-${rowIndex}`];
            const button = Array.isArray(buttonRef) ? buttonRef[0] : buttonRef;

            if (!button) return;

            const buttonRect = button.getBoundingClientRect();
            const dropdownWidth = 176; // w-44 = 11rem = 176px
            const dropdownHeight = (this.actions.length * 40) + 16;

            let left = buttonRect.right - dropdownWidth;
            let top = buttonRect.bottom + 4;

            // Ajustar se estiver muito à esquerda
            if (left < 10) {
                left = buttonRect.left;
            }

            // Ajustar se estiver muito abaixo da viewport
            if (top + dropdownHeight > window.innerHeight - 10) {
                top = buttonRect.top - dropdownHeight - 4;
            }

            this.dropdownPosition = { left, top };
        },
        handleClickOutside(event) {
            // Verifica se o clique foi no dropdown ou no botão
            const isDropdownClick = event.target.closest('.fixed[style*="z-50"]');
            const isButtonClick = event.target.closest('button[class*="inline-flex"]');

            if (!isDropdownClick && !isButtonClick) {
                this.closeDropdown();
            }
        },
        closeDropdown() {
            this.openDropdown = null;
            this.dropdownPosition = null;
        },
        executeAction(action, row) {
            this.closeDropdown();
            this.$emit('action', { action: action.name, row });
        },
        getActionClasses(type) {
            const classes = {
                danger: 'text-red-600 dark:text-red-400',
                warning: 'text-yellow-600 dark:text-yellow-400',
                success: 'text-green-600 dark:text-green-400',
                default: 'text-gray-700 dark:text-gray-200'
            };
            return classes[type] || classes.default;
        },
    },
};
</script>
