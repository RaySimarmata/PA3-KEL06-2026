<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_akademik', function (Blueprint $table) {
            $table->id();

            // Tahun ajaran format: 2025/2026
            $table->string('tahun_ajaran');

            // 1 = Ganjil, 2 = Genap
            $table->tinyInteger('semester');

            // Label biar tidak perlu hitung ulang
            $table->enum('semester_label', ['Ganjil', 'Genap']);

            // Tanggal mulai semester
            $table->date('start_date');

            // (Optional tapi sangat disarankan)
            $table->date('end_date')->nullable();

            // Status aktif (default false)
            $table->boolean('is_active')->default(false);

            $table->timestamps();

            // 🔒 Mencegah duplicate (WAJIB)
            $table->unique(['tahun_ajaran', 'semester']);
        });

        // 🔥 OPTIONAL (ADVANCED - PostgreSQL only)
        // Pastikan hanya 1 aktif
        // Kalau pakai MySQL, skip ini
        // DB::statement("CREATE UNIQUE INDEX one_active_periode ON periode_akademik (is_active) WHERE is_active = true;");
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_akademik');
    }
};