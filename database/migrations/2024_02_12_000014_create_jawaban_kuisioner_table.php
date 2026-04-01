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
        Schema::create('jawaban_kuisioner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pertanyaan_kuisioner_id')->constrained('pertanyaan_kuisioner')->onDelete('cascade');
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->onDelete('cascade');
            $table->string('responden_identifier')->nullable(); // Untuk mahasiswa anonima
            $table->text('jawaban');
            $table->date('tanggal_jawab')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jawaban_kuisioner');
    }
};
