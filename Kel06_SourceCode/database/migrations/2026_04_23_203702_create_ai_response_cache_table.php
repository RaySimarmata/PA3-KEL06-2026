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
        Schema::create('ai_response_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 64)->unique(); // Hash dari prompt + context
            $table->string('prompt_hash', 64)->index(); // Hash dari prompt saja
            $table->text('original_prompt'); // Prompt asli untuk referensi
            $table->json('context_metadata'); // Metadata context (template_id, periode, dll)
            $table->longText('ai_response'); // Response AI yang di-cache
            $table->string('ai_provider', 50); // Provider AI yang digunakan (groq, openai, dll)
            $table->string('ai_model', 100); // Model AI yang digunakan
            $table->integer('usage_count')->default(1); // Berapa kali cache ini digunakan
            $table->timestamp('last_used_at'); // Kapan terakhir digunakan
            $table->integer('response_length'); // Panjang response untuk statistik
            $table->float('similarity_threshold')->default(0.85); // Threshold similarity untuk matching
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index(['prompt_hash', 'created_at']);
            $table->index(['last_used_at']);
            $table->index(['usage_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_response_cache');
    }
};