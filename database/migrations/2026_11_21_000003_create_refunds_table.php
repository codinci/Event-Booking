<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('payment_attempt_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('reason');
            $table->unsignedBigInteger('amount');
            $table->string('phone')->nullable();
            $table->string('status');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('originator_conversation_id')->nullable()->unique();
            $table->string('transaction_id')->nullable();
            $table->string('result_code')->nullable();
            $table->string('result_desc')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'sent_at']);
        });

        // Late payments recorded before refunds existed were left waiting for manual review.
        DB::table('payment_attempts')
            ->where('status', 'late_success')->where('refund_status', 'review')
            ->orderBy('id')
            ->each(function ($attempt) {
                DB::table('refunds')->insert([
                    'reference' => (string) Str::uuid(),
                    'payment_attempt_id' => $attempt->id,
                    'booking_id' => $attempt->booking_id,
                    'reason' => 'late_payment',
                    'amount' => intdiv((int) $attempt->amount, 100) * 100,
                    'phone' => $attempt->phone,
                    'status' => 'awaiting_approval',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
