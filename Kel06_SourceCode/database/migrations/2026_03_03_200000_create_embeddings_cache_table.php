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
        // Cek apakah tabel sudah ada atau belum
        if (!Schema::hasTable('embeddings_cache')) {

            Schema::create('embeddings_cache', function (Blueprint $table) {
                $table->id();
                $table->string('text_hash', 64)->unique(); // SHA-256 hash of text
                $table->json('embedding'); // Vector embedding
                $table->string('model', 100); // Model used for embedding
                $table->timestamps();

                // Index untuk mempercepat pencarian
                $table->index(['text_hash', 'model']);
            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus tabel jika ada
        Schema::dropIfExists('embeddings_cache');
    }
};
