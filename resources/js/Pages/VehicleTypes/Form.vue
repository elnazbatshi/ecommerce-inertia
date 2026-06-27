<script setup>
defineProps({
    form: { type: Object, required: true },
    submitLabel: { type: String, default: 'ذخیره' },
});

defineEmits(['submit']);
</script>

<template>
    <form class="card space-y-4" @submit.prevent="$emit('submit')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block font-medium">نام نوع وسیله</label>
                <InputText v-model="form.name" class="w-full" />
                <small v-if="form.errors.name" class="text-red-600">{{ form.errors.name }}</small>
            </div>
            <div>
                <label class="mb-2 block font-medium">Slug</label>
                <InputText v-model="form.slug" class="w-full" />
                <small v-if="form.errors.slug" class="text-red-600">{{ form.errors.slug }}</small>
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
            <div class="md:col-span-2">
                <label class="mb-2 block font-medium">توضیحات</label>
                <Textarea v-model="form.description" class="w-full" rows="4" />
                <small v-if="form.errors.description" class="text-red-600">{{ form.errors.description }}</small>
            </div>
        </div>
        <div class="flex justify-end gap-2">
            <Button type="submit" :label="submitLabel" icon="pi pi-check" :loading="form.processing" />
        </div>
    </form>
</template>
