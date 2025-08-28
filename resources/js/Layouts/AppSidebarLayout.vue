<template>
    <div class="flex h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Sidebar -->
        <aside
            :class="[
        'bg-white dark:bg-gray-800 shadow-lg transition-all duration-300 ease-in-out',
        sidebarCollapsed ? 'w-16' : 'w-64',
        'lg:relative lg:translate-x-0',
        showMobileSidebar ? 'translate-x-0' : '-translate-x-full',
        'fixed inset-y-0 left-0 z-50 lg:z-auto'
      ]"
        >
            <!-- Header da Sidebar -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <div v-show="!sidebarCollapsed" class="flex items-center space-x-2">
                    <img src="/logo.svg" alt="Logo" class="h-8 w-8">
                    <span class="text-xl font-semibold text-gray-800 dark:text-white">Reseller</span>
                </div>

                <!-- Logo quando collapsed -->
                <div v-show="sidebarCollapsed" class="flex items-center justify-center w-full">
                    <img src="/logo.svg" alt="Logo" class="h-8 w-8">
                </div>

                <!-- Toggle Button -->
                <button
                    @click="toggleSidebar"
                    class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 lg:block hidden transition-colors"
                >
                    <ChevronDoubleLeftIcon
                        :class="sidebarCollapsed ? 'rotate-180' : ''"
                        class="h-5 w-5 transition-transform text-gray-500 dark:text-gray-400"
                    />
                </button>
            </div>

            <!-- Menu de Navegação -->
            <nav class="mt-4 px-2">
                <CollapsibleSection
                    v-for="section in menuSections"
                    :key="section.title"
                    :section="section"
                    :global-collapsed="sidebarCollapsed"
                    :default-expanded="getDefaultExpansionState(section)"
                    @item-click="closeMobileSidebar"
                />
            </nav>

            <!-- Footer da Sidebar -->
            <div
                class="absolute bottom-0 left-0 right-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                <!-- Theme Toggle -->
                <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                    <ThemeToggle :collapsed="sidebarCollapsed"/>
                </div>

                <!-- User Profile -->
                <div class="p-4">
                    <UserProfileDropdown :collapsed="sidebarCollapsed"/>
                </div>
            </div>
        </aside>

        <!-- Overlay para Mobile -->
        <div
            v-show="showMobileSidebar"
            @click="closeMobileSidebar"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
        ></div>

        <!-- Conteúdo Principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Mobile -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 lg:hidden">
                <div class="flex items-center justify-between p-4">
                    <button
                        @click="openMobileSidebar"
                        class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        <Bars3Icon class="h-6 w-6 text-gray-600 dark:text-gray-300"/>
                    </button>

                    <h1 class="text-lg font-semibold text-gray-800 dark:text-white">{{ pageTitle }}</h1>

                    <div class="w-10"></div> <!-- Spacer -->
                </div>
            </header>

            <!-- Page Header -->
            <header v-if="$slots.header">
                <slot name="header"/>
            </header>

            <!-- Conteúdo da Página -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
                <slot/>
            </main>
        </div>
    </div>
</template>

<script setup lang="ts">
import {ref, computed, onMounted} from 'vue'
import {usePage, router, Head} from '@inertiajs/vue3'
import SidebarMenuItem from '@/Components/UI/SidebarMenuItem.vue'
import CollapsibleSection from '@/Components/UI/CollapsibleSection.vue'
import UserProfileDropdown from '@/Components/UI/UserProfileDropdown.vue'
import ThemeToggle from '@/Components/UI/ThemeToggle.vue'
import {useTheme} from '@/composables/useTheme'
import {
    ChevronDoubleLeftIcon,
    Bars3Icon,
    HomeIcon,
    ShoppingCartIcon,
    UsersIcon,
    ChartBarIcon,
    CogIcon,
    CubeIcon,
    ArrowsUpDownIcon,
    ArchiveBoxIcon,
    BuildingStorefrontIcon,
    UserCircleIcon
} from '@heroicons/vue/24/outline'

// Estado da Sidebar
const sidebarCollapsed = ref(false)
const showMobileSidebar = ref(false)

