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
        Schema::create('laporan_gkm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->onDelete('cascade');
            $table->foreignId('ajaran_id')->constrained('ajaran')->onDelete('cascade');
            $table->foreignId('dosen_ketua')->constrained('dosen')->onDelete('cascade');
            $table->date('periode_mulai')->nullable();
            $table->date('periode_akhir')->nullable();
            $table->enum('jenis_laporan', ['bulanan', 'semester', 'tahunan'])->default('bulanan');
            $table->text('ringkasan_temuan')->nullable();
            $table->text('hasil_monitoring_rps')->nullable();
            $table->text('hasil_monitoring_materi')->nullable();
            $table->text('hasil_monitoring_kuisioner')->nullable();
            $table->text('rencana_perbaikan')->nullable();
            $table->string('file_laporan')->nullable();
            $table->enum('status_laporan', ['draft', 'menunggu_review', 'approved', 'revisi'])->default('draft');
            $table->date('tanggal_submit')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('dosen', 'id')->onDelete('set null');
            $table->date('tanggal_review')->nullable();
            $table->text('catatan_review')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_gkm');
    }
};
