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
        Schema::table('laporan_gjm', function (Blueprint $table) {
            // Simpan AI preview/draft yang dihasilkan dari chat
            $table->longText('ai_preview_draft')->nullable()->after('instruksi_prompt')->comment('AI-generated preview/draft dari chat assistant');
            
            // Simpan sections yang sudah di-parse dari AI preview
            $table->json('ai_sections')->nullable()->after('ai_preview_draft')->comment('Parsed sections dari AI preview (latar_belakang, dasar, tujuan, dll)');
            
            // Timestamp untuk tracking kapan preview dibuat
            $table->timestamp('ai_preview_created_at')->nullable()->after('ai_sections')->comment('Waktu AI preview dibuat');
            
            // Flag untuk tracking apakah preview sudah digunakan untuk generate
            $table->boolean('ai_preview_used_for_generation')->default(false)->after('ai_preview_created_at')->comment('Flag: preview sudah digunakan untuk generate laporan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->dropColumn([
                'ai_preview_draft',
                'ai_sections',
                'ai_preview_created_at',
                'ai_preview_used_for_generation',
            ]);
        });
    }
};
