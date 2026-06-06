# MotoPart Project Overview

## معرفی کلی

MotoPart یک پروژه فروشگاهی با ساختار Laravel + Inertia + Vue است. پروژه دو بخش اصلی دارد:

- سایت عمومی برای کاربران
- داشبورد مدیریت برای ادمین

تفکیک فرانت با مسیر فایل‌ها انجام شده است:

```text
resources/js/Frontend/Pages   صفحات سایت
resources/js/Pages            صفحات داشبورد
resources/js/Frontend         کامپوننت‌ها و layout سایت
resources/js/Layouts          layout داشبورد
```

در `resources/js/app.js` اگر نام صفحه با `Frontend/` شروع شود، صفحه از مسیر سایت خوانده می‌شود. در غیر این صورت از مسیر داشبورد خوانده می‌شود.

## Routeهای اصلی سایت

```text
GET /                         صفحه اصلی
GET /products                 آرشیو محصولات
GET /products/{product:slug}  صفحه تکی محصول
GET /category/{category:slug} آرشیو دسته‌بندی
GET /brand/{brand:slug}       آرشیو برند
GET /blog                     آرشیو بلاگ
GET /blog/{post:slug}         صفحه مقاله
GET /page/{page:slug}         صفحه CMS ساخته‌شده
```

## Routeهای اصلی داشبورد

همه routeهای داشبورد زیر prefix زیر هستند:

```text
/admin
```

بخش‌های اصلی داشبورد:

```text
/admin/dashboard
/admin/products
/admin/categories
/admin/brands
/admin/attributes
/admin/orders
/admin/payments
/admin/customers
/admin/pages
/admin/posts
/admin/media
/admin/menus
/admin/hero-sliders
/admin/vehicles
/admin/vehicle-brands
/admin/shipping-methods
/admin/payment-methods
/admin/provinces
/admin/cities
```

## ساختار محصول

مدل اصلی محصول:

```text
app/Models/Product.php
```

رابطه‌های محصول:

```text
Product belongsTo Category
Product belongsTo Brand
Product hasMany ProductImage
Product hasMany ProductVariant
Product belongsToMany Vehicle
Product morphToMany Media
Product belongsToMany Post
```

فیلدهای مهم محصول:

```text
name
slug
description
brand_id
category_id
sku
barcode
price
discount_price
main_image
status
type
is_original
is_featured
stock
```

## آرشیو محصولات

منطق آرشیو محصولات در این فایل قرار دارد:

```text
app/Services/ProductArchiveService.php
```

این سرویس برای صفحات زیر استفاده می‌شود:

```text
/products
/category/{slug}
/brand/{slug}
```

فیلترها و مرتب‌سازی‌هایی که پشتیبانی می‌کند:

```text
q
brand
category
in_stock
discounted
sort
mode
rows
```

خروجی محصول برای کارت‌ها به این شکل آماده می‌شود:

```text
id
slug
name
brand
feature
price
oldPrice
inStock
isNew
image
```

کامپوننت کارت محصول:

```text
resources/js/Components/Site/ProductCard.vue
```

کارت محصول به مسیر زیر لینک می‌شود:

```text
/products/{slug}
```

## صفحه Single Product

صفحه تکی محصول پیاده شده است:

```text
resources/js/Frontend/Pages/Products/Show.vue
```

Route:

```text
GET /products/{product:slug}
```

داده‌ها از این متد می‌آیند:

```text
app/Http/Controllers/PublicContentController.php
```

Relationهایی که برای محصول لود می‌شوند:

```text
brand
category
images
variants.attributeValues.attribute
vehicles.brand
```

بخش‌های صفحه تکی محصول:

```text
Breadcrumb
Product Gallery
Product Info
Variant Selector
Add To Cart UI
Trust Box
Compatibility / Vehicles
Product Specs
Description
Related Products
SEO Head
```

نکته: سبد خرید هنوز کامل نشده است؛ دکمه افزودن به سبد خرید فعلا پیام placeholder نشان می‌دهد.

## دسته‌بندی محصولات

مدل دسته‌بندی:

```text
app/Models/Category.php
```

دسته‌بندی‌ها ساختار parent/children دارند:

```text
Category parent
Category children
Category products
```

صفحه آرشیو دسته‌بندی:

```text
resources/js/Frontend/Pages/Categories/Show.vue
```

Route:

```text
GET /category/{category:slug}
```

## برندها

مدل برند:

```text
app/Models/Brand.php
```

صفحه آرشیو برند مثل آرشیو محصولات پیاده شده است:

```text
resources/js/Frontend/Pages/Brands/Show.vue
```

Route:

```text
GET /brand/{brand:slug}
```

## خودروها و سازگاری محصول

ساختار خودرو برای اتصال محصول به خودرو/موتورسیکلت اضافه شده است.

مدل‌ها:

```text
app/Models/Vehicle.php
app/Models/VehicleBrand.php
```

جدول‌های مهم:

```text
vehicle_brands
vehicles
product_vehicle
```

رابطه‌ها:

```text
Vehicle belongsTo VehicleBrand
Vehicle belongsToMany Product
Product belongsToMany Vehicle
```

در صفحه محصول، اگر محصول vehicle داشته باشد، در بخش «سازگار با خودرو / موتورسیکلت» نمایش داده می‌شود.

## Media Library

Media Library پیاده شده و در داشبورد وجود دارد:

