<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update kode prodi dari TK ke NM
        DB::table('prodi')
            ->where('kode_prodi', 'TK')
            ->update([
                'kode_prodi' => 'NM',
                'nama_singkat' => 'NM'
            ]);

        // Update username user dari gkm_tk ke gkm_nm
        DB::table('users')
            ->where('username', 'gkm_tk')
            ->update([
                'username' => 'gkm_nm',
                'name' => 'GKM NM',
                'email' => 'gkm.nm@example.com'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan dari NM ke TK
        DB::table('prodi')
            ->where('kode_prodi', 'NM')
            ->update([
                'kode_prodi' => 'TK',
                'nama_singkat' => 'TK'
            ]);

        // Kembalikan username user dari gkm_nm ke gkm_tk
        DB::table('users')
            ->where('username', 'gkm_nm')
            ->update([
                'username' => 'gkm_tk',
                'name' => 'GKM TK',
                'email' => 'gkm.tk@example.com'
            ]);
    }
};
