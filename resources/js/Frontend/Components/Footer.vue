<script setup>
defineProps({
    items: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <footer class="footer-premium">
        <div class="site-container py-14">
            <div class="grid gap-8 lg:grid-cols-[1.3fr_1fr_1fr]">
                <div>
                    <h3 class="text-3xl font-black text-[#111111]">MotoPart</h3>
                    <p class="mt-3 text-sm leading-7 text-[#666666]">
                        فروشگاه تخصصی قطعات و روغن موتور سیکلت با تمرکز بر اصالت کالا، سرعت تامین و تجربه خرید حرفه ای.
                    </p>
                </div>
                <div>
                    <h4 class="footer-title">مزایای خرید</h4>
                    <ul class="footer-list">
                        <li>ارسال سریع به سراسر کشور</li>
                        <li>ضمانت اصالت کالا</li>
                        <li>پشتیبانی تخصصی خرید</li>
                        <li>پرداخت امن</li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-title">لینک های مهم</h4>
                    <nav class="footer-links">
                        <a v-for="item in items" :key="item.id" :href="item.url" :target="item.target || '_self'" :rel="item.rel">{{ item.title }}</a>
                        <a v-if="!items.length" href="/blog">وبلاگ</a>
                        <a v-if="!items.length" href="/page/contact-expert">تماس با ما</a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>
</template>
