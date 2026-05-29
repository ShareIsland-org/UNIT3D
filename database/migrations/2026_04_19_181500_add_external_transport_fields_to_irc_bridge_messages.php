<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('irc_bridge_messages', function (Blueprint $table): void {
            $table->timestamp('transport_submission_at')->nullable()->after('provenance');
            $table->integer('transport_response_code')->nullable()->after('transport_submission_at');
            $table->text('transport_error')->nullable()->after('transport_response_code');
            $table->index(['delivery_state', 'created_at'], 'irc_bridge_messages_delivery_state_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('irc_bridge_messages', function (Blueprint $table): void {
            $table->dropIndex('irc_bridge_messages_delivery_state_created_at_index');
            $table->dropColumn(['transport_submission_at', 'transport_response_code', 'transport_error']);
        });
    }
};
