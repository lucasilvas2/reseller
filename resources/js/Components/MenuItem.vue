<template>
  <Link
    :href="item.href"
    :class="[
      'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
      item.current
        ? 'bg-blue-100 text-blue-700 border-r-2 border-blue-700'
        : 'text-gray-700 hover:text-blue-700 hover:bg-gray-100',
      collapsed ? 'justify-center' : ''
    ]"
  >
    <component
      :is="item.icon"
      :class="[
        'flex-shrink-0 h-5 w-5',
        item.current ? 'text-blue-700' : 'text-gray-400 group-hover:text-blue-700',
        !collapsed && 'mr-3'
      ]"
    />

    <span v-show="!collapsed" class="flex-1">
      {{ item.name }}
    </span>

    <!-- Badge -->
    <span
      v-if="item.badge && !collapsed"
      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
    >
      {{ item.badge }}
    </span>

    <!-- Tooltip para modo collapsed -->
    <div
      v-if="collapsed"
      class="absolute left-16 px-2 py-1 bg-gray-900 text-white text-sm rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50"
    >
      {{ item.name }}
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
  badge?: number
}

interface Props {
  item: MenuItem
  collapsed: boolean
}

defineProps<Props>()
defineEmits(['click'])
</script>
