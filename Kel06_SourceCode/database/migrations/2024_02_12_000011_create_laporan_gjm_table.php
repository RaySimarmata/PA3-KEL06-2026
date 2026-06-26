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
        Schema::create('laporan_gjm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajaran_id')->constrained('ajaran')->onDelete('cascade');
            $table->date('periode_mulai')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->enum('jenis_laporan', ['bulanan', 'semester', 'tahunan'])->default('bulanan');
            $table->text('ringkasan_mutu_institusi')->nullable();
            $table->text('analisis_kepatuhan')->nullable();
            $table->text('temuan_utama')->nullable();
            $table->text('rekomendasi_perbaikan')->nullable();
            $table->text('rencana_tindakan')->nullable();
            $table->string('file_laporan')->nullable();
            $table->enum('status_laporan', ['draft', 'menunggu_review', 'approved', 'revisi'])->default('draft');
            $table->date('tanggal_submit')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users', 'id')->onDelete('set null');
            $table->date('tanggal_review')->nullable();
            $table->text('catatan_review')->nullable();
            $table->integer('jumlah_prodi_terlibat')->nullable();
            $table->integer('jumlah_laporan_gkm_diterima')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_gjm');
    }
};
