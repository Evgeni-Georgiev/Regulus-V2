<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import axios from 'axios';
import echo from '@/echo';
import { TrendingUp, TrendingDown, Search, Filter, RefreshCw } from 'lucide-vue-next';

// Components
import Card from '@/components/ui/Card.vue';
import CardHeader from '@/components/ui/CardHeader.vue';
import CardTitle from '@/components/ui/CardTitle.vue';
import CardContent from '@/components/ui/CardContent.vue';
import Input from '@/components/ui/Input.vue';
import Button from '@/components/ui/Button.vue';

// State
const coinsData = ref([]);
const updatedFields = ref({});
const isLoading = ref(false);
const dataSource = ref('');
const searchQuery = ref('');
const sortBy = ref('market_cap');
const sortOrder = ref('desc');

// Computed
const filteredAndSortedCoins = computed(() => {
    let filtered = coinsData.value;
    
    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(coin => 
            coin.name.toLowerCase().includes(query) || 
            coin.symbol.toLowerCase().includes(query)
        );
    }
    
    // Sort coins
    filtered.sort((a, b) => {
        let aValue = a[sortBy.value];
        let bValue = b[sortBy.value];
        
        if (typeof aValue === 'string') {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }
        
        if (sortOrder.value === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    return filtered;
});

// Helper Functions
const updateCoinData = (updatedCoins) => {
    updatedCoins.forEach((updatedCoin) => {
        const index = coinsData.value.findIndex((coin) => coin.symbol === updatedCoin.symbol);
        
        if (index !== -1) {
            ['price', 'percent_change_1h', 'percent_change_24h', 'percent_change_7d'].forEach((field) => {
                const oldValue = coinsData.value[index][field];
                const newValue = updatedCoin[field];
                
                if (oldValue !== newValue) {
                    updatedFields.value[`${updatedCoin.symbol}-${field}`] = {
                        status: newValue > oldValue ? 'increased' : 'decreased',
                        timestamp: Date.now(),
                    };
                    
                    setTimeout(() => {
                        delete updatedFields.value[`${updatedCoin.symbol}-${field}`];
                    }, 4000);
                }
            });
            
            coinsData.value[index] = { ...coinsData.value[index], ...updatedCoin };
        }
    });
};

const getAnimationClass = (symbol, field) => {
    const update = updatedFields.value[`${symbol}-${field}`];
    if (!update || Date.now() - update.timestamp > 4000) return '';
    return update.status === 'increased' ? 'value-increased' : 'value-decreased';
};

const formatCurrency = (value) => {
    if (value >= 1e9) return `$${(value / 1e9).toFixed(2)}B`;
    if (value >= 1e6) return `$${(value / 1e6).toFixed(2)}M`;
    if (value >= 1e3) return `$${(value / 1e3).toFixed(2)}K`;
    return `$${value.toFixed(2)}`;
};

const formatPercentage = (value) => {
    return `${value > 0 ? '+' : ''}${value.toFixed(2)}%`;
};

const getPercentageColor = (value) => {
    return value > 0 
        ? 'text-green-600 dark:text-green-400' 
        : 'text-red-600 dark:text-red-400';
};

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'desc';
    }
};

// Fetch coins from API
const fetchCoins = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/coins');
        coinsData.value = response.data.coins;
        dataSource.value = response.data.dataSource;
    } catch (error) {
        console.error('Error fetching coin data:', error);
    } finally {
        isLoading.value = false;
    }
};

// Listen for broadcasted events
const listenForPriceUpdates = () => {
    console.log('Setting up listener for my-channel...');

    echo.channel('my-channel').listen('.my-event', (event) => {
        console.log('📡 Broadcast event received!', {
            eventKeys: Object.keys(event),
            coinCount: event.coinData ? Object.keys(event.coinData).length : 0,
            firstCoin: event.coinData ? Object.keys(event.coinData)[0] : 'none'
        });

        const updatedCoinsArray = Object.entries(event.coinData).map(([symbol, data]) => ({
            symbol,
            ...data,
        }));

        console.log('🔄 Updating coin data...', {
            coinsToUpdate: updatedCoinsArray.length,
            currentCoinsCount: coinsData.value.length
        });

        updateCoinData(updatedCoinsArray);
        dataSource.value = 'API';

        console.log('✅ Data updated!');
    });
};

