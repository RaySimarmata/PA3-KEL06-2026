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
        Schema::create('log_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->nullable()->constrained('reminder')->onDelete('cascade');
            $table->string('penerima_email');
            $table->string('subjek');
            $table->text('isi_email')->nullable();
            $table->enum('status_pengiriman', ['success', 'failed', 'pending'])->default('pending');
            $table->text('pesan_error')->nullable();
            $table->date('tanggal_pengiriman')->nullable();
            $table->integer('percobaan_kirim')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_email');
    }
};
