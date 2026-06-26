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
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            // Add tipe_laporan column to distinguish UTS vs UAS
            $table->enum('tipe_laporan', ['UTS', 'UAS'])->default('UTS')->after('status');

            // Add periode_akademik_id to reference the academic period
            $table->unsignedBigInteger('periode_akademik_id')->nullable()->after('user_id');

            // Foreign key constraint
            $table->foreign('periode_akademik_id')
                ->references('id')
                ->on('periode_akademik')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            $table->dropForeign(['periode_akademik_id']);
            $table->dropColumn(['tipe_laporan', 'periode_akademik_id']);
        });
    }
};
