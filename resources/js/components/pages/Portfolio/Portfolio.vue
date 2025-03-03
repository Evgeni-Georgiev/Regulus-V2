<template>
    <div class="flex flex-col min-h-screen bg-white dark:bg-gray-900">
        <!-- Main Content -->
        <main class="flex-grow p-6 bg-gray-50 dark:bg-gray-900" id="app">
            <div class="max-w-6xl mx-auto">
                <h1 class="text-3xl font-bold mb-6 text-gray-900 dark:text-white">My Portfolios</h1>

                <!-- Loading State -->
                <div v-if="isLoading" class="text-center text-gray-500 dark:text-gray-400">
                    <p>Loading portfolios...</p>
                </div>

                <!-- Portfolios List -->
                <div v-else>
                    <div v-if="portfolios.length === 0" class="text-center text-gray-500 dark:text-gray-400">
                        <p>No portfolios available. Create one to get started.</p>
                        <button
                            @click="createPortfolio"
                            class="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                        >
                            Create Portfolio
                        </button>
                    </div>
                    <div v-else>
                        <!-- Portfolios Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="portfolio in portfolios"
                                :key="portfolio.id"
                                class="p-4 bg-white dark:bg-gray-800 shadow-md rounded-lg hover:shadow-lg border border-gray-200 dark:border-gray-700"
                            >
                                <h2 class="text-xl font-semibold mb-2 text-gray-900 dark:text-white">{{ portfolio.name }}</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                    Total Value: ${{ portfolio.total_value }}
                                </p>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Created: {{ portfolio.created_at }}</div>
                                <div class="flex space-x-2 mt-4">
                                    <button
                                        @click="viewPortfolio(portfolio.id)"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                    >
                                        View
                                    </button>
                                    <button
                                        @click="deletePortfolio(portfolio.id)"
                                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination Controls -->
                        <div class="mt-6 flex justify-center items-center space-x-4">
                            <button
                                @click="fetchPortfolios(pagination.prev_page_url)"
                                :disabled="!pagination.prev_page_url"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                Previous
                            </button>
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Page {{ pagination.current_page }} of {{ pagination.last_page }}
                            </span>
                            <button
                                @click="fetchPortfolios(pagination.next_page_url)"
                                :disabled="!pagination.next_page_url"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

import Header from '@comps/Header.vue';
import Footer from '@comps/Footer.vue';

// State
const portfolios = ref([]);
const pagination = ref({});
const isLoading = ref(true);

// Methods
const fetchPortfolios = async (url = '/api/portfolios') => {
    isLoading.value = true;
    try {
        const response = await axios.get(url);

        // Populate portfolios and pagination
        portfolios.value = response.data.data; // Portfolio data
        pagination.value = response.data.meta !== undefined ? {
            current_page: response.data.meta.current_page,
            last_page: response.data.meta.last_page,
            prev_page_url: response.data.links.prev,
            next_page_url: response.data.links.next,
        } : '';
    } catch (error) {
        console.error('Error fetching portfolios:', error);
    } finally {
        isLoading.value = false;
    }
};

const viewPortfolio = (id) => {
    window.location.href = `/portfolios/${id}`;
};

const createPortfolio = async () => {
    const name = prompt('Enter the name for your new portfolio:');
    if (!name) return;

    try {
        const response = await axios.post('/api/portfolios', {name});
        portfolios.value.push(response.data);
    } catch (error) {
        console.error('Error creating portfolio:', error);
    }
};

const deletePortfolio = async (id) => {
    if (!confirm('Are you sure you want to delete this portfolio?')) return;

    try {
        await axios.delete(`/api/portfolios/${id}`);
        portfolios.value = portfolios.value.filter((portfolio) => portfolio.id !== id);
    } catch (error) {
        console.error('Error deleting portfolio:', error);
    }
};

// Lifecycle Hook
onMounted(() => {
    fetchPortfolios();
});
</script>

<style scoped>
/* Add any specific styling here */
</style>
