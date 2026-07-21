<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import ImageUploader from '@/Components/ImageUploader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const iconOptions = [
    { label: 'ارسال', value: 'truck', icon: 'pi pi-truck' },
    { label: 'ضمانت', value: 'shield', icon: 'pi pi-shield' },
    { label: 'پشتیبانی', value: 'headset', icon: 'pi pi-headphones' },
    { label: 'بازگشت', value: 'rotate', icon: 'pi pi-refresh' },
    { label: 'بسته‌بندی', value: 'package', icon: 'pi pi-box' },
    { label: 'پرداخت', value: 'credit-card', icon: 'pi pi-credit-card' },
    { label: 'موقعیت', value: 'map-pin', icon: 'pi pi-map-marker' },
    { label: 'تلفن', value: 'phone', icon: 'pi pi-phone' },
    { label: 'ایمیل', value: 'mail', icon: 'pi pi-envelope' },
    { label: 'ساعت کاری', value: 'clock', icon: 'pi pi-clock' },
    { label: 'ویژه', value: 'star', icon: 'pi pi-star' },
    { label: 'خدمات', value: 'wrench', icon: 'pi pi-wrench' },
    { label: 'روغن', value: 'oil', icon: 'pi pi-filter' },
    { label: 'موتورسیکلت', value: 'motorcycle', icon: 'pi pi-car' },
];

const tabs = [
    { key: 'general', label: 'عمومی', icon: 'pi pi-home' },
    { key: 'topbar', label: 'نوار بالای سایت', icon: 'pi pi-window-maximize' },
    { key: 'contact', label: 'اطلاعات تماس', icon: 'pi pi-phone' },
    { key: 'footer', label: 'فوتر', icon: 'pi pi-align-justify' },
    { key: 'social', label: 'شبکه‌های اجتماعی', icon: 'pi pi-share-alt' },
    { key: 'service_features', label: 'مزیت‌های فروشگاه', icon: 'pi pi-star' },
];

tabs.splice(3, 0, { key: 'page_heroes', label: 'Hero صفحه تماس با ما', icon: 'pi pi-image' });

const textPositionOptions = [
    { label: 'راست', value: 'right' },
    { label: 'وسط', value: 'center' },
    { label: 'چپ', value: 'left' },
];

const activeTab = ref('general');

