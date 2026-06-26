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
        Schema::create('dosenn', function (Blueprint $table) {

            // PRIMARY KEY dari API
            $table->bigInteger('dosen_id')->primary();

            $table->bigInteger('pegawai_id')->nullable()->index();
            $table->bigInteger('user_id')->nullable()->index();

            $table->string('nip')->nullable();
            $table->string('nidn')->nullable();

            // ⚠️ dibuat nullable karena API bisa "-"
            $table->string('nama')->nullable();
            $table->string('inisial_nama')->nullable();
            $table->string('email')->nullable();

            $table->integer('prodi_id')->nullable()->index();
            $table->string('prodi')->nullable();

            $table->string('jabatan_akademik')->nullable();
            $table->string('jabatan_akademik_desc')->nullable();

            $table->text('jenjang_pendidikan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dosenn');
    }
};
