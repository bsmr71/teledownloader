<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->date('download_date');
            $table->unsignedInteger('downloads_count')->default(1);
            $table->timestamps();

            $table->index(['device_id', 'download_date']);
            $table->index(['user_id', 'download_date']);
            $table->index(['ip_address', 'download_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_logs');
    }
};