const makeKey = () => {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    return `local-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

const settingsFor = (group, fallback = {}) => props.settings?.[group] ?? fallback;

const normalizeItems = (items, itemFallback) => (Array.isArray(items) ? items : [])
    .map((item, index) => ({
        client_key: item.client_key ?? makeKey(),
        ...itemFallback,
        ...item,
        is_active: item.is_active ?? true,
        sort_order: item.sort_order ?? index + 1,
    }));

const form = useForm({
    general: {
        site_name: settingsFor('general').site_name ?? '',
        site_description: settingsFor('general').site_description ?? '',
        logo: settingsFor('general').logo ?? null,
    },
    topbar: {
        items: normalizeItems(settingsFor('topbar').items, {
            title: '',
            description: '',
            icon: 'truck',
            is_active: true,
            sort_order: 1,
        }),
    },
    contact: {
        phone: settingsFor('contact').phone ?? '',
        mobile: settingsFor('contact').mobile ?? '',
        email: settingsFor('contact').email ?? '',
        address: settingsFor('contact').address ?? '',
        working_hours: settingsFor('contact').working_hours ?? '',
    },
    page_heroes: {
        contact: {
            image_url: settingsFor('page_heroes').contact?.image_url ?? null,
            image_media: settingsFor('page_heroes').contact?.image_media ?? null,
            title: settingsFor('page_heroes').contact?.title ?? 'تماس با ما',
            subtitle: settingsFor('page_heroes').contact?.subtitle ?? '',
            description: settingsFor('page_heroes').contact?.description ?? '',
            badge: settingsFor('page_heroes').contact?.badge ?? '',
            overlay_opacity: settingsFor('page_heroes').contact?.overlay_opacity ?? 60,
            text_position: settingsFor('page_heroes').contact?.text_position ?? 'right',
            is_active: settingsFor('page_heroes').contact?.is_active ?? true,
        },
    },
    footer: {
        description: settingsFor('footer').description ?? '',
        copyright: settingsFor('footer').copyright ?? '',
        links: normalizeItems(settingsFor('footer').links, {
            title: '',
            url: '',
            is_active: true,
            sort_order: 1,
        }),
    },
    social: {
        instagram: settingsFor('social').instagram ?? '',
        telegram: settingsFor('social').telegram ?? '',
        whatsapp: settingsFor('social').whatsapp ?? '',
        linkedin: settingsFor('social').linkedin ?? '',
    },
    service_features: {
        items: normalizeItems(settingsFor('service_features').items, {
            title: '',
            description: '',
            icon: 'truck',
            is_active: true,
            sort_order: 1,
        }),
    },
});

const activeTabMeta = computed(() => tabs.find((tab) => tab.key === activeTab.value) ?? tabs[0]);
const logoExistingImages = computed(() => {
    const media = settingsFor('general').logo_media;
    const url = media?.url ?? settingsFor('general').logo_url;

    return url ? [{
        id: media?.id ?? 'site-logo',
        name: media?.name ?? 'لوگوی فعلی سایت',
        url,
        size: media?.size,
    }] : [];
});

const contactHeroExistingImages = computed(() => {
    const hero = settingsFor('page_heroes').contact ?? {};
    const media = hero.image_media_data;
    const url = media?.url ?? hero.image_url;

    return url ? [{
        id: media?.id ?? hero.image_media ?? 'contact-hero',
        name: media?.name ?? 'تصویر فعلی Hero تماس با ما',
        url,
        size: media?.size,
    }] : [];
});

const iconMeta = (value) => iconOptions.find((option) => option.value === value) ?? iconOptions[0];

const errorFor = (path) => form.errors[path];

const syncSortOrders = (items) => {
    items.forEach((item, index) => {
        item.sort_order = index + 1;
    });
};

const addTopbarItem = () => {
    form.topbar.items.push({
        client_key: makeKey(),
        title: '',
        description: '',
        icon: 'truck',
        is_active: true,
        sort_order: form.topbar.items.length + 1,
    });
};

const addFooterLink = () => {
    form.footer.links.push({
        client_key: makeKey(),
        title: '',
        url: '',
        is_active: true,
        sort_order: form.footer.links.length + 1,
    });
};

const addServiceFeature = () => {
    form.service_features.items.push({
        client_key: makeKey(),
        title: '',
        description: '',
        icon: 'truck',
        is_active: true,
        sort_order: form.service_features.items.length + 1,
    });
};

const removeItem = (items, index) => {
    items.splice(index, 1);
    syncSortOrders(items);
};

const moveItem = (items, index, direction) => {
    const target = index + direction;

    if (target < 0 || target >= items.length) {
        return;
    }

    const [item] = items.splice(index, 1);
    items.splice(target, 0, item);
    syncSortOrders(items);
};

const cleanItems = (items) => items.map(({ client_key, ...item }) => item);

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            general: {
                site_name: data.general.site_name,
                site_description: data.general.site_description,
                logo: data.general.logo,
            },
            page_heroes: {
                contact: {
                    image_url: data.page_heroes.contact.image_url,
                    image_media: data.page_heroes.contact.image_media,
                    title: data.page_heroes.contact.title,
                    subtitle: data.page_heroes.contact.subtitle,
                    description: data.page_heroes.contact.description,
                    badge: data.page_heroes.contact.badge,
                    overlay_opacity: data.page_heroes.contact.overlay_opacity,
                    text_position: data.page_heroes.contact.text_position,
                    is_active: data.page_heroes.contact.is_active,
                },
            },
            topbar: {
                ...data.topbar,
                items: cleanItems(data.topbar.items),
            },
            footer: {
                ...data.footer,
                links: cleanItems(data.footer.links),
            },
            service_features: {
                ...data.service_features,
                items: cleanItems(data.service_features.items),
            },
        }))
        .put('/admin/site-settings', {
            preserveScroll: true,
        });
};
</script>

<template>
    <Head title="تنظیمات سایت" />

    <AppLayout>
        <TopNavTitle
            title="تنظیمات سایت"
            :breadcrumb="[{ label: 'تنظیمات سایت' }]"
        >
            <template #pageAction>
                <Button
                    label="ذخیره تنظیمات"
                    icon="pi pi-save"
                    :loading="form.processing"
                    :disabled="form.processing"
                    @click="submit"
                />
            </template>
        </TopNavTitle>

        <form class="space-y-4" dir="rtl" @submit.prevent="submit">
            <Card>
                <template #content>
                    <div>
                        <div>
                            <h2 class="m-0 text-xl font-black text-surface-900">مدیریت تنظیمات سایت</h2>
                            <p class="mt-2 text-sm text-surface-500">
                                تنظیمات عمومی، نوار بالا، تماس، فوتر، شبکه‌های اجتماعی و مزیت‌ها از همین صفحه مدیریت می‌شوند.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2 border-t border-surface-100 pt-4">
                        <Button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            :label="tab.label"
                            :icon="tab.icon"
                            :outlined="activeTab !== tab.key"
                            :severity="activeTab === tab.key ? 'primary' : 'secondary'"
                            class="tab-action"
                            @click="activeTab = tab.key"
                        />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>
                    <div class="flex items-center gap-2">
                        <i :class="activeTabMeta.icon"></i>
                        <span>{{ activeTabMeta.label }}</span>
                    </div>
                </template>

                <template #content>
                    <div v-if="activeTab === 'general'" class="grid gap-4 lg:grid-cols-2">
                        <div class="field">
                            <label class="mb-2 block font-bold">نام سایت</label>
                            <InputText v-model="form.general.site_name" class="w-full" :invalid="Boolean(errorFor('general.site_name'))" />
                            <small v-if="errorFor('general.site_name')" class="text-red-600">{{ errorFor('general.site_name') }}</small>
                        </div>

                        <div class="field">
                            <label class="mb-2 block font-bold">لوگو</label>
                            <div v-if="false" class="flex min-h-12 items-center justify-between rounded-xl border border-dashed border-surface-300 px-4 py-3 text-sm text-surface-500">
                                <span>{{ form.general.logo || 'لوگو هنوز از این فرم آپلود نمی‌شود' }}</span>
                                <Tag value="آماده برای مرحله بعد" severity="secondary" />
                            </div>
                            <ImageUploader
                                v-model="form.general.logo"
                                title="لوگوی سایت"
                                subtitle="لوگوی سایت را از مرکز رسانه انتخاب یا تصویر جدید آپلود کنید"
                                emptyText="هنوز لوگویی برای سایت انتخاب نشده است."
                                mode="single"
                                :existingImages="logoExistingImages"
                                :error="errorFor('general.logo')"
                                :sortable="false"
                            />
                        </div>

                        <div class="field lg:col-span-2">
                            <label class="mb-2 block font-bold">توضیح کوتاه سایت</label>
                            <Textarea v-model="form.general.site_description" rows="4" class="w-full" :invalid="Boolean(errorFor('general.site_description'))" />
                            <small v-if="errorFor('general.site_description')" class="text-red-600">{{ errorFor('general.site_description') }}</small>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'topbar'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="m-0 text-sm text-surface-500">آیتم‌های نوار بالای سایت را مدیریت کنید.</p>
                            <Button type="button" label="افزودن آیتم" icon="pi pi-plus" outlined @click="addTopbarItem" />
                        </div>

                        <div v-if="!form.topbar.items.length" class="rounded-xl border border-dashed border-surface-300 p-6 text-center text-surface-500">
                            هنوز آیتمی برای نوار بالا ثبت نشده است.
                        </div>

                        <div v-for="(item, index) in form.topbar.items" :key="item.client_key" class="rounded-2xl border border-surface-200 p-4">
                            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary">
                                        <i :class="iconMeta(item.icon).icon"></i>
                                    </span>
                                    <div>
                                        <strong>آیتم {{ index + 1 }}</strong>
                                        <p class="m-0 text-xs text-surface-500">{{ iconMeta(item.icon).label }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button type="button" icon="pi pi-arrow-up" rounded text :disabled="index === 0" @click="moveItem(form.topbar.items, index, -1)" />
                                    <Button type="button" icon="pi pi-arrow-down" rounded text :disabled="index === form.topbar.items.length - 1" @click="moveItem(form.topbar.items, index, 1)" />
                                    <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeItem(form.topbar.items, index)" />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-5">
                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">عنوان</label>
                                    <InputText v-model="item.title" class="w-full" :invalid="Boolean(errorFor(`topbar.items.${index}.title`))" />
                                    <small v-if="errorFor(`topbar.items.${index}.title`)" class="text-red-600">{{ errorFor(`topbar.items.${index}.title`) }}</small>
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">آیکن</label>
                                    <Select v-model="item.icon" :options="iconOptions" optionLabel="label" optionValue="value" class="w-full" />
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">ترتیب</label>
                                    <InputNumber v-model="item.sort_order" class="w-full" :min="0" />
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">وضعیت</label>
                                    <div class="flex h-12 items-center gap-3">
                                        <ToggleSwitch v-model="item.is_active" />
                                        <span>{{ item.is_active ? 'فعال' : 'غیرفعال' }}</span>
                                    </div>
                                </div>

                                <div class="field lg:col-span-5">
                                    <label class="mb-2 block font-bold">توضیح اختیاری</label>
                                    <InputText v-model="item.description" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'contact'" class="grid gap-4 lg:grid-cols-2">
                        <div class="field">
                            <label class="mb-2 block font-bold">تلفن ثابت</label>
                            <InputText v-model="form.contact.phone" dir="ltr" class="w-full text-left" placeholder="021..." />
                        </div>
                        <div class="field">
                            <label class="mb-2 block font-bold">موبایل</label>
                            <InputText v-model="form.contact.mobile" dir="ltr" class="w-full text-left" placeholder="09..." />
                        </div>
                        <div class="field">
                            <label class="mb-2 block font-bold">ایمیل</label>
                            <InputText v-model="form.contact.email" dir="ltr" class="w-full text-left" placeholder="info@example.com" :invalid="Boolean(errorFor('contact.email'))" />
                            <small v-if="errorFor('contact.email')" class="text-red-600">{{ errorFor('contact.email') }}</small>
                        </div>
                        <div class="field">
                            <label class="mb-2 block font-bold">ساعات کاری</label>
                            <InputText v-model="form.contact.working_hours" class="w-full" placeholder="شنبه تا چهارشنبه، ۹ تا ۱۸" />
                        </div>
                        <div class="field lg:col-span-2">
                            <label class="mb-2 block font-bold">آدرس</label>
                            <Textarea v-model="form.contact.address" rows="4" class="w-full" />
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'page_heroes'" class="space-y-5">
                        <div class="rounded-2xl border border-surface-200 p-4">
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="m-0 text-lg font-black">Hero صفحه تماس با ما</h3>
                                    <p class="mt-1 text-sm text-surface-500">
                                        تصویر، متن و وضعیت نمایش Hero صفحه تماس با ما را مدیریت کنید.
                                    </p>
                                </div>
                                <div class="flex h-12 items-center gap-3">
                                    <ToggleSwitch v-model="form.page_heroes.contact.is_active" />
                                    <span>{{ form.page_heroes.contact.is_active ? 'فعال' : 'غیرفعال' }}</span>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">تصویر پس‌زمینه</label>
                                    <ImageUploader
                                        v-model="form.page_heroes.contact.image_media"
                                        title="تصویر Hero تماس با ما"
                                        subtitle="تصویر پس‌زمینه Hero را از مرکز رسانه انتخاب یا تصویر جدید آپلود کنید"
                                        emptyText="هنوز تصویری برای Hero تماس با ما انتخاب نشده است."
                                        mode="single"
                                        :existingImages="contactHeroExistingImages"
                                        :error="errorFor('page_heroes.contact.image_media')"
                                        :sortable="false"
                                    />
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">Badge</label>
                                    <InputText v-model="form.page_heroes.contact.badge" class="w-full" :invalid="Boolean(errorFor('page_heroes.contact.badge'))" />
                                    <small v-if="errorFor('page_heroes.contact.badge')" class="text-red-600">{{ errorFor('page_heroes.contact.badge') }}</small>
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">Title</label>
                                    <InputText v-model="form.page_heroes.contact.title" class="w-full" :invalid="Boolean(errorFor('page_heroes.contact.title'))" />
                                    <small v-if="errorFor('page_heroes.contact.title')" class="text-red-600">{{ errorFor('page_heroes.contact.title') }}</small>
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">Subtitle</label>
                                    <InputText v-model="form.page_heroes.contact.subtitle" class="w-full" :invalid="Boolean(errorFor('page_heroes.contact.subtitle'))" />
                                    <small v-if="errorFor('page_heroes.contact.subtitle')" class="text-red-600">{{ errorFor('page_heroes.contact.subtitle') }}</small>
                                </div>

                                <div class="field">
                                    <label class="mb-2 block font-bold">Text Position</label>
                                    <Select
                                        v-model="form.page_heroes.contact.text_position"
                                        :options="textPositionOptions"
                                        optionLabel="label"
                                        optionValue="value"
                                        class="w-full"
                                        :invalid="Boolean(errorFor('page_heroes.contact.text_position'))"
                                    />
                                    <small v-if="errorFor('page_heroes.contact.text_position')" class="text-red-600">{{ errorFor('page_heroes.contact.text_position') }}</small>
                                </div>

                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">Description</label>
                                    <Textarea v-model="form.page_heroes.contact.description" rows="4" class="w-full" :invalid="Boolean(errorFor('page_heroes.contact.description'))" />
                                    <small v-if="errorFor('page_heroes.contact.description')" class="text-red-600">{{ errorFor('page_heroes.contact.description') }}</small>
                                </div>

                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">Overlay Opacity: {{ form.page_heroes.contact.overlay_opacity ?? 0 }}%</label>
                                    <Slider v-model="form.page_heroes.contact.overlay_opacity" :min="0" :max="100" class="mt-4" />
                                    <InputNumber v-model="form.page_heroes.contact.overlay_opacity" class="mt-4 w-full max-w-48" :min="0" :max="100" />
                                    <small v-if="errorFor('page_heroes.contact.overlay_opacity')" class="text-red-600">{{ errorFor('page_heroes.contact.overlay_opacity') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'footer'" class="space-y-4">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="field lg:col-span-2">
                                <label class="mb-2 block font-bold">توضیحات فوتر</label>
                                <Textarea v-model="form.footer.description" rows="4" class="w-full" />
                            </div>
                            <div class="field lg:col-span-2">
                                <label class="mb-2 block font-bold">متن کپی‌رایت</label>
                                <InputText v-model="form.footer.copyright" class="w-full" />
                            </div>
                        </div>

                        <Divider />

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="m-0 text-lg font-black">لینک‌های فوتر</h3>
                            <Button type="button" label="افزودن لینک" icon="pi pi-plus" outlined @click="addFooterLink" />
                        </div>

                        <div v-if="!form.footer.links.length" class="rounded-xl border border-dashed border-surface-300 p-6 text-center text-surface-500">
                            هنوز لینکی برای فوتر ثبت نشده است.
                        </div>

                        <div v-for="(link, index) in form.footer.links" :key="link.client_key" class="rounded-2xl border border-surface-200 p-4">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <strong>لینک {{ index + 1 }}</strong>
                                <div class="flex flex-wrap gap-2">
                                    <Button type="button" icon="pi pi-arrow-up" rounded text :disabled="index === 0" @click="moveItem(form.footer.links, index, -1)" />
                                    <Button type="button" icon="pi pi-arrow-down" rounded text :disabled="index === form.footer.links.length - 1" @click="moveItem(form.footer.links, index, 1)" />
                                    <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeItem(form.footer.links, index)" />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-5">
                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">عنوان</label>
                                    <InputText v-model="link.title" class="w-full" />
                                </div>
                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">URL</label>
                                    <InputText v-model="link.url" dir="ltr" class="w-full text-left" placeholder="/page/about-us" />
                                </div>
                                <div class="field">
                                    <label class="mb-2 block font-bold">وضعیت</label>
                                    <div class="flex h-12 items-center gap-3">
                                        <ToggleSwitch v-model="link.is_active" />
                                        <span>{{ link.is_active ? 'فعال' : 'غیرفعال' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'social'" class="grid gap-4 lg:grid-cols-2">
                        <IconField>
                            <InputIcon><i class="pi pi-instagram" /></InputIcon>
                            <InputText v-model="form.social.instagram" dir="ltr" class="w-full text-left" placeholder="https://instagram.com/motopart" />
                        </IconField>
                        <IconField>
                            <InputIcon><i class="pi pi-send" /></InputIcon>
                            <InputText v-model="form.social.telegram" dir="ltr" class="w-full text-left" placeholder="https://t.me/motopart" />
                        </IconField>
                        <IconField>
                            <InputIcon><i class="pi pi-whatsapp" /></InputIcon>
                            <InputText v-model="form.social.whatsapp" dir="ltr" class="w-full text-left" placeholder="https://wa.me/989..." />
                        </IconField>
                        <IconField>
                            <InputIcon><i class="pi pi-linkedin" /></InputIcon>
                            <InputText v-model="form.social.linkedin" dir="ltr" class="w-full text-left" placeholder="https://linkedin.com/company/motopart" />
                        </IconField>
                    </div>

                    <div v-else-if="activeTab === 'service_features'" class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="m-0 text-sm text-surface-500">مزیت‌هایی که در سایت به کاربر نمایش داده می‌شوند.</p>
                            <Button type="button" label="افزودن مزیت" icon="pi pi-plus" outlined @click="addServiceFeature" />
                        </div>

                        <div v-if="!form.service_features.items.length" class="rounded-xl border border-dashed border-surface-300 p-6 text-center text-surface-500">
                            هنوز مزیتی ثبت نشده است.
                        </div>

                        <div v-for="(feature, index) in form.service_features.items" :key="feature.client_key" class="rounded-2xl border border-surface-200 p-4">
                            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-center gap-3 rounded-xl bg-surface-50 p-3">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-primary">
                                        <i :class="iconMeta(feature.icon).icon"></i>
                                    </span>
                                    <div>
                                        <strong>{{ feature.title || `مزیت ${index + 1}` }}</strong>
                                        <p class="m-0 text-xs text-surface-500">{{ feature.description || 'توضیح کوتاه مزیت اینجا نمایش داده می‌شود.' }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button type="button" icon="pi pi-arrow-up" rounded text :disabled="index === 0" @click="moveItem(form.service_features.items, index, -1)" />
                                    <Button type="button" icon="pi pi-arrow-down" rounded text :disabled="index === form.service_features.items.length - 1" @click="moveItem(form.service_features.items, index, 1)" />
                                    <Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeItem(form.service_features.items, index)" />
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-5">
                                <div class="field lg:col-span-2">
                                    <label class="mb-2 block font-bold">عنوان</label>
                                    <InputText v-model="feature.title" class="w-full" :invalid="Boolean(errorFor(`service_features.items.${index}.title`))" />
                                    <small v-if="errorFor(`service_features.items.${index}.title`)" class="text-red-600">{{ errorFor(`service_features.items.${index}.title`) }}</small>
                                </div>
                                <div class="field">
                                    <label class="mb-2 block font-bold">آیکن</label>
                                    <Select v-model="feature.icon" :options="iconOptions" optionLabel="label" optionValue="value" class="w-full" />
                                </div>
                                <div class="field">
                                    <label class="mb-2 block font-bold">ترتیب</label>
                                    <InputNumber v-model="feature.sort_order" class="w-full" :min="0" />
                                </div>
                                <div class="field">
                                    <label class="mb-2 block font-bold">وضعیت</label>
                                    <div class="flex h-12 items-center gap-3">
                                        <ToggleSwitch v-model="feature.is_active" />
                                        <span>{{ feature.is_active ? 'فعال' : 'غیرفعال' }}</span>
                                    </div>
                                </div>
                                <div class="field lg:col-span-5">
                                    <label class="mb-2 block font-bold">توضیح</label>
                                    <Textarea v-model="feature.description" rows="3" class="w-full" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </form>
    </AppLayout>
</template>

<style scoped>
.tab-action {
    white-space: nowrap;
}
</style>
