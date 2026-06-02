<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_method_id')) {
                $table->foreignId('shipping_method_id')->nullable()->after('address_id')->constrained('shipping_methods')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('shipping_method_id')->constrained('payment_methods')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'shipping_method_name')) {
                $table->string('shipping_method_name')->nullable()->after('payment_method_id');
            }

            if (!Schema::hasColumn('orders', 'payment_method_name')) {
                $table->string('payment_method_name')->nullable()->after('shipping_method_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_method_id')) {
                $table->dropConstrainedForeignId('shipping_method_id');
            }

            if (Schema::hasColumn('orders', 'payment_method_id')) {
                $table->dropConstrainedForeignId('payment_method_id');
            }

            if (Schema::hasColumn('orders', 'shipping_method_name')) {
                $table->dropColumn('shipping_method_name');
            }

            if (Schema::hasColumn('orders', 'payment_method_name')) {
                $table->dropColumn('payment_method_name');
            }
        });
    }
};

