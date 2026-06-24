<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useCart } from '@/Composables/useCart';

const { items, count, subtotal, removeItem } = useCart();
const open = ref(false);

const previewItems = computed(() => items.value.slice(-3).reverse());
const extraCount = computed(() => Math.max(0, items.value.length - previewItems.value.length));
const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;
const productUrl = (item) => item.slug ? `/products/${item.slug}` : '/products';
</script>

<template>
    <div class="relative" @mouseenter="open = true" @mouseleave="open = false">
        <button
            type="button"
            class="site-icon-btn"
            aria-label="سبد خرید"
            aria-haspopup="true"
            :aria-expanded="open"
            @click="open = !open"
        >
            <i class="pi pi-shopping-cart text-lg" aria-hidden="true" />
            <span class="cart-badge">{{ count }}</span>
        </button>

        <transition name="fade-slide">
            <div
                v-if="open"
                class="absolute left-0 top-[calc(100%+0.75rem)] z-50 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-surface-200 bg-white text-right shadow-xl"
            >
                <div class="flex items-center justify-between border-b border-surface-100 p-4">
                    <div>
                        <h3 class="text-base font-black text-surface-950">سبد خرید</h3>
                        <p class="mt-1 text-xs text-surface-500">{{ count.toLocaleString('fa-IR') }} کالا</p>
                    </div>
                    <Link href="/cart" class="text-xs font-bold text-[#D4A017]" @click="open = false">مشاهده کامل</Link>
                </div>

                <div v-if="items.length" class="max-h-80 overflow-y-auto p-3">
                    <article
                        v-for="item in previewItems"
                        :key="item.key"
                        class="grid grid-cols-[64px_1fr_auto] gap-3 rounded-lg p-2 transition hover:bg-surface-50"
                    >
                        <Link :href="productUrl(item)" class="overflow-hidden rounded-lg bg-surface-100" @click="open = false">
                            <img
                                :src="item.image || 'https://picsum.photos/seed/cart-mini/160/140'"
                                :alt="item.name"
                                class="h-16 w-16 object-cover"
                            />
                        </Link>
                        <div class="min-w-0">
                            <Link :href="productUrl(item)" class="line-clamp-1 text-sm font-bold text-surface-900 hover:text-[#D4A017]" @click="open = false">
                                {{ item.name }}
                            </Link>
                            <p v-if="item.variant_label" class="mt-1 line-clamp-1 text-xs text-surface-500">{{ item.variant_label }}</p>
                            <p class="mt-1 text-xs text-surface-500">{{ item.quantity.toLocaleString('fa-IR') }} × {{ formatPrice(item.price) }}</p>
                        </div>
                        <Button
                            icon="pi pi-times"
                            rounded
                            text
                            severity="danger"
                            size="small"
                            aria-label="حذف از سبد"
                            @click="removeItem(item.key)"
                        />
                    </article>

                    <p v-if="extraCount" class="px-2 py-2 text-xs text-surface-500">
                        {{ extraCount.toLocaleString('fa-IR') }} آیتم دیگر در سبد شماست.
                    </p>
                </div>

                <div v-else class="p-6 text-center">
                    <i class="pi pi-shopping-cart mb-3 block text-3xl text-[#D4A017]" />
                    <p class="text-sm font-bold text-surface-900">سبد خرید خالی است</p>
                    <p class="mt-1 text-xs text-surface-500">محصولی انتخاب نشده است.</p>
                </div>

                <div class="border-t border-surface-100 bg-surface-50 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm text-surface-500">جمع کل</span>
                        <strong class="text-base text-surface-950">{{ formatPrice(subtotal) }}</strong>
                    </div>
                    <Link href="/cart" class="block" @click="open = false">
                        <Button label="رفتن به سبد خرید" icon="pi pi-shopping-cart" class="w-full" />
                    </Link>
                </div>
            </div>
        </transition>
    </div>
</template>
