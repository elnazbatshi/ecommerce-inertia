<script setup>
import { reactive } from 'vue';

defineProps({
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
});

const filters = reactive({
    search: '',
    categories: [],
    brands: [],
    priceRange: [200000, 6000000],
    inStockOnly: false,
    discountedOnly: false,
    compatibility: [],
    sae: []
});

const compatibilityOptions = [
    { label: 'خودرو', value: 'car' },
    { label: 'موتور سیکلت', value: 'motorcycle' },
];

const saeOptions = ['0W-20', '5W-30', '10W-40', '20W-50'];
</script>

<template>
    <aside class="space-y-4 overflow-hidden rounded-xl border border-surface-200 bg-white p-4">
        <h3 class="text-base font-bold text-surface-900">فیلترها</h3>

        <div>
            <label class="mb-2 block text-sm font-medium">جستجو</label>
            <InputText v-model="filters.search" class="w-full" placeholder="نام محصول یا برند" />
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium">دسته‌بندی</label>
            <MultiSelect v-model="filters.categories" :options="categories" optionLabel="label" optionValue="value" class="w-full" placeholder="انتخاب دسته‌بندی" />
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium">برند</label>
            <MultiSelect v-model="filters.brands" :options="brands" optionLabel="label" optionValue="value" class="w-full" placeholder="انتخاب برند" />
        </div>

        <div class="overflow-hidden">
            <label class="mb-2 block text-sm font-medium">محدوده قیمت (تومان)</label>
            <div class="px-1">
                <Slider v-model="filters.priceRange" range class="mb-3 w-full" />
            </div>
            <div class="flex justify-between text-xs text-surface-600">
                <span>{{ Number(filters.priceRange[0]).toLocaleString('fa-IR') }}</span>
                <span>{{ Number(filters.priceRange[1]).toLocaleString('fa-IR') }}</span>
            </div>
        </div>

        <div class="space-y-3 border-y border-surface-200 py-3">
            <div class="flex items-center justify-between">
                <label class="text-sm">فقط کالاهای موجود</label>
                <ToggleSwitch v-model="filters.inStockOnly" />
            </div>
            <div class="flex items-center justify-between">
                <label class="text-sm">فقط تخفیف‌دار</label>
                <ToggleSwitch v-model="filters.discountedOnly" />
            </div>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium">سازگاری</label>
            <MultiSelect v-model="filters.compatibility" :options="compatibilityOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="خودرو / موتور" />
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium">گرانروی روغن SAE</label>
            <MultiSelect v-model="filters.sae" :options="saeOptions" class="w-full" placeholder="انتخاب SAE" />
        </div>

        <div class="flex gap-2 pt-2">
            <Button label="اعمال فیلتر" icon="pi pi-filter" class="flex-1" />
            <Button label="پاکسازی" icon="pi pi-times" severity="secondary" outlined class="flex-1" />
        </div>
    </aside>
</template>
