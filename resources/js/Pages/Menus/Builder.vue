<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SortableMenu from './Partials/SortableMenu.vue';
import { cloneMenuItem, normalizeMenuItems, normalizeMenuTreeOrder, normalizePlainValue } from './menuItemNormalizer';
import { router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    menus: { type: Array, default: () => [] },
    menu: { type: Object, default: null },
    locationOptions: { type: Array, default: () => [] },
    typeOptions: { type: Array, default: () => [] },
    targetOptions: { type: Array, default: () => [] },
    linkOptions: { type: Object, default: () => ({}) },
});

const toast = useToast();
const confirm = useConfirm();
const tree = ref(normalizeMenuItems(props.menu?.items ?? []));
const savingOrder = ref(false);
const itemDialog = ref(false);
const editingItem = ref(null);
const itemForm = reactive(defaultItemForm());

const typeLabels = {
    custom: 'لینک سفارشی',
    page: 'صفحه',
    category: 'دسته‌بندی',
    product: 'محصول',
    brand: 'برند',
    post: 'مقاله',
    external: 'لینک خارجی'
};

const targetLabels = {
    _self: 'همین پنجره',
    _blank: 'تب جدید'
};

const menuForm = useForm({
    name: props.menu?.name ?? '',
    slug: props.menu?.slug ?? '',
    location: props.menu?.location ?? 'header',
    description: props.menu?.description ?? '',
    is_active: props.menu?.is_active ?? true,
});

const newMenuForm = useForm({
    name: '',
    slug: '',
    location: 'header',
    description: '',
    is_active: true,
});

const selectedMenuSlug = ref(props.menu?.slug ?? null);
const hasMenu = computed(() => Boolean(props.menu));

watch(() => props.menu, (menu) => {
    tree.value = normalizeMenuItems(menu?.items ?? []);
    selectedMenuSlug.value = menu?.slug ?? null;
    menuForm.defaults({
        name: menu?.name ?? '',
        slug: menu?.slug ?? '',
        location: menu?.location ?? 'header',
        description: menu?.description ?? '',
        is_active: menu?.is_active ?? true,
    });
    menuForm.reset();
});

function defaultItemForm() {
    return {
        title: '',
        title_attribute: '',
        type: 'custom',
        reference_id: null,
        url: '',
        route_name: '',
        route_params: null,
        target: '_self',
        icon: '',
        css_class: '',
        rel: '',
        is_active: true,
    };
}

function resetItemForm(overrides = {}) {
    const defaults = defaultItemForm();

    Object.keys(itemForm).forEach((key) => {
        if (!(key in defaults)) {
            delete itemForm[key];
        }
    });

    Object.assign(itemForm, defaults, itemToFormValues(overrides));
    editingItem.value = null;
}

function selectMenu(slug) {
    if (slug) {
        router.visit(`/admin/menus/${slug}`);
    }
}

function createMenu() {
    newMenuForm.post('/admin/menus', {
        preserveScroll: true,
        onSuccess: () => newMenuForm.reset(),
    });
}

function updateMenu() {
    if (!props.menu) return;
    menuForm.put(`/admin/menus/${props.menu.slug}`, {
        preserveScroll: true,
    });
}

function openCustomDialog() {
    resetItemForm({ type: 'custom', target: '_self', is_active: true });
    itemDialog.value = true;
}

function openEditDialog(item) {
    const editableItem = cloneMenuItem(item);

    resetItemForm(editableItem);
    editingItem.value = { id: editableItem.id };
    itemDialog.value = true;
}

function addInternal(type, option) {
    if (!props.menu) return;

    axios.post(`/admin/menus/${props.menu.slug}/items`, {
        title: option.label,
        title_attribute: option.label,
        type,
        reference_id: option.id,
        url: option.slug,
        route_params: { slug: option.slug },
        target: '_self',
        is_active: true,
    }, { headers: { Accept: 'application/json' } })
        .then(() => reloadBuilder('آیتم به منو اضافه شد.'))
        .catch(showError);
}

