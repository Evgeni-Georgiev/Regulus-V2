<template>
    <div id="app">
        <Header />
        <!-- Route View -->
        <router-view />
        <Footer />
    </div>
</template>

<script>
import Header from "./Components/Header.vue";
import Footer from "./Components/Footer.vue";

export default {
    name: 'App',
    components: {Footer, Header},
};
</script>

<style>
/* Example Styling */
nav ul {
    list-style: none;
    padding: 0;
}
nav ul li {
    display: inline;
    margin-right: 10px;
}
</style>
