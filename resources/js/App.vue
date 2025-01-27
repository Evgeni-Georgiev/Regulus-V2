<template>
    <div id="app">
        <Header />
        <main>
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
