<template>
  <div class="mb-6">
    <!-- Header da Seção (clicável para expandir/colapsar) -->
    <button
      v-show="!globalCollapsed"
      @click="toggleSection"
      :class="[
        'w-full flex items-center justify-between px-2 py-1.5 text-xs font-semibold uppercase tracking-wide transition-all duration-200 rounded-md group',
        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
        'hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50',
        hasActiveItem ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20' : ''
      ]"
    >
      <div class="flex items-center gap-2">
        <span>{{ section.title }}</span>

        <!-- Badge com contador de itens ativos -->
        <span
          v-if="hasActiveItem"
          class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200"
        >
          {{ activeItemCount }}
        </span>
      </div>

      <!-- Ícone de expansão/colapso -->
      <ChevronDownIcon
        :class="[
          'h-4 w-4 transition-all duration-200 group-hover:scale-110',
          isExpanded ? 'rotate-180' : 'rotate-0'
        ]"
      />
    </button>

    <!-- Separador quando sidebar está collapsed -->
    <hr
      v-show="globalCollapsed"
      class="my-4 border-gray-200 dark:border-gray-600"
    />

    <!-- Conteúdo da Seção com animação -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 transform -translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform -translate-y-2"
    >
      <div
        v-show="isExpanded || globalCollapsed"
        :class="[
          globalCollapsed ? '' : 'mt-2'
        ]"
      >
        <ul class="space-y-1">
          <li v-for="item in section.items" :key="item.name">
            <SidebarMenuItem
              :item="item"
              :collapsed="globalCollapsed"
              @click="$emit('itemClick')"
            />
          </li>
        </ul>
      </div>
    </Transition>

    <!-- Badge de itens quando seção collapsed -->
    <div
      v-show="!globalCollapsed && !isExpanded && hasActiveItem"
      class="px-2 mt-1"
    >
      <div class="h-px bg-indigo-200 dark:bg-indigo-700"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import SidebarMenuItem from '@/Components/UI/SidebarMenuItem.vue'

const props = defineProps({
  section: {
    type: Object,
    required: true
  },
  globalCollapsed: {
    type: Boolean,
    default: false
  },
  defaultExpanded: {
    type: Boolean,
    default: true
  }
})

defineEmits(['itemClick'])

const isExpanded = ref(props.defaultExpanded)

// Verificar se há item ativo na seção
const hasActiveItem = computed(() => {
  return props.section.items.some(item => item.current)
})

// Contador de itens ativos
const activeItemCount = computed(() => {
  return props.section.items.filter(item => item.current).length
})

// Auto-expandir se houver item ativo
watch(() => hasActiveItem.value, (hasActive) => {
  if (hasActive) {
    isExpanded.value = true
  }
})

// Sempre expandir quando a sidebar global é collapsed
watch(() => props.globalCollapsed, (collapsed) => {
  if (collapsed) {
    isExpanded.value = true
  }
})

const toggleSection = () => {
  if (!props.globalCollapsed) {
    isExpanded.value = !isExpanded.value

    // Salvar estado no localStorage
    const sectionKey = `section_${props.section.title.toLowerCase()}_expanded`
    localStorage.setItem(sectionKey, isExpanded.value.toString())
  }
}

// Carregar estado salvo
onMounted(() => {
  const sectionKey = `section_${props.section.title.toLowerCase()}_expanded`
  const saved = localStorage.getItem(sectionKey)

  if (saved !== null) {
    isExpanded.value = saved === 'true'
  }

  // Se houver item ativo, sempre expandir
  if (hasActiveItem.value) {
    isExpanded.value = true
  }
})
</script>
