<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_receiver_name')) {
                $table->string('shipping_receiver_name')->nullable()->after('address_id');
            }
            if (! Schema::hasColumn('orders', 'shipping_receiver_phone')) {
                $table->string('shipping_receiver_phone')->nullable()->after('shipping_receiver_name');
            }
            if (! Schema::hasColumn('orders', 'shipping_province_name')) {
                $table->string('shipping_province_name')->nullable()->after('shipping_receiver_phone');
            }
            if (! Schema::hasColumn('orders', 'shipping_city_name')) {
                $table->string('shipping_city_name')->nullable()->after('shipping_province_name');
            }
            if (! Schema::hasColumn('orders', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('shipping_city_name');
            }
            if (! Schema::hasColumn('orders', 'shipping_postal_code')) {
                $table->string('shipping_postal_code')->nullable()->after('shipping_address');
            }
            if (! Schema::hasColumn('orders', 'shipping_plaque')) {
                $table->string('shipping_plaque')->nullable()->after('shipping_postal_code');
            }
            if (! Schema::hasColumn('orders', 'shipping_unit')) {
                $table->string('shipping_unit')->nullable()->after('shipping_plaque');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'shipping_receiver_name',
                'shipping_receiver_phone',
                'shipping_province_name',
                'shipping_city_name',
                'shipping_address',
                'shipping_postal_code',
                'shipping_plaque',
                'shipping_unit',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