function saveItem() {
    if (!props.menu) return;

    const payload = itemFormPayload();
    const request = editingItem.value
        ? axios.put(`/admin/menus/${props.menu.slug}/items/${editingItem.value.id}`, payload, { headers: { Accept: 'application/json' } })
        : axios.post(`/admin/menus/${props.menu.slug}/items`, payload, { headers: { Accept: 'application/json' } });

    request
        .then(() => {
            itemDialog.value = false;
            reloadBuilder(editingItem.value ? 'آیتم منو ویرایش شد.' : 'آیتم به منو اضافه شد.');
        })
        .catch(showError);
}

function removeItem(item) {
    if (!props.menu) return;

    confirm.require({
        message: `آیا از حذف «${item.title}» و زیرمنوهای آن مطمئن هستید؟`,
        header: 'حذف آیتم منو',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'حذف',
        rejectLabel: 'انصراف',
        accept: () => {
            axios.delete(`/admin/menus/${props.menu.slug}/items/${item.id}`, { headers: { Accept: 'application/json' } })
                .then(() => reloadBuilder('آیتم منو حذف شد.'))
                .catch(showError);
        },
    });
}

function saveOrder() {
    if (!props.menu) return;
    savingOrder.value = true;

    axios.post(`/admin/menus/${props.menu.slug}/items/reorder`, { items: normalizeMenuTreeOrder(tree.value) }, { headers: { Accept: 'application/json' } })
        .then(() => {
            toast.add({ severity: 'success', summary: 'ذخیره شد', detail: 'ترتیب و ساختار منو ذخیره شد.', life: 2500 });
        })
        .catch(showError)
        .finally(() => {
            savingOrder.value = false;
        });
}

function reloadBuilder(message = null) {
    if (message) {
        toast.add({ severity: 'success', summary: 'ذخیره شد', detail: message, life: 2500 });
    }
    router.reload({ only: ['menus', 'menu'] });
}

function showError(error) {
    const detail = error.response?.data?.message || Object.values(error.response?.data?.errors ?? {})?.[0]?.[0] || 'درخواست با خطا مواجه شد.';
    toast.add({ severity: 'error', summary: 'خطا', detail, life: 5000 });
}

function itemToFormValues(item) {
    const raw = cloneMenuItem(item);

    return {
        title: raw.title,
        title_attribute: raw.title_attribute,
        type: raw.type,
        reference_id: raw.reference_id,
        url: raw.url,
        route_name: raw.route_name ?? '',
        route_params: raw.route_params,
        target: raw.target,
        icon: raw.icon,
        css_class: raw.css_class,
        rel: raw.rel,
        is_active: raw.is_active,
    };
}

function itemFormPayload() {
    return {
        title: itemForm.title ?? '',
        title_attribute: itemForm.title_attribute ?? '',
        type: itemForm.type ?? 'custom',
        reference_id: itemForm.reference_id ?? null,
        url: itemForm.url ?? '',
        route_name: itemForm.route_name || null,
        route_params: normalizePlainValue(itemForm.route_params ?? null),
        target: itemForm.target ?? '_self',
        icon: itemForm.icon ?? '',
        css_class: itemForm.css_class ?? '',
        rel: itemForm.rel ?? '',
        is_active: itemForm.is_active ?? true,
    };
}

const internalGroups = computed(() => [
    { key: 'pages', type: 'page', title: 'صفحات', items: props.linkOptions.pages ?? [] },
    { key: 'categories', type: 'category', title: 'دسته‌بندی‌ها', items: props.linkOptions.categories ?? [] },
    { key: 'products', type: 'product', title: 'محصولات', items: props.linkOptions.products ?? [] },
    { key: 'brands', type: 'brand', title: 'برندها', items: props.linkOptions.brands ?? [] },
    { key: 'posts', type: 'post', title: 'مقالات', items: props.linkOptions.posts ?? [] },
]);

