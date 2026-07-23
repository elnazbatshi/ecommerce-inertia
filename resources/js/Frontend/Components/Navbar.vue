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
        <div class="site-container flex h-[3.25rem] items-center gap-7">
            <a
                href="/products"
                class="inline-flex h-10 items-center gap-2 rounded-[0.9rem] bg-[#D4A017] px-[1.125rem] text-sm font-black text-[#111111] shadow-[0_8px_18px_rgba(212,160,23,0.18)] transition duration-200 hover:bg-[#f0bd38] hover:shadow-[0_12px_28px_rgba(212,160,23,0.28)]"
            >
                <i class="pi pi-th-large"></i>
                <span>دسته‌بندی کالاها</span>
            </a>

            <ul class="flex min-w-0 flex-1 items-center gap-8 overflow-hidden text-sm font-bold text-[#d6d8dc]">
                <li v-for="item in items" :key="item.slug || item.title" class="relative">
                    <a
                        :href="item.url || '#'"
                        class="group relative inline-flex h-10 items-center gap-1 px-1 text-[#d6d8dc] transition duration-200 after:absolute after:bottom-0 after:right-0 after:h-[3px] after:w-full after:origin-center after:scale-x-0 after:rounded-full after:bg-[#D4A017] after:transition-transform after:duration-200 hover:text-[#D4A017]"
                        :class="{ 'text-[#D4A017] after:scale-x-100': activeSlug === item.slug }"
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
