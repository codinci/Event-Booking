<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The ConversationID Daraja issues when it accepts a payout. Unlike the
        // OriginatorConversationID (our own reference, which is in the callback
        // URL), it is never public, so result callbacks are checked against it.
        Schema::table('refunds', function (Blueprint $table) {
            $table->string('conversation_id')->nullable()->unique()->after('originator_conversation_id');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropUnique(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
