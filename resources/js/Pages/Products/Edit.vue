<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import TagInput from '@/Components/TagInput.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    posts: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] },
    vehicles: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] }
});

const form = useForm({
    _method: 'put',
    name: props.product.name,
    slug: props.product.slug,
    meta_title: props.product.meta_title ?? '',
    meta_description: props.product.meta_description ?? '',
    meta_keywords: Array.isArray(props.product.meta_keywords) ? props.product.meta_keywords : [],
    canonical_url: props.product.canonical_url ?? '',
    seo_index: props.product.seo_index ?? true,
    seo_follow: props.product.seo_follow ?? true,
    description: props.product.description,
    brand_id: props.product.brand_id,
    category_id: props.product.category_id,
    material: props.product.material,
    origin: props.product.origin,
    sku: props.product.sku,
    barcode: props.product.barcode,
    price: Number(props.product.price ?? 0),
    discount_price: props.product.discount_price ? Number(props.product.discount_price) : null,
    currency: props.product.currency ?? 'IRR',
    main_image: null,
    remove_main_image: false,
    gallery_images: [],
    status: props.product.status,
    is_featured: Boolean(props.product.is_featured),
    type: props.product.type,
    stock: props.product.stock ?? 0,
    related_post_ids: props.product.related_post_ids ?? [],
    vehicle_ids: props.product.vehicle_ids ?? [],
    variants: props.product.variants?.map((variant) => ({
        id: variant.id,
        sku: variant.sku,
        price: variant.price ? Number(variant.price) : null,
        discount_price: variant.discount_price ? Number(variant.discount_price) : null,
        stock: variant.stock ?? 0,
        image: null,
        remove_image: false,
        image_url: variant.image_url,
        attribute_values: variant.attribute_values ?? []
    })) ?? [],
    deleted_image_ids: [],
    deleted_variant_ids: []
});

const images = computed(() => (props.product.images ?? []).filter((image) => !form.deleted_image_ids.includes(image.id)));
const mainImage = computed(() => {
    if (!props.product.main_image_url || form.main_image || form.remove_main_image) {
        return [];
    }

    return [{
        id: 'current-main',
        url: props.product.main_image_url,
        name: 'تصویر اصلی فعلی'
    }];
});
const seoTitle = computed(() => form.meta_title || form.name || 'عنوان محصول');
const seoDescription = computed(() => form.meta_description || form.description || 'توضیحات کوتاه محصول در این قسمت نمایش داده می‌شود.');
const seoUrl = computed(() => `${window.location.origin}/products/${form.slug || props.product.slug || 'نامک-خودکار'}`);
const attributeValueOptions = computed(() => props.attributes.flatMap((attribute) =>
    attribute.values.map((value) => ({
        label: `${attribute.name}: ${value.value}`,
        value: value.id
    }))
));
const vehicleGroups = computed(() => {
    const map = new Map();
    props.vehicles.forEach((vehicle) => {
        const typeName = vehicle.brand?.vehicle_type?.name ?? vehicle.vehicle_type ?? 'سایر';
        const brand = vehicle.brand?.name ?? vehicle.brand ?? 'سایر';
        const group = `${typeName} / ${brand}`;
        if (!map.has(group)) map.set(group, []);
        map.get(group).push({ label: vehicle.name, value: vehicle.id });
    });
    return Array.from(map.entries()).map(([label, items]) => ({ label, items }));
});
const remoteVehicleGroups = ref([]);
const vehicleLoading = ref(false);
const vehicleGroupsResolved = computed(() => remoteVehicleGroups.value.length ? remoteVehicleGroups.value : vehicleGroups.value);

const loadVehicleOptions = async (query = '') => {
    vehicleLoading.value = true;
    try {
        const { data } = await axios.get('/admin/api/vehicles/options', {
            params: { q: query || undefined, limit: 100 },
        });
        const map = new Map();
        (Array.isArray(data) ? data : []).forEach((vehicle) => {
            const group = `${vehicle.vehicle_type ?? 'سایر'} / ${vehicle.brand ?? 'سایر'}`;
            if (!map.has(group)) map.set(group, []);
            map.get(group).push({ label: vehicle.label ?? '', value: vehicle.id });
        });
        remoteVehicleGroups.value = Array.from(map.entries()).map(([label, items]) => ({ label, items }));
    } finally {
        vehicleLoading.value = false;
    }
};

const onVehicleFilter = (event) => {
    loadVehicleOptions(event.value ?? '');
};

onMounted(() => {
    loadVehicleOptions();
});

const addVariant = () => {
    form.variants.push({ sku: '', price: null, discount_price: null, stock: 0, image: null, remove_image: false, attribute_values: [] });
};

