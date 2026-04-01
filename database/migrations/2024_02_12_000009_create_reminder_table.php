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
        Schema::create('reminder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->nullable()->constrained('dosen')->onDelete('cascade');
            $table->foreignId('kaprodi_id')->nullable()->constrained('dosen', 'id')->onDelete('cascade');
            $table->enum('tipe_reminder', [
                'rps_review',
                'materi_upload',
                'perwaliaan',
                'persiapan_kuliah',
                'review_soal',
                'kuisioner'
            ])->nullable();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_reminder')->nullable();
            $table->time('waktu_reminder')->nullable();
            $table->enum('status', ['belum_kirim', 'sudah_kirim', 'dibaca', 'dikerjakan'])->default('belum_kirim');
            $table->date('tanggal_kirim')->nullable();
            $table->string('metode_pengiriman')->default('email'); // email, sms, in_app
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder');
    }
};
