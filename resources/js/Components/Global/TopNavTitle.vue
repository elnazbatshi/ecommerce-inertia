<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    breadcrumb: {
        type: Array,
        default: () => []
    },
    homeHref: {
        type: String,
        default: '/admin/dashboard'
    }
});
</script>

<template>
    <div class="breadcrumb-container">
        <div class="title-container">
            <h1 class="title">{{ props.title }}</h1>
            <slot name="pageAction" />
        </div>

        <Breadcrumb
            class="breadcrumb-sub"
            :home="{ label: 'داشبورد', href: props.homeHref }"
            :model="props.breadcrumb"
        >
            <template #item="{ item, props: itemProps }">
                <Link v-if="item.href" :href="item.href" v-bind="itemProps.action">
                    <span class="text-primary font-semibold">{{ item.label }}</span>
                </Link>
                <span v-else class="text-color">{{ item.label }}</span>
            </template>
        </Breadcrumb>
    </div>
</template>

<style scoped>
.breadcrumb-container {
    margin-bottom: 10px;
    border-radius: 6px;
    background-color: var(--surface-overlay);
    padding: 1.5rem;
    box-shadow:
        0 3px 5px rgba(0, 0, 0, 0.02),
        0 0 2px rgba(0, 0, 0, 0.05),
        0 1px 4px rgba(0, 0, 0, 0.08);
}

.title-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    line-height: 28px;
}

.breadcrumb-sub {
    border: none;
    background: transparent;
    padding: 8px 0 0 !important;
    font-size: 12px;
    line-height: 16px;
}

a {
    color: unset;
    text-decoration: none;
}
</style>
