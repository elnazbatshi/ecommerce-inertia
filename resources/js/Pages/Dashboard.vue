<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BestSellingWidget from '@/Components/Dashboard/BestSellingWidget.vue';
import NotificationsWidget from '@/Components/Dashboard/NotificationsWidget.vue';
import RecentSalesWidget from '@/Components/Dashboard/RecentSalesWidget.vue';
import RevenueStreamWidget from '@/Components/Dashboard/RevenueStreamWidget.vue';
import StatsWidget from '@/Components/Dashboard/StatsWidget.vue';
</script>

<template>
    <AppLayout>
        <div class="grid grid-cols-12 gap-8">
            <StatsWidget />

            <div class="col-span-12 xl:col-span-6">
                <RecentSalesWidget />
                <BestSellingWidget />
            </div>
            <div class="col-span-12 xl:col-span-6">
                <RevenueStreamWidget />
                <NotificationsWidget />
            </div>
        </div>
    </AppLayout>
</template>
