<script setup>
import ProductCard from '@/Components/Site/ProductCard.vue';
import BlogCard from '../../Components/Blog/BlogCard.vue';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
    relatedPosts: { type: Array, default: () => [] },
    previousPost: { type: Object, default: null },
    nextPost: { type: Object, default: null },
    popularPosts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    relatedProducts: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
    articleSchema: { type: Object, default: null },
});

const copied = ref(false);
const imageFailed = ref(false);
const pageUrl = computed(() => props.seo.canonical || (typeof window !== 'undefined' ? window.location.href : `/blog/${props.post.slug}`));
const heroImage = computed(() => (!imageFailed.value && props.post.featured_image_url) ? props.post.featured_image_url : '/images/product-placeholder.svg');
const schemaJson = computed(() => props.articleSchema ? JSON.stringify(props.articleSchema) : '');

const formatDate = (value) => {
    if (!value) return '';

    return new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(value));
};

const normalizedProducts = computed(() => {
    const source = props.relatedProducts?.length ? props.relatedProducts : (props.post.related_products || []);
    const unique = new Map();

    source.forEach((product) => {
        if (!product?.id || unique.has(product.id)) return;
        unique.set(product.id, {
            ...product,
            image: product.image || product.image_url || product.main_image_url || null,
            price: product.price ?? 0,
            oldPrice: product.oldPrice ?? product.old_price ?? null,
            inStock: product.inStock ?? true,
            url: product.url || (product.slug ? `/products/${product.slug}` : null),
        });
    });

    return Array.from(unique.values());
});

const shareText = computed(() => encodeURIComponent(props.post.title));
const shareUrl = computed(() => encodeURIComponent(pageUrl.value));

const copyLink = async () => {
    if (!navigator?.clipboard) return;

    await navigator.clipboard.writeText(pageUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2200);
};
</script>

