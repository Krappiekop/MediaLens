<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sommige feeds (The Federalist, Daily Mail, The Times of India) leveren artikel-URL's langer dan 255 tekens.
     */
    public function up(): void
    {
        Schema::table('artikelen', function (Blueprint $table) {
            $table->string('url', 2048)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artikelen', function (Blueprint $table) {
            $table->string('url')->change();
        });
    }
};
