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
        Schema::create('periode_akademik', function (Blueprint $table) {
    $table->id();
    $table->string('tahun_ajaran');
    $table->integer('semester');
    $table->string('semester_label');
    $table->date('start_date');
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_akademik');
    }
};
