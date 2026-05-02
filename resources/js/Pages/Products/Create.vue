<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import TagInput from '@/Components/TagInput.vue';
import ProductImageUploader from '@/Components/ProductImageUploader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] }
});

const form = useForm({
    name: '',
    slug: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: [],
    canonical_url: '',
    seo_index: true,
    seo_follow: true,
    description: '',
    brand_id: null,
    category_id: null,
    material: '',
    origin: '',
    sku: '',
    barcode: '',
    price: 0,
    discount_price: null,
    currency: 'IRR',
    main_image: null,
    gallery_images: [],
    status: 'draft',
    type: 'simple',
    stock: 0,
    variants: []
});

const attributeValueOptions = computed(() => props.attributes.flatMap((attribute) =>
    attribute.values.map((value) => ({
        label: `${attribute.name}: ${value.value}`,
        value: value.id
    }))
));
const seoTitle = computed(() => form.meta_title || form.name || 'عنوان محصول');
const seoDescription = computed(() => form.meta_description || form.description || 'توضیحات کوتاه محصول در این قسمت نمایش داده می‌شود.');
const seoUrl = computed(() => `${window.location.origin}/products/${form.slug || 'slug-auto'}`);

const addVariant = () => {
    form.variants.push({
        sku: '',
        price: null,
        discount_price: null,
        stock: 0,
        image: null,
        attribute_values: []
    });
};

const removeVariant = (index) => {
    form.variants.splice(index, 1);
};

const submit = () => {
    form.post('/products', { forceFormData: true });
};

const errorFor = (field) => form.errors[field];
</script>

