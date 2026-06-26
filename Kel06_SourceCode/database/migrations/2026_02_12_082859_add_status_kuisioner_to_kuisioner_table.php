<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuisioner', function (Blueprint $table) {
            $table->string('status_kuisioner')
                  ->default('Draft');
        });
    }

    public function down(): void
    {
        Schema::table('kuisioner', function (Blueprint $table) {
            $table->dropColumn('status_kuisioner');
        });
    }
};
