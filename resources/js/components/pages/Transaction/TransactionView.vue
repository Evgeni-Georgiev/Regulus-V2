<template>
    <div class="flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Main Content -->
        <main class="container mx-auto my-4 px-4 py-8 flex-grow bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 dark:border-gray-100"></div>
            </div>

            <template v-else>
                <!-- Back Button -->
                <div class="mb-4">
                    <button
                        @click="goBackToPortfolio"
                        class="flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Portfolio
                    </button>
                </div>

                <!-- Coin Overview -->
                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-white">
                        {{ coin?.name || 'Loading...' }} Transactions
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Portfolio: {{ portfolio?.name || 'Loading...' }}
                    </p>
                </div>

                <!-- Coin Details -->
                <div v-if="coin" class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Current Price</h3>
                            <p class="text-2xl font-semibold text-gray-800 dark:text-white">{{ formatPrice(coin.price || 0) }}</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Total Holding</h3>
                            <p class="text-2xl font-semibold text-gray-800 dark:text-white">
                                {{ parseFloat(coin.total_holding_quantity || 0).toFixed(8).replace(/\.?0+$/, '') }} {{ coin.symbol }}
                                <span v-if="isValidNumber(coin.price) && isValidNumber(coin.total_holding_quantity)" class="text-lg text-gray-600 dark:text-gray-400">
                                    ({{ formatPrice(parseFloat(coin.price) * parseFloat(coin.total_holding_quantity)) }})
                                </span>
                                <span v-else class="text-lg text-gray-600 dark:text-gray-400">
                                    ($0.00)
                                </span>
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Average Buy Price</h3>
                            <p class="text-2xl font-semibold text-gray-800 dark:text-white">{{ formatPrice(coin.average_buy_price || 0) }}</p>
                        </div>
                    </div>

                    <!-- Price Change Percentages -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">1h Change</h3>
                            <p class="text-2xl font-semibold" :class="getChangeClass(coin.percent_change_1h)">
                                <span v-if="(coin.percent_change_1h || 0) >= 0">▲</span>
                                <span v-else>▼</span>
                                {{ Math.abs(coin.percent_change_1h || 0).toFixed(2) }}%
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">24h Change</h3>
                            <p class="text-2xl font-semibold" :class="getChangeClass(coin.percent_change_24h)">
                                <span v-if="(coin.percent_change_24h || 0) >= 0">▲</span>
                                <span v-else>▼</span>
                                {{ Math.abs(coin.percent_change_24h || 0).toFixed(2) }}%
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">7d Change</h3>
                            <p class="text-2xl font-semibold" :class="getChangeClass(coin.percent_change_7d)">
                                <span v-if="(coin.percent_change_7d || 0) >= 0">▲</span>
                                <span v-else>▼</span>
                                {{ Math.abs(coin.percent_change_7d || 0).toFixed(2) }}%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Transaction Summary -->
                <div class="mt-8 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Transaction Summary</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">
                                <span class="tooltip-container">
                                    Total Bought
                                    <span class="tooltip">Total value of all buy transactions for this coin.</span>
                                </span>
                            </h3>
                            <p class="text-2xl font-semibold text-green-600 dark:text-green-400">
                                {{ formatPrice(totalBought) }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">
                                <span class="tooltip-container">
                                    Total Sold
                                    <span class="tooltip">Total value of all sell transactions for this coin.</span>
                                </span>
                            </h3>
                            <p class="text-2xl font-semibold text-red-600 dark:text-red-400">
                                {{ formatPrice(totalSold) }}
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">
                                <span class="tooltip-container">
                                    Profit/Loss
                                    <span class="tooltip">Current market value minus your invested amount.</span>
                                </span>
                            </h3>
                            <p class="text-2xl font-semibold" :class="getProfitLossClass(coin.profit_loss)">
                                {{ formatPrice(coin.profit_loss) }}
                                <span class="text-sm ml-1">({{ calculateProfitLossPercentage(coin) }}%)</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div class="mt-8">
                    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white">Transactions</h2>
                            <button
                                @click="openAddTransactionModal"
                                class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                            >
                                Add New Transaction
                            </button>
                        </div>

                        <!-- Transaction Type Tabs -->
                        <div class="flex border-b border-gray-200 dark:border-gray-700">
                            <button
                                @click="currentTransactionType = 'buy'"
                                :class="[
                                    'py-3 px-6 font-medium text-sm focus:outline-none',
                                    currentTransactionType === 'buy'
                                        ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                Buy Transactions
                            </button>
                            <button
                                @click="currentTransactionType = 'sell'"
                                :class="[
                                    'py-3 px-6 font-medium text-sm focus:outline-none',
                                    currentTransactionType === 'sell'
                                        ? 'border-b-2 border-red-500 text-red-600 dark:text-red-400'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                Sell Transactions
                            </button>
                        </div>

                        <div class="p-6">
                            <div v-if="(currentTransactionType === 'buy' && buyTransactions.length === 0) ||
                                      (currentTransactionType === 'sell' && sellTransactions.length === 0)"
                                 class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No {{ currentTransactionType }} transactions found.
                            </div>
                            <div v-else class="border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm w-full">
                                <!-- Column Headers (simplified) -->
                                <div class="grid grid-cols-12 gap-4 px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                    <div class="col-span-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                                        <span class="tooltip-container">
                                            Date
                                            <span class="tooltip">Transaction date and time as recorded in the system</span>
                                        </span>
                                    </div>
                                    <div class="col-span-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                                        <span class="tooltip-container">
                                            Quantity
                                            <span class="tooltip">Number of coins {{ currentTransactionType === 'buy' ? 'purchased' : 'sold' }}</span>
                                        </span>
                                    </div>
                                    <div class="col-span-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                                        <span class="tooltip-container">
                                            Price
                                            <span class="tooltip">Price per coin at time of transaction</span>
                                        </span>
                                    </div>
                                    <div class="col-span-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                                        <span class="tooltip-container">
                                            Total
                                            <span class="tooltip">Total value (quantity × price)</span>
                                        </span>
                                    </div>
                                    <div class="col-span-1 text-sm font-medium text-gray-500 dark:text-gray-400 text-right">
                                        <span class="tooltip-container">
                                            Actions
<!--                                            <span class="tooltip">Edit or delete transaction</span>-->
                                        </span>
                                    </div>
                                </div>

                                <!-- Transaction List with Dividers -->
                                <div>
                                    <div v-for="(transaction, index) in paginatedTransactions" :key="transaction.id"
                                        :class="[
                                            'grid grid-cols-12 gap-4 items-center px-4 py-3',
                                            'hover:bg-gray-50 dark:hover:bg-gray-700',
                                            index !== paginatedTransactions.length - 1 ? 'border-b border-gray-200 dark:border-gray-700' : ''
                                        ]">

                                        <!-- Date Column -->
                                        <div class="col-span-3 text-sm font-medium text-gray-900 dark:text-white">
                                            <span class="tooltip-container">
                                                {{ formatDate(transaction.created_at) || 'Invalid Date' }}
                                                <span class="tooltip">Transaction date and time as recorded in the system</span>
                                            </span>
                                        </div>

                                        <!-- Quantity Column -->
                                        <div class="col-span-2 text-sm text-gray-700 dark:text-gray-300">
                                            <span class="tooltip-container">
                                                {{ transaction.quantity }} {{ coin?.symbol || 'XXX' }}
                                                <span class="tooltip">Number of coins {{ currentTransactionType === 'buy' ? 'purchased' : 'sold' }} in this transaction.</span>
                                            </span>
                                        </div>

                                        <!-- Price Column -->
                                        <div class="col-span-3 text-sm text-gray-700 dark:text-gray-300">
                                            <span class="tooltip-container">
                                                {{ formatPrice(transaction.buy_price) }}
                                                <span class="tooltip">Price per coin at the time of {{ currentTransactionType === 'buy' ? 'purchase' : 'sale' }}.</span>
                                            </span>
                                        </div>

                                        <!-- Total Column -->
                                        <div class="col-span-3">
                                            <span :class="transaction.transaction_type === 'buy' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                                {{ transaction.transaction_type === 'buy' ? '+' : '-' }}
                                                {{ formatPrice(transaction.buy_price * transaction.quantity) }}
                                            </span>
                                        </div>

                                        <!-- Actions Column -->
                                        <div class="col-span-1 text-right relative">
                                            <button @click="toggleActionMenu(transaction.id)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </button>
                                            <!-- Action Menu -->
                                            <div v-if="activeActionMenu === transaction.id"
                                                class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10 py-1 text-left">
                                                <button @click="editTransaction(transaction)"
                                                    class="block w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    Edit
                                                </button>
                                                <button @click="deleteTransaction(transaction)"
                                                    class="block w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pagination -->
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Showing {{ paginationInfo.start }}-{{ paginationInfo.end }} of {{ paginationInfo.total }}
                                    </div>
                                    <div class="flex space-x-2">
                                        <button
                                            :disabled="currentPage === 1"
                                            @click="currentPage--"
                                            :class="[
                                                'px-3 py-1 rounded-md',
                                                currentPage === 1
                                                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400'
                                                    : 'bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700'
                                            ]"
                                        >
                                            Previous
                                        </button>
                                        <button
                                            :disabled="currentPage * itemsPerPage >= paginationInfo.total"
                                            @click="currentPage++"
                                            :class="[
                                                'px-3 py-1 rounded-md',
                                                currentPage * itemsPerPage >= paginationInfo.total
                                                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400'
                                                    : 'bg-blue-500 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700'
                                            ]"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-lg font-medium text-gray-800 dark:text-white">
                                    <template v-if="currentTransactionType === 'buy'">
                                        Total Bought: <span class="font-bold text-green-600 dark:text-green-400">{{ formatPrice(totalBought) }}</span>
                                    </template>
                                    <template v-else>
                                        Total Sold: <span class="font-bold text-red-600 dark:text-red-400">{{ formatPrice(totalSold) }}</span>
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>

