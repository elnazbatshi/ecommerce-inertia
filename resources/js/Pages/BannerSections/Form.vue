<script setup>
defineProps({
    form: { type: Object, required: true },
    placements: { type: Array, default: () => [] },
    layouts: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'ذخیره' },
});

defineEmits(['submit']);
</script>

<template>
    <form class="card space-y-4" @submit.prevent="$emit('submit')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block font-medium">عنوان</label>
                <InputText v-model="form.title" class="w-full" />
                <small v-if="form.errors.title" class="text-red-600">{{ form.errors.title }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">کلید یکتا</label>
                <InputText v-model="form.key" dir="ltr" class="w-full text-left" />
                <small v-if="form.errors.key" class="text-red-600">{{ form.errors.key }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">جایگاه</label>
                <Select v-model="form.placement" :options="placements" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.placement" class="text-red-600">{{ form.errors.placement }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">چیدمان</label>
                <Select v-model="form.layout" :options="layouts" optionLabel="label" optionValue="value" class="w-full" />
                <small v-if="form.errors.layout" class="text-red-600">{{ form.errors.layout }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">ترتیب</label>
                <InputNumber v-model="form.sort_order" class="w-full" inputClass="w-full" :min="0" />
                <small v-if="form.errors.sort_order" class="text-red-600">{{ form.errors.sort_order }}</small>
            </div>
            <div class="flex items-center gap-3 pt-7">
                <ToggleSwitch v-model="form.is_active" />
                <span class="font-medium">فعال</span>
            </div>
        </div>
        <div class="flex justify-end">
            <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="form.processing" />
        </div>
    </form>
</template>
