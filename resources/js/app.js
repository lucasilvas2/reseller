import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Importar componentes ECharts
import ECharts from 'vue-echarts';
import { use } from 'echarts/core';

// Importar componentes necessários do ECharts
import {
    CanvasRenderer
} from 'echarts/renderers';
import {
    BarChart,
    LineChart,
    PieChart,
    GaugeChart
} from 'echarts/charts';
import {
    GridComponent,
    TooltipComponent,
    TitleComponent,
    LegendComponent,
    DataZoomComponent,
    ToolboxComponent
} from 'echarts/components';

// Registrar componentes no ECharts
use([
    CanvasRenderer,
    BarChart,
    LineChart,
    PieChart,
    GaugeChart,
    GridComponent,
    TooltipComponent,
    TitleComponent,
    LegendComponent,
    DataZoomComponent,
    ToolboxComponent
]);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('v-chart', ECharts)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
