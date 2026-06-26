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
    $table->string('source')->default('excel');
    $table->float('index_kepuasan')->nullable();
    $table->float('persen_kepuasan')->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kuesioner_uploads', function (Blueprint $table) {
            //
        });
    }
};
