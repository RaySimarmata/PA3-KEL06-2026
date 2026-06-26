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
        Schema::table('template_laporan', function (Blueprint $table) {
            if (!Schema::hasColumn('template_laporan', 'jenis_template')) {
                $table->string('jenis_template', 50)->default('laporan_bulanan')->after('nama_template');
            }
            if (!Schema::hasColumn('template_laporan', 'struktur_template')) {
                $table->json('struktur_template')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('template_laporan', 'contoh_konten')) {
                $table->text('contoh_konten')->nullable()->after('struktur_template');
            }
            if (!Schema::hasColumn('template_laporan', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('contoh_konten');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_laporan', function (Blueprint $table) {
            $table->dropColumn(['jenis_template', 'struktur_template', 'contoh_konten', 'is_active']);
        });
    }
};
