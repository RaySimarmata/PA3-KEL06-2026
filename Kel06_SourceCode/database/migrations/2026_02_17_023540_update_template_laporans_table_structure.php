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
        // Cek apakah tabel sudah ada
        if (!Schema::hasTable('template_laporan')) {
            Schema::create('template_laporan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prodi_id')->nullable()->constrained('prodi')->onDelete('cascade');
                $table->string('nama_template');
                $table->string('nama_file');
                $table->string('jenis_file', 10);
                $table->string('file_path');
                $table->integer('ukuran_file')->nullable();
                $table->text('deskripsi')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        } else {
            // Jika tabel sudah ada, tambahkan kolom yang belum ada
            Schema::table('template_laporan', function (Blueprint $table) {
                if (!Schema::hasColumn('template_laporan', 'nama_template')) {
                    $table->string('nama_template')->after('id');
                }
                if (!Schema::hasColumn('template_laporan', 'nama_file')) {
                    $table->string('nama_file')->after('nama_template');
                }
                if (!Schema::hasColumn('template_laporan', 'jenis_file')) {
                    $table->string('jenis_file', 10)->after('nama_file');
                }
                if (!Schema::hasColumn('template_laporan', 'file_path')) {
                    $table->string('file_path')->after('jenis_file');
                }
                if (!Schema::hasColumn('template_laporan', 'ukuran_file')) {
                    $table->integer('ukuran_file')->nullable()->after('file_path');
                }
                if (!Schema::hasColumn('template_laporan', 'deskripsi')) {
                    $table->text('deskripsi')->nullable()->after('ukuran_file');
                }
                if (!Schema::hasColumn('template_laporan', 'uploaded_by')) {
                    $table->foreignId('uploaded_by')->nullable()->after('deskripsi')->constrained('users')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_laporan');
    }
};
