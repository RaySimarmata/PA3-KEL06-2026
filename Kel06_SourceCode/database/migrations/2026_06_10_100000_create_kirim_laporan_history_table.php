<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kirim_laporan_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('recipients');
            $table->text('cc')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->json('laporan_ids')->nullable();
            $table->json('attachment_names')->nullable();
            $table->json('recipient_statuses')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status')->default('failed');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kirim_laporan_history');
    }
};
