<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->text('description')->nullable()->after('logo');
            $table->longText('content')->nullable()->after('description');
            $table->string('featured_image')->nullable()->after('content');
            $table->string('cover_image')->nullable()->after('featured_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['description', 'content', 'featured_image', 'cover_image']);
        });
    }
};
