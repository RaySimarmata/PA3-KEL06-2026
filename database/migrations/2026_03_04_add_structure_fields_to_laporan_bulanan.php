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
            $table->longText('konten')->nullable()->after('hasil_laporan');
            $table->text('metadata')->nullable()->after('konten');
            $table->string('generated_by', 50)->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_bulanan', function (Blueprint $table) {
            $table->dropColumn(['konten', 'metadata', 'generated_by']);
        });
    }
};
