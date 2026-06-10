<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tambahkan columns untuk menyimpan draft AI preview yang sudah direvisi
     */
    public function up(): void
    {
        if (!Schema::hasTable('laporan_gkm')) {
            return;
        }

        Schema::table('laporan_gkm', function (Blueprint $table) {
            // Kolom untuk menyimpan draft laporan dari AI (setelah revisi user)
            if (!Schema::hasColumn('laporan_gkm', 'ai_preview_draft')) {
                $table->longText('ai_preview_draft')->nullable()->comment('Draft laporan hasil chat dengan AI (yang sudah direvisi user)');
            }

            // Kolom untuk menyimpan struktur sections dari markdown
            if (!Schema::hasColumn('laporan_gkm', 'ai_sections')) {
                $table->json('ai_sections')->nullable()->comment('Struktur sections dari markdown AI response');
            }

            // Kolom untuk track kapan terakhir kali preview diupdate
            if (!Schema::hasColumn('laporan_gkm', 'ai_preview_updated_at')) {
                $table->timestamp('ai_preview_updated_at')->nullable()->comment('Waktu terakhir AI preview diupdate');
            }

            // Kolom untuk track apakah laporan sudah di-generate dari preview terbaru
            if (!Schema::hasColumn('laporan_gkm', 'ai_preview_used_for_generation')) {
                $table->boolean('ai_preview_used_for_generation')->default(false)->comment('Apakah laporan sudah di-generate dari preview terbaru');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gkm', function (Blueprint $table) {
            $table->dropColumnIfExists('ai_preview_draft');
            $table->dropColumnIfExists('ai_sections');
            $table->dropColumnIfExists('ai_preview_updated_at');
            $table->dropColumnIfExists('ai_preview_used_for_generation');
        });
    }
};
