<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'province_id')) {
                $table->foreignId('province_id')->nullable()->after('receiver_phone')
                    ->constrained('provinces')->nullOnDelete();
            }

            if (! Schema::hasColumn('addresses', 'city_id')) {
                $table->foreignId('city_id')->nullable()->after('province_id')
                    ->constrained('cities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }
            if (Schema::hasColumn('addresses', 'province_id')) {
                $table->dropConstrainedForeignId('province_id');
            }
        });
    }
};

