<?php

use App\Models\Order;
use App\Models\Payment;

return [
    'pagination' => [
        'default_per_page' => env('SHOP_DEFAULT_PER_PAGE', 10),
        'max_per_page' => env('SHOP_MAX_PER_PAGE', 100),
    ],

    'api' => [
        'token_name' => env('SHOP_API_TOKEN_NAME', 'shop-inertia'),
    ],

    'uploads' => [
        'disk' => env('SHOP_UPLOAD_DISK', 'public'),
        'products_main_path' => env('SHOP_PRODUCTS_MAIN_PATH', 'products/main'),
        'products_gallery_path' => env('SHOP_PRODUCTS_GALLERY_PATH', 'products/gallery'),
    ],

    'orders' => [
        'number_prefix' => env('SHOP_ORDER_NUMBER_PREFIX', 'ORD'),
        'inventory_reducing_statuses' => ['processing', 'shipped', 'delivered'],
        'inventory_returning_statuses' => ['cancelled', 'returned'],
        'statuses' => Order::STATUSES,
        'payment_statuses' => Order::PAYMENT_STATUSES,
        'status_options' => [
            ['label' => 'Pending', 'value' => 'pending', 'severity' => 'warn'],
            ['label' => 'Processing', 'value' => 'processing', 'severity' => 'info'],
            ['label' => 'Shipped', 'value' => 'shipped', 'severity' => 'secondary'],
            ['label' => 'Delivered', 'value' => 'delivered', 'severity' => 'success'],
            ['label' => 'Cancelled', 'value' => 'cancelled', 'severity' => 'danger'],
            ['label' => 'Returned', 'value' => 'returned', 'severity' => 'contrast'],
        ],
        'payment_status_options' => [
            ['label' => 'Unpaid', 'value' => 'unpaid', 'severity' => 'warn'],
            ['label' => 'Paid', 'value' => 'paid', 'severity' => 'success'],
            ['label' => 'Failed', 'value' => 'failed', 'severity' => 'danger'],
            ['label' => 'Refunded', 'value' => 'refunded', 'severity' => 'info'],
        ],
    ],

    'payments' => [
        'statuses' => Payment::STATUSES,
        'methods' => Payment::METHODS,
        'status_options' => [
            ['label' => 'Pending', 'value' => 'pending', 'severity' => 'warn'],
            ['label' => 'Paid', 'value' => 'paid', 'severity' => 'success'],
            ['label' => 'Failed', 'value' => 'failed', 'severity' => 'danger'],
            ['label' => 'Cancelled', 'value' => 'cancelled', 'severity' => 'secondary'],
            ['label' => 'Refunded', 'value' => 'refunded', 'severity' => 'info'],
        ],
        'method_options' => [
            ['label' => 'Online', 'value' => 'online'],
            ['label' => 'Card to card', 'value' => 'card_to_card'],
            ['label' => 'Cash', 'value' => 'cash'],
            ['label' => 'Wallet', 'value' => 'wallet'],
        ],
    ],

    'products' => [
        'sortable_fields' => ['id', 'name', 'sku', 'price', 'status', 'type', 'is_featured'],
        'status_options' => [
            ['label' => 'فعال', 'value' => 'active'],
            ['label' => 'غیرفعال', 'value' => 'inactive'],
            ['label' => 'پیش‌نویس', 'value' => 'draft'],
        ],
        'type_options' => [
            ['label' => 'ساده', 'value' => 'simple'],
            ['label' => 'متغیر', 'value' => 'variable'],
        ],
    ],
];
