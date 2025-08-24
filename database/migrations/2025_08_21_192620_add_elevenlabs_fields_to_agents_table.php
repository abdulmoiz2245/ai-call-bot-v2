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
            $table->string('elevenlabs_agent_id')->nullable()->after('voice_id');
            $table->boolean('is_elevenlabs_connected')->default(false)->after('elevenlabs_agent_id');
            $table->json('elevenlabs_settings')->nullable()->after('is_elevenlabs_connected');
            $table->timestamp('elevenlabs_last_synced')->nullable()->after('elevenlabs_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'elevenlabs_agent_id',
                'is_elevenlabs_connected',
                'elevenlabs_settings',
                'elevenlabs_last_synced'
            ]);
        });
    }
};
