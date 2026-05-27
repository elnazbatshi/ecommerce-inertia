<script setup>
import TopNavTitle from '@/Components/Global/TopNavTitle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatJalaliDateTime } from '@/Utils/persianDate';

const props = defineProps({
    payment: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
    methodOptions: { type: Array, default: () => [] }
});

const status = ref(props.payment.status);
const statusMap = computed(() => Object.fromEntries(props.statusOptions.map((item) => [item.value, item])));
const methodMap = computed(() => Object.fromEntries(props.methodOptions.map((item) => [item.value, item])));
const money = (value) => Number(value ?? 0).toLocaleString('fa-IR');
const rawResponse = computed(() => props.payment.raw_response ? JSON.stringify(props.payment.raw_response, null, 2) : '-');

const changeStatus = () => router.patch(`/admin/payments/${props.payment.id}/status`, { status: status.value }, { preserveScroll: true });
const refund = () => router.patch(`/admin/payments/${props.payment.id}/refund`, {}, { preserveScroll: true });
</script>

<template>
    <Head :title="`Payment ${payment.transaction_id || payment.id}`">
        <meta name="robots" content="noindex,nofollow" />
    </Head>

    <AppLayout>
        <TopNavTitle :title="`Payment ${payment.transaction_id || payment.id}`" :breadcrumb="[{ label: 'Payments', href: '/admin/payments' }, { label: 'Details' }]">
            <template #pageAction>
                <Link href="/admin/payments">
                    <Button label="Back" icon="pi pi-arrow-right" severity="secondary" outlined />
                </Link>
            </template>
        </TopNavTitle>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-1">
                <h2 class="mb-4 text-lg font-semibold">Order</h2>
                <div class="space-y-2">
                    <div>Order: {{ payment.order?.order_number || '-' }}</div>
                    <div>Order total: {{ money(payment.order?.total) }}</div>
                    <div>Order status: {{ payment.order?.status || '-' }}</div>
                    <div>Payment status: {{ payment.order?.payment_status || '-' }}</div>
                    <Link v-if="payment.order" :href="`/admin/orders/${payment.order.id}`">
                        <Button label="View Order" icon="pi pi-eye" text />
                    </Link>
                </div>
            </div>

            <div class="card xl:col-span-1">
                <h2 class="mb-4 text-lg font-semibold">Customer</h2>
                <div class="space-y-2">
                    <div>{{ payment.customer?.name || payment.order?.customer?.name || '-' }}</div>
                    <div>{{ payment.customer?.phone || payment.order?.customer?.phone || '-' }}</div>
                    <div>{{ payment.customer?.email || payment.order?.customer?.email || '-' }}</div>
                </div>
            </div>

            <div class="card xl:col-span-1">
                <h2 class="mb-4 text-lg font-semibold">Status</h2>
                <div class="space-y-4">
                    <Tag :value="statusMap[payment.status]?.label ?? payment.status" :severity="statusMap[payment.status]?.severity ?? 'secondary'" />
                    <div class="flex gap-2">
                        <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
                        <Button icon="pi pi-check" @click="changeStatus" />
                    </div>
                    <Button label="Refund" icon="pi pi-refresh" severity="warn" :disabled="payment.status === 'refunded'" @click="refund" />
                </div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="card xl:col-span-2">
                <h2 class="mb-4 text-lg font-semibold">Payment Details</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div><div class="mb-1 font-medium">Amount</div><div>{{ money(payment.amount) }}</div></div>
                    <div><div class="mb-1 font-medium">Method</div><div>{{ methodMap[payment.method]?.label ?? payment.method }}</div></div>
                    <div><div class="mb-1 font-medium">Gateway</div><div>{{ payment.gateway || '-' }}</div></div>
                    <div><div class="mb-1 font-medium">Paid At</div><div>{{ formatJalaliDateTime(payment.paid_at) }}</div></div>
                    <div><div class="mb-1 font-medium">Created At</div><div>{{ formatJalaliDateTime(payment.created_at) }}</div></div>
                    <div><div class="mb-1 font-medium">Transaction ID</div><div>{{ payment.transaction_id || '-' }}</div></div>
                    <div><div class="mb-1 font-medium">Reference ID</div><div>{{ payment.reference_id || '-' }}</div></div>
                </div>
                <Divider />
                <div>
                    <div class="mb-2 font-medium">Admin Note</div>
                    <p>{{ payment.admin_note || '-' }}</p>
                </div>
            </div>
            <div class="card">
                <h2 class="mb-4 text-lg font-semibold">Raw Response</h2>
                <pre class="max-h-96 overflow-auto rounded-md bg-surface-100 p-3 text-xs ltr:text-left">{{ rawResponse }}</pre>
            </div>
        </div>
    </AppLayout>
</template>
