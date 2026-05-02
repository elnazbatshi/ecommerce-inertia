<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('meta_title', 60)->nullable()->after('slug');
                $table->string('meta_description', 160)->nullable()->after('meta_title');
                $table->text('meta_keywords')->nullable()->after('meta_description');
                $table->string('canonical_url')->nullable()->after('meta_keywords');
                $table->boolean('seo_index')->default(true)->after('canonical_url');
                $table->boolean('seo_follow')->default(true)->after('seo_index');
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn([
                    'meta_title',
                    'meta_description',
                    'meta_keywords',
                    'canonical_url',
                    'seo_index',
                    'seo_follow',
                ]);
            });
        }
    }
};
