<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] }
});

const productSuggestions = ref([]);
const productById = Object.fromEntries(props.products.map((product) => [product.id, product]));

const form = useForm({
    _method: 'put',
    customer_id: props.order.customer_id,
    address_id: props.order.address_id,
    status: props.order.status,
    payment_status: props.order.payment_status,
    items: props.order.items.map((item) => {
        const product = productById[item.product_id] ?? null;
        const variant = product?.variants?.find((variant) => variant.id === item.product_variant_id);
        return {
            product,
            product_id: item.product_id,
            variant_id: item.product_variant_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            discount_price: item.discount_price,
            sku: item.sku,
            stock: variant?.stock ?? product?.stock ?? 0
        };
    }),
    shipping_cost: Number(props.order.shipping_cost ?? 0),
    tax_total: Number(props.order.tax_total ?? 0),
    discount_total: Number(props.order.discount_total ?? 0),
    total: Number(props.order.total ?? 0),
    customer_note: props.order.customer_note ?? '',
    admin_note: props.order.admin_note ?? ''
});

const customer = computed(() => props.customers.find((item) => item.id === form.customer_id));
const addressOptions = computed(() => customer.value?.addresses ?? []);

watch(() => form.customer_id, () => {
    if (!addressOptions.value.some((address) => address.id === form.address_id)) {
        const defaultAddress = addressOptions.value.find((address) => address.is_default);
        form.address_id = defaultAddress?.id ?? addressOptions.value[0]?.id ?? null;
    }
});

const searchProducts = (event) => {
    const query = event.query.toLowerCase();
    productSuggestions.value = props.products.filter((product) =>
        product.label.toLowerCase().includes(query) || (product.sku ?? '').toLowerCase().includes(query)
    );
};

const addItem = () => {
    form.items.push({ product: null, product_id: null, variant_id: null, quantity: 1, unit_price: 0, discount_price: null, sku: '', stock: 0 });
};

const removeItem = (index) => form.items.splice(index, 1);

const onProductSelect = (item) => {
    const product = item.product;
    item.product_id = product.id;
    item.variant_id = null;
    item.unit_price = product.price;
    item.discount_price = product.discount_price;
    item.sku = product.sku;
    item.stock = product.stock;
};

const onVariantChange = (item) => {
    const variant = item.product?.variants?.find((variant) => variant.id === item.variant_id);
    if (!variant) return;
    item.unit_price = variant.price;
    item.discount_price = variant.discount_price;
    item.sku = variant.sku;
    item.stock = variant.stock;
};

const itemTotal = (item) => Number(item.quantity || 0) * Number(item.discount_price ?? item.unit_price ?? 0);
const subtotal = computed(() => form.items.reduce((sum, item) => sum + itemTotal(item), 0));
const total = computed(() => Math.max(0, subtotal.value - Number(form.discount_total || 0) + Number(form.shipping_cost || 0) + Number(form.tax_total || 0)));
const money = (value) => Number(value ?? 0).toLocaleString('fa-IR');

const submit = () => {
    form.total = total.value;
    form
        .transform((data) => ({
            ...data,
            items: data.items.map((item) => ({
                product_id: item.product_id,
                product_variant_id: item.variant_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                discount_price: item.discount_price
            }))
        }))
        .post(`/admin/orders/${props.order.id}`);
};
</script>

