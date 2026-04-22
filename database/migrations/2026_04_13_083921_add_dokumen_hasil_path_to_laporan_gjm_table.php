<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->string('dokumen_hasil_path')->nullable()->after('dokumen_path');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_gjm', function (Blueprint $table) {
            $table->dropColumn('dokumen_hasil_path');
        });
    }
};
