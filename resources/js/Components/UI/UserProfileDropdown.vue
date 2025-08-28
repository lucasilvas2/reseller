<template>
  <div class="relative">
    <!-- Botão do perfil -->
    <button
      @click="toggleDropdown"
      :class="[
        'w-full flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white transition-all duration-150 ease-in-out',
        collapsed ? 'justify-center' : '',
        isOpen ? 'bg-gray-50 dark:bg-gray-700' : ''
      ]"
    >
      <UserCircleIcon
        class="h-8 w-8 text-gray-400 dark:text-gray-500 flex-shrink-0 transition-colors duration-150"
        :class="collapsed ? '' : 'mr-3'"
      />

      <div v-show="!collapsed" class="flex-1 text-left min-w-0">
        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
          {{ user?.name || 'Usuário' }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
          {{ user?.email || '' }}
        </div>
      </div>

      <ChevronUpIcon
        v-show="!collapsed"
        :class="[
          'h-4 w-4 text-gray-400 dark:text-gray-500 transition-all duration-200',
          isOpen ? 'rotate-180' : ''
        ]"
      />
    </button>

    <!-- Dropdown menu com animação -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="isOpen"
        :class="[
          'absolute z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg',
          collapsed ? 'left-full ml-2 bottom-0 w-48' : 'bottom-full left-0 right-0 mb-2'
        ]"
      >
        <div class="py-1">
          <a
            :href="getRoute('profile.show')"
            class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
            @click="closeDropdown"
          >
            <UserCircleIcon class="h-4 w-4 mr-3 text-gray-400 dark:text-gray-500" />
            Perfil
          </a>

          <div class="border-t border-gray-100 dark:border-gray-600"></div>

          <button
            @click="logout"
            class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
          >
            <ArrowRightOnRectangleIcon class="h-4 w-4 mr-3 text-gray-400 dark:text-gray-500" />
            Sair
          </button>
        </div>

        <!-- Seta para tooltip quando collapsed -->
        <div
          v-if="collapsed"
          class="absolute right-full top-4 border-4 border-transparent border-r-white dark:border-r-gray-800"
        ></div>
      </div>
    </Transition>

    <!-- Overlay para fechar dropdown -->
    <div
      v-show="isOpen"
      @click="closeDropdown"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import {
  UserCircleIcon,
  ChevronUpIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'

interface Props {
  collapsed: boolean
}

defineProps<Props>()

const { props } = usePage()
const isOpen = ref(false)

const user = computed(() => (props as any).auth?.user)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
}

const closeDropdown = () => {
  isOpen.value = false
}

const getRoute = (routeName: string) => {
  try {
    return (window as any).route(routeName)
  } catch {
    return '#'
  }
}

const logout = () => {
  router.post((window as any).route('logout'))
}

// Fechar dropdown ao clicar fora
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  const dropdown = document.querySelector('[data-dropdown]')
  if (dropdown && !dropdown.contains(target)) {
    closeDropdown()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
