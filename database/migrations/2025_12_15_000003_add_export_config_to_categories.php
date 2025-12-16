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
        Schema::table('categories', function (Blueprint $table) {
            $table->json('export_columns')->nullable()->after('status');
            $table->boolean('show_stats')->default(false)->after('export_columns');
            $table->json('stats_config')->nullable()->after('show_stats');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['export_columns', 'show_stats', 'stats_config']);
        });
    }
};

