<template>
    <div :class="cardClasses">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div :class="iconClasses">
                    <i :class="iconClass"></i>
                </div>
            </div>
            <div class="ml-4">
                <div class="text-sm font-medium text-gray-500">{{ label }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ value }}</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        icon: String,
        label: String,
        value: [String, Number],
        color: String,
    },
    computed: {
        cardClasses() {
            const baseClasses = 'bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-t-4';
            const colorClass = `border-${this.color}-500`;
            return `${baseClasses} ${colorClass}`;
        },
        iconClasses() {
            const baseClasses = 'w-8 h-8 rounded-full flex items-center justify-center';
            const colorClass = `bg-${this.color}-500`;
            return `${baseClasses} ${colorClass}`;
        },
        iconClass() {
            return `${this.icon} text-white text-sm`;
        }
    }
};
</script>
