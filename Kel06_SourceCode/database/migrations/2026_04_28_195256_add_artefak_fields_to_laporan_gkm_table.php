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
        Schema::table('laporan_gkm', function (Blueprint $table) {
            // Add fields for laporan artefak only if they don't exist
            if (!Schema::hasColumn('laporan_gkm', 'periode')) {
                $table->string('periode')->nullable()->after('jenis_laporan');
            }
            if (!Schema::hasColumn('laporan_gkm', 'bulan')) {
                $table->string('bulan')->nullable()->after('periode');
            }
            if (!Schema::hasColumn('laporan_gkm', 'tahun')) {
                $table->integer('tahun')->nullable()->after('bulan');
            }
            if (!Schema::hasColumn('laporan_gkm', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('prodi_id');
            }
            if (!Schema::hasColumn('laporan_gkm', 'template_id')) {
                $table->unsignedBigInteger('template_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('laporan_gkm', 'status')) {
                $table->string('status')->default('pending')->after('template_id');
            }
            if (!Schema::hasColumn('laporan_gkm', 'konten_laporan')) {
                $table->text('konten_laporan')->nullable()->after('status');
            }
            if (!Schema::hasColumn('laporan_gkm', 'file_word')) {
                $table->string('file_word')->nullable()->after('konten_laporan');
            }
            if (!Schema::hasColumn('laporan_gkm', 'file_pdf')) {
                $table->string('file_pdf')->nullable()->after('file_word');
            }
            if (!Schema::hasColumn('laporan_gkm', 'total_rps')) {
                $table->integer('total_rps')->default(0)->after('file_pdf');
            }
            if (!Schema::hasColumn('laporan_gkm', 'total_materi')) {
                $table->integer('total_materi')->default(0)->after('total_rps');
            }
            if (!Schema::hasColumn('laporan_gkm', 'error_message')) {
                $table->text('error_message')->nullable()->after('total_materi');
            }
            if (!Schema::hasColumn('laporan_gkm', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('error_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gkm', function (Blueprint $table) {
            // Drop columns if they exist
            $columns = ['periode', 'bulan', 'tahun', 'user_id', 'template_id', 
                       'status', 'konten_laporan', 'file_word', 'file_pdf', 'total_rps', 
                       'total_materi', 'error_message', 'generated_at'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('laporan_gkm', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
