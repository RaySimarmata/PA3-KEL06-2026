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
        Schema::create('pencapaian_kpi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->onDelete('cascade');
            $table->foreignId('ajaran_id')->constrained('ajaran')->onDelete('cascade');
            $table->string('nama_kpi');
            $table->text('deskripsi')->nullable();
            $table->decimal('target_nilai', 5, 2)->nullable();
            $table->decimal('nilai_realisasi', 5, 2)->nullable();
            $table->decimal('persentase_pencapaian', 5, 2)->nullable();
            $table->enum('status_kpi', ['tidak_tercapai', 'tercapai', 'terlampaui'])->default('tidak_tercapai');
            $table->text('catatan_kpi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pencapaian_kpi');
    }
};
