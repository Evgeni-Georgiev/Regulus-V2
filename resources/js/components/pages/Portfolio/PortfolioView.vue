<template>
    <div class="flex flex-col min-h-screen">
        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8 flex-grow">
            <!-- Portfolio Overview -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <h1 class="text-4xl font-bold text-gray-800">Portfolio: {{ portfolio.name }}</h1>
                <p class="text-2xl text-green-500 mt-4">Total Value: ${{ portfolio.total_value.toFixed(2) }}</p>
            </div>

            <!-- Coins Section -->
            <section class="mb-12">
                <h2 class="text-3xl font-semibold mb-6 text-gray-800">Coins</h2>
                <div v-for="coin in portfolio.coins" :key="coin.symbol" class="bg-white shadow-md rounded-lg p-6 mb-6">
                    <h3 class="text-2xl font-medium text-gray-700 mb-4">
                        {{ coin.name }} ({{ coin.symbol }}) - ${{ coin.price.toFixed(4) }}
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
                        <div>{{ coin.price.toFixed(4) }}</div>
                        <div>{{ coin.total_holding_quantity }} - {{ coin.symbol }}, ${{ coin.fiat_spent_on_quantity.toFixed(4) }}</div>
                        <div>{{ coin.average_buy_price.toFixed(3) }}</div>
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

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps(['id']);
const portfolio = ref({
    name: '',
    total_value: 0,
    coins: [],
    transactions: [],
});

onMounted(async () => {
    try {
        const response = await axios.get(`/api/portfolios/${props.id}`);
        portfolio.value = response.data.portfolio;
    } catch (error) {
        console.error('Error fetching portfolio:', error);
    }
});
</script>

<style scoped>
main {
    background-color: #f9f9f9;
}
</style>
