<script setup>
import Logo from '@comps/Logo.vue';
import config from "tailwindcss/defaultConfig";
import { HOME_URL } from "../constants";
</script>

<template>
    <header class="text-gray-800 py-4 px-6 flex items-center justify-between shadow-md">
        <!-- Left Section: Logo and Navigation -->
        <div class="flex items-center space-x-6">
            <Logo/>

            <!-- Navigation Links -->
<!--            <nav class="hidden md:flex space-x-4">-->
                <a :href="HOME_URL" class="hover:text-gray-400">Home</a>
                <a href="/coins" class="hover:text-gray-400">Cryptocurrencies</a>
<!--                <a href="/dexscan" class="hover:text-gray-400">DexScan</a>-->
<!--                <a href="/exchanges" class="hover:text-gray-400">Exchanges</a>-->
<!--                <a href="/community" class="hover:text-gray-400">Community</a>-->
<!--                <a href="/products" class="hover:text-gray-400">Products</a>-->
<!--            </nav>-->
        </div>

        <!-- Right Section: Search Bar and User Links -->
        <div class="flex items-center space-x-4">

            <!-- Search Bar -->
            <div class="relative w-64">
                <input
                    type="text"
                    placeholder="Search"
                    class="w-full py-2 pl-10 pr-4 bg-gray-100 text-sm text-gray-400 rounded-lg focus:outline-none focus:ring focus:ring-blue-500"
                />
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
            </div>

            <!-- Portfolio and Watchlist -->
            <a href="/portfolios" class="hover:text-gray-400 flex items-center space-x-1">
                <i class="fas fa-briefcase"></i>
                <span>Portfolio</span>
            </a>
<!--            <a href="/watchlist" class="hover:text-gray-400 flex items-center space-x-1">-->
<!--                <i class="fas fa-star"></i>-->
<!--                <span>Watchlist</span>-->
<!--            </a>-->

            <!-- Icons -->
            <div class="flex items-center space-x-2">
                <button class="p-2 bg-gray-100 rounded-full hover:bg-gray-700">
                    <i class="fas fa-bell"></i>
                </button>
                <button class="p-2 bg-gray-100 rounded-full hover:bg-gray-700">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="p-2 bg-gray-100 rounded-full hover:bg-gray-700">
                    <img src="@images/avatar.jpeg" alt="User Profile" class="h-6 w-6 rounded-full" />
                </button>
            </div>
        </div>
    </header>
</template>

<style scoped>

</style>