const treeTableNodes = computed(() => toTreeTableNodes(tree.value));

function toTreeTableNodes(items) {
    return items.map((item) => ({
        key: String(item.id),
        data: {
            title: item.title,
            type: typeLabels[item.type] || item.type,
            url: item.url || item.route_name || '',
            target: targetLabels[item.target] || item.target,
            status: item.is_active ? 'فعال' : 'غیرفعال',
        },
        children: toTreeTableNodes(item.children ?? []),
    }));
}
</script>

<template>
    <AppLayout>
        <ConfirmDialog />

        <div class="p-6" dir="rtl">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-surface-900">منوهای سایت</h1>
                    <p class="mt-1 text-sm text-surface-500">ساخت منوی داینامیک، چندسطحی و قابل جابجایی شبیه WordPress Menu Builder.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Select
                        v-model="selectedMenuSlug"
                        :options="menus"
                        optionLabel="name"
                        optionValue="slug"
                        placeholder="انتخاب منو"
                        class="w-64"
                        @change="selectMenu(selectedMenuSlug)"
                    />
                    <Button label="ذخیره تنظیمات منو" icon="pi pi-save" :disabled="!hasMenu" @click="updateMenu" />
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[25rem_1fr]">
                <aside class="space-y-4">
                    <div class="rounded-lg border border-surface-border bg-surface-card p-4">
                        <h2 class="mb-3 text-base font-bold">ساخت منوی جدید</h2>
                        <div class="space-y-3">
                            <InputText v-model="newMenuForm.name" class="w-full" placeholder="نام منو" />
                            <InputText v-model="newMenuForm.slug" class="w-full" placeholder="اسلاگ (اختیاری)" />
                            <Select v-model="newMenuForm.location" :options="locationOptions" optionLabel="label" optionValue="value" class="w-full" />
                            <Button label="ساخت منو" icon="pi pi-plus" class="w-full" :loading="newMenuForm.processing" @click="createMenu" />
                        </div>
                    </div>

                    <div class="rounded-lg border border-surface-border bg-surface-card p-4">
                        <h2 class="mb-3 text-base font-bold">افزودن آیتم به منو</h2>
                        <Accordion multiple>
                            <AccordionPanel value="custom">
                                <AccordionHeader>لینک سفارشی</AccordionHeader>
                                <AccordionContent>
                                    <Button label="افزودن لینک سفارشی" icon="pi pi-link" class="w-full" :disabled="!hasMenu" @click="openCustomDialog" />
                                </AccordionContent>
                            </AccordionPanel>

                            <AccordionPanel v-for="group in internalGroups" :key="group.key" :value="group.key">
                                <AccordionHeader>{{ group.title }}</AccordionHeader>
                                <AccordionContent>
                                    <div class="max-h-64 space-y-2 overflow-auto pr-1">
                                        <div v-for="option in group.items" :key="`${group.key}-${option.id}`" class="flex items-center justify-between gap-3 rounded border border-surface-border p-2">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-bold">{{ option.label }}</div>
                                                <div class="truncate text-xs text-surface-500">{{ option.url }}</div>
                                            </div>
                                            <Button icon="pi pi-plus" rounded text :disabled="!hasMenu" aria-label="افزودن" @click="addInternal(group.type, option)" />
                                        </div>
                                        <p v-if="!group.items.length" class="text-sm text-surface-500">موردی یافت نشد.</p>
                                    </div>
                                </AccordionContent>
                            </AccordionPanel>
                        </Accordion>
                    </div>
                </aside>

                <section class="space-y-4">
                    <div v-if="menu" class="rounded-lg border border-surface-border bg-surface-card p-4">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold">نام منو</label>
                                <InputText v-model="menuForm.name" class="w-full" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold">اسلاگ</label>
                                <InputText v-model="menuForm.slug" class="w-full" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold">موقعیت نمایش</label>
                                <Select v-model="menuForm.location" :options="locationOptions" optionLabel="label" optionValue="value" class="w-full" />
                            </div>
                            <div class="flex items-end gap-3">
                                <ToggleSwitch v-model="menuForm.is_active" />
                                <span class="pb-1 text-sm font-bold">منو فعال باشد</span>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="mb-2 block text-sm font-bold">توضیحات</label>
                                <Textarea v-model="menuForm.description" rows="2" class="w-full" />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-surface-border bg-surface-card">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-surface-border p-4">
                            <div>
                                <h2 class="text-lg font-black">ساختار منو</h2>
                                <p class="text-sm text-surface-500">آیتم‌ها را بکشید و رها کنید؛ برای ساخت زیرمنو، آیتم را داخل آیتم دیگر قرار دهید.</p>
                            </div>
                            <Button label="ذخیره ترتیب و زیرمنوها" icon="pi pi-sort-alt" :loading="savingOrder" :disabled="!hasMenu" @click="saveOrder" />
                        </div>

                        <div class="p-4">
                            <SortableMenu
                                v-if="hasMenu"
                                v-model:items="tree"
                                @edit="openEditDialog"
                                @remove="removeItem"
                            />
                            <div v-if="hasMenu && !tree.length" class="rounded border border-dashed border-surface-300 p-8 text-center text-surface-500">
                                از پنل سمت چپ آیتم اضافه کنید تا ساختار منو ساخته شود.
                            </div>
                            <div v-if="!hasMenu" class="rounded border border-dashed border-surface-300 p-8 text-center text-surface-500">
                                ابتدا یک منو بسازید.
                            </div>
                        </div>
                    </div>

                    <div v-if="hasMenu && tree.length" class="rounded-lg border border-surface-border bg-surface-card p-4">
                        <h2 class="mb-3 text-lg font-black">پیش‌نمایش درختی منو</h2>
                        <TreeTable :value="treeTableNodes" tableStyle="min-width: 42rem">
                            <Column field="title" header="عنوان" expander />
                            <Column field="type" header="نوع" />
                            <Column field="url" header="آدرس" />
                            <Column field="target" header="نحوه باز شدن" />
                            <Column field="status" header="وضعیت" />
                        </TreeTable>
                    </div>
                </section>
            </div>
        </div>

        <Dialog v-model:visible="itemDialog" modal :header="editingItem ? 'ویرایش آیتم منو' : 'افزودن آیتم منو'" :style="{ width: '44rem', maxWidth: '95vw' }">
            <div class="grid gap-4 md:grid-cols-2" dir="rtl">
                <div>
                    <label class="mb-2 block text-sm font-bold">برچسب نمایشی</label>
                    <InputText v-model="itemForm.title" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold">ویژگی title</label>
                    <InputText v-model="itemForm.title_attribute" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold">نوع لینک</label>
                    <Select v-model="itemForm.type" :options="typeOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold">نحوه باز شدن</label>
                    <Select v-model="itemForm.target" :options="targetOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-bold">آدرس لینک</label>
                    <InputText v-model="itemForm.url" class="w-full" placeholder="/about یا https://example.com" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold">آیکون</label>
                    <InputText v-model="itemForm.icon" class="w-full" placeholder="pi pi-home" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold">کلاس CSS</label>
                    <InputText v-model="itemForm.css_class" class="w-full" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold">Rel</label>
                    <InputText v-model="itemForm.rel" class="w-full" placeholder="nofollow noopener" />
                </div>
                <div class="flex items-end gap-3">
                    <ToggleSwitch v-model="itemForm.is_active" />
                    <span class="pb-1 text-sm font-bold">آیتم فعال باشد</span>
                </div>
            </div>

            <template #footer>
                <Button label="انصراف" text @click="itemDialog = false" />
                <Button label="ذخیره آیتم" icon="pi pi-save" @click="saveItem" />
            </template>
        </Dialog>
    </AppLayout>
</template>
