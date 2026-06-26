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
        Schema::create('matakuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->constrained('prodi')->onDelete('cascade');
            $table->string('kode_mk')->unique();
            $table->string('nama_mk');
            $table->integer('sks')->nullable();
            $table->integer('semester')->nullable();
            $table->string('jenis_mk')->nullable(); // Teori, Praktik, dll
            $table->text('deskripsi')->nullable();
            $table->text('capaian_pembelajaran')->nullable();
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matakuliah');
    }
};
