<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    roles: {
        type: Object,
        required: true
    },
    permissions: {
        type: Array,
        default: () => []
    }
});

const dt = ref();
const visible = ref(false);
const editingRole = ref(null);
const loading = ref(false);
const roles = ref(props.roles.data ?? []);
const totalRecords = ref(props.roles.total ?? 0);
const searchTimeout = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    id: {
        operator: FilterOperator.AND,
        constraints: [{ value: null, matchMode: FilterMatchMode.EQUALS }]
    },
    name: {
        operator: FilterOperator.AND,
        constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
    },
    permissions: {
        operator: FilterOperator.AND,
        constraints: [{ value: null, matchMode: FilterMatchMode.CONTAINS }]
    }
});

let lazyParams = reactive({
    first: 0,
    page: 1,
    rows: 10,
    sortField: 'id',
    sortOrder: 1,
    filters: filters.value
});

const permissionOptions = computed(() => props.permissions.map((permission) => ({
    label: permission.label,
    value: permission.id
})));

const form = useForm({
    name: '',
    permissions: []
});

const loadRoles = () => {
    loading.value = true;

    window.axios
        .get('/api/roles-with-permissions', { params: lazyParams })
        .then(({ data }) => {
            const payload = data.data;
            roles.value = payload.data ?? [];
            totalRecords.value = payload.total ?? 0;
        })
        .finally(() => {
            loading.value = false;
        });
};

const refreshTable = () => {
    loadRoles();
};

const onPage = (event) => {
    lazyParams.first = event.first;
    lazyParams.page = event.page + 1;
    lazyParams.rows = event.rows;
    lazyParams.sortField = event.sortField ?? lazyParams.sortField;
    lazyParams.sortOrder = event.sortOrder ?? lazyParams.sortOrder;
    loadRoles();
};

const onSort = (event) => {
    lazyParams.sortField = event.sortField;
    lazyParams.sortOrder = event.sortOrder;
    loadRoles();
};

const onFilter = (event = null) => {
    lazyParams.first = 0;
    lazyParams.page = 1;
    lazyParams.filters = event?.filters ?? filters.value;
    loadRoles();
};

const globalSearch = () => {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(() => onFilter(), 450);
};

const openCreate = () => {
    editingRole.value = null;
    form.reset();
    form.clearErrors();
    visible.value = true;
};

const openEdit = (role) => {
    editingRole.value = role;
    form.clearErrors();
    form.name = role.name;
    form.permissions = role.permissions.map((permission) => permission.id);
    visible.value = true;
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            form.reset();
            refreshTable();
        }
    };

    if (editingRole.value) {
        form.put(`/accesses/${editingRole.value.id}`, options);
        return;
    }

    form.post('/accesses', options);
};

onMounted(() => {
    lazyParams = {
        first: dt.value?.d_first ?? 0,
        page: 1,
        rows: dt.value?.d_rows ?? 10,
        sortField: dt.value?.d_sortField ?? 'id',
        sortOrder: dt.value?.d_sortOrder ?? 1,
        filters: filters.value
    };
    loadRoles();
});
</script>

<template>
    <Head title="مدیریت نقش‌ها و دسترسی‌ها" />

    <AppLayout>
        <TopNavTitle title="مدیریت نقش‌ها و دسترسی‌ها" :breadcrumb="[{ label: 'مدیریت نقش‌ها و دسترسی‌ها' }]">
            <template #pageAction>
                <Button label="ایجاد نقش" icon="pi pi-plus" @click="openCreate" />
            </template>
        </TopNavTitle>

        <div class="card">
            <DataTable
                ref="dt"
                :value="roles"
                dataKey="id"
                lazy
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50]"
                :totalRecords="totalRecords"
                :loading="loading"
                :filters="filters"
                filterDisplay="menu"
                showGridlines
                @page="onPage"
                @sort="onSort"
                @filter="onFilter"
            >
                <template #header>
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <IconField class="w-full md:max-w-md">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText
                                v-model="filters.global.value"
                                class="w-full"
                                placeholder="جستجو در نقش یا دسترسی..."
                                @input="globalSearch"
                            />
                        </IconField>
                        <span class="text-sm text-surface-500">{{ totalRecords }} نقش</span>
                    </div>
                </template>

                <template #empty>نقشی پیدا نشد.</template>

                <Column field="id" header="شناسه" sortable style="width: 7rem">
                    <template #filter="{ filterModel }">
                        <InputNumber v-model="filterModel.value" placeholder="شناسه" class="w-full" />
                    </template>
                </Column>

                <Column field="name" header="نقش" sortable style="min-width: 14rem">
                    <template #body="{ data }">
                        <Tag v-if="data.name === 'admin'" :value="data.label" severity="success" />
                        <span v-else class="font-semibold">{{ data.label }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="نام نقش" class="w-full" />
                    </template>
                </Column>

                <Column header="دسترسی‌ها" filterField="permissions" style="min-width: 26rem">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Tag
                                v-for="permission in data.permissions"
                                :key="permission.id"
                                :value="permission.label"
                                severity="info"
                            />
                            <span v-if="!data.permissions?.length" class="text-surface-500">بدون دسترسی</span>
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="نام دسترسی" class="w-full" />
                    </template>
                </Column>

                <Column
                    header="عملیات"
                    headerStyle="width: 8rem"
                    bodyStyle="text-align: center; overflow: visible"
                >
                    <template #body="{ data }">
                        <Button
                            v-if="data.name !== 'admin'"
                            icon="pi pi-pencil"
                            rounded
                            text
                            severity="secondary"
                            aria-label="ویرایش نقش"
                            @click="openEdit(data)"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog
            v-model:visible="visible"
            modal
            :header="editingRole ? 'ویرایش نقش' : 'ایجاد نقش'"
            :style="{ width: '48rem', maxWidth: '95vw' }"
        >
            <div class="space-y-5">
                <div>
                    <label class="mb-2 block font-semibold">نام نقش</label>
                    <InputText v-model="form.name" class="w-full" :invalid="Boolean(form.errors.name)" />
                    <small v-if="form.errors.name" class="mt-2 block text-red-600">{{ form.errors.name }}</small>
                </div>

                <div>
                    <label class="mb-2 block font-semibold">دسترسی‌ها</label>
                    <MultiSelect
                        v-model="form.permissions"
                        :options="permissionOptions"
                        optionLabel="label"
                        optionValue="value"
                        display="chip"
                        filter
                        class="w-full"
                        placeholder="انتخاب دسترسی‌ها"
                    />
                    <small v-if="form.errors.permissions" class="mt-2 block text-red-600">{{ form.errors.permissions }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="انصراف" severity="secondary" text @click="visible = false" />
                <Button label="ذخیره" :loading="form.processing" @click="save" />
            </template>
        </Dialog>
    </AppLayout>
</template>
