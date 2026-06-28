<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_sliders', function (Blueprint $table) {
            if (!Schema::hasColumn('hero_sliders', 'placement')) {
                $table->string('placement')->default('hero')->after('layout')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hero_sliders', function (Blueprint $table) {
            if (Schema::hasColumn('hero_sliders', 'placement')) {
                $table->dropIndex(['placement']);
                $table->dropColumn('placement');
            }
        });
    }
};