<template>
    <Head :title="`ویرایش ${order.order_number}`">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <TopNavTitle :title="`ویرایش ${order.order_number}`" :breadcrumb="[{ label: 'سفارش‌ها', href: '/admin/orders' }, { label: order.order_number }]">
            <template #pageAction>
                <Link :href="`/admin/orders/${order.id}`">
                    <Button label="مشاهده" icon="pi pi-eye" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="card">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <Select v-model="form.customer_id" :options="customers" optionLabel="phone" optionValue="id" filter placeholder="انتخاب مشتری" class="w-full md:col-span-2">
                        <template #option="{ option }">{{ option.name || '-' }} - {{ option.phone }}</template>
                    </Select>
                    <Select v-model="form.address_id" :options="addressOptions" optionLabel="label" optionValue="id" placeholder="آدرس ارسال" class="w-full md:col-span-2" />
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                    <Select v-model="form.payment_status" :options="paymentStatusOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
            </div>

            <div class="card">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">اقلام</h2>
                    <Button type="button" label="افزودن قلم" icon="pi pi-plus" severity="secondary" @click="addItem" />
                </div>
                <DataTable :value="form.items" showGridlines>
                    <Column header="محصول" style="min-width: 18rem">
                        <template #body="{ data, index }">
                            <AutoComplete v-model="data.product" :suggestions="productSuggestions" optionLabel="label" forceSelection dropdown class="w-full" @complete="searchProducts" @item-select="onProductSelect(data)" />
                            <small v-if="form.errors[`items.${index}.product_id`]" class="text-red-600">{{ form.errors[`items.${index}.product_id`] }}</small>
                        </template>
                    </Column>
                    <Column header="متغیر" style="min-width: 14rem">
                        <template #body="{ data, index }">
                            <Select v-if="data.product?.type === 'variable'" v-model="data.variant_id" :options="data.product.variants" optionLabel="label" optionValue="id" class="w-full" @change="onVariantChange(data)" />
                            <span v-else>-</span>
                            <small v-if="form.errors[`items.${index}.product_variant_id`]" class="text-red-600">{{ form.errors[`items.${index}.product_variant_id`] }}</small>
                        </template>
                    </Column>
                    <Column header="شناسه کالا"><template #body="{ data }">{{ data.sku || '-' }}</template></Column>
                    <Column header="موجودی"><template #body="{ data }"><Tag :value="data.stock" :severity="data.quantity > data.stock ? 'danger' : 'success'" /></template></Column>
                    <Column header="تعداد" style="width: 9rem">
                        <template #body="{ data, index }">
                            <InputNumber v-model="data.quantity" inputClass="w-full" :min="1" />
                            <small v-if="form.errors[`items.${index}.quantity`]" class="text-red-600">{{ form.errors[`items.${index}.quantity`] }}</small>
                        </template>
                    </Column>
                    <Column header="قیمت واحد"><template #body="{ data }"><InputNumber v-model="data.unit_price" inputClass="w-full" :min="0" /></template></Column>
                    <Column header="تخفیف"><template #body="{ data }"><InputNumber v-model="data.discount_price" inputClass="w-full" :min="0" /></template></Column>
                    <Column header="جمع"><template #body="{ data }">{{ money(itemTotal(data)) }}</template></Column>
                    <Column header=""><template #body="{ index }"><Button type="button" icon="pi pi-trash" rounded text severity="danger" @click="removeItem(index)" /></template></Column>
                </DataTable>
            </div>

            <div class="card">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <InputNumber :modelValue="subtotal" inputClass="w-full" disabled placeholder="جمع جزئی" />
                    <InputNumber v-model="form.discount_total" inputClass="w-full" :min="0" placeholder="مبلغ تخفیف" />
                    <InputNumber v-model="form.shipping_cost" inputClass="w-full" :min="0" placeholder="هزینه ارسال" />
                    <InputNumber v-model="form.tax_total" inputClass="w-full" :min="0" placeholder="مالیات" />
                </div>
                <div class="mt-4 text-left text-xl font-semibold">جمع کل: {{ money(total) }}</div>
            </div>

            <div class="card">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Textarea v-model="form.customer_note" rows="4" placeholder="یادداشت مشتری" class="w-full" />
                    <Textarea v-model="form.admin_note" rows="4" placeholder="یادداشت مدیریت" class="w-full" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Link href="/admin/orders"><Button type="button" label="انصراف" severity="secondary" text /></Link>
                <Button type="submit" label="ذخیره تغییرات" icon="pi pi-check" :loading="form.processing" />
            </div>
        </form>
    </AppLayout>
</template>
