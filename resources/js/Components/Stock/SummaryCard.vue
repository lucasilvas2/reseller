<template>
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="text-2xl" :class="iconColorClass">
                        {{ icon }}
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">
                            {{ title }}
                        </dt>
                        <dd class="text-lg font-medium text-gray-900">
                            {{ formattedValue }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="px-5 py-3" :class="backgroundColorClass">
            <div class="text-sm">
                <slot name="footer">
                    <span class="font-medium" :class="textColorClass">
                        {{ subtitle || 'Updated just now' }}
                    </span>
                </slot>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SummaryCard',
    props: {
        title: {
            type: String,
            required: true
        },
        value: {
            type: [Number, String],
            required: true
        },
        icon: {
            type: String,
            default: '📊'
        },
        color: {
            type: String,
            default: 'blue',
            validator: (value) => ['blue', 'green', 'yellow', 'red', 'purple', 'gray'].includes(value)
        },
        subtitle: {
            type: String,
            default: null
        },
        formatType: {
            type: String,
            default: 'number',
            validator: (value) => ['number', 'currency', 'percentage', 'text'].includes(value)
        }
    },
    computed: {
        formattedValue() {
            if (this.formatType === 'currency') {
                return new Intl.NumberFormat('pt-BR', {
                    style: 'currency',
                    currency: 'BRL'
                }).format(this.value);
            } else if (this.formatType === 'percentage') {
                return this.value + '%';
            } else if (this.formatType === 'number') {
                return new Intl.NumberFormat('pt-BR').format(this.value);
            }
            return this.value;
        },
        iconColorClass() {
            const colorMap = {
                blue: 'text-blue-500',
                green: 'text-green-500',
                yellow: 'text-yellow-500',
                red: 'text-red-500',
                purple: 'text-purple-500',
                gray: 'text-gray-500'
            };
            return colorMap[this.color] || colorMap.blue;
        },
        backgroundColorClass() {
            const colorMap = {
                blue: 'bg-blue-50',
                green: 'bg-green-50',
                yellow: 'bg-yellow-50',
                red: 'bg-red-50',
                purple: 'bg-purple-50',
                gray: 'bg-gray-50'
            };
            return colorMap[this.color] || colorMap.blue;
        },
        textColorClass() {
            const colorMap = {
                blue: 'text-blue-600',
                green: 'text-green-600',
                yellow: 'text-yellow-600',
                red: 'text-red-600',
                purple: 'text-purple-600',
                gray: 'text-gray-600'
            };
            return colorMap[this.color] || colorMap.blue;
        }
    }
};
</script>
