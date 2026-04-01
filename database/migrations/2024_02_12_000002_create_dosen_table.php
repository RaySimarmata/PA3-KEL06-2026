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
        Schema::create('dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_lengkap');
            $table->string('nidn')->unique();
            $table->string('gelar_akademik')->nullable();
            $table->string('jabatan_akademik')->nullable();
            $table->string('kontak_email');
            $table->foreignId('prodi_id')->constrained('prodi')->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('foto_profil')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif', 'pensiun'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosen');
    }
};
