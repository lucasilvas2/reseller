<template>
    <div class="bg-white shadow-xl sm:rounded-lg overflow-hidden">
        <!-- Header com Search e Items per page -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                <div class="flex-1">
                    <h3 v-if="title" class="text-lg font-medium text-gray-900">{{ title }}</h3>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Items per page -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-700">Show:</span>
                        <select
                            v-model="itemsPerPage"
                            class="border border-gray-300 rounded-md text-sm py-1 px-2 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="(header, index) in headers"
                            :key="index"
                            @click="sort(header.key || header.toLowerCase())"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 select-none"
                            :class="{ 'bg-gray-100': sortKey === (header.key || header.toLowerCase()) }"
                        >
                            <div class="flex items-center space-x-1">
                                <span>{{ header.label || header }}</span>
                                <svg v-if="sortKey === (header.key || header.toLowerCase())" class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path v-if="sortOrder === 'asc'" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                    <path v-else d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                </svg>
                            </div>
                        </th>
                        <th
                            v-if="actions && actions.length > 0"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(row, rowIndex) in paginatedData"
                        :key="rowIndex"
                        class="hover:bg-gray-50 transition-colors duration-150"
                    >
                        <td
                            v-for="(header, headerIndex) in headers"
                            :key="headerIndex"
                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                        >
                            <slot
                                :name="`cell-${header.key || header.toLowerCase()}`"
                                :row="row"
                                :value="getNestedValue(row, header.key || header.toLowerCase())"
                            >
                                {{ formatValue(getNestedValue(row, header.key || header.toLowerCase())) }}
                            </slot>
                        </td>
                        <td
                            v-if="actions && actions.length > 0"
                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                        >
                            <div class="relative inline-block text-left">
                                <button
                                    :ref="`actionButton-${rowIndex}`"
                                    @click="toggleDropdown(rowIndex)"
                                    class="inline-flex items-center p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full"
                                    type="button"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- Empty state -->
                    <tr v-if="paginatedData.length === 0">
                        <td :colspan="headers.length + (actions?.length > 0 ? 1 : 0)" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-3 3m-3-3l3 3"></path>
                                </svg>
                                <p class="text-sm">{{ emptyMessage || 'No data available' }}</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <!-- Mobile pagination info -->
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing {{ paginationInfo.from }} to {{ paginationInfo.to }} of {{ paginationInfo.total }} results
                        </p>
                    </div>
                    <!-- Mobile pagination buttons (only when multiple pages) -->
                    <div v-if="totalPages > 1" class="flex space-x-2">
                        <button
                            @click="previousPage"
                            :disabled="currentPage === 1"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="nextPage"
                            :disabled="currentPage === totalPages"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ paginationInfo.from }}</span>
                            to
                            <span class="font-medium">{{ paginationInfo.to }}</span>
                            of
                            <span class="font-medium">{{ paginationInfo.total }}</span>
                            results
                        </p>
                    </div>
                    <div v-if="totalPages > 1">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <!-- Previous -->
                            <button
                                @click="previousPage"
                                :disabled="currentPage === 1"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>

                            <!-- Page numbers -->
                            <template v-for="page in visiblePages" :key="page">
                                <button
                                    v-if="page !== '...'"
                                    @click="goToPage(page)"
                                    :class="{
                                        'bg-blue-50 border-blue-500 text-blue-600': page === currentPage,
                                        'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !== currentPage
                                    }"
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                >
                                    ...
                                </span>
                            </template>

                            <!-- Next -->
                            <button
                                @click="nextPage"
                                :disabled="currentPage === totalPages"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dropdown Menu (Teleported to body) -->
        <Teleport to="body">
            <div
                v-if="openDropdown !== null && dropdownPosition"
                class="fixed z-50 bg-white divide-y divide-gray-100 rounded-lg shadow-lg border border-gray-200 w-48"
                :style="{
                    top: dropdownPosition.top + 'px',
                    left: dropdownPosition.left + 'px'
                }"
            >
                <div class="py-1">
                    <button
                        v-for="action in actions"
                        :key="action.name"
                        @click="executeAction(action, paginatedData[openDropdown])"
                        :class="getActionClasses(action.type)"
                        class="group flex items-center w-full px-4 py-2 text-sm hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                    >
                        <i :class="action.icon" class="mr-3 text-gray-400 group-hover:text-gray-500"></i>
                        {{ action.label }}
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
export default {
    name: 'PaginatedTable',
    props: {
        title: {
            type: String,
            default: null
        },
        headers: {
            type: Array,
            required: true,
            // Format: [{ label: 'Name', key: 'name' }, 'email', { label: 'Created At', key: 'created_at' }]
        },
        data: {
            type: Array,
            required: true,
        },
        actions: {
            type: Array,
            default: () => [],
        },
        emptyMessage: {
            type: String,
            default: 'No data available'
        },
        defaultItemsPerPage: {
            type: Number,
            default: 10
        },
        searchable: {
            type: Boolean,
            default: true
        }
    },
    data() {
        return {
            currentPage: 1,
            itemsPerPage: this.defaultItemsPerPage,
            searchQuery: '',
            sortKey: '',
            sortOrder: 'asc',
            openDropdown: null,
            dropdownPosition: null,
        };
    },
    computed: {
        filteredData() {
            if (!this.searchQuery) return this.data;

            const query = this.searchQuery.toLowerCase();
            return this.data.filter(row => {
                return this.headers.some(header => {
                    const key = header.key || header.toLowerCase();
                    const value = this.getNestedValue(row, key);
                    return value && value.toString().toLowerCase().includes(query);
                });
            });
        },
        sortedData() {
            if (!this.sortKey) return this.filteredData;

            return [...this.filteredData].sort((a, b) => {
                const aVal = this.getNestedValue(a, this.sortKey);
                const bVal = this.getNestedValue(b, this.sortKey);

                let comparison = 0;
                if (aVal > bVal) comparison = 1;
                if (aVal < bVal) comparison = -1;

                return this.sortOrder === 'desc' ? comparison * -1 : comparison;
            });
        },
        totalPages() {
            return Math.ceil(this.sortedData.length / this.itemsPerPage);
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.sortedData.slice(start, end);
        },
        paginationInfo() {
            const start = (this.currentPage - 1) * this.itemsPerPage + 1;
            const end = Math.min(this.currentPage * this.itemsPerPage, this.sortedData.length);
            return {
                from: this.sortedData.length === 0 ? 0 : start,
                to: end,
                total: this.sortedData.length
            };
        },
        visiblePages() {
            const pages = [];
            const total = this.totalPages;
            const current = this.currentPage;

            if (total <= 7) {
                for (let i = 1; i <= total; i++) {
                    pages.push(i);
                }
            } else {
                if (current <= 4) {
                    for (let i = 1; i <= 5; i++) pages.push(i);
                    pages.push('...');
                    pages.push(total);
                } else if (current >= total - 3) {
                    pages.push(1);
                    pages.push('...');
                    for (let i = total - 4; i <= total; i++) pages.push(i);
                } else {
                    pages.push(1);
                    pages.push('...');
                    for (let i = current - 1; i <= current + 1; i++) pages.push(i);
                    pages.push('...');
                    pages.push(total);
                }
            }

            return pages;
        }
    },
    watch: {
        itemsPerPage() {
            this.currentPage = 1;
        },
        searchQuery() {
            this.currentPage = 1;
        }
    },
    mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        getNestedValue(obj, path) {
            return path.split('.').reduce((current, key) => current?.[key], obj) || '-';
        },
        formatValue(value) {
            if (value === null || value === undefined) return '-';
            if (typeof value === 'boolean') return value ? 'Yes' : 'No';
            return value;
        },
        sort(key) {
            if (this.sortKey === key) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortKey = key;
                this.sortOrder = 'asc';
            }
        },
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },
        previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },
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
            const dropdownWidth = 192; // w-48 = 12rem = 192px
            const dropdownHeight = (this.actions.length * 40) + 8;

            let left = buttonRect.right - dropdownWidth;
            let top = buttonRect.bottom + 4;

            // Adjust if too far left
            if (left < 10) {
                left = buttonRect.left;
            }

            // Adjust if too far down
            if (top + dropdownHeight > window.innerHeight - 10) {
                top = buttonRect.top - dropdownHeight - 4;
            }

            this.dropdownPosition = { left, top };
        },
        handleClickOutside(event) {
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
                danger: 'text-red-600 hover:text-red-700 hover:bg-red-50',
                warning: 'text-yellow-600 hover:text-yellow-700 hover:bg-yellow-50',
                success: 'text-green-600 hover:text-green-700 hover:bg-green-50',
                primary: 'text-blue-600 hover:text-blue-700 hover:bg-blue-50',
                default: 'text-gray-700 hover:text-gray-900'
            };
            return classes[type] || classes.default;
        },
    },
};
</script>
