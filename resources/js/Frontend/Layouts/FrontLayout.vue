<script setup>
import Header from '../Components/Header.vue';
import FeatureBar from '../Components/FeatureBar.vue';
import Footer from '../Components/Footer.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { useSiteSettings } from '../Composables/useSiteSettings';

const page = usePage();
const footerMenu = computed(() => page.props.footer_menu ?? []);
const { loadSettings } = useSiteSettings();

onMounted(() => {
    loadSettings();
});
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
