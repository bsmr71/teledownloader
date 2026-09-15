<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('order_id')->unique();
            $table->unsignedInteger('gross_amount');
            $table->string('payment_gateway')->default('bri');
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->default('pending');
            $table->text('payment_url')->nullable();
            $table->text('qr_string')->nullable();
            $table->string('va_number')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
