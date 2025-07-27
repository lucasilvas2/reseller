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
    name: 'MovementsTrendChart',
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
            default: 'Stock Movements Trend'
        },
        height: {
            type: String,
            default: '350px'
        }
    },
    computed: {
        chartOption() {
            const dates = this.data.map(item => item.date);
            const inMovements = this.data.map(item => item.in || 0);
            const outMovements = this.data.map(item => item.out || 0);

            return {
                title: {
                    text: '',
                    left: 'center'
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross'
                    },
                    formatter: (params) => {
                        let result = `<strong>${params[0].axisValue}</strong><br/>`;
                        params.forEach(param => {
                            result += `${param.marker} ${param.seriesName}: ${param.value}<br/>`;
                        });
                        return result;
                    }
                },
                legend: {
                    data: ['Stock In', 'Stock Out'],
                    top: 30
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    top: '80px',
                    containLabel: true
                },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: dates,
                    axisLabel: {
                        formatter: (value) => {
                            return new Date(value).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                            });
                        }
                    }
                },
                yAxis: {
                    type: 'value',
                    name: 'Quantity',
                    nameLocation: 'middle',
                    nameGap: 50
                },
                series: [
                    {
                        name: 'Stock In',
                        type: 'line',
                        data: inMovements,
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 6,
                        lineStyle: {
                            width: 3
                        },
                        areaStyle: {
                            opacity: 0.3,
                            color: {
                                type: 'linear',
                                x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [
                                    { offset: 0, color: 'rgba(34, 197, 94, 0.4)' },
                                    { offset: 1, color: 'rgba(34, 197, 94, 0.1)' }
                                ]
                            }
                        },
                        itemStyle: {
                            color: '#22C55E'
                        }
                    },
                    {
                        name: 'Stock Out',
                        type: 'line',
                        data: outMovements,
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 6,
                        lineStyle: {
                            width: 3
                        },
                        areaStyle: {
                            opacity: 0.3,
                            color: {
                                type: 'linear',
                                x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [
                                    { offset: 0, color: 'rgba(239, 68, 68, 0.4)' },
                                    { offset: 1, color: 'rgba(239, 68, 68, 0.1)' }
                                ]
                            }
                        },
                        itemStyle: {
                            color: '#EF4444'
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
