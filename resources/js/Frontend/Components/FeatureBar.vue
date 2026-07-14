<script setup>
import { useSiteSettings } from '../Composables/useSiteSettings';
import SiteSettingIcon from './SiteSettingIcon.vue';

const { serviceFeatures } = useSiteSettings();
</script>

<template>
    <section class="border-t border-[#2A2A2A] bg-[#111111]">
        <div class="mx-auto grid max-w-7xl gap-4 px-6 py-6 text-sm text-[#D6D6D6] md:grid-cols-3">
            <div
                v-for="feature in serviceFeatures"
                :key="`${feature.title}-${feature.sort_order}`"
                class="border border-[#2A2A2A] bg-[#1A1A1A] p-4"
            >
                <div class="mb-2 flex items-center gap-2 text-[#D4A017]">
                    <SiteSettingIcon :name="feature.icon" />
                    <strong class="block text-white">{{ feature.title }}</strong>
                </div>
                <span v-if="feature.description" class="mt-1 block text-[#D6D6D6]">
                    {{ feature.description }}
                </span>
            </div>
        </div>
    </section>
</template>
