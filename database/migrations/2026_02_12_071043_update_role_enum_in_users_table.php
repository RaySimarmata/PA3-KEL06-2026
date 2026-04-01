<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kaprodi','dosen','GJM','GKM','gjm_reviewer','gkm_reviewer') DEFAULT 'dosen'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','kaprodi','dosen','gjm_reviewer','gkm_reviewer') DEFAULT 'dosen'");
    }
};
