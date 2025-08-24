<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('company_id')->constrained('campaigns')->onDelete('set null');
            $table->string('name')->nullable()->after('last_name'); // Add name field for import compatibility
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['campaign_id', 'name']);
        });
    }
};