<template>
    <Head>
        <title>{{ seo.title || post.title }}</title>
        <meta v-if="seo.description" name="description" :content="seo.description" />
        <link v-if="seo.canonical" rel="canonical" :href="seo.canonical" />
        <meta property="og:title" :content="seo.title || post.title" />
        <meta v-if="seo.description" property="og:description" :content="seo.description" />
        <meta v-if="seo.image || post.featured_image_url" property="og:image" :content="seo.image || post.featured_image_url" />
        <meta property="og:type" :content="seo.type || 'article'" />
        <meta v-if="seo.published_time" property="article:published_time" :content="seo.published_time" />
        <meta v-if="seo.modified_time" property="article:modified_time" :content="seo.modified_time" />
        <component :is="'script'" v-if="schemaJson" type="application/ld+json">{{ schemaJson }}</component>
    </Head>

    <FrontLayout>
        <article>
            <section class="site-hero-surface">
                <div class="site-container py-10 lg:py-14">
                    <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-white/70">
                        <Link href="/" class="hover:text-[var(--site-gold)]">خانه</Link>
                        <span>/</span>
                        <Link href="/blog" class="hover:text-[var(--site-gold)]">مجله موتوپارت</Link>
                        <span>/</span>
                        <span class="text-white">{{ post.title }}</span>
                    </nav>

                    <div class="grid gap-8 lg:grid-cols-[1fr_420px] lg:items-center">
                        <div>
                            <Link
                                v-if="post.category"
                                :href="`/blog/category/${post.category.slug}`"
                                class="inline-flex rounded-full bg-[var(--site-gold)] px-4 py-1.5 text-sm font-black text-[#111]"
                            >
                                {{ post.category.name }}
                            </Link>
                            <h1 class="mt-5 text-3xl font-black leading-[1.55] text-white md:text-5xl">{{ post.title }}</h1>
                            <p v-if="post.excerpt" class="mt-4 max-w-3xl text-base leading-8 text-white/75 md:text-lg">{{ post.excerpt }}</p>

                            <div class="mt-6 flex flex-wrap gap-4 text-sm font-bold text-white/70">
                                <span>{{ post.author?.name || 'تحریریه موتوپارت' }}</span>
                                <span v-if="post.published_at">{{ formatDate(post.published_at) }}</span>
                                <span>{{ post.reading_time || 1 }} دقیقه مطالعه</span>
                                <span>{{ Number(post.views || 0).toLocaleString('fa-IR') }} بازدید</span>
                            </div>
                        </div>

                        <img
                            :src="heroImage"
                            :alt="post.featured_image_alt || post.title"
                            class="aspect-[4/3] w-full rounded-xl border border-white/10 object-cover shadow-2xl"
                            loading="eager"
                            @error="imageFailed = true"
                        />
                    </div>
                </div>
            </section>

            <section class="site-container py-10 lg:py-14">
                <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <main class="min-w-0">
                        <div class="site-card min-w-0 overflow-hidden rounded-xl p-5 md:p-8">
                            <div class="blog-content min-w-0 max-w-full" v-html="post.content"></div>
                        </div>

                        <div v-if="post.tags?.length" class="mt-6 flex flex-wrap gap-2">
                            <Link
                                v-for="tag in post.tags"
                                :key="tag.id"
                                :href="`/blog/tag/${tag.slug}`"
                                class="rounded-full border border-[var(--site-border)] px-4 py-2 text-sm font-bold hover:border-[var(--site-gold)] hover:text-[var(--site-gold)]"
                            >
                                #{{ tag.name }}
                            </Link>
                        </div>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            <Link v-if="previousPost" :href="`/blog/${previousPost.slug}`" class="site-card rounded-xl p-5">
                                <span class="text-xs font-black text-[var(--site-gold)]">مطلب قبلی</span>
                                <strong class="mt-2 block line-clamp-2 leading-7">{{ previousPost.title }}</strong>
                            </Link>
                            <Link v-if="nextPost" :href="`/blog/${nextPost.slug}`" class="site-card rounded-xl p-5">
                                <span class="text-xs font-black text-[var(--site-gold)]">مطلب بعدی</span>
                                <strong class="mt-2 block line-clamp-2 leading-7">{{ nextPost.title }}</strong>
                            </Link>
                        </div>

                        <section v-if="normalizedProducts.length" class="mt-10">
                            <h2 class="mb-5 text-2xl font-black text-[var(--site-dark)]">محصولات مرتبط با این مقاله</h2>
                            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                                <ProductCard v-for="product in normalizedProducts" :key="product.id" :product="product" />
                            </div>
                        </section>

                        <section v-if="relatedPosts.length" class="mt-10">
                            <h2 class="mb-5 text-2xl font-black text-[var(--site-dark)]">مطالب مرتبط</h2>
                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                <BlogCard v-for="item in relatedPosts.slice(0, 6)" :key="item.id" :post="item" />
                            </div>
                        </section>
                    </main>

                    <aside class="space-y-5 lg:sticky lg:top-32 lg:self-start">
                        <div class="site-card rounded-xl p-5">
                            <h2 class="text-lg font-black text-[var(--site-dark)]">اشتراک گذاری</h2>
                            <div class="mt-4 grid gap-2">
                                <button class="site-btn-dark rounded-lg" type="button" @click="copyLink">کپی لینک</button>
                                <a class="site-btn-secondary rounded-lg" :href="`https://t.me/share/url?url=${shareUrl}&text=${shareText}`" target="_blank" rel="noopener">تلگرام</a>
                                <a class="site-btn-secondary rounded-lg" :href="`https://wa.me/?text=${shareText}%20${shareUrl}`" target="_blank" rel="noopener">واتساپ</a>
                            </div>
                            <p v-if="copied" class="mt-3 text-sm font-bold text-[var(--site-gold)]">لینک مقاله کپی شد.</p>
                        </div>

                        <div class="site-card rounded-xl p-5">
                            <h2 class="text-lg font-black text-[var(--site-dark)]">دسته بندی ها</h2>
                            <div class="mt-4 grid gap-2">
                                <Link v-for="category in categories" :key="category.id" :href="`/blog/category/${category.slug}`" class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-[var(--site-soft)] hover:text-[var(--site-gold)]">
                                    {{ category.name }}
                                </Link>
                            </div>
                        </div>

                        <div v-if="popularPosts.length" class="site-card rounded-xl p-5">
                            <h2 class="text-lg font-black text-[var(--site-dark)]">مقالات محبوب</h2>
                            <div class="mt-4 grid gap-4">
                                <Link v-for="item in popularPosts" :key="item.id" :href="`/blog/${item.slug}`" class="group block">
                                    <strong class="line-clamp-2 text-sm leading-6 text-[var(--site-dark)] group-hover:text-[var(--site-gold)]">{{ item.title }}</strong>
                                    <span class="mt-1 block text-xs text-[var(--site-text-secondary)]">{{ Number(item.views || 0).toLocaleString('fa-IR') }} بازدید</span>
                                </Link>
                            </div>
                        </div>
                    </aside>
                </div>
            </section>
        </article>
    </FrontLayout>
