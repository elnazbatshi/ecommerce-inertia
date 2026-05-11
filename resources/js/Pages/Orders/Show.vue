<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    order: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] }
});

const status = ref(props.order.status);
const paymentStatus = ref(props.order.payment_status);
const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));
const paymentMap = computed(() => Object.fromEntries(props.paymentStatusOptions.map((item) => [item.value, item])));
const money = (value) => Number(value ?? 0).toLocaleString('fa-IR');

const changeStatus = () => router.patch(`/orders/${props.order.id}/status`, { status: status.value }, { preserveScroll: true });
const changePaymentStatus = () => router.patch(`/orders/${props.order.id}/payment-status`, { payment_status: paymentStatus.value }, { preserveScroll: true });
</script>

<template>
    <Head :title="order.order_number">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <TopNavTitle :title="order.order_number" :breadcrumb="[{ label: 'سفارش‌ها', href: '/orders' }, { label: order.order_number }]">
            <template #pageAction>
                <Link :href="`/orders/${order.id}/edit`">
                    <Button label="ویرایش" icon="pi pi-pencil" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-1">
                <h2 class="mb-4 text-lg font-semibold">مشتری</h2>
                <div class="space-y-2">
                    <div>{{ order.customer?.name || '-' }}</div>
                    <div>{{ order.customer?.phone || '-' }}</div>
                    <div>{{ order.customer?.email || '-' }}</div>
                </div>
                <h2 class="mb-4 mt-6 text-lg font-semibold">آدرس ارسال</h2>
                <div v-if="order.address" class="space-y-2 text-sm">
                    <div>{{ order.address.receiver_name }} - {{ order.address.receiver_phone }}</div>
                    <div>{{ order.address.province }} / {{ order.address.city }}</div>
                    <div>{{ order.address.address }}</div>
                    <div>کد پستی: {{ order.address.postal_code || '-' }}</div>
                </div>
                <div v-else>-</div>
            </div>

            <div class="card xl:col-span-2">
                <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block font-medium">وضعیت سفارش</label>
                        <div class="flex gap-2">
                            <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                            <Button icon="pi pi-check" @click="changeStatus" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block font-medium">وضعیت پرداخت</label>
                        <div class="flex gap-2">
                            <Select v-model="paymentStatus" :options="paymentStatusOptions" optionLabel="label" optionValue="value" class="w-full" />
                            <Button icon="pi pi-check" @click="changePaymentStatus" />
                        </div>
                    </div>
                    <Tag :value="statusMap[order.status]?.label ?? order.status" :severity="statusMap[order.status]?.severity ?? 'secondary'" />
                    <Tag :value="paymentMap[order.payment_status]?.label ?? order.payment_status" :severity="paymentMap[order.payment_status]?.severity ?? 'secondary'" />
                </div>

                <DataTable :value="order.items" showGridlines>
                    <Column field="product_name" header="محصول" />
                    <Column field="variant_name" header="متغیر"><template #body="{ data }">{{ data.variant_name || '-' }}</template></Column>
                    <Column field="sku" header="شناسه کالا"><template #body="{ data }">{{ data.sku || '-' }}</template></Column>
                    <Column field="quantity" header="تعداد" />
                    <Column field="unit_price" header="قیمت واحد"><template #body="{ data }">{{ money(data.unit_price) }}</template></Column>
                    <Column field="discount_price" header="تخفیف"><template #body="{ data }">{{ data.discount_price ? money(data.discount_price) : '-' }}</template></Column>
                    <Column field="total_price" header="جمع"><template #body="{ data }">{{ money(data.total_price) }}</template></Column>
                </DataTable>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-2">
                <h2 class="mb-4 text-lg font-semibold">یادداشت‌ها</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div><div class="mb-2 font-medium">یادداشت مشتری</div><p>{{ order.customer_note || '-' }}</p></div>
                    <div><div class="mb-2 font-medium">یادداشت مدیریت</div><p>{{ order.admin_note || '-' }}</p></div>
                </div>
                <h2 class="mb-4 mt-6 text-lg font-semibold">تاریخ‌ها</h2>
                <div class="grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
                    <div>ایجاد شده: {{ order.created_at || '-' }}</div>
                    <div>پرداخت شده: {{ order.paid_at || '-' }}</div>
                    <div>ارسال شده: {{ order.shipped_at || '-' }}</div>
                    <div>تحویل شده: {{ order.delivered_at || '-' }}</div>
                    <div>لغو شده: {{ order.cancelled_at || '-' }}</div>
                </div>
            </div>
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">جمع کل</h2>
                <div class="space-y-3">
                    <div class="flex justify-between"><span>جمع جزئی</span><span>{{ money(order.subtotal) }}</span></div>
                    <div class="flex justify-between"><span>تخفیف</span><span>{{ money(order.discount_total) }}</span></div>
                    <div class="flex justify-between"><span>ارسال</span><span>{{ money(order.shipping_cost) }}</span></div>
                    <div class="flex justify-between"><span>مالیات</span><span>{{ money(order.tax_total) }}</span></div>
                    <Divider />
                    <div class="flex justify-between text-xl font-semibold"><span>جمع کل</span><span>{{ money(order.total) }}</span></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
