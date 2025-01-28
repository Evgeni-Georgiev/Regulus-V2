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
                    <table class="w-full border-collapse">
                        <thead>
                        <tr class="bg-gray-100 text-left">
                            <th class="py-2 px-4 border-b">Name</th>
                            <th class="py-2 px-4 border-b">Type</th>
                            <th class="py-2 px-4 border-b">Current Price</th>
                            <th class="py-2 px-4 border-b">Total Holding</th>
                            <th class="py-2 px-4 border-b">Avg. Buy Price</th>
                            <th class="py-2 px-4 border-b">Add Transaction</th>
                            <th class="py-2 px-4 border-b">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b">{{ coin.name }}</td>
                            <td class="py-2 px-4 border-b capitalize">{{ coin.symbol }}</td>
                            <td class="py-2 px-4 border-b capitalize">{{ coin.price.toFixed(4) }}</td>
                            <td class="py-2 px-4 border-b capitalize">{{ coin.total_holding_quantity }} - {{ coin.symbol }}, ${{ coin.fiat_spent_on_quantity.toFixed(4) }}</td> <!--TODO: Describe better - currently this quantity based on the current price is -->
                            <td class="py-2 px-4 border-b">{{ coin.average_buy_price.toFixed(3) }}</td>
                            <td class="py-2 px-4 border-b"><!-- TODO: Add transaction - open modal --></td>
                            <td class="py-2 px-4 border-b"><!-- TODO: Actions - View transactions, Move asset to another portfolio, remove asset from portfolio --></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Transactions Section -->
            <section>
                <h2 class="text-3xl font-semibold mb-6 text-gray-800">Transactions</h2>
                <table class="w-full border-collapse bg-white shadow-md rounded-lg">
                    <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="py-2 px-4 border-b">Date</th>
                        <th class="py-2 px-4 border-b">Coin</th>
                        <th class="py-2 px-4 border-b">Type</th>
                        <th class="py-2 px-4 border-b">Quantity</th>
                        <th class="py-2 px-4 border-b">Price</th>
                        <th class="py-2 px-4 border-b">Value</th> <!-- TODO: Total price that was spent in Fiat for the given transactions - Complete -->
                    </tr>
                    </thead>
                    <tbody v-for="coin in portfolio.coins" :key="coin.symbol" class="hover:bg-gray-50">
                    <tr v-for="transaction in coin.transactions" :key="transaction.id" class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-b">{{ transaction.created_at }}</td>
                        <td class="py-2 px-4 border-b">
                            {{ coin.name }} ({{ coin.symbol }})
                        </td>
                        <td class="py-2 px-4 border-b capitalize">{{ transaction.transaction_type }}</td>
                        <td class="py-2 px-4 border-b">{{ transaction.quantity }}</td>
                        <td class="py-2 px-4 border-b">${{ transaction.buy_price }}</td>
                        <td class="py-2 px-4 border-b">
                            ${{ (transaction.total_price).toFixed(4) }}
                        </td>
                    </tr>
                    </tbody>
                </table>
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
