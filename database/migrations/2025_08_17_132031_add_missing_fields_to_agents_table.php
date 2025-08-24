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
        Schema::table('agents', function (Blueprint $table) {
            // Add missing fields that the frontend expects
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('system_prompt')->nullable();
            $table->text('greeting_message')->nullable();
            $table->text('closing_message')->nullable();
            $table->json('voice_settings')->nullable();
            $table->json('transfer_conditions')->nullable();
            $table->json('conversation_flow')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'created_by',
                'system_prompt',
                'greeting_message',
                'closing_message',
                'voice_settings',
                'transfer_conditions',
                'conversation_flow'
            ]);
        });
    }
};
