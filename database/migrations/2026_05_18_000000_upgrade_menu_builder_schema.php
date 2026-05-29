<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_items', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('type')->index();
            }

            if (! Schema::hasColumn('menu_items', 'title_attribute')) {
                $table->string('title_attribute')->nullable()->after('title');
            }

            if (! Schema::hasColumn('menu_items', 'depth')) {
                $table->unsignedInteger('depth')->default(0)->after('rel')->index();
            }
        });

        DB::table('menu_items')->where('type', 'internal')->update(['type' => 'custom']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE menu_items MODIFY type ENUM('custom', 'page', 'category', 'product', 'brand', 'post', 'external') NOT NULL DEFAULT 'custom'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE menu_items MODIFY type ENUM('internal', 'external') NOT NULL");
        }

        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'depth')) {
                $table->dropColumn('depth');
            }

            if (Schema::hasColumn('menu_items', 'title_attribute')) {
                $table->dropColumn('title_attribute');
            }

            if (Schema::hasColumn('menu_items', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
        });
    }
};
