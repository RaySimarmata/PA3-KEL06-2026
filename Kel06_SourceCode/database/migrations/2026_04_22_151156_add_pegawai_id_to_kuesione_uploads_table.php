<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->unsignedBigInteger('pegawai_id')->nullable()->after('tingkat');

        });
    }

    public function down(): void
    {
        Schema::table('kuesione_uploads', function (Blueprint $table) {
            $table->dropForeign(['pegawai_id']);
            $table->dropColumn('pegawai_id');
        });
    }
};