<script setup>
import { computed } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    category: { type: Object, default: null },
    brands: { type: Array, default: () => [] },
    vehicles: { type: Array, default: () => [] },
    quickLinks: { type: Array, default: () => [] }
});

const children = computed(() => props.category?.children ?? []);
</script>

<template>
    <transition name="fade-slide">
        <div v-if="open" class="mega-overlay hidden lg:block">
            <div class="site-container">
                <section class="mega-panel">
                    <div class="grid gap-8 lg:grid-cols-[1fr_1fr_280px]">
                        <div>
                            <h3 class="mega-title">{{ category?.title || 'دسته‌بندی‌ها' }}</h3>
                            <ul class="mt-4 grid gap-2">
                                <li v-for="child in children" :key="child.slug">
                                    <a :href="child.url || `/category/${child.slug}`" class="mega-link">{{ child.title || child.name }}</a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="mega-subtitle">دسته‌های مرتبط</h4>
                            <ul class="mt-4 grid grid-cols-2 gap-2">
                                <li v-for="child in children" :key="`chip-${child.slug}`">
                                    <a :href="child.url || `/category/${child.slug}`" class="mega-chip">{{ child.title || child.name }}</a>
                                </li>
                            </ul>
                        </div>

                        <aside class="space-y-6 border-r border-[var(--site-border)] pr-6">
                            <div>
                                <h4 class="mega-subtitle">برندهای محبوب</h4>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a v-for="brand in brands" :key="brand.slug || brand.name" :href="brand.url || '#'" class="mega-chip">
                                        {{ brand.title || brand.name }}
                                    </a>
                                </div>
                            </div>
                            <div>
                                <h4 class="mega-subtitle">خودروهای محبوب</h4>
                                <ul class="mt-3 space-y-2">
                                    <li v-for="vehicle in vehicles" :key="vehicle.slug">
                                        <a href="#" class="mega-vehicle-link">{{ vehicle.title }}</a>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="mega-subtitle">لینک‌های سریع</h4>
                                <ul class="mt-3 space-y-2">
                                    <li v-for="link in quickLinks" :key="link.title">
                                        <a :href="link.url" class="mega-quick-link">{{ link.title }}</a>
                                    </li>
                                </ul>
                            </div>
                        </aside>
                    </div>
                </section>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.mega-overlay {
    background: rgba(5, 6, 8, 0.42);
    backdrop-filter: blur(10px);
}

.mega-panel {
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: #111318;
    color: #e5e7eb;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
}

.mega-title,
.mega-subtitle {
    color: #ffffff;
}

.mega-link,
.mega-chip,
.mega-vehicle-link,
.mega-quick-link {
    color: #d6d8dc;
}

.mega-link:hover,
.mega-chip:hover,
.mega-vehicle-link:hover,
.mega-quick-link:hover {
    color: #d4a017;
}
</style>
