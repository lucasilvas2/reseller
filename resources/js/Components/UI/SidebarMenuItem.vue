<template>
  <Link
    :href="item.href"
    :class="[
      'group relative flex items-center px-2 py-2 text-sm font-medium rounded-md transition-all duration-150 ease-in-out',
      item.current
        ? 'bg-indigo-100 text-indigo-900 border-r-4 border-indigo-500 dark:bg-indigo-900 dark:text-indigo-100'
        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white',
      collapsed ? 'justify-center' : ''
    ]"
    @click="$emit('click')"
  >
    <component
      :is="item.icon"
      :class="[
        'flex-shrink-0 h-6 w-6 transition-colors duration-150',
        item.current ? 'text-indigo-500 dark:text-indigo-300' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300',
        collapsed ? '' : 'mr-3'
      ]"
    />

    <!-- Nome do item -->
    <span
      v-show="!collapsed"
      class="flex-1 transition-opacity duration-150"
    >
      {{ item.name }}
    </span>

    <!-- Badge (se existir) -->
    <span
      v-if="item.badge && !collapsed"
      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 animate-pulse"
    >
      {{ item.badge }}
    </span>

    <!-- Tooltip para modo collapsed -->
    <div
      v-if="collapsed"
      class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50 pointer-events-none"
      style="transform: translateY(-50%); top: 50%;"
    >
      {{ item.name }}
      <span v-if="item.badge" class="ml-1 px-1.5 py-0.5 bg-red-500 text-white rounded-full text-xs">
        {{ item.badge }}
      </span>
      <!-- Seta do tooltip -->
      <div class="absolute right-full top-1/2 transform -translate-y-1/2 border-4 border-transparent border-r-gray-900 dark:border-r-gray-700"></div>
    </div>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface MenuItem {
  name: string
  href: string
  icon: any
  current: boolean
  badge?: number | string
}

interface Props {
  item: MenuItem
  collapsed: boolean
}

defineProps<Props>()
defineEmits<{
  click: []
}>()
</script>
