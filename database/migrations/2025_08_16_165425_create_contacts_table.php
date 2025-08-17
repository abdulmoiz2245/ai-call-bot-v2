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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->json('tags')->nullable(); // Array of tags
            $table->string('segment')->nullable(); // Customer segment
            $table->string('locale', 5)->default('en');
            $table->enum('status', ['new', 'queued', 'calling', 'called', 'success', 'failed', 'opted_out'])->default('new');
            $table->boolean('is_dnc')->default(false); // Do Not Call
            $table->timestamp('opted_out_at')->nullable();
            $table->string('opt_out_reason')->nullable();
            $table->json('custom_fields')->nullable(); // Additional custom data
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'phone']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'is_dnc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
