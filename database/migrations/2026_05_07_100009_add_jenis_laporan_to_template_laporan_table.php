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
            if (!Schema::hasColumn('template_laporan', 'jenis_laporan')) {
                $table->string('jenis_laporan', 50)->nullable()->after('jenis_template');
            }
            
            if (!Schema::hasColumn('template_laporan', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('uploaded_by')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_laporan', function (Blueprint $table) {
            if (Schema::hasColumn('template_laporan', 'jenis_laporan')) {
                $table->dropColumn('jenis_laporan');
            }
            
            if (Schema::hasColumn('template_laporan', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
