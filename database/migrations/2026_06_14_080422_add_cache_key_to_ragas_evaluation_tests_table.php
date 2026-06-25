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
        Schema::table('ragas_evaluation_tests', function (Blueprint $table) {
            $table->string('cache_key', 64)->nullable()->after('id')->unique()->comment('Reference to AI cache in MongoDB');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ragas_evaluation_tests', function (Blueprint $table) {
            $table->dropColumn('cache_key');
        });
    }
};
