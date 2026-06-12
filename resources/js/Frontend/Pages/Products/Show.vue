<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import FrontLayout from '../../Layouts/FrontLayout.vue';
import ProductCard from '@/Components/Site/ProductCard.vue';
import { useCart } from '@/Composables/useCart';

const props = defineProps({
    product: { type: Object, required: true },
    relatedProducts: { type: Array, default: () => [] },
});

const toast = useToast();
const { addItem } = useCart();
const selectedImageIndex = ref(0);
const selectedVariantId = ref(props.product.variants?.find((variant) => variant.stock > 0)?.id ?? props.product.variants?.[0]?.id ?? null);
const quantity = ref(1);

const selectedVariant = computed(() => props.product.variants?.find((variant) => variant.id === selectedVariantId.value) ?? null);
const gallery = computed(() => {
    const items = [...(props.product.gallery ?? [])];
    if (selectedVariant.value?.image_url) {
        items.unshift({ id: `variant-${selectedVariant.value.id}`, url: selectedVariant.value.image_url, alt: selectedVariant.value.label });
    }

    return items.length ? items : [{ id: 'placeholder', url: 'https://picsum.photos/seed/motopart-product/900/700', alt: props.product.name }];
});

const currentImage = computed(() => gallery.value[selectedImageIndex.value] ?? gallery.value[0]);
const activePrice = computed(() => selectedVariant.value?.discount_price ?? selectedVariant.value?.price ?? props.product.discount_price ?? props.product.price);
const oldPrice = computed(() => {
    if (selectedVariant.value?.discount_price && Number(selectedVariant.value.price) > Number(selectedVariant.value.discount_price)) {
        return selectedVariant.value.price;
    }

    return props.product.discount_price ? props.product.price : null;
});
const activeSku = computed(() => selectedVariant.value?.sku || props.product.sku || '-');
const activeStock = computed(() => selectedVariant.value ? selectedVariant.value.stock : props.product.stock);
const inStock = computed(() => Number(activeStock.value) > 0);
const metaDescription = computed(() => props.product.meta_description || props.product.short_description || '');
const groupedSpecs = computed(() => {
    const groups = new Map();
    (props.product.specs ?? []).forEach((spec) => {
        const group = spec.group || 'مشخصات';
        if (!groups.has(group)) groups.set(group, []);
        groups.get(group).push(spec);
    });

    return Array.from(groups, ([group, specs]) => ({ group, specs }));
});

const formatPrice = (value) => `${Number(value || 0).toLocaleString('fa-IR')} تومان`;

const selectVariant = (variant) => {
    if (!variant.active || variant.stock <= 0) return;
    selectedVariantId.value = variant.id;
    quantity.value = 1;
    selectedImageIndex.value = 0;
};

const addToCart = () => {
    addItem({
        id: props.product.id,
        variant_id: selectedVariant.value?.id ?? null,
        slug: props.product.slug,
        name: props.product.name,
        variant_label: selectedVariant.value?.label ?? null,
        brand: props.product.brand?.name,
        sku: activeSku.value,
        image: currentImage.value?.url,
        price: activePrice.value,
        old_price: oldPrice.value,
        stock: activeStock.value,
    }, quantity.value);

    toast.add({
        severity: 'success',
        summary: 'سبد خرید',
        detail: 'محصول به سبد خرید اضافه شد.',
        life: 2200,
    });
};
</script>