```text
/admin/media
```

فایل‌های اصلی:

```text
app/Http/Controllers/MediaController.php
app/Models/Media.php
resources/js/Pages/Media/Index.vue
resources/js/Components/Media/MediaBrowser.vue
resources/js/Components/Media/MediaPicker.vue
resources/js/Components/Media/MediaUploader.vue
resources/js/Composables/useMediaLibrary.js
```

قابلیت‌ها:

```text
آپلود رسانه
لیست رسانه‌ها
جستجو
ویرایش meta مثل title و alt
حذف تکی
حذف گروهی
اتصال به مدل‌ها
جدا کردن از مدل‌ها
مرتب‌سازی mediaهای متصل
```

Media Library در اسلایدر صفحه اصلی و ادیتور صفحات CMS هم استفاده می‌شود.

## صفحات CMS

مدیریت صفحات در داشبورد:

```text
/admin/pages
```

فرم ساخت/ویرایش صفحه:

```text
resources/js/Pages/CMS/Pages/PageForm.vue
```

نمایش صفحه در سایت:

```text
resources/js/Frontend/Pages/Pages/Show.vue
```

Route فرانت:

```text
GET /page/{page:slug}
```

ویژگی‌های پیاده‌شده:

```text
نمایش محتوای ساخته‌شده در فرانت
نمایش featured image
SEO title و description
canonical
robots index/follow
نمایش HTML content با استایل پایه
```

## Rich Text Editor صفحات

کامپوننت ادیتور:

```text
resources/js/Components/CMS/RichTextEditor.vue
```

برای صفحات CMS قابلیت‌های زیر اضافه شده:

```text
ویرایشگر دیداری
حالت HTML Source
انتخاب تصویر از Media Library مرکزی
درج تصویر داخل محتوای صفحه
```

در فرم صفحات با این props فعال شده:

```vue
<RichTextEditor
    v-model="form.content"
    allow-html-source
    allow-media-browser
    media-collection="page_content"
/>
```

## منوی سایت

منوی سایت از API زیر خوانده می‌شود:

```text
GET /api/menus/{location}
```

سرویس اصلی:

```text
app/Services/MenuService.php
```

کامپوننت‌های فرانت:

```text
resources/js/Frontend/Components/Header.vue
resources/js/Frontend/Components/Navbar.vue
resources/js/Frontend/Components/MegaMenu.vue
```

داده‌هایی که برای منو می‌آید:

```text
items
popular_brands
popular_vehicles
quick_links
```

محبوب‌های فعلی آماری نیستند.

خودروهای محبوب:

```text
Vehicle active
order by sort_order
limit 8
```

برندهای محبوب:

```text
Brand active
order by name
limit 8
```

## Hero Section

کامپوننت Hero صفحه اصلی:

```text
resources/js/Frontend/Components/HeroSection.vue
```

برای بنر/بخش اصلی صفحه خانه استفاده می‌شود و مربوط به فرانت سایت است، نه داشبورد.

## اسلایدر صفحه اصلی

مدیریت اسلایدر:

```text
/admin/hero-sliders
```

فایل‌های مهم:

```text
app/Http/Controllers/HeroSliderController.php
resources/js/Pages/HeroSliders/
resources/js/Pages/HeroSliders/Partials/HeroSliderForm.vue
```

تصویرهای اسلایدر از Media Library انتخاب می‌شوند.

## پرداخت و ارسال

ساختار روش‌های پرداخت و ارسال اضافه شده است.

مدل‌ها:

```text
app/Models/ShippingMethod.php
app/Models/PaymentMethod.php
```

جداول:

```text
shipping_methods
payment_methods
```

فیلدهای اضافه‌شده به سفارش:

```text
shipping_method_id
payment_method_id
shipping_method_name
payment_method_name
```

داشبوردهای مدیریت:

```text
/admin/shipping-methods
/admin/payment-methods
```

## استان و شهر

ساختار استان و شهر برای آدرس/ارسال اضافه شده است:

```text
/admin/provinces
/admin/cities
```

## سرچ سایت

APIهای سرچ:

```text
GET /api/search/suggestions
GET /api/search
```

کامپوننت فرانت:

```text
resources/js/Components/Site/SearchBox.vue
```

در داشبورد نیز مدیریت پیشنهادهای سرچ و گزارش سرچ وجود دارد:

```text
/admin/search/suggestions
/admin/search/logs
```

## محبوب بودن محصولات

در حال حاضر محبوب بودن محصول به صورت آماری کامل پیاده نشده است.

چیزهایی که فعلا استفاده می‌شوند:

```text
is_featured
sort_order برای خودروها
latest id برای related products
same category / same brand برای محصولات مرتبط
```

برای محبوب واقعی بهتر است در آینده این فیلدها یا metricها اضافه شوند:

```text
view_count
sales_count
wishlist_count
rating_average
rating_count
```

## نکات باقی‌مانده

مواردی که هنوز کامل نیستند یا می‌توانند بهتر شوند:

```text
Cart واقعی
Wishlist واقعی
محبوبیت آماری محصولات
امتیازدهی و نظرات
فیلترهای واقعی ProductArchiveFilters در فرانت
صفحه checkout
اتصال کامل shipping/payment به سفارش فرانت
بهبود encoding متن‌های فارسی قدیمی در بعضی فایل‌ها
```

