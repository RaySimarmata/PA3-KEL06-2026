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
            $table->string('ppt_path')->nullable()->after('dokumen_hasil_path');
            $table->timestamp('ppt_generated_at')->nullable()->after('ppt_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->dropColumn(['ppt_path', 'ppt_generated_at']);
        });
    }
};