<template>
    <Head>
        <title>{{ product.name }} | MotoPart</title>
        <meta v-if="metaDescription" head-key="description" name="description" :content="metaDescription" />
        <link v-if="product.canonical_url" rel="canonical" :href="product.canonical_url" />
    </Head>

    <FrontLayout>
        <main class="mx-auto max-w-7xl px-4 py-8 md:px-6">
            <nav class="mb-5 flex flex-wrap items-center gap-2 text-sm text-surface-500">
                <Link href="/" class="hover:text-surface-800">خانه</Link>
                <i class="pi pi-angle-left text-xs" />
                <Link href="/products" class="hover:text-surface-800">محصولات</Link>
                <template v-if="product.category">
                    <i class="pi pi-angle-left text-xs" />
                    <Link :href="`/category/${product.category.slug}`" class="hover:text-surface-800">{{ product.category.name }}</Link>
                </template>
                <i class="pi pi-angle-left text-xs" />
                <span class="text-surface-900">{{ product.name }}</span>
            </nav>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
                <div class="space-y-4 lg:order-1">
                    <div class="overflow-hidden rounded-2xl border border-surface-200 bg-white">
                        <img :src="currentImage.url" :alt="currentImage.alt || product.name" class="aspect-[4/3] w-full object-cover" />
                    </div>

                    <div class="flex gap-3 overflow-x-auto pb-1">
                        <button
                            v-for="(image, index) in gallery"
                            :key="image.id"
                            type="button"
                            class="h-20 w-24 shrink-0 overflow-hidden rounded-xl border bg-white p-1"
                            :class="index === selectedImageIndex ? 'border-[#D4A017]' : 'border-surface-200'"
                            @click="selectedImageIndex = index"
                        >
                            <img :src="image.url" :alt="image.alt || product.name" class="h-full w-full rounded-lg object-cover" />
                        </button>
                    </div>
                </div>

                <aside class="space-y-4 lg:order-2">
                    <div class="rounded-2xl border border-surface-200 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <Tag v-if="product.is_original" value="اصالت کالا" severity="contrast" />
                            <Tag :value="inStock ? 'موجود' : 'ناموجود'" :severity="inStock ? 'success' : 'danger'" />
                            <Tag v-if="product.brand" :value="product.brand.name" severity="secondary" />
                        </div>

                        <h1 class="text-2xl font-black leading-10 text-surface-950 md:text-3xl">{{ product.name }}</h1>
                        <p v-if="metaDescription" class="mt-3 text-sm leading-7 text-surface-600">{{ metaDescription }}</p>

                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-surface-50 p-3">
                                <dt class="text-surface-500">دسته‌بندی</dt>
                                <dd class="mt-1 font-bold text-surface-900">{{ product.category?.name || '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-surface-50 p-3">
                                <dt class="text-surface-500">کد کالا</dt>
                                <dd class="mt-1 font-bold text-surface-900">{{ activeSku }}</dd>
                            </div>
                        </dl>

                        <div v-if="product.variants?.length" class="mt-5">
                            <h2 class="mb-3 text-sm font-bold text-surface-900">انتخاب تنوع</h2>
                            <div class="grid grid-cols-1 gap-2">
                                <button
                                    v-for="variant in product.variants"
                                    :key="variant.id"
                                    type="button"
                                    class="rounded-xl border p-3 text-right transition"
                                    :class="[
                                        selectedVariantId === variant.id ? 'border-[#D4A017] bg-[#FFF8E1]' : 'border-surface-200 bg-white',
                                        variant.stock <= 0 ? 'cursor-not-allowed opacity-50' : 'hover:border-[#D4A017]'
                                    ]"
                                    :disabled="!variant.active || variant.stock <= 0"
                                    @click="selectVariant(variant)"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-bold text-surface-900">{{ variant.label }}</span>
                                        <span class="text-xs text-surface-500">{{ variant.stock > 0 ? `${variant.stock} عدد` : 'ناموجود' }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-surface-600">{{ formatPrice(variant.discount_price || variant.price) }}</div>
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-surface-200 pt-5">
                            <div class="flex items-end gap-3">
                                <strong class="text-2xl font-black text-surface-950">{{ formatPrice(activePrice) }}</strong>
                                <span v-if="oldPrice" class="pb-1 text-sm text-surface-400 line-through">{{ formatPrice(oldPrice) }}</span>
                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <InputNumber v-model="quantity" showButtons buttonLayout="horizontal" :min="1" :max="Math.max(1, activeStock)" inputClass="w-16 text-center" />
                                <Button
                                    label="افزودن به سبد خرید"
                                    icon="pi pi-shopping-cart"
                                    class="flex-1"
                                    :disabled="!inStock"
                                    @click="addToCart"
                                />
                                <Button icon="pi pi-heart" rounded outlined severity="secondary" />
                            </div>
                            <p class="mt-3 text-xs text-surface-500">
                                {{ inStock ? `موجودی قابل سفارش: ${Number(activeStock).toLocaleString('fa-IR')} عدد` : 'این محصول فعلا موجود نیست.' }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 rounded-2xl border border-surface-200 bg-[#1A1A1A] p-4 text-sm text-white">
                        <div class="flex items-center gap-2"><i class="pi pi-verified text-[#D4A017]" /> ضمانت اصالت</div>
                        <div class="flex items-center gap-2"><i class="pi pi-truck text-[#D4A017]" /> ارسال سریع</div>
                        <div class="flex items-center gap-2"><i class="pi pi-lock text-[#D4A017]" /> پرداخت امن</div>
                        <div class="flex items-center gap-2"><i class="pi pi-refresh text-[#D4A017]" /> ۷ روز بازگشت</div>
                    </div>
                </aside>
            </section>

            <section class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-[1fr_360px]">
                <div class="space-y-5">
                    <section class="rounded-2xl border border-surface-200 bg-white p-5">
                        <h2 class="mb-3 text-xl font-black text-surface-950">توضیحات محصول</h2>
                        <p v-if="product.short_description" class="mb-4 text-sm leading-7 text-surface-600">{{ product.short_description }}</p>
                        <div v-if="product.description" class="cms-product-content leading-8 text-surface-700" v-html="product.description" />
                        <p v-else class="text-sm text-surface-500">برای این محصول هنوز توضیحات کامل ثبت نشده است.</p>
                    </section>

                    <section class="rounded-2xl border border-surface-200 bg-white p-5">
                        <h2 class="mb-4 text-xl font-black text-surface-950">مشخصات فنی</h2>
                        <div v-if="groupedSpecs.length" class="space-y-5">
                            <div v-for="group in groupedSpecs" :key="group.group">
                                <h3 class="mb-2 text-sm font-black text-[#D4A017]">{{ group.group }}</h3>
                                <dl class="grid grid-cols-1 overflow-hidden rounded-xl border border-surface-200 md:grid-cols-2">
                                    <div v-for="spec in group.specs" :key="`${group.group}-${spec.name}`" class="flex justify-between gap-4 border-b border-surface-100 p-3 text-sm">
                                        <dt class="text-surface-500">{{ spec.name }}</dt>
                                        <dd class="font-bold text-surface-900">{{ spec.value }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                        <p v-else class="text-sm text-surface-500">مشخصات فنی برای این محصول ثبت نشده است.</p>
                    </section>
                </div>

                <section class="rounded-2xl border border-surface-200 bg-white p-5 lg:self-start">
                    <h2 class="mb-4 text-xl font-black text-surface-950">سازگار با خودرو / موتورسیکلت</h2>
                    <div v-if="product.vehicles?.length" class="space-y-3">
                        <div v-for="vehicle in product.vehicles" :key="vehicle.id" class="rounded-xl border border-surface-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-black text-surface-900">{{ [vehicle.brand, vehicle.name].filter(Boolean).join(' ') }}</h3>
                                    <p class="mt-1 text-xs text-surface-500">
                                        {{ [vehicle.trim, vehicle.engine].filter(Boolean).join(' / ') || 'جزئیات تکمیلی ثبت نشده' }}
                                    </p>
                                </div>
                                <Tag value="سازگار" severity="success" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm leading-7 text-surface-500">برای این محصول هنوز سازگاری ثبت نشده است.</p>
                </section>
            </section>

            <section v-if="relatedProducts.length" class="mt-10">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-2xl font-black text-surface-950">محصولات مرتبط</h2>
                        <p class="mt-1 text-sm text-surface-500">انتخاب‌هایی از همین دسته یا برند</p>
                    </div>
                    <Link href="/products" class="text-sm font-bold text-[#D4A017]">مشاهده همه</Link>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
                </div>
            </section>
        </main>

        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-surface-200 bg-white/95 p-3 shadow-lg backdrop-blur md:hidden">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-xs text-surface-500">{{ product.name }}</div>
                    <div class="font-black text-surface-950">{{ formatPrice(activePrice) }}</div>
                </div>
                <Button label="افزودن" icon="pi pi-shopping-cart" :disabled="!inStock" @click="addToCart" />
            </div>
        </div>
    </FrontLayout>
</template>

<style scoped>
.cms-product-content :deep(p) {
    margin-bottom: 1rem;
}

.cms-product-content :deep(ul),
.cms-product-content :deep(ol) {
    margin: 1rem 1.25rem;
    padding: 0 1rem;
}

.cms-product-content :deep(img) {
    margin: 1.5rem auto;
    max-width: 100%;
    border-radius: 0.75rem;
}
</style>