<script>
export default {
    name: 'TransactionView',
}
</script>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const portfolioId = route.params.portfolioId;
const coinId = route.params.coinId;

// Component state
const loading = ref(true);
const portfolio = ref(null);
const coin = ref(null);
const transactions = ref([]);
const transactionMeta = ref({
    total_bought: 0,
    total_sold: 0,
    net_position: 0
});

// State variables for transaction display
const currentTransactionType = ref('buy');
const currentPage = ref(1);
const itemsPerPage = ref(5);
const activeActionMenu = ref(null);

const fetchData = async () => {
    loading.value = true;
    try {
        // Make sure to explicitly request JSON responses
        const config = {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };

        // Fetch the portfolio data first to get accurate coin data
        const portfolioResponse = await axios.get(`/api/portfolios/${portfolioId}`, config);
        portfolio.value = portfolioResponse.data.portfolio;
        console.log('Portfolio data:', portfolio.value);

        // Find the correct coin in the portfolio's coins array
        if (portfolio.value && portfolio.value.coins && Array.isArray(portfolio.value.coins)) {
            const foundCoin = portfolio.value.coins.find(c => c.id == coinId);
            if (foundCoin) {
                console.log('Found coin in portfolio data:', foundCoin);
                console.log('Price changes:', {
                    '1h': foundCoin.percent_change_1h,
                    '24h': foundCoin.percent_change_24h,
                    '7d': foundCoin.percent_change_7d
                });
                coin.value = foundCoin;
            }
        }

        // If we didn't find the coin in portfolio data, try direct API calls as fallback
        if (!coin.value) {
            console.log('Coin not found in portfolio data, trying direct API calls');
            const coinEndpoints = [
                `/api/portfolios/${portfolioId}/coins/${coinId}`,
                `/api/coins/${coinId}`,
                `/api/portfolios/${portfolioId}/coin/${coinId}`,
                `/api/coin/${coinId}`
            ];

            for (const endpoint of coinEndpoints) {
                try {
                    console.log(`Attempting to fetch coin data from: ${endpoint}`);
                    const response = await axios.get(endpoint, config);
                    console.log(`Response from ${endpoint}:`, response.data);

                    // Try to extract coin data from various response formats
                    if (response.data.coin) {
                        coin.value = response.data.coin;
                        break;
                    } else if (response.data.data) {
                        coin.value = response.data.data;
                        break;
                    } else if (response.data.symbol || response.data.name) {
                        coin.value = response.data;
                        break;
                    }
                } catch (error) {
                    console.error(`Error fetching from ${endpoint}:`, error.message);
                }
            }
        }

        // If still no coin data, create a placeholder
        if (!coin.value) {
            console.error('Could not fetch coin data from any source. Creating placeholder.');
            coin.value = {
                id: coinId,
                name: 'Unknown Coin',
                symbol: 'XXX',
                price: 0,
                total_holding_quantity: 0,
                average_buy_price: 0,
                price_change_24h: 0,
                image: ''
            };
        }

        // Fetch transactions with multiple fallbacks
        let transactionData = null;
        let metaData = null;
        const transactionEndpoints = [
            `/api/portfolios/${portfolioId}/coins/${coinId}/transactions`,
            `/api/transactions?portfolio_id=${portfolioId}&coin_id=${coinId}`,
            `/api/portfolios/${portfolioId}/transactions?coin_id=${coinId}`
        ];

        let foundValidResponse = false;
        for (const endpoint of transactionEndpoints) {
            try {
                console.log(`Attempting to fetch transactions from: ${endpoint}`);
                const response = await axios.get(endpoint, config);
                console.log(`Response from ${endpoint}:`, response.data);

                // Try to extract transaction data and meta data
                if (response.data.data && Array.isArray(response.data.data)) {
                    transactionData = response.data.data;
                    // If meta data exists, store it
                    if (response.data.meta) {
                        metaData = response.data.meta;
                    }
                    foundValidResponse = true;
                    break;
                } else if (Array.isArray(response.data)) {
                    transactionData = response.data;
                    foundValidResponse = true;
                    break;
                }
            } catch (error) {
                console.error(`Error fetching from ${endpoint}:`, error.message);
                // Continue to the next endpoint
            }
        }

        if (!foundValidResponse) {
            console.warn('Could not fetch transactions from any endpoint');
        }

        transactions.value = transactionData || [];
        if (metaData) {
            transactionMeta.value = metaData;
        }
        console.log('Final transactions data:', transactions.value);
        console.log('Transaction meta data:', transactionMeta.value);

    } catch (error) {
        console.error('General error in fetchData:', error);
        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', error.response.data);
        }
    } finally {
        loading.value = false;
    }
};