// Lifecycle Hooks
onMounted(() => {
    fetchCoins();
    listenForPriceUpdates();
});

onBeforeUnmount(() => {
    echo.leaveChannel('my-channel');
});
</script>

<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Cryptocurrency Prices</h1>
                <p class="text-muted-foreground mt-1">
                    Real-time cryptocurrency market data and prices
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="fetchCoins"
                    :disabled="isLoading"
                >
                    <RefreshCw :class="{ 'animate-spin': isLoading }" class="h-4 w-4 mr-2" />
                    Refresh
                </Button>
                <div v-if="isLoading" class="text-sm text-muted-foreground">
                    Updating...
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <Card>
            <CardContent class="p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search cryptocurrencies..."
                            class="pl-10"
                        />
                    </div>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Filter class="h-4 w-4" />
                        <span>{{ filteredAndSortedCoins.length }} of {{ coinsData.length }} coins</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Coins Table -->
        <Card>
            <CardContent class="p-0">
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left p-4 font-medium text-muted-foreground">#</th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('name')"
                                >
                                    Name
                                    <span v-if="sortBy === 'name'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('price')"
                                >
                                    Price
                                    <span v-if="sortBy === 'price'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('percent_change_1h')"
                                >
                                    1h %
                                    <span v-if="sortBy === 'percent_change_1h'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('percent_change_24h')"
                                >
                                    24h %
                                    <span v-if="sortBy === 'percent_change_24h'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('percent_change_7d')"
                                >
                                    7d %
                                    <span v-if="sortBy === 'percent_change_7d'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('market_cap')"
                                >
                                    Market Cap
                                    <span v-if="sortBy === 'market_cap'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th 
                                    class="text-left p-4 font-medium text-muted-foreground cursor-pointer hover:text-foreground transition-colors"
                                    @click="handleSort('volume_24h')"
                                >
                                    24h Volume
                                    <span v-if="sortBy === 'volume_24h'" class="ml-1">
                                        {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr 
                                v-for="(coin, index) in filteredAndSortedCoins" 
                                :key="coin.symbol"
                                class="border-b border-border hover:bg-muted/50 transition-colors"
                            >
                                <td class="p-4 text-muted-foreground">{{ index + 1 }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <div class="font-medium text-foreground">{{ coin.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ coin.symbol }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div 
                                        class="font-medium text-foreground"
                                        :class="getAnimationClass(coin.symbol, 'price')"
                                    >
                                        {{ formatCurrency(coin.price) }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div 
                                        class="flex items-center gap-1 font-medium"
                                        :class="getPercentageColor(coin.percent_change_1h)"
                                    >
                                        <TrendingUp v-if="coin.percent_change_1h > 0" class="h-3 w-3" />
                                        <TrendingDown v-else class="h-3 w-3" />
                                        {{ formatPercentage(coin.percent_change_1h) }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div 
                                        class="flex items-center gap-1 font-medium"
                                        :class="getPercentageColor(coin.percent_change_24h)"
                                    >
                                        <TrendingUp v-if="coin.percent_change_24h > 0" class="h-3 w-3" />
                                        <TrendingDown v-else class="h-3 w-3" />
                                        {{ formatPercentage(coin.percent_change_24h) }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div 
                                        class="flex items-center gap-1 font-medium"
                                        :class="getPercentageColor(coin.percent_change_7d)"
                                    >
                                        <TrendingUp v-if="coin.percent_change_7d > 0" class="h-3 w-3" />
                                        <TrendingDown v-else class="h-3 w-3" />
                                        {{ formatPercentage(coin.percent_change_7d) }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div 
                                        class="text-foreground"
                                        :class="getAnimationClass(coin.symbol, 'market_cap')"
                                    >
                                        {{ formatCurrency(coin.market_cap) }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div 
                                        class="text-foreground"
                                        :class="getAnimationClass(coin.symbol, 'volume_24h')"
                                    >
                                        {{ formatCurrency(coin.volume_24h) }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="lg:hidden space-y-4 p-4">
                    <div 
                        v-for="(coin, index) in filteredAndSortedCoins" 
                        :key="coin.symbol"
                        class="bg-card border border-border rounded-lg p-4 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-muted-foreground">#{{ index + 1 }}</span>
                                <div>
                                    <div class="font-medium text-foreground">{{ coin.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ coin.symbol }}</div>
                                </div>
                            </div>
                            <div 
                                class="text-lg font-bold text-foreground"
                                :class="getAnimationClass(coin.symbol, 'price')"
                            >
                                {{ formatCurrency(coin.price) }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-muted-foreground">1h</div>
                                <div 
                                    class="font-medium flex items-center gap-1"
                                    :class="getPercentageColor(coin.percent_change_1h)"
                                >
                                    <TrendingUp v-if="coin.percent_change_1h > 0" class="h-3 w-3" />
                                    <TrendingDown v-else class="h-3 w-3" />
                                    {{ formatPercentage(coin.percent_change_1h) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-muted-foreground">24h</div>
                                <div 
                                    class="font-medium flex items-center gap-1"
                                    :class="getPercentageColor(coin.percent_change_24h)"
                                >
                                    <TrendingUp v-if="coin.percent_change_24h > 0" class="h-3 w-3" />
                                    <TrendingDown v-else class="h-3 w-3" />
                                    {{ formatPercentage(coin.percent_change_24h) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-muted-foreground">7d</div>
                                <div 
                                    class="font-medium flex items-center gap-1"
                                    :class="getPercentageColor(coin.percent_change_7d)"
                                >
                                    <TrendingUp v-if="coin.percent_change_7d > 0" class="h-3 w-3" />
                                    <TrendingDown v-else class="h-3 w-3" />
                                    {{ formatPercentage(coin.percent_change_7d) }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm pt-2 border-t border-border">
                            <div>
                                <div class="text-muted-foreground">Market Cap</div>
                                <div 
                                    class="font-medium text-foreground"
                                    :class="getAnimationClass(coin.symbol, 'market_cap')"
                                >
                                    {{ formatCurrency(coin.market_cap) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-muted-foreground">24h Volume</div>
                                <div 
                                    class="font-medium text-foreground"
                                    :class="getAnimationClass(coin.symbol, 'volume_24h')"
                                >
                                    {{ formatCurrency(coin.volume_24h) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
/* Animation Styles */
.value-increased {
    animation: highlight-green 1s ease-out;
}

.value-decreased {
    animation: highlight-red 1s ease-out;
}

@keyframes highlight-green {
    0%, 20% {
        background-color: rgba(34, 197, 94, 0.2);
        border-radius: 4px;
    }
    100% {
        background-color: transparent;
    }
}

@keyframes highlight-red {
    0%, 20% {
        background-color: rgba(239, 68, 68, 0.2);
        border-radius: 4px;
    }
    100% {
        background-color: transparent;
    }
}

/* Dark mode animation styles */
.dark .value-increased {
    animation: dark-highlight-green 1s ease-out;
}

.dark .value-decreased {
    animation: dark-highlight-red 1s ease-out;
}

@keyframes dark-highlight-green {
    0%, 20% {
        background-color: rgba(34, 197, 94, 0.3);
        border-radius: 4px;
    }
    100% {
        background-color: transparent;
    }
}

@keyframes dark-highlight-red {
    0%, 20% {
        background-color: rgba(239, 68, 68, 0.3);
        border-radius: 4px;
    }
    100% {
        background-color: transparent;
    }
}
</style>
