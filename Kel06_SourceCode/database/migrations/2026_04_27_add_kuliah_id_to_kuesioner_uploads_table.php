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
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->string('kuliah_id')->nullable()->after('pegawai_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->dropColumn('kuliah_id');
        });
    }
};
