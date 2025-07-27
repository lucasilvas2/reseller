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
    name: 'StockDistributionChart',
    components: {
        BaseChart
    },
    props: {
        data: {
            type: Array,
            required: true
        },
        title: {
            type: String,
            default: 'Stock Distribution'
        },
        height: {
            type: String,
            default: '350px'
        },
        type: {
            type: String,
            default: 'pie', // 'pie' or 'doughnut'
            validator: value => ['pie', 'doughnut'].includes(value)
        }
    },
    computed: {
        chartOption() {
            const colors = [
                '#3B82F6', // blue
                '#10B981', // emerald
                '#F59E0B', // amber
                '#EF4444', // red
                '#8B5CF6', // violet
                '#06B6D4', // cyan
                '#84CC16', // lime
                '#F97316', // orange
                '#EC4899', // pink
                '#6B7280'  // gray
            ];

            return {
                tooltip: {
                    trigger: 'item',
                    formatter: '{a} <br/>{b}: {c} ({d}%)'
                },
                legend: {
                    orient: 'vertical',
                    left: 'left',
                    top: 'middle',
                    itemGap: 12,
                    textStyle: {
                        fontSize: 12
                    }
                },
                series: [
                    {
                        name: this.title,
                        type: 'pie',
                        radius: this.type === 'doughnut' ? ['40%', '70%'] : '70%',
                        center: ['60%', '50%'],
                        data: this.data.map((item, index) => ({
                            value: item.value,
                            name: item.name,
                            itemStyle: {
                                color: colors[index % colors.length]
                            }
                        })),
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        },
                        label: {
                            show: true,
                            formatter: '{b}\n{d}%',
                            fontSize: 12
                        },
                        labelLine: {
                            show: true
                        }
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
