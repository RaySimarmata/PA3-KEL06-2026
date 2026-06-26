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
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuesioner_upload_id')->constrained('kuesioner_uploads')->onDelete('cascade');
            $table->text('chunk_text');
            $table->integer('chunk_index');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('kuesioner_upload_id');
            $table->index('chunk_index');
        });
        
        Schema::create('embeddings_cache', function (Blueprint $table) {
            $table->id();
            $table->string('text_hash', 64)->unique();
            $table->json('embedding');
            $table->string('model', 100);
            $table->timestamps();
            
            $table->index('text_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('embeddings_cache');
        Schema::dropIfExists('document_chunks');
    }
};
