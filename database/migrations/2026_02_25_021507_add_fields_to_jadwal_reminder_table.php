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
        Schema::table('jadwal_reminder', function (Blueprint $table) {
            $table->string('nama_jadwal')->nullable()->after('id');
            $table->json('hari_pengiriman')->nullable()->after('hari_sebelum_deadline');
            $table->time('jam_pengiriman')->nullable()->after('hari_pengiriman');
            $table->date('tanggal_mulai')->nullable()->after('jam_pengiriman');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            $table->text('pesan_template')->nullable()->after('template_pesan');
            $table->boolean('is_active')->default(true)->after('status');
            $table->foreignId('dibuat_oleh')->nullable()->after('is_active')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_reminder', function (Blueprint $table) {
            $table->dropForeign(['dibuat_oleh']);
            $table->dropColumn([
                'nama_jadwal',
                'hari_pengiriman',
                'jam_pengiriman',
                'tanggal_mulai',
                'tanggal_selesai',
                'pesan_template',
                'is_active',
                'dibuat_oleh'
            ]);
        });
    }
};
