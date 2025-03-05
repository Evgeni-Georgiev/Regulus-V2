<script setup>
import Logo from '@comps/Logo.vue';
import config from "tailwindcss/defaultConfig";
import { HOME_URL } from "../constants";
import ThemeToggle from "@comps/ui/ThemeToggle.vue";
</script>

<template>
    <header class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 py-4 px-6 flex items-center justify-between shadow-md">
        <!-- Left Section: Logo and Navigation -->
        <div class="flex items-center space-x-6">
            <router-link
                to="/"
            >
                <Logo/>
            </router-link>

            <nav class="flex items-center space-x-6">
                <router-link
                    to="/coins"
                    class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white"
                >
                    Cryptocurrencies
                </router-link>

                <!-- Theme Toggle -->
                <ThemeToggle />

                <!-- User dropdown -->
<!--                <div class="relative">-->
<!--                    <button-->
<!--                        class="flex items-center text-gray-900 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"-->
<!--                    >-->
<!--                        <span class="mr-2">Account</span>-->
<!--                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">-->
<!--                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />-->
<!--                        </svg>-->
<!--                    </button>-->
<!--                </div>-->
            </nav>
        </div>

        <!-- Right Section: Search Bar and User Links -->
        <div class="flex items-center space-x-4">

            <!-- Search Bar -->
            <div class="relative w-64">
                <input
                    type="text"
                    placeholder="Search"
                    class="w-full py-2 pl-10 pr-4 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white rounded-lg focus:outline-none focus:ring focus:ring-blue-500"
                />
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
            </div>

            <!-- Portfolio and Watchlist -->
            <router-link
                to="/portfolios"
                class="text-gray-900 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white"
            >
                Portfolios
            </router-link>

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
