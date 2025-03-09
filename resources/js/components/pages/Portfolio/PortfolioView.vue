<template>
    <div class="flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Main Content -->
        <main class="container mx-auto my-4 px-4 py-8 flex-grow bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <!-- Portfolio Overview -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-white">Portfolio: {{ portfolio.name }}</h1>
                <div class="flex mt-4 items-baseline">
                    <p class="text-2xl text-gray-800 dark:text-white mr-4">Total Value: {{ formatPrice(portfolio.total_value) }}</p>
                    <p :class="getProfitLossClass(portfolio.total_profit_loss)">
                        {{ formatProfitLoss(portfolio.total_profit_loss) }} 
                        ({{ calculatePortfolioProfitLossPercentage(portfolio) }}%)
                    </p>
                </div>
            </div>

            <!-- Portfolio Chart Component -->
            <PortfolioChart
              :portfolioId="id"
              :snapshotData="originalSnapshot"
            />

            <!-- Portfolio Assets Component -->
            <PortfolioAssets
              :coins="portfolio.coins"
              :portfolioId="id"
              @add-transaction="openAddTransactionModal"
              @view-transactions="viewTransactions"
              @move-asset="moveAsset"
              @remove-asset="removeAsset"
            />
        </main>
    </div>
</template>

<script>
export default {
    name: 'PortfolioView',
}
</script>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
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
import echo from '@/echo';
import { useRouter } from 'vue-router';
import PortfolioChart from './PortfolioChart.vue';
import PortfolioAssets from './PortfolioAssets.vue';

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

// Dark mode detection
const isDarkMode = ref(false);

const updateThemeDetection = () => {
    isDarkMode.value = document.documentElement.classList.contains('dark');
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

// Component state
const props = defineProps(['id']);
const portfolio = ref({ name: '', total_value: 0, coins: [] });
const originalSnapshot = ref([]);
const chartKey = ref(0);
const loading = ref(false);
const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

const router = useRouter();

// API calls and data fetching
const fetchPortfolioData = async () => {
    loading.value = true;
    try {
        const [portfolioResponse, snapshotResponse] = await Promise.all([
            axios.get(`/api/portfolios/${props.id}`),
            axios.get(`/api/portfolios/${props.id}/snapshot`)
        ]);

        portfolio.value = portfolioResponse.data.portfolio;
        originalSnapshot.value = snapshotResponse.data.data;
        chartKey.value++; // Force chart re-render
    } catch (error) {
        console.error('Error fetching portfolio data:', error);
    } finally {
        loading.value = false;
    }
};

// Listen for real-time updates
const setupRealtimeUpdates = () => {
    echo.channel('coins')
        .listen('CoinDataUpdated', () => {
            fetchPortfolioData();
        });
};

// Lifecycle hooks
onMounted(() => {
    setupThemeWatcher(); // Set up theme detection
    fetchPortfolioData();
    setupRealtimeUpdates();
});

// Watch for prop changes to reload data
watch(() => props.id, () => {
    fetchPortfolioData();
}, { immediate: false });

// Event handlers for child components
const openAddTransactionModal = (coin) => {
    // TODO: Implement modal for adding transactions
    console.log('Add transaction for:', coin.name);
};

const viewTransactions = (coin) => {
    // Navigate to transactions page for this coin
    router.push({
        name: 'TransactionView',
        params: {
            portfolioId: portfolio.value.id,
            coinId: coin.id
        }
    });
};

const moveAsset = (coin) => {
    // TODO: Implement functionality to move asset to another portfolio
    console.log('Move asset:', coin.name);
};

const removeAsset = (coin) => {
    // TODO: Implement functionality to remove asset from portfolio
    console.log('Remove asset:', coin.name);
};

// Add these to your script setup section

// For dropdown menu functionality
const activeDropdown = ref(null);

// Close dropdown when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            activeDropdown.value = null;
        }
    });
});

// Format price with consistent decimal places
const formatPrice = (value) => {
    if (value === undefined || value === null) return '$0.00';
    return formatter.format(value);
};

// Get CSS class for profit/loss
const getProfitLossClass = (value) => {
    if (value === undefined || value === null) return 'text-gray-500 dark:text-gray-400';
    
    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    
    if (numValue > 0) return 'text-green-500 dark:text-green-400';
    if (numValue < 0) return 'text-red-500 dark:text-red-400';
    return 'text-gray-500 dark:text-gray-400';
};

// Format profit/loss with sign
const formatProfitLoss = (value) => {
    if (value === undefined || value === null) return '$0.00';
    
    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    const formatted = formatter.format(Math.abs(numValue));
    
    return numValue < 0 ? '-' + formatted : '+' + formatted;
};

// Calculate portfolio profit/loss percentage
const calculatePortfolioProfitLossPercentage = (portfolio) => {
    if (!portfolio || !portfolio.total_profit_loss || !portfolio.total_cost_basis || 
        portfolio.total_cost_basis === 0) {
        return '0.00';
    }
    
    // Use the formula: Total P/L% = (Total Current Value - Total Cost Basis) / Total Cost Basis × 100
    // Since total_profit_loss = Total Current Value - Total Cost Basis, we can simplify:
    const percentage = (parseFloat(portfolio.total_profit_loss) / parseFloat(portfolio.total_cost_basis)) * 100;
    return percentage.toFixed(2);
};
</script>
