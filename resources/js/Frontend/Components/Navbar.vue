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
    <nav class="site-navbar hidden lg:block">
        <div class="site-container flex h-16 items-center justify-between">
            <ul class="flex items-center gap-1 text-sm font-bold text-[#1A1A1A]">
                <li v-for="item in items" :key="item.slug || item.title" class="relative">
                    <a
                        :href="item.url || '#'"
                        class="site-nav-link"
                        :class="{ 'is-active': activeSlug === item.slug }"
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
