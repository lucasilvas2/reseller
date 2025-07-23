<template>
    <div class="bg-white p-6 rounded-lg shadow-xl">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ title }}</h3>
        <BaseChart
            :option="chartOption"
            :height="height"
            @chart-click="handleClick"
        />
    </div>
</template>

<script>
import BaseChart from './BaseChart.vue';

export default {
    name: 'StockLevelGauge',
    components: {
        BaseChart
    },
    props: {
        currentStock: {
            type: Number,
            required: true
        },
        maxStock: {
            type: Number,
            required: true
        },
        title: {
            type: String,
            default: 'Stock Level'
        },
        height: {
            type: String,
            default: '300px'
        }
    },
    computed: {
        percentage() {
            return Math.round((this.currentStock / this.maxStock) * 100);
        },
        chartOption() {
            const percentage = this.percentage;

            // Definir cores baseadas no nível
            let color;
            if (percentage >= 70) {
                color = '#10B981'; // verde
            } else if (percentage >= 30) {
                color = '#F59E0B'; // amarelo
            } else {
                color = '#EF4444'; // vermelho
            }

            return {
                series: [
                    {
                        name: 'Stock Level',
                        type: 'gauge',
                        center: ['50%', '60%'],
                        startAngle: 200,
                        endAngle: -40,
                        min: 0,
                        max: 100,
                        splitNumber: 5,
                        itemStyle: {
                            color: color
                        },
                        progress: {
                            show: true,
                            width: 30
                        },
                        pointer: {
                            show: false
                        },
                        axisLine: {
                            lineStyle: {
                                width: 30
                            }
                        },
                        axisTick: {
                            distance: -45,
                            splitNumber: 5,
                            lineStyle: {
                                width: 2,
                                color: '#999'
                            }
                        },
                        splitLine: {
                            distance: -52,
                            length: 14,
                            lineStyle: {
                                width: 3,
                                color: '#999'
                            }
                        },
                        axisLabel: {
                            distance: -20,
                            color: '#999',
                            fontSize: 12,
                            formatter: (value) => value + '%'
                        },
                        anchor: {
                            show: false
                        },
                        title: {
                            show: false
                        },
                        detail: {
                            valueAnimation: true,
                            width: '60%',
                            lineHeight: 40,
                            borderRadius: 8,
                            offsetCenter: [0, '-15%'],
                            fontSize: 30,
                            fontWeight: 'bolder',
                            formatter: `${percentage}%`,
                            color: 'inherit'
                        },
                        data: [
                            {
                                value: percentage
                            }
                        ]
                    }
                ]
            };
        }
    },
    methods: {
        handleClick(params) {
            this.$emit('chart-click', params);
        }
    }
};
</script>
