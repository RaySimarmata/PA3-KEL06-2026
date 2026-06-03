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
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            // Tambah kolom jenis_kuesioner untuk membedakan UTS, UAS, dll
            if (!Schema::hasColumn('kuesioner_uploads', 'jenis_kuesioner')) {
                $table->string('jenis_kuesioner')->nullable()->after('nama_matakuliah')->comment('UTS, UAS, REGULAR, dll');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->dropColumn('jenis_kuesioner');
        });
    }
};
