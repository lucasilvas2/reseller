<template>
  <div class="relative group">
    <!-- Container similar ao SidebarMenuItem -->
    <button
      @click="toggleTheme"
      :class="[
        'group relative flex items-center px-2 py-2 text-sm font-medium rounded-md transition-all duration-150 ease-in-out w-full',
        'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white',
        collapsed ? 'justify-center' : ''
      ]"
      :title="collapsed ? `${isDark ? '🌙 Modo Escuro' : '☀️ Modo Claro'}` : ''"
    >
      <!-- Ícone principal (posição do ícone dos menu items) -->
      <component
        :is="isDark ? MoonIcon : SunIcon"
        :class="[
          'flex-shrink-0 h-5 w-5 transition-all duration-300',
          isDark
            ? 'text-indigo-400 dark:text-indigo-300'
            : 'text-yellow-500 dark:text-yellow-400',
          collapsed ? '' : 'mr-3'
        ]"
      />

      <!-- Texto e toggle quando não collapsed -->
      <div v-show="!collapsed" class="flex-1 flex items-center justify-between">
        <!-- Texto do tema -->
        <span class="transition-opacity duration-150">
          Tema
        </span>

        <!-- Mini toggle switch (mais discreto) -->
        <div
          :class="[
            'relative inline-flex items-center h-5 w-9 rounded-full transition-all duration-300 ease-in-out',
            'focus:outline-none',
            isDark
              ? 'bg-indigo-600 shadow-indigo-500/25'
              : 'bg-gray-300 shadow-gray-400/25'
          ]"
        >
          <!-- Handle do mini toggle -->
          <span
            :class="[
              'inline-block w-3 h-3 transform transition-transform duration-300 ease-in-out',
              'bg-white rounded-full shadow-sm',
              isDark ? 'translate-x-5' : 'translate-x-1'
            ]"
          />
        </div>
      </div>

      <!-- Badge indicador quando collapsed -->
      <div
        v-if="collapsed"
        :class="[
          'absolute -top-1 -right-1 w-3 h-3 rounded-full transition-all duration-300',
          isDark ? 'bg-indigo-500' : 'bg-yellow-500'
        ]"
      />
    </button>

    <!-- Tooltip para modo collapsed (igual aos outros itens) -->
    <div
      v-if="collapsed"
      class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
      style="transform: translateY(-50%); top: 50%;"
    >
      {{ isDark ? 'Modo Escuro' : 'Modo Claro' }}
      <!-- Seta do tooltip -->
      <div class="absolute right-full top-1/2 transform -translate-y-1/2 border-4 border-transparent border-r-gray-900 dark:border-r-gray-700"></div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useTheme } from '@/composables/useTheme'
import {
  SunIcon,
  MoonIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  collapsed: {
    type: Boolean,
    default: false
  }
})

const { isDark, toggleTheme, initializeTheme } = useTheme()

onMounted(() => {
  initializeTheme()
})
</script>
