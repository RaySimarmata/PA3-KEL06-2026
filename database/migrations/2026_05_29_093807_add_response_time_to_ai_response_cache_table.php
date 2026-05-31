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
        Schema::table('ai_response_cache', function (Blueprint $table) {
            $table->decimal('response_time', 8, 2)->nullable()->after('response_length')->comment('Actual API response time in seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_response_cache', function (Blueprint $table) {
            $table->dropColumn('response_time');
        });
    }
};
