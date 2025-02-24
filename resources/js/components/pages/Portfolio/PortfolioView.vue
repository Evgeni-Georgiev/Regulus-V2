<template>
    <div class="flex flex-col min-h-screen">
        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 flex-grow">
            <!-- Portfolio Overview -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h1 class="text-4xl font-bold text-gray-800">Portfolio: {{ portfolio.name }}</h1>
                <p class="text-2xl text-green-500 mt-4">Total Value: {{ formatPrice(portfolio.total_value) }}</p>
            </div>

            <!-- Portfolio History Chart -->
            <section class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h2 class="text-3xl font-semibold text-gray-800 mb-4">Portfolio Value Over Time</h2>
                <div class="h-96">
                    <Line
                        v-if="chartData"
                        :data="chartData"
                        :options="chartOptions"
                    />
                </div>
            </section>

            <!-- Coins Section -->
            <section class="mb-12">
                <h2 class="text-3xl font-semibold mb-6 text-gray-800">Coins</h2>
                <div v-for="coin in portfolio.coins" :key="coin.symbol" class="bg-white shadow-md rounded-lg p-6 mb-6">
                    <h3 class="text-2xl font-medium text-gray-700 mb-4">
                        {{ coin.name }} ({{ coin.symbol }}) - {{ formatPrice(coin.price) }}
                    </h3>
                    <div class="grid grid-cols-7 gap-4 bg-gray-100 p-4 rounded-t-lg font-medium text-gray-700">
                        <div>Name</div>
                        <div>Type</div>
                        <div>Current Price</div>
                        <div>Total Holding</div>
                        <div>Avg. Buy Price</div>
                        <div>Add Transaction</div>
                        <div>Actions</div>
                    </div>
                    <div class="grid grid-cols-7 gap-4 p-4 hover:bg-gray-50 border-t border-gray-200">
                        <div>{{ coin.name }}</div>
                        <div class="capitalize">{{ coin.symbol }}</div>
                        <div>{{ formatPrice(coin.price) }}</div>
                        <div>{{ formatPrice(coin.total_holding_quantity) }} - {{ coin.symbol }}, {{ formatPrice(coin.fiat_spent_on_quantity) }}</div>
                        <div>{{ formatPrice(coin.average_buy_price) }}</div>
                        <div><!-- TODO: Add transaction - open modal --></div>
                        <div><!-- TODO: Actions - View transactions, Move asset to another portfolio, remove asset from portfolio --></div>
                    </div>
                </div>
            </section>

            <!-- Transactions Section -->
            <section>
                <h2 class="text-3xl font-semibold mb-6 text-gray-800">Transactions</h2>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="grid grid-cols-6 gap-4 bg-gray-100 p-4 font-medium text-gray-700">
                        <div>Date</div>
                        <div>Coin</div>
                        <div>Type</div>
                        <div>Quantity</div>
                        <div>Price</div>
                        <div>Value</div>
                    </div>
                    <div v-for="coin in portfolio.coins" :key="coin.symbol">
                        <div v-for="transaction in coin.transactions"
                             :key="transaction.id"
                             class="grid grid-cols-6 gap-4 p-4 hover:bg-gray-50 border-t border-gray-200">
                            <div>{{ transaction.created_at }}</div>
                            <div>{{ coin.name }} ({{ coin.symbol }})</div>
                            <div class="capitalize">{{ transaction.transaction_type }}</div>
                            <div>{{ transaction.quantity }}</div>
                            <div>${{ transaction.buy_price }}</div>
                            <div>${{ (transaction.total_price).toFixed(4) }}</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<script>
export default {
    name: 'PortfolioView',
}
</script>

<script setup>
import { ref, onMounted, computed } from 'vue';
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
import { Line } from 'vue-chartjs';
import echo from '@/echo';

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

const props = defineProps(['id']);
const portfolio = ref({
    name: '',
    total_value: 0,
    coins: [],
    transactions: [],
});
const portfolioHistory = ref([]);

// Chart Options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return `$${context.parsed.y.toFixed(2)}`;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: false,
            ticks: {
                callback: function(value) {
                    return `$${value.toFixed(2)}`;
                }
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    },
    interaction: {
        intersect: false,
        mode: 'index'
    }
};

// Computed property for chart data
const chartData = computed(() => {
    if (!portfolioHistory.value.length) return null;

    const sortedHistory = [...portfolioHistory.value].sort((a, b) =>
        new Date(a.changed_at) - new Date(b.changed_at)
    );

    return {
        labels: sortedHistory.map(entry => {
            const date = new Date(entry.changed_at);
            return date.toLocaleDateString();
        }),
        datasets: [
            {
                label: 'Portfolio Value',
                data: sortedHistory.map(entry => entry.new_value),
                borderColor: '#10B981', // Emerald-500
                backgroundColor: 'rgba(16, 185, 129, 0.1)', // Emerald-500 with opacity
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#10B981',
            }
        ]
    };
});

// Fetch portfolio data
const fetchPortfolioData = async () => {
    try {
        const [portfolioResponse, historyResponse] = await Promise.all([
            axios.get(`/api/portfolios/${props.id}`),
            axios.get(`/api/portfolios/${props.id}/history`)
        ]);

        portfolio.value = portfolioResponse.data.portfolio;
        portfolioHistory.value = historyResponse.data.history;
    } catch (error) {
        console.error('Error fetching portfolio data:', error);
    }
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 4,
        maximumFractionDigits: 4
    }).format(value);
}

// Listen for price updates
const listenForPriceUpdates = () => {
    echo.channel('coins')
        .listen('CoinDataUpdated', () => {
            fetchPortfolioData();
        });
};

onMounted(() => {
    fetchPortfolioData();
    listenForPriceUpdates();
});
</script>

<style scoped>
.chart-container {
    position: relative;
    width: 100%;
    height: 100%;
}
main {
    background-color: #f9f9f9;
}
</style>
