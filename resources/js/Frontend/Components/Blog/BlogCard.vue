<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    post: { type: Object, required: true },
});

const imageFailed = ref(false);
const fallbackImage = '/images/product-placeholder.svg';
const postUrl = computed(() => `/blog/${props.post.slug}`);
const imageSrc = computed(() => (!imageFailed.value && props.post.featured_image_url) ? props.post.featured_image_url : fallbackImage);

const formatDate = (value) => {
    if (!value) return '';

    return new Intl.DateTimeFormat('fa-IR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(value));
};
</script>

<template>
    <article class="site-card flex h-full flex-col overflow-hidden rounded-xl bg-white">
        <Link :href="postUrl" class="block overflow-hidden bg-[#f4f4f4]">
            <img
                :src="imageSrc"
                :alt="post.featured_image_alt || post.title"
                class="aspect-[16/10] w-full object-cover transition duration-300 hover:scale-105"
                loading="lazy"
                @error="imageFailed = true"
            />
        </Link>

        <div class="flex flex-1 flex-col p-5">
            <div class="mb-3 flex flex-wrap items-center gap-2 text-xs font-bold text-[var(--site-text-secondary)]">
                <Link
                    v-if="post.category"
                    :href="`/blog/category/${post.category.slug}`"
                    class="rounded-full bg-[#111] px-3 py-1 text-white hover:bg-[var(--site-gold)] hover:text-[#111]"
                >
                    {{ post.category.name }}
                </Link>
                <span v-if="post.published_at">{{ formatDate(post.published_at) }}</span>
            </div>

            <Link :href="postUrl" class="line-clamp-2 text-xl font-black leading-8 text-[var(--site-dark)] hover:text-[var(--site-gold)]">
                {{ post.title }}
            </Link>

            <p class="mt-3 line-clamp-3 text-sm leading-7 text-[var(--site-text-secondary)]">
                {{ post.excerpt || 'خلاصه ای برای این مقاله ثبت نشده است.' }}
            </p>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--site-border)] pt-4 text-xs font-bold text-[var(--site-text-secondary)]">
                <span>{{ post.reading_time || 1 }} دقیقه مطالعه</span>
                <span>{{ Number(post.views || 0).toLocaleString('fa-IR') }} بازدید</span>
                <Link :href="postUrl" class="text-[var(--site-gold)] hover:text-[var(--site-gold-hover)]">
                    ادامه مطلب
                </Link>
            </div>
        </div>
    </article>
</template>
