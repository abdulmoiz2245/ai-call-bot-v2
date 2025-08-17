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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('role')->nullable(); // Sales Agent, Support Agent, etc.
            $table->string('tone')->default('professional'); // professional, friendly, casual
            $table->text('persona')->nullable(); // Agent personality description
            $table->string('voice_id')->nullable(); // ElevenLabs voice ID
            $table->string('language', 5)->default('en');
            $table->json('scripts')->nullable(); // greeting, voicemail, fallback scripts
            $table->json('settings')->nullable(); // Additional agent settings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