<template>
    <Head title="ایجاد محصول" />

    <AppLayout>
        <TopNavTitle title="ایجاد محصول" :breadcrumb="[{ label: 'محصولات', href: '/products' }, { label: 'ایجاد محصول' }]">
            <template #pageAction>
                <Link href="/products">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">اطلاعات پایه</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block font-medium">نام محصول</label>
                        <InputText v-model="form.name" class="w-full" :invalid="Boolean(form.errors.name)" />
                        <small class="text-red-600">{{ form.errors.name }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">وضعیت</label>
                        <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <small v-if="errorFor('status')" class="text-red-600">{{ errorFor('status') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">برند</label>
                        <Select v-model="form.brand_id" :options="brands" optionLabel="name" optionValue="id" showClear class="w-full" />
                        <small v-if="errorFor('brand_id')" class="text-red-600">{{ errorFor('brand_id') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">دسته‌بندی</label>
                        <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" showClear class="w-full" />
                        <small v-if="errorFor('category_id')" class="text-red-600">{{ errorFor('category_id') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">نوع محصول</label>
                        <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <small v-if="errorFor('type')" class="text-red-600">{{ errorFor('type') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">جنس</label>
                        <InputText v-model="form.material" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">مبدا</label>
                        <InputText v-model="form.origin" class="w-full" />
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">SKU</label>
                        <InputText v-model="form.sku" class="w-full" />
                        <small v-if="errorFor('sku')" class="text-red-600">{{ errorFor('sku') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">بارکد</label>
                        <InputText v-model="form.barcode" class="w-full" />
                        <small v-if="errorFor('barcode')" class="text-red-600">{{ errorFor('barcode') }}</small>
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-2 block font-medium">توضیحات</label>
                        <Textarea v-model="form.description" rows="4" class="w-full" />
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">قیمت‌گذاری</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-2 block font-medium">قیمت</label>
                        <InputNumber v-model="form.price" class="w-full" inputClass="w-full" :min="0" />
                        <small v-if="errorFor('price')" class="text-red-600">{{ errorFor('price') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">قیمت تخفیف</label>
                        <InputNumber v-model="form.discount_price" class="w-full" inputClass="w-full" :min="0" />
                        <small v-if="errorFor('discount_price')" class="text-red-600">{{ errorFor('discount_price') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">واحد پول</label>
                        <InputText v-model="form.currency" class="w-full" />
                    </div>
                    <div v-if="form.type === 'simple'">
                        <label class="mb-2 block font-medium">موجودی</label>
                        <InputNumber v-model="form.stock" class="w-full" inputClass="w-full" :min="0" />
                        <small v-if="errorFor('stock')" class="text-red-600">{{ errorFor('stock') }}</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">SEO</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">Slug</label>
                        <InputText v-model="form.slug" class="w-full" placeholder="اگر خالی باشد خودکار از نام ساخته می‌شود" />
                        <small class="text-surface-500">اگر خالی باشد خودکار از نام محصول ساخته می‌شود.</small>
                        <small v-if="errorFor('slug')" class="block text-red-600">{{ errorFor('slug') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">Canonical URL</label>
                        <InputText v-model="form.canonical_url" class="w-full" placeholder="https://example.com/product" />
                        <small v-if="errorFor('canonical_url')" class="text-red-600">{{ errorFor('canonical_url') }}</small>
                    </div>
                    <div>
                        <div class="mb-2 flex justify-between">
                            <label class="font-medium">Meta Title</label>
                            <small :class="form.meta_title.length > 60 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_title.length }}/60</small>
                        </div>
                        <InputText v-model="form.meta_title" class="w-full" />
                    </div>
                    <div>
                        <div class="mb-2 flex justify-between">
                            <label class="font-medium">Meta Description</label>
                            <small :class="form.meta_description.length > 160 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_description.length }}/160</small>
                        </div>
                        <Textarea v-model="form.meta_description" rows="3" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">Meta Keywords</label>
                        <TagInput v-model="form.meta_keywords" />
                        <small v-if="errorFor('meta_keywords')" class="text-red-600">{{ errorFor('meta_keywords') }}</small>
                    </div>
                    <div class="flex items-center gap-3">
                        <ToggleSwitch v-model="form.seo_index" />
                        <span>اجازه index</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <ToggleSwitch v-model="form.seo_follow" />
                        <span>اجازه follow</span>
                    </div>
                </div>
                <div class="mt-5 rounded-md border border-surface-200 p-4">
                    <div class="text-lg text-blue-700">{{ seoTitle }}</div>
                    <div class="text-sm text-green-700">{{ seoUrl }}</div>
                    <p class="mt-1 text-sm text-surface-600">{{ seoDescription }}</p>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">تصاویر</h2>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <ProductImageUploader
                        v-model="form.main_image"
                        title="تصویر اصلی"
                        mode="single"
                        :error="errorFor('main_image')"
                    />
                    <ProductImageUploader
                        v-model="form.gallery_images"
                        title="گالری تصاویر"
                        mode="multiple"
                        :error="errorFor('gallery_images')"
                    />
                </div>
            </div>

            <div v-if="form.type === 'variable'" class="card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">تنوع‌های محصول</h2>
                    <Button type="button" label="افزودن تنوع" icon="pi pi-plus" severity="secondary" @click="addVariant" />
                </div>
                <div v-for="(variant, index) in form.variants" :key="index" class="mb-4 rounded-md border border-surface-200 p-4">
                    <div class="mb-3 flex justify-end">
                        <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeVariant(index)" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <InputText v-model="variant.sku" placeholder="SKU" class="w-full" />
                        <InputNumber v-model="variant.price" placeholder="قیمت" class="w-full" inputClass="w-full" :min="0" />
                        <InputNumber v-model="variant.discount_price" placeholder="قیمت تخفیف" class="w-full" inputClass="w-full" :min="0" />
                        <InputNumber v-model="variant.stock" placeholder="موجودی" class="w-full" inputClass="w-full" :min="0" />
                        <MultiSelect v-model="variant.attribute_values" :options="attributeValueOptions" optionLabel="label" optionValue="value" filter display="chip" placeholder="ویژگی‌ها" class="w-full md:col-span-2" />
                        <FileUpload mode="basic" customUpload auto chooseLabel="تصویر تنوع" accept="image/*" @select="variant.image = $event.files[0]" />
                    </div>
                    <Message v-if="Object.keys(form.errors).some((key) => key.startsWith(`variants.${index}.`))" severity="error" class="mt-3">
                        اطلاعات این تنوع را بررسی کنید.
                    </Message>
                </div>
            </div>

            <Message v-if="Object.keys(form.errors).length" severity="error">
                لطفاً خطاهای فرم را بررسی کنید.
            </Message>

            <div class="flex justify-end gap-2">
                <Link href="/products">
                    <Button type="button" label="انصراف" severity="secondary" text />
                </Link>
                <Button type="submit" label="ذخیره محصول" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
