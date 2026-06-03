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
            // Tambah kolom semester jika belum ada
            if (!Schema::hasColumn('kuesioner_uploads', 'semester')) {
                $table->string('semester')->nullable()->after('periode');
            }
            
            // Tambah kolom index_kepuasan jika belum ada
            if (!Schema::hasColumn('kuesioner_uploads', 'index_kepuasan')) {
                $table->decimal('index_kepuasan', 5, 2)->nullable()->after('hasil_analisis');
            }
            
            // Tambah kolom persen_kepuasan jika belum ada
            if (!Schema::hasColumn('kuesioner_uploads', 'persen_kepuasan')) {
                $table->decimal('persen_kepuasan', 5, 2)->nullable()->after('index_kepuasan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->dropColumn(['semester', 'index_kepuasan', 'persen_kepuasan']);
        });
    }
};
