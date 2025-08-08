<template>
  <div
    v-if="show"
    :class="[
      'flex items-center justify-center',
      containerClasses
    ]"
  >
    <!-- Spinner -->
    <div
      v-if="type === 'spinner'"
      :class="[
        'animate-spin rounded-full border-t-2 border-b-2',
        spinnerClasses
      ]"
    ></div>

    <!-- Dots -->
    <div
      v-else-if="type === 'dots'"
      class="flex space-x-1"
    >
      <div
        v-for="i in 3"
        :key="i"
        :class="[
          'rounded-full animate-pulse',
          dotClasses
        ]"
        :style="{ animationDelay: `${(i - 1) * 0.2}s` }"
      ></div>
    </div>

    <!-- Pulse bars -->
    <div
      v-else-if="type === 'bars'"
      class="flex space-x-1"
    >
      <div
        v-for="i in 4"
        :key="i"
        :class="[
          'animate-pulse',
          barClasses
        ]"
        :style="{
          animationDelay: `${(i - 1) * 0.1}s`,
          animationDuration: '1s'
        }"
      ></div>
    </div>

    <!-- Loading text -->
    <div
      v-if="showText && message"
      :class="[
        'ml-3 text-sm font-medium',
        textClasses
      ]"
    >
      {{ message }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: true
  },
  type: {
    type: String,
    default: 'spinner', // 'spinner', 'dots', 'bars'
    validator: value => ['spinner', 'dots', 'bars'].includes(value)
  },
  size: {
    type: String,
    default: 'md', // 'sm', 'md', 'lg', 'xl'
    validator: value => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'blue', // 'blue', 'gray', 'green', 'red', 'yellow', 'purple'
    validator: value => ['blue', 'gray', 'green', 'red', 'yellow', 'purple'].includes(value)
  },
  message: {
    type: String,
    default: null
  },
  showText: {
    type: Boolean,
    default: true
  },
  centered: {
    type: Boolean,
    default: true
  },
  fullScreen: {
    type: Boolean,
    default: false
  },
  overlay: {
    type: Boolean,
    default: false
  }
});

const sizeConfig = {
  xs: {
    spinner: 'h-3 w-3',
    dot: 'h-1 w-1',
    bar: 'h-2 w-1'
  },
  sm: {
    spinner: 'h-4 w-4',
    dot: 'h-1.5 w-1.5',
    bar: 'h-3 w-1'
  },
  md: {
    spinner: 'h-6 w-6',
    dot: 'h-2 w-2',
    bar: 'h-4 w-1'
  },
  lg: {
    spinner: 'h-8 w-8',
    dot: 'h-3 w-3',
    bar: 'h-5 w-1'
  },
  xl: {
    spinner: 'h-12 w-12',
    dot: 'h-4 w-4',
    bar: 'h-6 w-1.5'
  }
};

const colorConfig = {
  blue: {
    spinner: 'border-blue-500',
    dot: 'bg-blue-500',
    bar: 'bg-blue-500',
    text: 'text-blue-600'
  },
  gray: {
    spinner: 'border-gray-500',
    dot: 'bg-gray-500',
    bar: 'bg-gray-500',
    text: 'text-gray-600'
  },
  green: {
    spinner: 'border-green-500',
    dot: 'bg-green-500',
    bar: 'bg-green-500',
    text: 'text-green-600'
  },
  red: {
    spinner: 'border-red-500',
    dot: 'bg-red-500',
    bar: 'bg-red-500',
    text: 'text-red-600'
  },
  yellow: {
    spinner: 'border-yellow-500',
    dot: 'bg-yellow-500',
    bar: 'bg-yellow-500',
    text: 'text-yellow-600'
  },
  purple: {
    spinner: 'border-purple-500',
    dot: 'bg-purple-500',
    bar: 'bg-purple-500',
    text: 'text-purple-600'
  }
};

const containerClasses = computed(() => {
  const classes = [];

  if (props.centered) {
    classes.push('justify-center');
  }

  if (props.fullScreen) {
    classes.push('fixed inset-0 z-50');
  }

  if (props.overlay) {
    classes.push('bg-white bg-opacity-75');
  }

  return classes.join(' ');
});

const spinnerClasses = computed(() => {
  return [
    sizeConfig[props.size].spinner,
    colorConfig[props.color].spinner
  ].join(' ');
});

const dotClasses = computed(() => {
  return [
    sizeConfig[props.size].dot,
    colorConfig[props.color].dot
  ].join(' ');
});

const barClasses = computed(() => {
  return [
    sizeConfig[props.size].bar,
    colorConfig[props.color].bar
  ].join(' ');
});

const textClasses = computed(() => {
  return colorConfig[props.color].text;
});
</script>

<style scoped>
/* Custom keyframes for different loading animations */
@keyframes pulse-dot {
  0%, 80%, 100% {
    transform: scale(0);
    opacity: 0.5;
  }
  40% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes pulse-bar {
  0%, 40%, 100% {
    transform: scaleY(0.4);
    opacity: 0.5;
  }
  20% {
    transform: scaleY(1);
    opacity: 1;
  }
}

.animate-pulse-dot {
  animation: pulse-dot 1.4s ease-in-out infinite;
}

.animate-pulse-bar {
  animation: pulse-bar 1s ease-in-out infinite;
}
</style>
