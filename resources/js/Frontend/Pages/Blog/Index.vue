<script setup>
import BlogCard from '../../Components/Blog/BlogCard.vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    posts: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    popularPosts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    currentCategory: { type: Object, default: null },
    currentTag: { type: Object, default: null },
    seo: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');

const pageTitle = computed(() => {
    if (props.currentCategory) return `مقالات دسته بندی «${props.currentCategory.name}»`;
    if (props.currentTag) return `مطالب با برچسب «${props.currentTag.name}»`;
    return 'مجله موتوپارت';
});

const pageSubtitle = computed(() => {
    if (props.currentCategory?.description) return props.currentCategory.description;
    return 'راهنمای خرید، آموزش نگهداری و تازه ترین مطالب دنیای خودرو و موتورسیکلت';
});

const visibleLinks = computed(() => (props.posts.links || []).filter((link) => link.url || !['&laquo; Previous', 'Next &raquo;'].includes(link.label)));

const cleanLabel = (label) => String(label || '')
    .replace('&laquo; Previous', 'قبلی')
    .replace('Next &raquo;', 'بعدی')
    .replace('&laquo;', '')
    .replace('&raquo;', '');

const submitSearch = () => {
    router.get('/blog', {
        search: search.value || undefined,
        sort: props.filters.sort || undefined,
    }, {
        preserveState: true,
        preserveScroll: false,
        replace: true,
    });
};

const clearSearch = () => {
    search.value = '';
    router.get('/blog', {}, { preserveScroll: false });
};
</script>

<template>
    <Head>
        <title>{{ seo.title || pageTitle }}</title>
        <meta v-if="seo.description" name="description" :content="seo.description" />
        <link v-if="seo.canonical" rel="canonical" :href="seo.canonical" />
        <meta property="og:title" :content="seo.title || pageTitle" />
        <meta v-if="seo.description" property="og:description" :content="seo.description" />
        <meta property="og:type" :content="seo.type || 'website'" />
    </Head>

    <FrontLayout>
        <section class="site-hero-surface">
            <div class="site-container py-12 lg:py-16">
                <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-white/70">
                    <Link href="/" class="hover:text-[var(--site-gold)]">خانه</Link>
                    <span>/</span>
                    <Link href="/blog" class="hover:text-[var(--site-gold)]">مجله موتوپارت</Link>
                    <template v-if="currentCategory || currentTag">
                        <span>/</span>
                        <span class="text-white">{{ currentCategory?.name || currentTag?.name }}</span>
                    </template>
                </nav>

                <p class="site-gold-kicker">MotoPart Journal</p>
                <h1 class="mt-3 max-w-3xl text-3xl font-black leading-[1.5] text-white md:text-5xl">{{ pageTitle }}</h1>
                <p class="mt-4 max-w-3xl text-base leading-8 text-white/75 md:text-lg">{{ pageSubtitle }}</p>
            </div>
        </section>

        <section class="site-container py-10 lg:py-14">
            <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
                <div>
                    <div v-if="posts.data?.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <BlogCard v-for="post in posts.data" :key="post.id" :post="post" />
                    </div>

                    <div v-else class="rounded-xl border border-dashed border-[var(--site-border)] bg-white p-10 text-center">
                        <h2 class="text-xl font-black text-[var(--site-dark)]">مقاله ای با این شرایط پیدا نشد.</h2>
                        <Link href="/blog" class="site-btn-primary mt-6">مشاهده همه مقالات</Link>
                    </div>

                    <div v-if="posts.links?.length" class="mt-8 flex flex-wrap justify-center gap-2">
                        <Link
                            v-for="link in visibleLinks"
                            :key="`${link.label}-${link.url}`"
                            :href="link.url || '#'"
                            preserve-scroll
                            class="min-w-10 rounded-lg border px-3 py-2 text-center text-sm font-black"
                            :class="[
                                link.active ? 'border-[var(--site-gold)] bg-[var(--site-gold)] text-[#111]' : 'border-[var(--site-border)] bg-white text-[var(--site-dark)] hover:border-[var(--site-gold)]',
                                !link.url ? 'pointer-events-none opacity-50' : ''
                            ]"
                            v-html="cleanLabel(link.label)"
                        />
                    </div>
                </div>

                <aside class="space-y-5 lg:order-2">
                    <div class="site-card rounded-xl p-5">
                        <h2 class="text-lg font-black text-[var(--site-dark)]">جستجو</h2>
                        <form class="mt-4 flex gap-2" @submit.prevent="submitSearch">
                            <input v-model="search" class="min-w-0 flex-1 rounded-lg border border-[var(--site-border)] px-3 py-2 outline-none focus:border-[var(--site-gold)]" placeholder="جستجو در مقالات" />
                            <button class="site-btn-primary rounded-lg px-4" type="submit">جستجو</button>
                        </form>
                        <button v-if="filters.search" class="mt-3 text-sm font-bold text-[var(--site-gold)]" type="button" @click="clearSearch">پاک کردن جستجو</button>
                    </div>

                    <div class="site-card rounded-xl p-5">
                        <h2 class="text-lg font-black text-[var(--site-dark)]">دسته بندی ها</h2>
                        <div class="mt-4 grid gap-2">
                            <Link href="/blog" class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-[var(--site-soft)]">همه مقالات</Link>
                            <Link
                                v-for="category in categories"
                                :key="category.id"
                                :href="`/blog/category/${category.slug}`"
                                class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-[var(--site-soft)] hover:text-[var(--site-gold)]"
                            >
                                {{ category.name }}
                            </Link>
                        </div>
                    </div>

                    <div v-if="popularPosts.length" class="site-card rounded-xl p-5">
                        <h2 class="text-lg font-black text-[var(--site-dark)]">مقالات محبوب</h2>
                        <div class="mt-4 grid gap-4">
                            <Link v-for="post in popularPosts" :key="post.id" :href="`/blog/${post.slug}`" class="group block">
                                <strong class="line-clamp-2 text-sm leading-6 text-[var(--site-dark)] group-hover:text-[var(--site-gold)]">{{ post.title }}</strong>
                                <span class="mt-1 block text-xs text-[var(--site-text-secondary)]">{{ Number(post.views || 0).toLocaleString('fa-IR') }} بازدید</span>
                            </Link>
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </FrontLayout>
</template>
