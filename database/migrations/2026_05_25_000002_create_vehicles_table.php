<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_brand_id')->nullable()->constrained('vehicle_brands')->nullOnDelete();
                $table->enum('type', ['motorcycle', 'car'])->index();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedSmallInteger('year_from')->nullable()->index();
                $table->unsignedSmallInteger('year_to')->nullable()->index();
                $table->string('engine')->nullable();
                $table->string('trim')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('image_media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->integer('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });

            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'type')) {
                $table->enum('type', ['motorcycle', 'car'])->index();
            }
            if (!Schema::hasColumn('vehicles', 'vehicle_brand_id')) {
                $table->foreignId('vehicle_brand_id')->nullable()->after('id')->constrained('vehicle_brands')->nullOnDelete();
            }
            if (!Schema::hasColumn('vehicles', 'name')) {
                $table->string('name')->nullable()->after('type');
            }
            if (!Schema::hasColumn('vehicles', 'year_from')) {
                $table->unsignedSmallInteger('year_from')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('vehicles', 'year_to')) {
                $table->unsignedSmallInteger('year_to')->nullable()->after('year_from');
            }
            if (!Schema::hasColumn('vehicles', 'engine')) {
                $table->string('engine')->nullable()->after('year_to');
            }
            if (!Schema::hasColumn('vehicles', 'trim')) {
                $table->string('trim')->nullable()->after('engine');
            }
            if (!Schema::hasColumn('vehicles', 'description')) {
                $table->text('description')->nullable()->after('trim');
            }
            if (!Schema::hasColumn('vehicles', 'image_media_id')) {
                $table->foreignId('image_media_id')->nullable()->after('description')->constrained('media')->nullOnDelete();
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (!Schema::hasColumn('vehicles', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('vehicles', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
