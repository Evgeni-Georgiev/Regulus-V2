<script setup>
import Logo from '@comps/Logo.vue';
</script>

<template>
    <footer class="text-gray-800 py-6 mt-8 bg-gray-50 dark:bg-gray-900 dark:text-gray-100">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Logo and Socials -->
            <Logo/>
        </div>

        <!-- Bottom Line -->
        <div class="mt-6 border-t border-gray-800 pt-4 text-center text-sm">
            © {{ new Date().getFullYear() }} CoinMarketCap. All rights reserved.
        </div>
    </footer>
</template>

<style scoped>

</style>