// Format helpers
const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

const formatPrice = (value) => {
    if (value === undefined || value === null) return '$0.00';
    return formatter.format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return 'Invalid Date';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
};

// Use controller-provided values when available, otherwise compute locally
const totalBought = computed(() => {
    return transactionMeta.value.total_bought || transactions.value
        .filter(t => t.transaction_type === 'buy')
        .reduce((sum, t) => sum + (t.buy_price * t.quantity), 0);
});

const totalSold = computed(() => {
    return transactionMeta.value.total_sold || transactions.value
        .filter(t => t.transaction_type === 'sell')
        .reduce((sum, t) => sum + (t.buy_price * t.quantity), 0);
});

// Net investment is buy minus sell (actual capital invested)
const netInvestment = computed(() => {
    return totalBought.value - totalSold.value;
});

const currentHoldingValue = computed(() => {
    return coin.value ? coin.value.price * coin.value.total_holding_quantity : 0;
});

const unrealizedProfit = computed(() => {
    return currentHoldingValue.value - (netInvestment.value);
});

const totalProfit = computed(() => {
    return unrealizedProfit.value + (totalSold.value - boughtValueOfSold.value);
});

const boughtValueOfSold = computed(() => {
    // Approximation based on average buy price
    const soldQuantity = transactions.value
        .filter(t => t.transaction_type === 'sell')
        .reduce((sum, t) => sum + parseFloat(t.quantity), 0);

    return soldQuantity * (coin.value?.average_buy_price || 0);
});

