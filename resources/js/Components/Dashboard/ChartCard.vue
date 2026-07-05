<script setup>
/**
 * Generic Chart.js wrapper card.
 *
 * Requires `chart.js` to be installed:
 *   npm install chart.js
 *
 * Usage:
 *   <ChartCard
 *     title="Faculty Load Distribution"
 *     type="bar"
 *     :labels="charts.faculty_load.labels"
 *     :datasets="[{ label: 'Units', data: charts.faculty_load.data }]"
 *     college="CCS"
 *   />
 */
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import {
    Chart,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js'
import { collegeClasses } from '@/Utils/collegeColors'

Chart.register(
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend
)

const props = defineProps({
    title: { type: String, required: true },
    type: { type: String, default: 'bar' }, // 'bar' | 'line' | 'doughnut'
    labels: { type: Array, default: () => [] },
    datasets: { type: Array, default: () => [] },
    height: { type: Number, default: 200 },
    emptyMessage: { type: String, default: 'No data yet for the working term.' },
    college: { type: String, default: null }, // e.g. 'CCS' — tints the chart's primary series and card hover
})

const canvasEl = ref(null)
let chartInstance = null

const palette = computed(() => (props.college ? collegeClasses(props.college) : null))

// Actual hex values behind each college color, used for Chart.js
// (which needs real color values, not Tailwind classes).
const COLLEGE_HEX = {
    CCS: '#f59e0b',    // amber
    CRIM: '#a855f7',   // purple
    CTE: '#3b82f6',    // blue
    SHTM: '#f97316',   // orange
    CBA: '#10b981',    // emerald
    General: '#64748b',
    Shared: '#64748b',
}

const defaultPalette = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#a855f7', '#14b8a6']

function seriesPalette() {
    if (props.college && COLLEGE_HEX[props.college]) {
        // Lead with the department's own color, then fall back to the
        // shared palette for any additional series/doughnut segments.
        return [COLLEGE_HEX[props.college], ...defaultPalette]
    }
    return defaultPalette
}

function readCssVar(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name)
    return value?.trim() || fallback
}

function buildDatasets() {
    const colors = seriesPalette()
    return props.datasets.map((ds, i) => ({
        borderRadius: props.type === 'bar' ? 6 : 0,
        backgroundColor: props.type === 'doughnut'
            ? props.labels.map((_, idx) => colors[idx % colors.length])
            : colors[i % colors.length] + (props.type === 'line' ? '33' : 'cc'),
        borderColor: colors[i % colors.length],
        borderWidth: props.type === 'line' ? 2 : 1,
        tension: 0.35,
        fill: props.type === 'line',
        ...ds,
    }))
}

function renderChart() {
    if (!canvasEl.value) return

    if (chartInstance) {
        chartInstance.destroy()
        chartInstance = null
    }

    const textColor = readCssVar('--text-secondary', '#94a3b8')
    const gridColor = readCssVar('--card-border', 'rgba(148,163,184,0.2)')

    chartInstance = new Chart(canvasEl.value, {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: buildDatasets(),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 600,
                easing: 'easeOutQuart',
            },
            plugins: {
                legend: {
                    display: props.type === 'doughnut',
                    labels: { color: textColor },
                },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: props.type === 'doughnut' ? {} : {
                x: {
                    ticks: { color: textColor },
                    grid: { color: gridColor },
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor },
                    grid: { color: gridColor },
                },
            },
        },
    })
}

onMounted(async () => {
    await nextTick()
    renderChart()
})

onBeforeUnmount(() => {
    chartInstance?.destroy()
})

watch(() => [props.labels, props.datasets, props.type, props.college], () => {
    renderChart()
}, { deep: true })
</script>

<template>
    <div
        class="group relative bg-[var(--card-bg)] rounded-xl shadow p-4 border border-[var(--card-border)] overflow-hidden
               transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg"
        :class="palette ? palette.hoverBorder : 'hover:border-indigo-300 dark:hover:border-indigo-500'"
    >
        <div
            v-if="palette"
            class="absolute top-0 left-0 right-0 h-1 opacity-70 group-hover:opacity-100 transition-opacity duration-300"
            :class="palette.dot"
        ></div>

        <div class="flex items-center gap-2 mb-3">
            <span v-if="palette" class="w-1.5 h-1.5 rounded-full" :class="palette.dot"></span>
            <h2 class="text-base font-semibold text-[var(--text-primary)]">{{ title }}</h2>
        </div>

        <div v-if="!labels.length" class="text-sm text-[var(--text-secondary)] py-10 text-center">
            {{ emptyMessage }}
        </div>

        <div v-else :style="{ height: height + 'px' }">
            <canvas ref="canvasEl"></canvas>
        </div>
    </div>
</template>