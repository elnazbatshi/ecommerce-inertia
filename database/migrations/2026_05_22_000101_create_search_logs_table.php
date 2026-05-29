<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query');
            $table->enum('type', ['product', 'category', 'brand', 'free_text']);
            $table->unsignedBigInteger('matched_id')->nullable();
            $table->string('matched_type')->nullable();
            $table->integer('results_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('searched_at')->nullable();
            $table->timestamps();

            $table->index('query');
            $table->index(['type', 'matched_type']);
            $table->index('searched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
