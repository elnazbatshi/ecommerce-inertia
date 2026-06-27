<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicle_types')) {
            Schema::create('vehicle_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        $now = now();
        $types = [
            ['name' => 'ماشین', 'slug' => 'car', 'sort_order' => 10],
            ['name' => 'موتورسیکلت', 'slug' => 'motorcycle', 'sort_order' => 20],
            ['name' => 'کامیون', 'slug' => 'truck', 'sort_order' => 30],
            ['name' => 'وانت', 'slug' => 'pickup', 'sort_order' => 40],
        ];

        foreach ($types as $type) {
            DB::table('vehicle_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => null,
                    'sort_order' => $type['sort_order'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        Schema::table('vehicle_brands', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicle_brands', 'vehicle_type_id')) {
                $table->foreignId('vehicle_type_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('vehicle_types')
                    ->nullOnDelete();
            }
        });

        $typeIds = DB::table('vehicle_types')->pluck('id', 'slug');
        DB::table('vehicle_brands')
            ->whereNull('vehicle_type_id')
            ->orderBy('id')
            ->get(['id', 'type'])
            ->each(function ($brand) use ($typeIds) {
                $slug = match ($brand->type) {
                    'car' => 'car',
                    'motorcycle' => 'motorcycle',
                    default => 'car',
                };

                DB::table('vehicle_brands')
                    ->where('id', $brand->id)
                    ->update(['vehicle_type_id' => $typeIds[$slug] ?? null]);
            });
    }

    public function down(): void
    {
        Schema::table('vehicle_brands', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_brands', 'vehicle_type_id')) {
                $table->dropConstrainedForeignId('vehicle_type_id');
            }
        });

        Schema::dropIfExists('vehicle_types');
    }
};
