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
        Schema::table('service_checks', function (Blueprint $table) {
            // Ajouter un champ notes pour des observations générales
            if (!Schema::hasColumn('service_checks', 'notes')) {
                $table->text('notes')->nullable()->after('commentaire');
            }
            
            // Ajouter un champ observations si il n'existe pas
            if (!Schema::hasColumn('service_checks', 'observations')) {
                $table->text('observations')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_checks', function (Blueprint $table) {
            // Supprimer les champs ajoutés
            if (Schema::hasColumn('service_checks', 'notes')) {
                $table->dropColumn('notes');
            }
            
            if (Schema::hasColumn('service_checks', 'observations')) {
                $table->dropColumn('observations');
            }
        });
    }
}; 