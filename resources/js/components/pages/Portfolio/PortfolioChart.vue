<template>
    <section class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col mb-6">
            <div class="flex items-center mb-3">
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white">History</h2>
            </div>
            <div class="self-end">
                <div class="inline-flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button
                        v-for="period in timePeriods"
                        :key="period"
                        @click="setActivePeriod(period)"
                        :class="[
                            'px-4 py-1.5 text-sm rounded-md transition-colors',
                            activePeriod === period
                                ? 'bg-green-500 text-white'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600'
                        ]"
                    >
                        {{ period }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Chart container -->
        <div class="chart-container bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 h-96">
            <Line
                v-if="filteredChartData"
                :data="filteredChartData"
                :options="chartOptions"
                :key="chartKey"
            />
            <div v-else class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                {{ originalSnapshot.length ? 'No data available for selected period' : 'Loading chart data...' }}
            </div>
        </div>
    </section>
</template>

<script>
export default {
    name: 'PortfolioChart',
}
</script>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';
import { Line } from 'vue-chartjs';

// Register ChartJS components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    portfolioId: {
        type: [Number, String],
        required: true
    },
    snapshotData: {
        type: Array,
        default: () => []
    }
});

// Component state
const originalSnapshot = ref([]);
const activePeriod = ref('24h'); // Default to 24h view
const chartKey = ref(0);
const timePeriods = ['24h', '7d', '30d', '90d', 'All'];
const isDarkMode = ref(false);

// Format helpers
const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

// Dark mode detection
const updateThemeDetection = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
};

const setActivePeriod = (period) => {
    activePeriod.value = period;
    chartKey.value++; // Force chart re-render
};

// Watch for theme changes
const setupThemeWatcher = () => {
    // Initial detection
    updateThemeDetection();

    // Use MutationObserver to detect class changes on html element
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                updateThemeDetection();
                chartKey.value++; // Force chart re-render when theme changes
            }
        });
    });

    observer.observe(document.documentElement, { attributes: true });

    return () => observer.disconnect();
};

