<script setup>
defineProps({
    items: {
        type: Array,
        default: () => []
    },
    activeSlug: {
        type: String,
        default: ''
    }
});

defineEmits(['hover-item', 'leave-nav', 'toggle-mobile']);
</script>

<template>
    <nav class="hidden border-y border-white/10 bg-[#08090b] lg:block">
        <div class="site-container flex h-14 items-center gap-3">
            <a
                href="/products"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-[#D4A017] px-4 text-sm font-black text-[#111111] transition hover:bg-[#f0bd38]"
            >
                <i class="pi pi-th-large"></i>
                <span>دسته‌بندی کالاها</span>
            </a>

            <ul class="flex min-w-0 flex-1 items-center gap-1 overflow-hidden text-sm font-bold text-[#d6d8dc]">
                <li v-for="item in items" :key="item.slug || item.title" class="relative">
                    <a
                        :href="item.url || '#'"
                        class="inline-flex h-10 items-center gap-1 rounded-xl px-3 text-[#d6d8dc] transition hover:bg-white/5 hover:text-[#D4A017]"
                        :class="{ 'bg-white/5 text-[#D4A017]': activeSlug === item.slug }"
                        @mouseenter="$emit('hover-item', item)"
                    >
                        <span>{{ item.title }}</span>
                        <svg v-if="item.children?.length" viewBox="0 0 20 20" class="h-4 w-4">
                            <path fill="currentColor" d="M5.5 7.5 10 12l4.5-4.5z" />
                        </svg>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</template>
