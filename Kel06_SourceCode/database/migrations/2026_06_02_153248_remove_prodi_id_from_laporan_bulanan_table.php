<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL for more control
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Drop unique constraint if exists
        try {
            DB::statement('ALTER TABLE laporan_bulanan DROP INDEX laporan_bulanan_periode_prodi_id_unique');
        } catch (\Exception $e) {
            // Constraint might not exist
        }
        
        // Drop index if exists
        try {
            DB::statement('ALTER TABLE laporan_bulanan DROP INDEX laporan_bulanan_prodi_id_index');
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        // Drop foreign key if exists
        try {
            DB::statement('ALTER TABLE laporan_bulanan DROP FOREIGN KEY laporan_bulanan_prodi_id_foreign');
        } catch (\Exception $e) {
            // Foreign key might not exist
        }
        
        // Drop column if exists
        if (Schema::hasColumn('laporan_bulanan', 'prodi_id')) {
            DB::statement('ALTER TABLE laporan_bulanan DROP COLUMN prodi_id');
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            // Re-add prodi_id column if rollback is needed
            $table->foreignId('prodi_id')->nullable()->after('id')->constrained('prodi')->onDelete('cascade');
        });
    }
};
