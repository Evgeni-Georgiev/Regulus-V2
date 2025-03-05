<template>
    <section class="mb-12">
        <h2 class="text-3xl font-semibold mb-6 text-gray-800 dark:text-white">Assets</h2>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-visible">
            <!-- Header Row -->
            <div class="grid grid-cols-9 gap-4 bg-gray-100 dark:bg-gray-700 p-4 font-medium text-gray-700 dark:text-gray-200">
                <div>Name</div>
                <div>Price</div>
                <div>1h%</div>
                <div>24h%</div>
                <div>7d%</div>
                <div>Holdings</div>
                <div>Avg. Buy Price</div>
                <div>Profit/Loss</div>
                <div>Actions</div>
            </div>

            <!-- Asset Rows -->
            <div v-for="coin in coins" :key="coin.symbol" class="border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-9 gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-100">
                    <!-- Name with Logo -->
                    <div class="flex items-center">
                        <div class="w-8 h-8 mr-3 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center overflow-hidden text-xs">
                            <span class="font-bold">{{ coin.symbol.charAt(0) }}</span>
                        </div>
                        <div>
                            <div class="font-medium">{{ coin.name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ coin.symbol }}</div>
                        </div>
                    </div>

                    <!-- Price -->
                    <div>${{ formatNumber(coin.price) }}</div>

                    <!-- 1h Change -->
                    <div :class="getChangeClass(coin.percent_change_1h || 0.23)">
                        <span v-if="(coin.percent_change_1h || 0.23) >= 0">▲</span>
                        <span v-else>▼</span>
                        {{ Math.abs(coin.percent_change_1h || 0.23).toFixed(2) }}%
                    </div>

                    <!-- 24h Change -->
                    <div :class="getChangeClass(coin.percent_change_24h || -8.39)">
                        <span v-if="(coin.percent_change_24h || -8.39) >= 0">▲</span>
                        <span v-else>▼</span>
                        {{ Math.abs(coin.percent_change_24h || -8.39).toFixed(2) }}%
                    </div>

                    <!-- 7d Change -->
                    <div :class="getChangeClass(coin.percent_change_7d || 3.37)">
                        <span v-if="(coin.percent_change_7d || 3.37) >= 0">▲</span>
                        <span v-else>▼</span>
                        {{ Math.abs(coin.percent_change_7d || 3.37).toFixed(2) }}%
                    </div>

                    <!-- Holdings -->
                    <div class="flex flex-col">
                        <span class="font-medium">{{ formatCryptoAmount(coin.total_holding_quantity) }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatPrice(coin.total_holding_quantity * coin.price) }}
            </span>
                    </div>

                    <!-- Avg Buy Price -->
                    <div>{{ formatPrice(coin.average_buy_price) }}</div>

                    <!-- Profit/Loss -->
                    <div :class="getProfitLossClass(coin)">
                        {{ formatProfitLoss(coin.profit_loss) }}
                        <span class="text-xs block">
                            ({{ calculateProfitLossPercentage(coin) }}%)
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2 relative">
                        <button class="p-1.5 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                @click="openAddTransactionModal(coin)" title="Add Transaction">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                        <div class="relative">
                            <button
                                ref="actionButton"
                                class="p-1.5 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                @click.stop="toggleDropdown(coin, $event)"
                                title="More Options">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!coins || coins.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                No assets found in this portfolio. Add some assets to get started.
            </div>
        </div>
    </section>

    <!-- Global dropdown portal -->
    <teleport to="body">
        <div
            v-if="activeDropdown !== null"
            class="absolute z-50 bg-white dark:bg-gray-800 rounded-md shadow-xl border border-gray-200 dark:border-gray-700"
            :style="dropdownPositionStyle"
            @click.stop>
            <div class="py-1">
                <button
                    @click.stop="handleAction('viewTransactions')"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                    View Transactions
                </button>
                <button
                    @click.stop="handleAction('moveAsset')"
                    class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Move Asset
                </button>
                <button
                    @click.stop="handleAction('removeAsset')"
                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Remove Asset
                </button>
            </div>
        </div>
    </teleport>
</template>

<script>
export default {
    name: 'PortfolioAsset',
}
</script>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';

// Props
const props = defineProps({
    coins: {
        type: Array,
        default: () => []
    },
    portfolioId: {
        type: [Number, String],
        required: true
    }
});

// Emits
const emit = defineEmits([
    'add-transaction',
    'view-transactions',
    'move-asset',
    'remove-asset'
]);

// For dropdown menu functionality
const activeDropdown = ref(null);
const selectedCoin = ref(null);
const dropdownPositionStyle = ref({
    top: '0px',
    left: '0px',
    width: '180px'
});

