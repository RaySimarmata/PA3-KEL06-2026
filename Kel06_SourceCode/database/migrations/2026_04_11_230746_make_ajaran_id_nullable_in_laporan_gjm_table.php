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
            // Drop the foreign key constraint first
            $table->dropForeign(['ajaran_id']);
            
            // Make ajaran_id nullable
            $table->foreignId('ajaran_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('ajaran_id')->references('id')->on('ajaran')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['ajaran_id']);
            
            // Make ajaran_id NOT nullable again
            $table->foreignId('ajaran_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with cascade
            $table->foreign('ajaran_id')->references('id')->on('ajaran')->onDelete('cascade');
        });
    }
};
