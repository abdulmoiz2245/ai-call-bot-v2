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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'archived'])->default('draft');
            $table->enum('data_source_type', ['contacts', 'leads', 'orders']);
            $table->json('schedule_settings')->nullable(); // start_time, end_time, days, timezone
            $table->integer('max_retries')->default(3);
            $table->integer('max_concurrency')->default(5);
            $table->enum('call_order', ['sequential', 'random', 'priority'])->default('sequential');
            $table->boolean('record_calls')->default(false);
            $table->string('caller_id')->nullable();
            $table->json('filter_criteria')->nullable(); // Filters for contacts/leads/orders
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
