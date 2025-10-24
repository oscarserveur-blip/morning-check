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
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time');
            $table->foreignId('client_id')->constrained()->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->enum('statut', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();
            $table->unique(['client_id', 'date_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
