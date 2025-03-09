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
import { Line } from 'vue-chartjs';
import {
    filterSnapshotByPeriod,
    createChartData,
    createChartOptions
} from '@/utils/chartUtils';

// Component state
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

// Watch for snapshot data changes
watch(() => props.snapshotData, (newData) => {
    originalSnapshot.value = newData;
    chartKey.value++; // Force chart re-render
}, { immediate: true });

// Use the utility functions for filtering data
const filteredSnapshot = computed(() => {
    return filterSnapshotByPeriod(originalSnapshot.value, activePeriod.value);
});

// Use the utility function for chart data
const filteredChartData = computed(() => {
    if (!filteredSnapshot.value.length) return null;
    
    return createChartData(
        filteredSnapshot.value, 
        isDarkMode.value, 
        'total_portfolio_value',
        { label: 'Portfolio Value' }
    );
});

// Use the utility function for chart options
const chartOptions = computed(() => {
    return createChartOptions(
        filteredSnapshot.value, 
        activePeriod.value, 
        isDarkMode.value, 
        // Custom Y-axis formatter
        (value) => {
            if (value >= 1000) {
                return `$${(value / 1000).toFixed(1)}k`;
            }
            return `$${value.toFixed(0)}`;
        }
    );
});

// Lifecycle hooks
onMounted(() => {
    setupThemeWatcher(); // Set up theme detection
});
</script>

<style scoped>

</style>
