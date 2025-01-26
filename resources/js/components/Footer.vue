<script setup>
import Logo from '@comps/Logo.vue';
</script>

<template>
    <footer class="bg-gray-900 text-white py-6 mt-8">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Logo and Socials -->
            <div>
                <Logo/>
                <div class="flex space-x-4 mt-4">
                    <a href="#" class="hover:text-gray-400"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-gray-400"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="hover:text-gray-400"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="hover:text-gray-400"><i class="fab fa-facebook"></i></a>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h2 class="text-lg font-semibold">Company</h2>
                <ul class="mt-4 space-y-2">
                    <li><a href="/about" class="hover:text-gray-400">About us</a></li>
                    <li><a href="/terms" class="hover:text-gray-400">Terms of Use</a></li>
                    <li><a href="/privacy" class="hover:text-gray-400">Privacy Policy</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h2 class="text-lg font-semibold">Support</h2>
                <ul class="mt-4 space-y-2">
                    <li><a href="/contact" class="hover:text-gray-400">Contact Support</a></li>
                    <li><a href="/faq" class="hover:text-gray-400">FAQ</a></li>
                    <li><a href="/glossary" class="hover:text-gray-400">Glossary</a></li>
                </ul>
            </div>
        </div>

        <!-- Bottom Line -->
        <div class="mt-6 border-t border-gray-800 pt-4 text-center text-sm">
            © {{ new Date().getFullYear() }} CoinMarketCap. All rights reserved.
        </div>
    </footer>
</template>

<style scoped>

</style>
