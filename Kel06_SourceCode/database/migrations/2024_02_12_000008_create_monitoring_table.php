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
        Schema::create('monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajaran_id')->constrained('ajaran')->onDelete('cascade');
            $table->foreignId('prodi_id')->constrained('prodi')->onDelete('cascade');
            $table->enum('jenis_monitoring', ['rps', 'materi', 'kuisioner', 'perwaliaan', 'evaluasi'])->nullable();
            $table->integer('total_dosen')->nullable();
            $table->integer('dosen_selesai')->nullable();
            $table->integer('dosen_belum')->nullable();
            $table->decimal('persentase_kepatuhan', 5, 2)->nullable();
            $table->date('tanggal_monitoring')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring');
    }
};
