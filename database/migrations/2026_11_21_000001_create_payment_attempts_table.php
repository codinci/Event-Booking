<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('payment_hold_expires_at')->nullable()->index();
        });

        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->restrictOnDelete();
            $table->uuid('reference')->unique();
            $table->string('gateway');
            $table->string('provider_reference')->nullable();
            $table->string('status');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->unsignedBigInteger('organizer_share');
            $table->unsignedBigInteger('platform_share');
            $table->string('phone')->nullable();
            $table->timestamp('next_check_at')->nullable();
            $table->timestamp('review_at');
            $table->string('refund_status')->nullable();
            $table->string('sms_status')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'provider_reference']);
            $table->index(['status', 'next_check_at']);
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_hold_expires_at']);
            $table->dropColumn('payment_hold_expires_at');
        });
    }
};
