<template>
    <section id="finder" class="mx-auto max-w-7xl px-6 py-12">
        <div class="site-card block gap-6 p-6 lg:grid-cols-[0.8fr_1.2fr] rounded-xl">
            <div>
                <p class="text-sm font-black text-[#D4A017]">انتخاب سریع قطعه</p>
                <h2 class="mt-2 text-2xl font-black text-[#1A1A1A]">وسیله نقلیه خود را پیدا کنید</h2>
                <p class="mt-3 text-sm leading-7 text-[#666666]">
                    نوع وسیله، برند و مدل را انتخاب کنید تا فقط قطعات سازگار نمایش داده شوند.
                </p>
            </div>
            <div class="grid gap-3 md:grid-cols-3">
                <select class="border border-[#E5E5E5] bg-white px-4 py-3 text-sm text-[#1A1A1A] outline-none transition focus:border-[#D4A017]">
                    <option>نوع وسیله</option>
                    <option>خودرو</option>
                    <option>موتورسیکلت</option>
                </select>
                <select class="border border-[#E5E5E5] bg-white px-4 py-3 text-sm text-[#1A1A1A] outline-none transition focus:border-[#D4A017]">
                    <option>برند</option>
                    <option>ایران خودرو</option>
                    <option>هوندا</option>
                </select>
                <button class="site-btn-primary">
                    جست‌وجو
                </button>
            </div>
        </div>
    </section>
</template>
