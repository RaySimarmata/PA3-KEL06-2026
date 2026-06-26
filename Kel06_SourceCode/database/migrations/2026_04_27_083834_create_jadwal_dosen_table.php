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
        Schema::create('jadwal_dosen', function (Blueprint $table) {
    $table->id();
    $table->string('pegawai_id');
    $table->string('kode_mk');
    $table->string('kuliah_id')->nullable();
    $table->string('semester');
    $table->string('tahun_ajaran');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_dosen');
    }
};
