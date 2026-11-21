<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->unique();
            $table->string('email_status')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropIndex(['email_status']);
            $table->dropColumn(['receipt_number', 'email_status']);
        });
    }
};
