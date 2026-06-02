<script setup>
defineProps({ log: Object });
</script>

<template>
    <div class="card p-4 space-y-2">
        <h1 class="text-lg font-bold">جزئیات لاگ سرچ</h1>
        <p><strong>query:</strong> {{ log.query }}</p>
        <p><strong>type:</strong> {{ log.type }}</p>
        <p><strong>matched_type:</strong> {{ log.matched_type || '-' }}</p>
        <p><strong>results_count:</strong> {{ log.results_count }}</p>
        <p><strong>user:</strong> {{ log.user?.name || '-' }}</p>
        <p><strong>ip:</strong> {{ log.ip_address || '-' }}</p>
        <p><strong>searched_at:</strong> {{ log.searched_at }}</p>
        <p><strong>user_agent:</strong> {{ log.user_agent }}</p>
    </div>
</template>