// Composables
const {props} = usePage()
const {initializeTheme} = useTheme()
const pageTitle = computed(() => props.title || 'Dashboard');
const hasRole = (role: string): boolean => {
    const user = (props as any).auth?.user
    if (!user || !user.roles) return false

    // If roles is array of strings
    if (Array.isArray(user.roles) && typeof user.roles[0] === 'string') {
        return user.roles.includes(role)
    }

    // If roles is array of objects with name property
    if (Array.isArray(user.roles) && typeof user.roles[0] === 'object') {
        return user.roles.some((r: any) => r.name === role)
    }

    return false
}

// Persistir estado da sidebar
const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value.toString())
}

const openMobileSidebar = () => {
    showMobileSidebar.value = true
}

const closeMobileSidebar = () => {
    showMobileSidebar.value = false
}

// Carregar estado salvo
onMounted(() => {
    const saved = localStorage.getItem('sidebarCollapsed')
    if (saved) {
        sidebarCollapsed.value = saved === 'true'
    }

    // Inicializar tema
    initializeTheme()
})

const routeExists = (routeName: string): boolean => {
    try {
        ;(window as any).route(routeName)
        return true
    } catch {
        return false
    }
}

// Menu de navegação baseado em permissões
const menuSections = computed(() => {
    const sections = []

    // Seção Principal
    const mainItems = []

    // Dashboard (sempre disponível)
    if (routeExists('dashboard')) {
        mainItems.push({
            name: 'Dashboard',
            href: (window as any).route('dashboard'),
            icon: HomeIcon,
            current: (window as any).route().current('dashboard')
        })
    }

    // Vendas (sempre disponível)
    if (routeExists('sales.index')) {
        mainItems.push({
            name: 'Vendas',
            href: (window as any).route('sales.index'),
            icon: ShoppingCartIcon,
            current: (window as any).route().current('sales.*'),
            badge: (props as any).pendingSalesCount || null
        })
    }

    if (mainItems.length > 0) {
        sections.push({
            title: 'Principal',
            items: mainItems
        })
    }

    // Seção Gestão
    const managementItems = []

    // Clientes (para resellers)
    if (routeExists('clients.index') && hasRole('reseller')) {
        managementItems.push({
            name: 'Clientes',
            href: (window as any).route('clients.index'),
            icon: UsersIcon,
            current: (window as any).route().current('clients.*')
        })
    }

    // Produtos (para resellers)
    if (routeExists('products.index') && hasRole('reseller')) {
        managementItems.push({
            name: 'Produtos',
            href: (window as any).route('products.index'),
            icon: CubeIcon,
            current: (window as any).route().current('products.*')
        })
    }

    if (managementItems.length > 0) {
        sections.push({
            title: 'Gestão',
            items: managementItems
        })
    }

    // Seção Estoque (para resellers)
    const stockItems = []

    if (hasRole('reseller')) {
        if (routeExists('stocks.movements.index')) {
            stockItems.push({
                name: 'Movimentações',
                href: (window as any).route('stocks.movements.index'),
                icon: ArrowsUpDownIcon,
                current: (window as any).route().current('stocks.movements.*')
            })
        }

        if (routeExists('stocks.inventory.index')) {
            stockItems.push({
                name: 'Inventário',
                href: (window as any).route('stocks.inventory.index'),
                icon: ArchiveBoxIcon,
                current: (window as any).route().current('stocks.inventory.*')
            })
        }
    }

    if (stockItems.length > 0) {
        sections.push({
            title: 'Estoque',
            items: stockItems
        })
    }

    // Seção Lojas (para usuários comuns)
    const storeItems = []

    if (routeExists('stores.index') && hasRole('user')) {
        storeItems.push({
            name: 'Lojas',
            href: (window as any).route('stores.index'),
            icon: BuildingStorefrontIcon,
            current: (window as any).route().current('stores.*')
        })
    }

    if (storeItems.length > 0) {
        sections.push({
            title: 'Lojas',
            items: storeItems
        })
    }

    return sections
})

// Função para determinar o estado inicial de expansão das seções
const getDefaultExpansionState = (section: any): boolean => {
    // Principais seções sempre expandidas por padrão
    if (['Principal', 'Gestão'].includes(section.title)) {
        return true
    }

    // Expandir se houver item ativo
    return section.items.some((item: any) => item.current)
}
</script>
