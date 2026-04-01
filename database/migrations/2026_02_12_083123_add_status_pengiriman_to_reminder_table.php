<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->string('status_pengiriman')
                  ->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('reminder', function (Blueprint $table) {
            $table->dropColumn('status_pengiriman');
        });
    }
};
