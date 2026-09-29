<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('artikelen', function (Blueprint $table) {
            $table->id();
            $table->string('titel');
            $table->date('publicatiedatum');
            $table->longText('volledige_tekst');
            $table->string('url');
            $table->foreignId('bron_id')->constrained('bronnen');
            $table->foreignId('gebeurtenis_id')->constrained('gebeurtenissen');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artikelen');
    }
};