// Date formatting for chart
const formatDateForXAxis = (date, period) => {
    const d = new Date(date);

    switch(period) {
        case '24h':
            // Include date + hour: "May 15, 6 AM", "May 15, 2 PM"
            const hour = d.getHours();
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            })}, ${hour12} ${ampm}`;

        case '7d':
            // Include day of week + date: "Mon, May 15"
            return `${d.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric'
            })}`;

        case '30d':
            // Include month + day: "May 15"
            return d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });

        case '90d':
            // Month + year if needed: "Jan 2023", "Mar 2023"
            const monthDay = d.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric'
            });
            return monthDay;

        case 'All':
            // Month + year: "Jan 2022", "Apr 2023"
            return d.toLocaleDateString('en-US', {
                month: 'short',
                year: 'numeric'
            });

        default:
            return d.toLocaleDateString();
    }
};

const generateDateTicks = (data, period) => {
    if (!data.length) return [];

    const sortedDates = [...data].sort((a, b) =>
        new Date(a.recorded_at) - new Date(b.recorded_at)
    );

    const startDate = new Date(sortedDates[0].recorded_at);
    const endDate = new Date(sortedDates[sortedDates.length - 1].recorded_at);
    const range = endDate - startDate;

    // Reduce tick count for periods with longer label text
    let tickCount;
    switch(period) {
        case '24h': tickCount = 6; break;    // 6 ticks (longer text needs more space)
        case '7d': tickCount = 5; break;     // 5 ticks (longer text needs more space)
        case '30d': tickCount = 6; break;    // 6 ticks
        case '90d': tickCount = 5; break;    // 5 ticks
        case 'All': tickCount = 6; break;    // 6 ticks
        default: tickCount = 5;
    }

    // Generate evenly spaced ticks
    const ticks = [];
    for (let i = 0; i < tickCount; i++) {
        const tickPosition = i / (tickCount - 1);
        const tickDate = new Date(startDate.getTime() + (range * tickPosition));
        const formattedTick = formatDateForXAxis(tickDate, period);

        ticks.push({
            value: tickPosition,
            label: formattedTick,
            date: tickDate
        });
    }

    return ticks;
};

const sampleDataPoints = (data, period) => {
    if (!data.length) return [];

    const sortedData = [...data].sort((a, b) =>
        new Date(a.recorded_at) - new Date(b.recorded_at)
    );

    // Fibonacci-based sampling rates (60, 180, 480, 1260, 3300 minutes)
    const intervals = {
        '24h': 60,     // 1 hour
        '7d': 60 * 3,  // 3 hours
        '30d': 60 * 8, // 8 hours
        '90d': 60 * 21, // 21 hours
        'All': 60 * 55  // ~2.3 days
    };

    const samplingInterval = intervals[period] || 15;

    // If we have few data points, return as is
    if (sortedData.length < 30) return sortedData;

    const result = [];
    let lastTimestamp = null;

    for (const point of sortedData) {
        const currentTime = new Date(point.recorded_at);

        if (!lastTimestamp ||
            (currentTime - lastTimestamp) >= (samplingInterval * 60 * 1000)) {
            result.push(point);
            lastTimestamp = currentTime;
        }
    }

    // Always include the most recent point
    const lastPoint = sortedData[sortedData.length - 1];
    if (result[result.length - 1] !== lastPoint) {
        result.push(lastPoint);
    }

    return result;
};

// Watch for snapshot data changes
watch(() => props.snapshotData, (newData) => {
    originalSnapshot.value = newData;
    chartKey.value++; // Force chart re-render
}, { immediate: true });

const filteredSnapshot = computed(() => {
    if (!originalSnapshot.value.length) return [];

    const now = new Date().getTime();

    // Filter out invalid dates
    const validSnapshots = originalSnapshot.value.filter(item =>
        !isNaN(new Date(item.recorded_at).getTime())
    );

    const timeRanges = {
        '24h': now - (24 * 60 * 60 * 1000),
        '7d': now - (7 * 24 * 60 * 60 * 1000),
        '30d': now - (30 * 24 * 60 * 60 * 1000),
        '90d': now - (90 * 24 * 60 * 60 * 1000)
    };

    // Apply time filter if not "All"
    let periodFiltered = validSnapshots;
    if (activePeriod.value !== 'All') {
        const cutoff = timeRanges[activePeriod.value];
        periodFiltered = validSnapshots.filter(item =>
            new Date(item.recorded_at).getTime() >= cutoff
        );
    }

    // Apply sampling based on period
    return sampleDataPoints(periodFiltered, activePeriod.value);
});

// Modified filteredChartData to support dark mode
const filteredChartData = computed(() => {
    if (!filteredSnapshot.value.length) return null;

    const sortedSnapshot = [...filteredSnapshot.value].sort((a, b) =>
        new Date(a.recorded_at) - new Date(b.recorded_at)
    );

    // For the chart display, use numeric indices for even spacing
    const labels = sortedSnapshot.map((_, index) => index);

    // Use different colors based on theme
    const borderColor = isDarkMode.value ? '#34d399' : '#10B981';

    return {
        labels,
        datasets: [{
            label: 'Portfolio Value',
            data: sortedSnapshot.map(entry => entry.total_portfolio_value),
            borderColor: borderColor,
            backgroundColor: (context) => {
                const ctx = context.chart.ctx;
                const gradient = ctx.createLinearGradient(0, 0, 0, 200);

                if (isDarkMode.value) {
                    gradient.addColorStop(0, 'rgba(52, 211, 153, 0.3)');
                    gradient.addColorStop(1, 'rgba(52, 211, 153, 0)');
                } else {
                    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
                    gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
                }

                return gradient;
            },
            fill: true,
            tension: 0.3,
            borderWidth: 2.5,
            pointRadius: 0,
        }]
    };
});

// Update chart options for both light and dark themes
const chartOptions = computed(() => {
    if (!filteredSnapshot.value.length) {
        return { responsive: true, maintainAspectRatio: false };
    }

    // Generate appropriate ticks for the time period
    const dateTicks = generateDateTicks(filteredSnapshot.value, activePeriod.value);

    // Show points only for shorter periods
    const showPoints = ['24h', '7d'].includes(activePeriod.value);

    // Dark mode specific styles
    const gridColor = isDarkMode.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
    const textColor = isDarkMode.value ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';

    const tooltipBackgroundColor = isDarkMode.value
        ? 'rgba(31, 41, 55, 0.95)'
        : 'rgba(255, 255, 255, 0.95)';
    const tooltipTitleColor = isDarkMode.value ? '#9CA3AF' : '#6B7280';
    const tooltipBodyColor = isDarkMode.value ? '#F3F4F6' : '#111827';
    const tooltipBorderColor = isDarkMode.value
        ? 'rgba(75, 85, 99, 0.5)'
        : 'rgba(209, 213, 219, 0.5)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: tooltipBackgroundColor,
                titleColor: tooltipTitleColor,
                bodyColor: tooltipBodyColor,
                borderColor: tooltipBorderColor,
                borderWidth: 1,
                callbacks: {
                    title: (tooltipItems) => {
                        const dataIndex = tooltipItems[0].dataIndex;
                        const point = filteredSnapshot.value[dataIndex];
                        if (!point) return '';

                        const date = new Date(point.recorded_at);
                        return date.toLocaleString('en-US', {
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    },
                    label: (context) => {
                        return formatter.format(context.parsed.y);
                    }
                },
                padding: 10,
                displayColors: false,
            }
        },
        scales: {
            x: {
                grid: {
                    display: true,
                    color: gridColor,
                    drawBorder: false,
                    drawTicks: false,
                    tickLength: 10
                },
                ticks: {
                    color: textColor,
                    padding: 10,
                    autoSkip: false,
                    maxRotation: activePeriod.value === '24h' ? 45 : 0,
                    minRotation: 0,
                    font: {
                        size: 11,
                        weight: 'bold'
                    },
                    callback: (value, index, values) => {
                        // Only show a subset of labels to prevent overlap
                        // Number of labels to show depends on period and chart width
                        let interval;
                        switch(activePeriod.value) {
                            case '24h': interval = Math.ceil(values.length / 4); break;
                            case '7d': interval = Math.ceil(values.length / 4); break;
                            case '30d': interval = Math.ceil(values.length / 5); break;
                            case '90d':
                            case 'All': interval = Math.ceil(values.length / 5); break;
                            default: interval = Math.ceil(values.length / 4);
                        }

                        // Only show labels at intervals to prevent crowding
                        if (index % interval !== 0 && index !== values.length - 1) {
                            return '';
                        }

                        // Find the correct tick label based on position
                        const position = value / (filteredSnapshot.value.length - 1);
                        const closestTick = dateTicks.reduce((prev, curr) =>
                            Math.abs(curr.value - position) < Math.abs(prev.value - position) ? curr : prev
                        );
                        return closestTick.label;
                    }
                }
            },
            y: {
                position: 'right',
                beginAtZero: false,
                grid: {
                    color: gridColor,
                    drawBorder: false
                },
                ticks: {
                    color: textColor,
                    font: {
                        size: 12,
                        weight: 'bold'
                    },
                    padding: 10,
                    callback: (value) => {
                        // Compact number formatting
                        if (value >= 1000) {
                            return `$${(value / 1000).toFixed(1)}k`;
                        }
                        return `$${value.toFixed(0)}`;
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        },
        elements: {
            line: {
                borderWidth: 2.5,
                tension: 0.2,
            },
            point: {
                radius: 0,
                hitRadius: 10,
                hoverRadius: 4,
            }
        },
        layout: {
            padding: {
                top: 10,
                right: 20,
                bottom: 30,
                left: 10
            }
        }
    };
});

// Lifecycle hooks
onMounted(() => {
    setupThemeWatcher(); // Set up theme detection
});
</script>

<style scoped>

</style>
