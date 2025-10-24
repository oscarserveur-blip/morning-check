<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type')->default('excel');
            $table->string('header_logo')->nullable();
            $table->string('header_title')->nullable();
            $table->string('header_color')->nullable();
            $table->json('section_config')->nullable();
            $table->string('footer_text')->nullable();
            $table->string('footer_color')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
}; 