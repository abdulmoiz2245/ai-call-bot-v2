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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $table->string('external_call_id')->nullable(); // Gateway call ID
            $table->string('from_number');
            $table->string('to_number');
            $table->enum('status', ['queued', 'ringing', 'answered', 'voicemail', 'busy', 'failed', 'no_answer'])->default('queued');
            $table->integer('duration')->nullable(); // Call duration in seconds
            $table->decimal('cost', 8, 4)->nullable(); // Call cost
            $table->string('recording_url')->nullable();
            $table->json('metadata')->nullable(); // Additional call metadata
            $table->json('gateway_data')->nullable(); // Raw gateway response data
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index(['company_id', 'started_at']);
            $table->index(['external_call_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
