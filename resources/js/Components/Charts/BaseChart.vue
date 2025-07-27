<template>
    <div :style="{ height: height, width: width }">
        <v-chart
            :option="chartOption"
            :style="{ height: '100%', width: '100%' }"
            autoresize
            @click="handleChartClick"
        />
    </div>
</template>

<script>
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import {
    LineChart,
    BarChart,
    PieChart,
    GaugeChart,
    ScatterChart
} from 'echarts/charts';
import {
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    DatasetComponent,
    TransformComponent,
    ToolboxComponent
} from 'echarts/components';
import VChart from 'vue-echarts';

use([
    CanvasRenderer,
    LineChart,
    BarChart,
    PieChart,
    GaugeChart,
    ScatterChart,
    TitleComponent,
    TooltipComponent,
    LegendComponent,
    GridComponent,
    DatasetComponent,
    TransformComponent,
    ToolboxComponent
]);

export default {
    name: 'BaseChart',
    components: {
        VChart
    },
    props: {
        option: {
            type: Object,
            required: true
        },
        height: {
            type: String,
            default: '400px'
        },
        width: {
            type: String,
            default: '100%'
        }
    },
    computed: {
        chartOption() {
            return {
                backgroundColor: 'transparent',
                ...this.option
            };
        }
    },
    methods: {
        handleChartClick(params) {
            this.$emit('chart-click', params);
        }
    }
};
</script>