// For display in the transactions table
const calculateTransactionValue = (transaction) => {
    const value = transaction.buy_price * transaction.quantity;
    return transaction.transaction_type === 'buy' ? value : -value;
};

// Computed properties for filtered transactions
const buyTransactions = computed(() => {
    return transactions.value.filter(t => t.transaction_type === 'buy');
});

const sellTransactions = computed(() => {
    return transactions.value.filter(t => t.transaction_type === 'sell');
});

// Computed properties for pagination
const paginatedTransactions = computed(() => {
    const filteredTransactions = currentTransactionType.value === 'buy'
        ? buyTransactions.value
        : sellTransactions.value;

    const startIndex = (currentPage.value - 1) * itemsPerPage.value;
    const endIndex = startIndex + itemsPerPage.value;
    return filteredTransactions.slice(startIndex, endIndex);
});

const paginationInfo = computed(() => {
    const total = currentTransactionType.value === 'buy'
        ? buyTransactions.value.length
        : sellTransactions.value.length;

    const start = total === 0 ? 0 : Math.min(total, (currentPage.value - 1) * itemsPerPage.value + 1);
    const end = Math.min(currentPage.value * itemsPerPage.value, total);

    return { start, end, total };
});

// Action handlers
const openAddTransactionModal = () => {
    // TODO: Implement modal for adding transactions.
    console.log('Open add transaction modal');
};

