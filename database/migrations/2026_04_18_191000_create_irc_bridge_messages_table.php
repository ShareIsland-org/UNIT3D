<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('irc_bridge_messages', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('direction');
            $table->string('transport')->default('irc');
            $table->unsignedInteger('local_message_id')->nullable();
            $table->unsignedInteger('chatroom_id')->nullable();
            $table->string('remote_channel')->nullable();
            $table->string('external_message_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->string('canonical_username')->nullable();
            $table->string('irc_nick')->nullable();
            $table->string('delivery_state')->default('reserved');
            $table->char('payload_hash', 64)->nullable();
            $table->json('provenance')->nullable();
            $table->timestamps();

            $table->unique(['direction', 'remote_channel', 'external_message_id'], 'irc_bridge_messages_direction_channel_external_unique');
            $table->unique('local_message_id');
            $table->index(['chatroom_id', 'created_at']);
            $table->index(['canonical_username', 'created_at']);

            $table->foreign('local_message_id')->references('id')->on('messages')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('chatroom_id')->references('id')->on('chatrooms')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('irc_bridge_messages');
    }
};