const toggleDropdown = (coin, event) => {
    if (activeDropdown.value === coin.id) {
        activeDropdown.value = null;
        selectedCoin.value = null;
    } else {
        activeDropdown.value = coin.id;
        selectedCoin.value = coin;

        // Position dropdown near the button but ensure it's visible
        nextTick(() => {
            const button = event.currentTarget;
            const rect = button.getBoundingClientRect();

            // Get window dimensions
            const windowWidth = window.innerWidth;

            // Calculate position making sure it stays on screen
            let left = rect.left - 130; // Default position

            // Adjust if dropdown would go off-screen
            if (left + 180 > windowWidth) {
                left = windowWidth - 190; // 10px margin from the right edge
            }
            if (left < 10) {
                left = 10; // 10px margin from the left edge
            }

            dropdownPositionStyle.value = {
                top: `${rect.bottom + window.scrollY + 5}px`,
                left: `${left}px`,
                width: '180px'
            };
        });
    }
};

// Handle dropdown actions
const handleAction = (action) => {
    if (!selectedCoin.value) return;

    switch (action) {
        case 'viewTransactions':
            viewTransactions(selectedCoin.value);
            break;
        case 'moveAsset':
            moveAsset(selectedCoin.value);
            break;
        case 'removeAsset':
            removeAsset(selectedCoin.value);
            break;
    }

    // Close the dropdown after action
    activeDropdown.value = null;
    selectedCoin.value = null;
};

// Document click handler
const documentClickHandler = (e) => {
    // Close dropdown when clicking outside
    if (activeDropdown.value !== null) {
        activeDropdown.value = null;
        selectedCoin.value = null;
    }
};

// Close dropdown on page scroll to avoid positioning issues
const scrollHandler = () => {
    if (activeDropdown.value !== null) {
        activeDropdown.value = null;
        selectedCoin.value = null;
    }
};

// Lifecycle hooks
onMounted(() => {
    document.addEventListener('click', documentClickHandler);
    window.addEventListener('scroll', scrollHandler, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', documentClickHandler);
    window.removeEventListener('scroll', scrollHandler, true);
});

// Formatting helpers
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

// Format crypto amounts with appropriate precision
const formatCryptoAmount = (amount) => {
    if (amount === undefined || amount === null) return '0';

    // For very small numbers, use scientific notation
    if (amount < 0.00001) {
        return amount.toExponential(4);
    }

    // For small numbers, show more decimal places
    if (amount < 1) {
        return amount.toFixed(6);
    }

    // For medium numbers
    if (amount < 1000) {
        return amount.toFixed(4);
    }

    // For large numbers
    return new Intl.NumberFormat('en-US', {
        maximumFractionDigits: 2
    }).format(amount);
};

// Format regular numbers
const formatNumber = (value) => {
    if (value === undefined || value === null) return '0.00';
    return value.toFixed(5);
};

// Get CSS class for price changes
const getChangeClass = (change) => {
    return change >= 0
        ? 'text-green-500 dark:text-green-400'
        : 'text-red-500 dark:text-red-400';
};

// Get CSS class for profit/loss based on the backend value
const getProfitLossClass = (coin) => {
    if (coin.profit_loss === undefined || coin.profit_loss === null) {
        return 'text-gray-500 dark:text-gray-400';
    }
    
    // Ensure we're dealing with a number
    const profitLoss = typeof coin.profit_loss === 'string' 
        ? parseFloat(coin.profit_loss) 
        : coin.profit_loss;
    
    if (profitLoss > 0) return 'text-green-500 dark:text-green-400';
    if (profitLoss < 0) return 'text-red-500 dark:text-red-400';
    return 'text-gray-500 dark:text-gray-400';
};

// We no longer need the calculateProfitLoss method since we're using the backend value
// But we can keep it as a reference or for debugging
const calculateProfitLoss = (coin) => {
    // Just return the backend value
    return formatPrice(coin.profit_loss || 0);
};

// Action handlers
const openAddTransactionModal = (coin) => {
    emit('add-transaction', coin);
};

const viewTransactions = (coin) => {
    emit('view-transactions', coin);
};

const moveAsset = (coin) => {
    emit('move-asset', coin);
};

const removeAsset = (coin) => {
    emit('remove-asset', coin);
};

// New method to format profit/loss
const formatProfitLoss = (value) => {
    if (value === undefined || value === null) return '$0.00';
    
    // Make sure value is a number
    const numValue = typeof value === 'string' ? parseFloat(value) : value;
    
    // Format the absolute value with the currency formatter
    const formatted = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Math.abs(numValue));
    
    // Add the sign manually
    return numValue < 0 ? '-' + formatted : formatted;
};

// Calculate profit/loss percentage for a coin
const calculateProfitLossPercentage = (coin) => {
    if (!coin || !coin.profit_loss || !coin.total_buy_value || coin.total_buy_value === 0) {
        return '0.00';
    }
    
    // Calculate according to formula: P/L% = (Current Value - Remaining Cost Basis) / Remaining Cost Basis × 100
    const percentage = (parseFloat(coin.profit_loss) / parseFloat(coin.total_buy_value)) * 100;
    return percentage.toFixed(2);
};
</script>
