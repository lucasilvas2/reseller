<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Store Manager - Plataforma de Vendas" />
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
        <!-- Header -->
        <header class="relative z-10 px-6 py-6">
            <nav class="mx-auto max-w-7xl flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-slate-900 dark:text-white">Store Manager</span>
                </div>

                <!-- Navigation -->
                <div v-if="canLogin" class="flex items-center space-x-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white transition-colors"
                    >
                        Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white transition-colors"
                        >
                            Entrar
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                        >
                            Começar Agora
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="relative">
            <div class="mx-auto max-w-7xl px-6 py-20 lg:py-32">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-6xl lg:text-7xl">
                        Gerencie suas
                        <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            vendas
                        </span>
                        com simplicidade
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-300 max-w-3xl mx-auto">
                        Uma plataforma minimalista e moderna para revendedores gerenciarem todo o ciclo de vendas.
                        Controle produtos, clientes, estoque e relatórios em tempo real.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        >
                            Começar Gratuitamente
                        </Link>
                        <Link
                            :href="route('login')"
                            class="px-8 py-4 text-slate-700 dark:text-slate-300 font-semibold hover:text-slate-900 dark:hover:text-white transition-colors"
                        >
                            Fazer Login →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="mx-auto max-w-7xl px-6 py-20">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white sm:text-4xl">
                        Tudo que você precisa para vender mais
                    </h2>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-300">
                        Funcionalidades essenciais para revendedores modernos
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="group relative bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 border border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                            Gestão de Produtos
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300">
                            Cadastre e organize seus produtos com SKUs, controle de estoque e categorização inteligente.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="group relative bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 border border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                            CRM de Clientes
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300">
                            Mantenha um relacionamento próximo com seus clientes e acompanhe todo o histórico de vendas.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="group relative bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 border border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                            Relatórios em Tempo Real
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300">
                            Acompanhe suas vendas, estoque e performance com dashboards intuitivos e dados atualizados.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="group relative bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 border border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                            Controle de Vendas
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300">
                            Registre vendas rapidamente e acompanhe toda a movimentação financeira do seu negócio.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="group relative bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 border border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                            Segurança Total
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300">
                            Autenticação robusta com Laravel Fortify e proteção completa dos seus dados comerciais.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="group relative bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-200 border border-slate-200 dark:border-slate-700">
                        <div class="w-12 h-12 bg-gradient-to-r from-pink-500 to-pink-600 rounded-xl flex items-center justify-center mb-6">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-3">
                            Interface Responsiva
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300">
                            Acesse sua plataforma de qualquer dispositivo com uma interface moderna e intuitiva.
                        </p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mx-auto max-w-7xl px-6 py-20">
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-3xl p-12 text-center">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">
                        Pronto para revolucionar suas vendas?
                    </h2>
                    <p class="mt-4 text-xl text-blue-100">
                        Junte-se aos revendedores que já estão vendendo mais com nossa plataforma.
                    </p>
                    <div class="mt-8">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                        >
                            Começar Agora - É Grátis
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
            <div class="mx-auto max-w-7xl px-6 py-12">
                <div class="flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-900 dark:text-white">Store Manager</span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Construído com Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Animações suaves para hover states */
.group:hover {
    transform: translateY(-2px);
}

/* Gradientes personalizados */
.bg-gradient-to-br {
    background-image: linear-gradient(to bottom right, var(--tw-gradient-stops));
}

/* Efeitos de sombra personalizados */
.shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>

