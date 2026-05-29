<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (! Schema::hasColumn('menu_items', 'auto_children')) {
                $table->boolean('auto_children')->default(false)->after('is_active')->index();
            }

            if (! Schema::hasColumn('menu_items', 'children_source')) {
                $table->enum('children_source', ['categories', 'brands', 'products', 'posts', 'pages'])->nullable()->after('auto_children');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasColumn('menu_items', 'children_source')) {
                $table->dropColumn('children_source');
            }

            if (Schema::hasColumn('menu_items', 'auto_children')) {
                $table->dropColumn('auto_children');
            }
        });
    }
};
