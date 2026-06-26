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
        Schema::table('ai_evaluation_tests', function (Blueprint $table) {
            $table->unsignedBigInteger('laporan_id')->nullable()->after('feature');
            $table->foreign('laporan_id')->references('id')->on('laporan_gjm')->onDelete('cascade');
            $table->index('laporan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_evaluation_tests', function (Blueprint $table) {
            $table->dropForeign(['laporan_id']);
            $table->dropIndex(['laporan_id']);
            $table->dropColumn('laporan_id');
        });
    }
};
