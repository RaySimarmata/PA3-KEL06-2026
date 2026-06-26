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
        Schema::table('document_chunks', function (Blueprint $table) {
            // Make kuesioner_upload_id nullable
            $table->foreignId('kuesioner_upload_id')->nullable()->change();
            
            // Add template_id
            $table->foreignId('template_id')->nullable()->after('kuesioner_upload_id')
                ->constrained('template_laporan')->onDelete('cascade');
            
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_chunks', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropIndex(['template_id']);
            $table->dropColumn('template_id');
            
            // Restore kuesioner_upload_id to not nullable
            $table->foreignId('kuesioner_upload_id')->nullable(false)->change();
        });
    }
};