const editTransaction = (transaction) => {
    // TODO: Implement edit transaction functionality.
    console.log('Edit transaction:', transaction.id);
    activeActionMenu.value = null; // Close the menu after clicking
};

const deleteTransaction = async (transaction) => {
    if (!confirm(`Are you sure you want to delete this ${transaction.transaction_type} transaction?`)) {
        return;
    }

    try {
        await axios.delete(`/api/transactions/${transaction.id}`);
        // Remove from local state
        transactions.value = transactions.value.filter(t => t.id !== transaction.id);
        // Refresh coin data to update totals
        const coinResponse = await axios.get(`/api/portfolios/${portfolioId}/coins/${coinId}`);
        coin.value = coinResponse.data.coin;
        activeActionMenu.value = null; // Close the menu after deleting
    } catch (error) {
        console.error('Error deleting transaction:', error);
        // TODO: Handle error - maybe show a notification
    }
};

// Navigation
const goBackToPortfolio = () => {
    const portfolioId = route.params.portfolioId;

    router.push({
        name: 'PortfolioView',
        params: { id: portfolioId }
    });
};

// Toggle action menu visibility
const toggleActionMenu = (transactionId) => {
    if (activeActionMenu.value === transactionId) {
        activeActionMenu.value = null;
    } else {
        activeActionMenu.value = transactionId;
    }
};

// Reset page when switching transaction types
watch(currentTransactionType, () => {
    currentPage.value = 1;
});

// Lifecycle hooks
onMounted(() => {
    fetchData();

    document.addEventListener('click', (event) => {
        // If we're clicking outside the action menu and one is open
        if (activeActionMenu.value && !event.target.closest('.relative')) {
            activeActionMenu.value = null;
        }
    });
});

// Helper function to check if a value is a valid number
const isValidNumber = (val) => {
    if (val === null || val === undefined) return false;
    const num = parseFloat(val);
    return !isNaN(num) && isFinite(num);
};

// Add this helper function for price changes
const getChangeClass = (change) => {
    return change >= 0
        ? 'text-green-500 dark:text-green-400'
        : 'text-red-500 dark:text-red-400';
};

// Get CSS class for profit/loss based on value
const getProfitLossClass = (value) => {
    if (!isValidNumber(value)) return 'text-gray-500 dark:text-gray-400';
    return parseFloat(value) >= 0 
        ? 'text-green-600 dark:text-green-400' 
        : 'text-red-600 dark:text-red-400';
};

// Calculate profit/loss percentage
const calculateProfitLossPercentage = (coin) => {
    if (!coin || !isValidNumber(coin.profit_loss) || !isValidNumber(coin.total_buy_value)) {
        return '0.00';
    }
    
    // Don't divide by zero
    if (parseFloat(coin.total_buy_value) === 0) return '0.00';
    
    const percentage = (parseFloat(coin.profit_loss) / parseFloat(coin.total_buy_value)) * 100;
    return percentage.toFixed(2);
};
</script>

<style scoped>
/* Improved tooltip/popover styles */
.tooltip-container {
    position: relative;
    display: inline-block;
}

.tooltip-container:hover .tooltip {
    visibility: visible;
    opacity: 1;
}

.tooltip {
    visibility: hidden;
    width: 180px;
    background-color: rgba(55, 65, 81, 0.95);
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 8px 12px;
    position: absolute;
    z-index: 10;
    left: 0;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 0.75rem;
    line-height: 1.25rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    top: 100%;
    margin-top: 5px;
    pointer-events: none;
    white-space: normal;
    word-wrap: break-word;
}

.dark .tooltip {
    background-color: rgba(31, 41, 55, 0.95);
}
</style>
