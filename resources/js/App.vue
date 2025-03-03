<template>
    <div id="app" class="flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        <Header />
        <main class="flex-grow">
            <!-- Route View -->
            <router-view />
        </main>
        <Footer />
    </div>
</template>

<script>
import Header from "@comps/Header.vue";
import Footer from "@comps/Footer.vue";

export default {
    name: 'App',
    components: {Footer, Header},
};
</script>

<style>
nav ul {
    list-style: none;
    padding: 0;
}
nav ul li {
    display: inline;
    margin-right: 10px;
}
</style>
