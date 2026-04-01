<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['prodi_id']);
            // Drop the column
            $table->dropColumn('prodi_id');
            // Add user_id to track who uploaded
            $table->unsignedBigInteger('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->unsignedBigInteger('prodi_id')->after('periode');
            $table->foreign('prodi_id')->references('id')->on('prodi');
        });
    }
};