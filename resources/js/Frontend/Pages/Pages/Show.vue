<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';

const props = defineProps({
    page: {
        type: Object,
        default: () => ({})
    }
});

const title = computed(() => props.page.meta_title || props.page.title || 'صفحه');
const description = computed(() => props.page.meta_description || '');
const robots = computed(() => {
    const index = props.page.seo_index === false ? 'noindex' : 'index';
    const follow = props.page.seo_follow === false ? 'nofollow' : 'follow';

    return `${index},${follow}`;
});
</script>

<template>
    <Head>
        <title>{{ title }} | MotoPart</title>
        <meta v-if="description" head-key="description" name="description" :content="description" />
        <meta head-key="robots" name="robots" :content="robots" />
        <link v-if="page.canonical_url" rel="canonical" :href="page.canonical_url" />
    </Head>

    <FrontLayout>
        <article class="mx-auto max-w-5xl px-4 py-8 md:px-6">
            <nav class="mb-4 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-700">خانه</Link>
                <i class="pi pi-angle-left mx-2 text-xs" />
                <span class="text-surface-800">{{ page.title }}</span>
            </nav>

            <header class="mb-8">
                <p v-if="page.template" class="mb-2 text-xs font-bold uppercase text-primary">
                    {{ page.template }}
                </p>
                <h1 class="text-2xl font-black leading-tight text-surface-950 md:text-4xl">
                    {{ page.title }}
                </h1>
                <p v-if="description" class="mt-3 max-w-3xl text-sm leading-7 text-surface-600 md:text-base">
                    {{ description }}
                </p>
            </header>

            <img
                v-if="page.featured_image_url"
                :src="page.featured_image_url"
                :alt="page.title"
                class="mb-8 aspect-[16/7] w-full rounded-xl border border-surface-200 object-cover"
            />

            <div
                class="cms-page-content rounded-xl border border-surface-200 bg-white p-5 leading-8 text-surface-700 md:p-8"
                v-html="page.content"
            />
        </article>
    </FrontLayout>
</template>

<style scoped>
.cms-page-content :deep(h1),
.cms-page-content :deep(h2),
.cms-page-content :deep(h3) {
    margin: 1.75rem 0 0.75rem;
    font-weight: 900;
    line-height: 1.5;
    color: var(--p-surface-950);
}

.cms-page-content :deep(h1) {
    font-size: 1.875rem;
}

.cms-page-content :deep(h2) {
    font-size: 1.5rem;
}

.cms-page-content :deep(h3) {
    font-size: 1.25rem;
}

.cms-page-content :deep(p) {
    margin: 0 0 1rem;
}

.cms-page-content :deep(a) {
    color: var(--p-primary-color);
    font-weight: 700;
}

.cms-page-content :deep(ul),
.cms-page-content :deep(ol) {
    margin: 1rem 1.25rem;
    padding: 0 1rem;
}

.cms-page-content :deep(li) {
    margin: 0.35rem 0;
}

.cms-page-content :deep(blockquote) {
    margin: 1.25rem 0;
    border-inline-start: 4px solid var(--p-primary-color);
    background: var(--p-surface-50);
    padding: 1rem 1.25rem;
    color: var(--p-surface-700);
}

.cms-page-content :deep(img) {
    margin: 1.5rem auto;
    max-width: 100%;
    border-radius: 0.75rem;
}

.cms-page-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    overflow: hidden;
    border-radius: 0.75rem;
}

.cms-page-content :deep(th),
.cms-page-content :deep(td) {
    border: 1px solid var(--p-surface-200);
    padding: 0.75rem;
    text-align: start;
}
</style>
