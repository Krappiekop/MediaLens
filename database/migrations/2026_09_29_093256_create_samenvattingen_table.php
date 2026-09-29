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
        Schema::create('samenvattingen', function (Blueprint $table) {
            $table->id();
            $table->longText('kernfeiten');
            $table->longText('betrokkenen');
            $table->longText('overeenstemming');
            $table->longText('verschil');
            $table->foreignId('gebeurtenis_id')->constrained('gebeurtenissen');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samenvattingen');
    }
};
