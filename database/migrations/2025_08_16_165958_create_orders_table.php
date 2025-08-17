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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('ordered_at')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'order_number']);
            $table->index(['contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
