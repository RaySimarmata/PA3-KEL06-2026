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
        Schema::table('jadwal_dosen', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | KELAS DARI API
            |--------------------------------------------------------------------------
            | Contoh:
            | 14IF1
            | 14SI1
            | 14TRPL1
            |--------------------------------------------------------------------------
            */
            $table->string('kelas')
                ->nullable()
                ->after('kode_mk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_dosen', function (Blueprint $table) {

            $table->dropColumn('kelas');
        });
    }
};