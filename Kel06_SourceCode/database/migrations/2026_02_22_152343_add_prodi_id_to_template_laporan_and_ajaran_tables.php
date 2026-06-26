<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah prodi_id ke template_laporan (sudah ada di migration sebelumnya, skip jika sudah ada)
        if (Schema::hasTable('template_laporan') && !Schema::hasColumn('template_laporan', 'prodi_id')) {
            Schema::table('template_laporan', function (Blueprint $table) {
                $table->unsignedBigInteger('prodi_id')->nullable()->after('id');
                $table->foreign('prodi_id')->references('id')->on('prodi')->onDelete('cascade');
            });
        }

        // Tambah prodi_id ke ajaran (periode akademik)
        if (Schema::hasTable('ajaran') && !Schema::hasColumn('ajaran', 'prodi_id')) {
            Schema::table('ajaran', function (Blueprint $table) {
                $table->unsignedBigInteger('prodi_id')->nullable()->after('id');
                $table->foreign('prodi_id')->references('id')->on('prodi')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('template_laporan')) {
            Schema::table('template_laporan', function (Blueprint $table) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            });
        }

        if (Schema::hasTable('ajaran')) {
            Schema::table('ajaran', function (Blueprint $table) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            });
        }
    }
};
