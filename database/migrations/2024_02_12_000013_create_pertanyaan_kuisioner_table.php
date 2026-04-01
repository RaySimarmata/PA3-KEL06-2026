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
        Schema::create('pertanyaan_kuisioner', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuisioner_id')->constrained('kuisioner')->onDelete('cascade');
            $table->integer('urutan_pertanyaan');
            $table->text('pertanyaan');
            $table->enum('tipe_pertanyaan', ['pilihan_ganda', 'skala_likert', 'teks_terbuka', 'checkbox'])->default('skala_likert');
            $table->text('opsi_jawaban')->nullable(); // JSON format
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_kuisioner');
    }
};
