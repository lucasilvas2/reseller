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
    name: 'TopProductsChart',
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
            default: 'Top Products by Stock'
        },
        height: {
            type: String,
            default: '350px'
        },
        orientation: {
            type: String,
            default: 'horizontal', // 'horizontal' or 'vertical'
            validator: value => ['horizontal', 'vertical'].includes(value)
        }
    },
    computed: {
        chartOption() {
            const isHorizontal = this.orientation === 'horizontal';
            const products = this.data.map(item => item.name);
            const values = this.data.map(item => item.value);

            return {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    },
                    formatter: (params) => {
                        const param = params[0];
                        return `<strong>${param.name}</strong><br/>
                                Stock: ${param.value} units`;
                    }
                },
                grid: {
                    left: isHorizontal ? '25%' : '3%',
                    right: '4%',
                    bottom: isHorizontal ? '3%' : '15%',
                    top: '40px',
                    containLabel: true
                },
                xAxis: {
                    type: isHorizontal ? 'value' : 'category',
                    data: isHorizontal ? null : products,
                    name: isHorizontal ? 'Stock Quantity' : '',
                    nameLocation: 'middle',
                    nameGap: 30,
                    axisLabel: isHorizontal ? null : {
                        rotate: 45,
                        formatter: (value) => {
                            return value.length > 10 ? value.substring(0, 10) + '...' : value;
                        }
                    }
                },
                yAxis: {
                    type: isHorizontal ? 'category' : 'value',
                    data: isHorizontal ? products : null,
                    name: isHorizontal ? '' : 'Stock Quantity',
                    nameLocation: 'middle',
                    nameGap: 50,
                    axisLabel: isHorizontal ? {
                        formatter: (value) => {
                            return value.length > 15 ? value.substring(0, 15) + '...' : value;
                        }
                    } : null
                },
                series: [
                    {
                        name: 'Stock',
                        type: 'bar',
                        data: values,
                        itemStyle: {
                            color: {
                                type: 'linear',
                                x: 0, y: 0,
                                x2: isHorizontal ? 1 : 0,
                                y2: isHorizontal ? 0 : 1,
                                colorStops: [
                                    { offset: 0, color: '#3B82F6' },
                                    { offset: 1, color: '#1D4ED8' }
                                ]
                            }
                        },
                        emphasis: {
                            itemStyle: {
                                color: {
                                    type: 'linear',
                                    x: 0, y: 0,
                                    x2: isHorizontal ? 1 : 0,
                                    y2: isHorizontal ? 0 : 1,
                                    colorStops: [
                                        { offset: 0, color: '#60A5FA' },
                                        { offset: 1, color: '#3B82F6' }
                                    ]
                                }
                            }
                        },
                        barWidth: '60%'
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
