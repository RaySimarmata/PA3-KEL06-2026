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
            // Add error_message column for storing error information when generation fails
            if (!Schema::hasColumn('laporan_bulanan', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