</template>

<style scoped>
.blog-content {
    color: var(--site-text-primary);
    font-size: 1rem;
    line-height: 2.15;
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.blog-content :deep(*) {
    box-sizing: border-box;
    max-width: 100%;
}

.blog-content :deep(h1),
.blog-content :deep(h2),
.blog-content :deep(h3),
.blog-content :deep(h4) {
    color: var(--site-dark);
    font-weight: 900;
    line-height: 1.6;
    margin: 1.8rem 0 0.8rem;
    overflow-wrap: anywhere;
}

.blog-content :deep(h1) { font-size: 2rem; }
.blog-content :deep(h2) { font-size: 1.65rem; }
.blog-content :deep(h3) { font-size: 1.35rem; }
.blog-content :deep(h4) { font-size: 1.1rem; }

.blog-content :deep(p),
.blog-content :deep(ul),
.blog-content :deep(ol),
.blog-content :deep(blockquote),
.blog-content :deep(table),
.blog-content :deep(pre) {
    margin-block: 1rem;
}

.blog-content :deep(ul),
.blog-content :deep(ol) {
    padding-inline-start: 1.5rem;
}

.blog-content :deep(ul) { list-style: disc; }
.blog-content :deep(ol) { list-style: decimal; }

.blog-content :deep(a) {
    color: var(--site-gold);
    font-weight: 800;
    text-decoration: underline;
}

.blog-content :deep(blockquote) {
    border-inline-start: 4px solid var(--site-gold);
    background: var(--site-soft);
    padding: 1rem 1.25rem;
    color: var(--site-text-secondary);
    overflow-wrap: anywhere;
}

.blog-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.75rem;
}

.blog-content :deep(iframe),
.blog-content :deep(video) {
    aspect-ratio: 16 / 9;
    height: auto;
    width: 100%;
}

.blog-content :deep(table) {
    display: block;
    max-width: 100%;
    overflow-x: auto;
    border-collapse: collapse;
    white-space: normal;
}

.blog-content :deep(th),
.blog-content :deep(td) {
    border: 1px solid var(--site-border);
    padding: 0.75rem;
}

.blog-content :deep(pre),
.blog-content :deep(code) {
    direction: ltr;
    border-radius: 0.5rem;
    background: #111;
    color: #f7f7f7;
}

.blog-content :deep(pre) {
    max-width: 100%;
    overflow-x: auto;
    padding: 1rem;
    white-space: pre;
}

.blog-content :deep(code) {
    padding: 0.15rem 0.35rem;
    white-space: break-spaces;
}

@media (max-width: 640px) {
    .blog-content {
        font-size: 0.95rem;
        line-height: 2;
    }

    .blog-content :deep(h1) { font-size: 1.55rem; }
    .blog-content :deep(h2) { font-size: 1.35rem; }
    .blog-content :deep(h3) { font-size: 1.2rem; }
}
</style>
