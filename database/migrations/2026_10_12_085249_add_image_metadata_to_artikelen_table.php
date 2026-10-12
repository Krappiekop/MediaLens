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
        Schema::table('artikelen', function (Blueprint $table) {
            $table->text('afbeelding_url')->nullable();
            $table->string('afbeelding_credit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artikelen', function (Blueprint $table) {
            $table->dropColumn(['afbeelding_url', 'afbeelding_credit']);
        });
    }
};
