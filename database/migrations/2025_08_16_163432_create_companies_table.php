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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_type_id')->constrained()->onDelete('restrict');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('business_hours')->nullable(); // {monday: {start: '09:00', end: '17:00'}, ...}
            $table->json('call_settings')->nullable(); // max_concurrency, retries, recording, etc.
            $table->json('provider_settings')->nullable(); // ElevenLabs API key, Gateway credentials
            $table->json('compliance_settings')->nullable(); // GDPR/HIPAA mode, data retention
            $table->string('default_language', 5)->default('en');
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
