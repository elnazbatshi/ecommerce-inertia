<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('eyebrow_text')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('background_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('foreground_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->decimal('overlay_opacity', 3, 2)->nullable()->default(0.55);
            $table->string('button_primary_text')->nullable();
            $table->string('button_primary_url')->nullable();
            $table->string('button_secondary_text')->nullable();
            $table->string('button_secondary_url')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('badge_url')->nullable();
            $table->string('stat_1_label')->nullable();
            $table->string('stat_1_value')->nullable();
            $table->string('stat_2_label')->nullable();
            $table->string('stat_2_value')->nullable();
            $table->string('stat_3_label')->nullable();
            $table->string('stat_3_value')->nullable();
            $table->string('text_color')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('button_color')->nullable();
            $table->enum('layout', ['image_left_content_right', 'image_right_content_left', 'content_center'])->default('image_left_content_right');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_sliders');
    }
};
