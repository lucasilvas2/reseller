<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-200',
      statusClasses
    ]"
  >
    <span
      v-if="showIcon"
      :class="[
        'w-1.5 h-1.5 rounded-full mr-2',
        iconColorClass
      ]"
    ></span>
    {{ displayLabel }}
    <span
      v-if="showPulse && status === 'processing'"
      class="ml-2 relative"
    >
      <span class="flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
      </span>
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: value => [
      'pending', 'processing', 'completed', 'paid', 'failed', 'canceled', 'cancelled'
    ].includes(value)
  },
  type: {
    type: String,
    default: 'sale', // 'sale' or 'item'
    validator: value => ['sale', 'item'].includes(value)
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  showPulse: {
    type: Boolean,
    default: true
  },
  customLabel: {
    type: String,
    default: null
  }
});

// Define status configurations for different types
const statusConfig = {
  sale: {
    pending: {
      label: 'Pending',
      classes: 'bg-yellow-100 text-yellow-800 border-yellow-200',
      iconColor: 'bg-yellow-400'
    },
    processing: {
      label: 'Processing',
      classes: 'bg-blue-100 text-blue-800 border-blue-200',
      iconColor: 'bg-blue-400'
    },
    completed: {
      label: 'Completed',
      classes: 'bg-green-100 text-green-800 border-green-200',
      iconColor: 'bg-green-400'
    },
    paid: {
      label: 'Paid',
      classes: 'bg-emerald-100 text-emerald-800 border-emerald-200',
      iconColor: 'bg-emerald-400'
    },
    failed: {
      label: 'Failed',
      classes: 'bg-red-100 text-red-800 border-red-200',
      iconColor: 'bg-red-400'
    },
    canceled: {
      label: 'Canceled',
      classes: 'bg-gray-100 text-gray-800 border-gray-200',
      iconColor: 'bg-gray-400'
    },
    cancelled: {
      label: 'Canceled',
      classes: 'bg-gray-100 text-gray-800 border-gray-200',
      iconColor: 'bg-gray-400'
    }
  },
  item: {
    pending: {
      label: 'Pending',
      classes: 'bg-yellow-50 text-yellow-700 border border-yellow-200',
      iconColor: 'bg-yellow-500'
    },
    processing: {
      label: 'Processing',
      classes: 'bg-blue-50 text-blue-700 border border-blue-200',
      iconColor: 'bg-blue-500'
    },
    completed: {
      label: 'Completed',
      classes: 'bg-green-50 text-green-700 border border-green-200',
      iconColor: 'bg-green-500'
    },
    failed: {
      label: 'Failed',
      classes: 'bg-red-50 text-red-700 border border-red-200',
      iconColor: 'bg-red-500'
    },
    canceled: {
      label: 'Canceled',
      classes: 'bg-gray-50 text-gray-700 border border-gray-200',
      iconColor: 'bg-gray-500'
    },
    cancelled: {
      label: 'Canceled',
      classes: 'bg-gray-50 text-gray-700 border border-gray-200',
      iconColor: 'bg-gray-500'
    }
  }
};

const currentConfig = computed(() => {
  const config = statusConfig[props.type]?.[props.status] || statusConfig.sale.pending;
  return config;
});

const statusClasses = computed(() => {
  return currentConfig.value.classes;
});

const iconColorClass = computed(() => {
  return currentConfig.value.iconColor;
});

const displayLabel = computed(() => {
  return props.customLabel || currentConfig.value.label;
});
</script>

<style scoped>
/* Additional hover effects for interactive badges */
.group:hover .status-badge {
  @apply shadow-sm;
}

/* Custom animations for processing status */
@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse-dot {
  animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