const removeVariant = (index) => {
    const variant = form.variants[index];
    if (variant.id) {
        form.deleted_variant_ids.push(variant.id);
    }
    form.variants.splice(index, 1);
};

const removeImage = (image) => {
    form.deleted_image_ids.push(image.id);
};

const removeMainImage = () => {
    form.main_image = null;
    form.remove_main_image = true;
};

const setMainImage = (file) => {
    form.main_image = file;
    form.remove_main_image = false;
};

const variantExistingImage = (variant) => {
    if (!variant.image_url || variant.image || variant.remove_image) {
        return [];
    }

    return [{ id: `variant-${variant.id}`, url: variant.image_url, name: 'تصویر فعلی تنوع' }];
};

const setVariantImage = (variant, file) => {
    variant.image = file;
    variant.remove_image = false;
};

const removeVariantImage = (variant) => {
    variant.image = null;
    variant.remove_image = true;
};

const submit = () => {
    form.post(`/admin/products/${props.product.slug}`, { forceFormData: true });
};

const errorFor = (field) => form.errors[field];
</script>

<template>
    <Head :title="`ویرایش ${product.name}`" />

    <AppLayout>
        <TopNavTitle :title="`ویرایش ${product.name}`" :breadcrumb="[{ label: 'محصولات', href: '/admin/products' }, { label: 'ویرایش محصول' }]">
            <template #pageAction>
                <Link href="/admin/products">
                    <Button label="بازگشت" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">اطلاعات پایه</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <InputText v-model="form.name" placeholder="نام محصول" class="w-full" />
                        <small v-if="errorFor('name')" class="text-red-600">{{ errorFor('name') }}</small>
                    </div>
                    <div>
                        <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <small v-if="errorFor('status')" class="text-red-600">{{ errorFor('status') }}</small>
                    </div>
                    <Select v-model="form.brand_id" :options="brands" optionLabel="name" optionValue="id" showClear placeholder="برند" class="w-full" />
                    <Select v-model="form.category_id" :options="categories" optionLabel="name" optionValue="id" showClear placeholder="دسته‌بندی" class="w-full" />
                    <Select v-model="form.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                    <InputText v-model="form.material" placeholder="جنس" class="w-full" />
                    <InputText v-model="form.origin" placeholder="مبدا" class="w-full" />
                    <div>
                        <InputText v-model="form.sku" placeholder="کد کالا" class="w-full" />
                        <small v-if="errorFor('sku')" class="text-red-600">{{ errorFor('sku') }}</small>
                    </div>
                    <div>
                        <InputText v-model="form.barcode" placeholder="بارکد" class="w-full" />
                        <small v-if="errorFor('barcode')" class="text-red-600">{{ errorFor('barcode') }}</small>
                    </div>
                    <Textarea v-model="form.description" rows="4" placeholder="توضیحات" class="w-full md:col-span-3" />
                    <div class="md:col-span-3">
                        <label class="mb-2 block font-medium">مقالات مرتبط سئو</label>
                        <MultiSelect v-model="form.related_post_ids" :options="posts" optionLabel="title" optionValue="id" filter display="chip" class="w-full" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-2 block font-medium">سازگار با خودرو/موتور</label>
                        <MultiSelect
                            v-model="form.vehicle_ids"
                            :options="vehicleGroupsResolved"
                            optionGroupLabel="label"
                            optionGroupChildren="items"
                            optionLabel="label"
                            optionValue="value"
                            filter
                            :loading="vehicleLoading"
                            display="chip"
                            :virtualScrollerOptions="{ itemSize: 40 }"
                            class="w-full"
                            @filter="onVehicleFilter"
                        />
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">قیمت‌گذاری</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <InputNumber v-model="form.price" placeholder="قیمت" class="w-full" inputClass="w-full" :min="0" />
                        <small v-if="errorFor('price')" class="text-red-600">{{ errorFor('price') }}</small>
                    </div>
                    <div>
                        <InputNumber v-model="form.discount_price" placeholder="قیمت تخفیف" class="w-full" inputClass="w-full" :min="0" />
                        <small v-if="errorFor('discount_price')" class="text-red-600">{{ errorFor('discount_price') }}</small>
                    </div>
                    <InputText v-model="form.currency" placeholder="واحد پول" class="w-full" />
                    <InputNumber v-if="form.type === 'simple'" v-model="form.stock" placeholder="موجودی" class="w-full" inputClass="w-full" :min="0" />
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">تصاویر</h2>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div class="card xl:col-span-2">
                        <div class="flex items-center gap-3">
                            <ToggleSwitch v-model="form.is_featured" />
                            <div>
                                <label class="block font-medium">نمایش در محصولات منتخب MotoPart</label>
                                <small class="text-surface-500">در صفحه اصلی سایت، بخش محصولات منتخب نمایش داده می‌شود.</small>
                                <small v-if="form.errors.is_featured" class="block text-red-600">{{ form.errors.is_featured }}</small>
                            </div>
                        </div>
                    </div>
                    <ImageUploader
                        :modelValue="form.main_image"
                        title="تصویر اصلی"
                        mode="single"
                        :existingImages="mainImage"
                        :error="errorFor('main_image')"
                        @update:modelValue="setMainImage"
                        @remove-existing="removeMainImage"
                    />
                    <ImageUploader
                        v-model="form.gallery_images"
                        title="گالری تصاویر"
                        mode="multiple"
                        :existingImages="images"
                        :error="errorFor('gallery_images')"
                        @remove-existing="removeImage"
                    />
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">سئو</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">نامک</label>
                        <InputText v-model="form.slug" placeholder="اگر خالی باشد خودکار از نام ساخته می‌شود" class="w-full" />
                        <small class="text-surface-500">اگر خالی باشد خودکار از نام محصول ساخته می‌شود.</small>
                        <small v-if="errorFor('slug')" class="block text-red-600">{{ errorFor('slug') }}</small>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">نشانی اصلی</label>
                        <InputText v-model="form.canonical_url" placeholder="https://example.com/p/123" class="w-full" />
                        <small v-if="errorFor('canonical_url')" class="text-red-600">{{ errorFor('canonical_url') }}</small>
                    </div>
                    <div>
                        <div class="mb-2 flex justify-between">
                            <label class="font-medium">عنوان سئو</label>
                            <small :class="form.meta_title.length > 60 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_title.length }}/60</small>
                        </div>
                        <InputText v-model="form.meta_title" class="w-full" />
                    </div>
                    <div>
                        <div class="mb-2 flex justify-between">
                            <label class="font-medium">توضیحات سئو</label>
                            <small :class="form.meta_description.length > 160 ? 'text-red-600' : 'text-surface-500'">{{ form.meta_description.length }}/160</small>
                        </div>
                        <Textarea v-model="form.meta_description" rows="3" class="w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block font-medium">کلمات کلیدی</label>
                        <TagInput v-model="form.meta_keywords" />
                        <small v-if="errorFor('meta_keywords')" class="text-red-600">{{ errorFor('meta_keywords') }}</small>
                    </div>
                    <div class="flex items-center gap-3">
                        <ToggleSwitch v-model="form.seo_index" />
                        <span>اجازه نمایه‌سازی</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <ToggleSwitch v-model="form.seo_follow" />
                        <span>اجازه دنبال‌کردن لینک‌ها</span>
                    </div>
                </div>
                <div class="mt-5 rounded-md border border-surface-200 p-4">
                    <div class="text-lg text-blue-700">{{ seoTitle }}</div>
                    <div class="text-sm text-green-700">{{ seoUrl }}</div>
                    <p class="mt-1 text-sm text-surface-600">{{ seoDescription }}</p>
                </div>
            </div>

            <div v-if="form.type === 'variable'" class="card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">تنوع‌های محصول</h2>
                    <Button type="button" label="افزودن تنوع" icon="pi pi-plus" severity="secondary" @click="addVariant" />
                </div>
                <div v-for="(variant, index) in form.variants" :key="variant.id ?? index" class="mb-4 rounded-md border border-surface-200 p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <img v-if="variant.image_url && !variant.image && !variant.remove_image" :src="variant.image_url" class="h-14 w-14 rounded-md object-cover" alt="تنوع محصول" />
                        <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeVariant(index)" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <InputText v-model="variant.sku" placeholder="کد کالا" class="w-full" />
                        <InputNumber v-model="variant.price" placeholder="قیمت" class="w-full" inputClass="w-full" :min="0" />
                        <InputNumber v-model="variant.discount_price" placeholder="قیمت تخفیف" class="w-full" inputClass="w-full" :min="0" />
                        <InputNumber v-model="variant.stock" placeholder="موجودی" class="w-full" inputClass="w-full" :min="0" />
                        <MultiSelect v-model="variant.attribute_values" :options="attributeValueOptions" optionLabel="label" optionValue="value" filter display="chip" placeholder="ویژگی‌ها" class="w-full md:col-span-2" />
                        <div class="md:col-span-3">
                            <ImageUploader
                                :modelValue="variant.image"
                                title="تصویر تنوع"
                                mode="single"
                                :existingImages="variantExistingImage(variant)"
                                :error="errorFor(`variants.${index}.image`)"
                                @update:modelValue="setVariantImage(variant, $event)"
                                @remove-existing="removeVariantImage(variant)"
                            />
                        </div>
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
                <Link href="/admin/products">
                    <Button type="button" label="انصراف" severity="secondary" text />
                </Link>
                <Button type="submit" label="ذخیره تغییرات" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
