<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const vehicleTypes = ref([]);
const brands = ref([]);
const vehicles = ref([]);
const selectedType = ref('');
const selectedBrand = ref('');
const selectedVehicle = ref('');
const loading = ref(false);

const canSearch = computed(() => Boolean(selectedVehicle.value));

const loadTypes = async () => {
    const { data } = await axios.get('/api/frontend/vehicle-finder/types');
    vehicleTypes.value = data.data ?? [];
};

const loadBrands = async () => {
    brands.value = [];
    vehicles.value = [];
    selectedBrand.value = '';
    selectedVehicle.value = '';

    if (!selectedType.value) return;

    loading.value = true;
    try {
        const { data } = await axios.get('/api/frontend/vehicle-finder/brands', {
            params: { vehicle_type_id: selectedType.value },
        });
        brands.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
};

const loadVehicles = async () => {
    vehicles.value = [];
    selectedVehicle.value = '';

    if (!selectedBrand.value) return;

    loading.value = true;
    try {
        const { data } = await axios.get('/api/frontend/vehicle-finder/vehicles', {
            params: { vehicle_brand_id: selectedBrand.value },
        });
        vehicles.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
};

const searchProducts = () => {
    if (!selectedVehicle.value) return;

    router.get('/products', { vehicle: selectedVehicle.value }, { preserveScroll: false });
};

watch(selectedType, loadBrands);
watch(selectedBrand, loadVehicles);
onMounted(loadTypes);
</script>

<template>
    <section id="finder" class="relative z-20 mx-auto max-w-7xl px-4 pb-10 pt-5 sm:px-6 lg:-mt-[70px] lg:pb-12 lg:pt-0">
        <div class="rounded-2xl border border-black/5 bg-white p-5 shadow-[0_22px_60px_rgba(0,0,0,0.14)] lg:grid lg:grid-cols-[0.78fr_1.22fr] lg:items-center lg:gap-6 lg:p-6">
            <div>
                <p class="text-sm font-black text-[#D4A017]">انتخاب سریع قطعه</p>
                <h2 class="mt-2 text-2xl font-black text-[#1A1A1A]">وسیله نقلیه خود را پیدا کنید</h2>
                <p class="mt-3 text-sm leading-7 text-[#666666]">
                    نوع وسیله، برند و مدل را انتخاب کنید تا فقط قطعات سازگار نمایش داده شوند.
                </p>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2 lg:mt-0 lg:grid-cols-4">
                <select v-model="selectedType" class="h-12 rounded-xl border border-[#E5E5E5] bg-white px-4 text-sm text-[#1A1A1A] outline-none transition focus:border-[#D4A017] focus:ring-4 focus:ring-[#D4A017]/10">
                    <option value="">نوع وسیله</option>
                    <option v-for="type in vehicleTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                </select>

                <select v-model="selectedBrand" class="h-12 rounded-xl border border-[#E5E5E5] bg-white px-4 text-sm text-[#1A1A1A] outline-none transition focus:border-[#D4A017] focus:ring-4 focus:ring-[#D4A017]/10 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400" :disabled="!selectedType || loading">
                    <option value="">برند</option>
                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                </select>

                <select v-model="selectedVehicle" class="h-12 rounded-xl border border-[#E5E5E5] bg-white px-4 text-sm text-[#1A1A1A] outline-none transition focus:border-[#D4A017] focus:ring-4 focus:ring-[#D4A017]/10 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400" :disabled="!selectedBrand || loading">
                    <option value="">مدل</option>
                    <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.name }}</option>
                </select>

                <button class="site-btn-primary h-12 rounded-xl disabled:cursor-not-allowed disabled:opacity-50" :disabled="!canSearch || loading" @click="searchProducts">
                    جست‌وجو
                </button>
            </div>
        </div>
    </section>
</template>
