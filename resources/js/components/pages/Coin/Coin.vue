<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import echo from '@/echo';

// State
const coinsData = ref([]);
const updatedFields = ref({});
const isLoading = ref(false);

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
        console.log('Updated coinsData:', coinsData.value);
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
    <div class="flex flex-col min-h-screen">
        <!-- Header -->
        <div v-if="isLoading" class="text-sm text-gray-500">Updating...</div>

        <!-- Main Content -->
        <main class="flex-grow p-6 bg-gray-50">
            <div class="bg-white shadow-md rounded-lg">

                <!-- Desktop View -->
                <div class="hidden sm:block">
                    <div class="grid grid-cols-9 bg-gray-100 py-3 px-6 text-sm font-semibold">
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
                        class="grid grid-cols-9 border-b border-gray-200 py-3 px-6 hover:bg-gray-50 text-sm"
                    >
                        <div>{{ index + 1 }}</div>
                        <div class="font-medium">{{ coin.name }}</div>
                        <div>{{ coin.symbol }}</div>
                        <div :class="getAnimationClass(coin.symbol, 'price')">
                            ${{ coin.price.toFixed(2) }}
                        </div>
                        <div :class="coin.percent_change_1h > 0 ? 'text-green-500' : 'text-red-500'">
                            {{ coin.percent_change_1h.toFixed(2) }}%
                        </div>
                        <div :class="coin.percent_change_24h > 0 ? 'text-green-500' : 'text-red-500'">
                            {{ coin.percent_change_24h.toFixed(2) }}%
                        </div>
                        <div :class="coin.percent_change_7d > 0 ? 'text-green-500' : 'text-red-500'">
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
</style>
