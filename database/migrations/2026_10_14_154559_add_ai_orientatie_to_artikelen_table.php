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
            $table->string('ai_orientatie')->nullable()->after('embedding');
            $table->unsignedTinyInteger('ai_zekerheid')->nullable()->after('ai_orientatie');
            $table->text('ai_onderbouwing')->nullable()->after('ai_zekerheid');
            $table->string('ai_model')->nullable()->after('ai_onderbouwing');
            $table->timestamp('ai_geschat_op')->nullable()->after('ai_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artikelen', function (Blueprint $table) {
            $table->dropColumn(['ai_orientatie', 'ai_zekerheid', 'ai_onderbouwing', 'ai_model', 'ai_geschat_op']);
        });
    }
};
