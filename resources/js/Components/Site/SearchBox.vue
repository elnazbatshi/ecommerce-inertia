<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { getSearchSuggestions } from '@/Frontend/services/menuApi';

const query = ref('');
const focused = ref(false);
const suggestions = ref({ popular_terms: [], products: [], categories: [], brands: [], posts: [] });
let timer = null;

const open = computed(() => focused.value && (query.value.trim().length > 0 || suggestions.value.popular_terms?.length > 0));

const load = async () => {
    suggestions.value = await getSearchSuggestions(query.value);
};

watch(query, () => {
    if (!focused.value) return;
    clearTimeout(timer);
    timer = setTimeout(load, 300);
});

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="relative">
        <div class="site-search">
            <input
                v-model="query"
                type="search"
                placeholder="جستجو برای محصولات..."
                @focus="focused = true; load()"
                @blur="setTimeout(() => { focused = false }, 120)"
            >
            <button type="button">جستجو</button>
        </div>
        <div v-if="open" class="search-dropdown">
            <div v-if="suggestions.popular_terms?.length" class="mb-3 flex flex-wrap gap-2">
                <button v-for="term in suggestions.popular_terms" :key="term" class="mega-chip" @click="query = term">{{ term }}</button>
            </div>
            <a v-for="item in [...(suggestions.products || []), ...(suggestions.categories || []), ...(suggestions.brands || [])]" :key="`${item.title}-${item.id}`" :href="item.url" class="search-item">
                {{ item.title }}
            </a>
        </div>
    </div>
</template>
