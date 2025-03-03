<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import echo from '@/echo';

// State
const coinsData = ref([]);
const updatedFields = ref({});
const isLoading = ref(false);
const dataSource = ref('');

// Helper Functions
const updateCoinData = (updatedCoins) => {
    console.log('Starting updateCoinData function');
    console.log('Received updatedCoins:', updatedCoins);

    updatedCoins.forEach((updatedCoin) => {
        console.log('Processing updatedCoin:', updatedCoin);

        const index = coinsData.value.findIndex((coin) => coin.symbol === updatedCoin.symbol);
        console.log(`Index of ${updatedCoin.symbol}:`, index);

        if (index !== -1) {
            console.log(`Updating existing coin: ${updatedCoin.symbol}`);
            ['price', 'percent_change_1h', 'percent_change_24h', 'percent_change_7d'].forEach((field) => {
                const oldValue = coinsData.value[index][field];
                const newValue = updatedCoin[field];
                console.log(`Checking field "${field}" for ${updatedCoin.symbol}: oldValue=${oldValue}, newValue=${newValue}`);

                if (oldValue !== newValue) {
                    console.log(
                        `Field "${field}" for ${updatedCoin.symbol} changed from ${oldValue} to ${newValue}`
                    );
                    updatedFields.value[`${updatedCoin.symbol}-${field}`] = {
                        status: newValue > oldValue ? 'increased' : 'decreased',
                        timestamp: Date.now(),
                    };

                    console.log('Updated fields:', updatedFields.value);

                    setTimeout(() => {
                        console.log(`Removing highlight for ${updatedCoin.symbol}-${field}`);
                        delete updatedFields.value[`${updatedCoin.symbol}-${field}`];
                    }, 4000);
                }
            });

            // Update the coin data
            console.log(`Updating coin data for ${updatedCoin.symbol}`);
            coinsData.value[index] = { ...coinsData.value[index], ...updatedCoin };
        } else {
            console.warn(`Coin not found: ${updatedCoin.symbol}`);
        }
    });

    console.log('Finished updateCoinData function');
};

const getAnimationClass = (symbol, field) => {
    const update = updatedFields.value[`${symbol}-${field}`];
    if (!update || Date.now() - update.timestamp > 4000) return '';
    return update.status === 'increased' ? 'value-increased' : 'value-decreased';
};

// Fetch coins from API
const fetchCoins = async () => {
    console.log('Fetching coins data from API...');
    isLoading.value = true;

    try {
        const response = await axios.get('/api/coins');
        console.log('API response:', response.data);
        coinsData.value = response.data.coins;
        dataSource.value = response.data.dataSource;
        console.log('Updated coinsData:', coinsData.value);
        console.log('Source:', dataSource.value);
    } catch (error) {
        console.error('Error fetching coin data:', error);
    } finally {
        isLoading.value = false;
        console.log('Finished fetching coins data. isLoading:', isLoading.value);
    }
};

// Listen for broadcasted events
const listenForPriceUpdates = () => {
    console.log('Listening for real-time updates...');
    echo.channel('my-channel').listen('.my-event', (event) => {
        console.log('Real-time chunk received:', event.coinData);

        const updatedCoinsArray = Object.entries(event.coinData).map(([symbol, data]) => ({
            symbol,
            ...data,
        }));

        console.log('Parsed real-time updatedCoinsArray:', updatedCoinsArray);
        updateCoinData(updatedCoinsArray);
        dataSource.value = 'API';
    });
};

// Lifecycle Hooks
onMounted(() => {
    console.log('Component mounted.');
    console.log('Starting initial coin fetch and real-time updates...');
    fetchCoins();
    listenForPriceUpdates();
    console.log('Real-time updates initialized.');
});

onBeforeUnmount(() => {
    console.log('Component unmounted. Leaving channel...');
    echo.leaveChannel('my-channel');
    console.log('Successfully left channel.');
});
</script>

<template>
    <div class="flex flex-col min-h-screen bg-white dark:bg-gray-900">
        <!-- Data Source Indicator -->
        <!--        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">-->
        <!--            Data Source: {{ dataSource }}-->
        <!--        </div>-->
        <!-- Header -->
        <div v-if="isLoading" class="text-sm text-gray-500 dark:text-gray-400">Updating...</div>

        <!-- Main Content -->
        <main class="flex-grow p-6 bg-gray-50 dark:bg-gray-900">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg">

                <!-- Desktop View -->
                <div class="hidden sm:block">
                    <div class="grid grid-cols-9 bg-gray-100 dark:bg-gray-700 py-3 px-6 text-sm font-semibold text-gray-800 dark:text-gray-200">
                        <div>#</div>
                        <div>Name</div>
                        <div>Symbol</div>
                        <div>Price</div>
                        <div>1h %</div>
                        <div>24h %</div>
                        <div>7d %</div>
                        <div>Market Cap</div>
                        <div>24h Volume</div>
                    </div>
                    <div
                        v-for="(coin, index) in coinsData"
                        :key="coin.symbol"
                        class="grid grid-cols-9 border-b border-gray-200 dark:border-gray-700 py-3 px-6 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-800 dark:text-gray-200"
                    >
                        <div>{{ index + 1 }}</div>
                        <div class="font-medium">{{ coin.name }}</div>
                        <div>{{ coin.symbol }}</div>
                        <div :class="getAnimationClass(coin.symbol, 'price')">
                            ${{ coin.price.toFixed(2) }}
                        </div>
                        <div :class="[
                            coin.percent_change_1h > 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400'
                        ]">
                            {{ coin.percent_change_1h.toFixed(2) }}%
                        </div>
                        <div :class="[
                            coin.percent_change_24h > 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400'
                        ]">
                            {{ coin.percent_change_24h.toFixed(2) }}%
                        </div>
                        <div :class="[
                            coin.percent_change_7d > 0 ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400'
                        ]">
                            {{ coin.percent_change_7d.toFixed(2) }}%
                        </div>
                        <div :class="getAnimationClass(coin.symbol, 'market_cap')">
                            ${{ coin.market_cap.toLocaleString() }}
                        </div>
                        <div :class="getAnimationClass(coin.symbol, 'volume_24h')">
                            ${{ coin.volume_24h.toLocaleString() }}
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
/* Hide the scrollbar while keeping scroll functionality */
.hide-scrollbar {
    overflow-x: auto;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
}

.hide-scrollbar::-webkit-scrollbar {
    display: none; /* Chrome, Safari, and Edge */
}

/* Animation Styles */
.value-increased {
    animation: highlight-green 1s ease-out;
}

.value-decreased {
    animation: highlight-red 1s ease-out;
}

@keyframes highlight-green {
    0%,
    20% {
        background-color: rgba(16, 185, 129, 0.2);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes highlight-red {
    0%,
    20% {
        background-color: rgba(239, 68, 68, 0.2);
    }
    100% {
        background-color: transparent;
    }
}

/* Dark mode animation styles */
html.dark .value-increased {
    animation: dark-highlight-green 1s ease-out;
}

html.dark .value-decreased {
    animation: dark-highlight-red 1s ease-out;
}

@keyframes dark-highlight-green {
    0%,
    20% {
        background-color: rgba(16, 185, 129, 0.3);
    }
    100% {
        background-color: transparent;
    }
}

@keyframes dark-highlight-red {
    0%,
    20% {
        background-color: rgba(239, 68, 68, 0.3);
    }
    100% {
        background-color: transparent;
    }
}
</style>
