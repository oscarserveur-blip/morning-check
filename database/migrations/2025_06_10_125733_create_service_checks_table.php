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
        Schema::create('service_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('check_id')->constrained()->onDelete('cascade');
            $table->enum('etat', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('commentaire')->nullable();
            $table->enum('statut', ['pending', 'completed', 'failed'])->default('pending');
            $table->foreignId('intervenant')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_checks');
    }
};
