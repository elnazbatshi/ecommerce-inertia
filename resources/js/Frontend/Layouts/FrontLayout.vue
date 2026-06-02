<script setup>
import Header from '../Components/Header.vue';
import FeatureBar from '../Components/FeatureBar.vue';
import Footer from '../Components/Footer.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const footerMenu = computed(() => page.props.footer_menu ?? []);
</script>

<template>
    <div class="site-shell">
        <Header />
        <main class="overflow-hidden">
            <slot />
        </main>
        <FeatureBar />
        <Footer :items="footerMenu" />
    </div>
</template>
