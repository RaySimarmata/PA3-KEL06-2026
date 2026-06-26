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
        Schema::table('template_laporan', function (Blueprint $table) {
            $table->boolean('is_indexed')->default(false)->after('is_active');
            $table->timestamp('indexed_at')->nullable()->after('is_indexed');
            $table->integer('total_chunks')->default(0)->after('indexed_at');
            $table->json('structure_metadata')->nullable()->after('total_chunks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_laporan', function (Blueprint $table) {
            $table->dropColumn(['is_indexed', 'indexed_at', 'total_chunks', 'structure_metadata']);
        });
    }
};
