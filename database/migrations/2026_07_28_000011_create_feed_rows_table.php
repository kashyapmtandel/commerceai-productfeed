<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('data');
            $table->json('fixed_data')->nullable();
            $table->enum('status', ['valid', 'warning', 'error'])->default('valid');
            $table->json('issues')->nullable();
            $table->text('ai_suggestion')->nullable();
            $table->json('ai_fixed_data')->nullable();
            $table->boolean('ai_applied')->default(false);
            $table->timestamps();

            $table->index(['feed_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_rows');
    }
};
