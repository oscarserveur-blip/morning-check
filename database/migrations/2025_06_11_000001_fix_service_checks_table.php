<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // D'abord, mettre à jour les données existantes pour qu'elles correspondent au nouvel enum
        DB::statement("UPDATE service_checks SET statut = 'pending' WHERE statut NOT IN ('pending', 'in_progress', 'success', 'warning', 'error')");
        
        Schema::table('service_checks', function (Blueprint $table) {
            // Supprimer le champ 'etat' redondant s'il existe
            if (Schema::hasColumn('service_checks', 'etat')) {
                $table->dropColumn('etat');
            }
            
            // Renommer 'commentaire' en 'observations' pour plus de clarté
            if (Schema::hasColumn('service_checks', 'commentaire')) {
                $table->renameColumn('commentaire', 'observations');
            }
            
            // Ajouter un champ notes pour des observations générales
            if (!Schema::hasColumn('service_checks', 'notes')) {
                $table->text('notes')->nullable()->after('observations');
            }
        });
        
        // Mettre à jour l'enum statut pour être cohérent avec les checks
        // Utiliser une approche plus sûre pour MySQL
        try {
            DB::statement("ALTER TABLE service_checks MODIFY COLUMN statut ENUM('pending', 'in_progress', 'success', 'warning', 'error') DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Si l'approche directe échoue, utiliser une approche alternative
            DB::statement("ALTER TABLE service_checks CHANGE statut statut ENUM('pending', 'in_progress', 'success', 'warning', 'error') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_checks', function (Blueprint $table) {
            // Remettre le champ 'etat'
            $table->enum('etat', ['pending', 'completed', 'failed'])->default('pending')->after('check_id');
            
            // Renommer 'observations' en 'commentaire'
            if (Schema::hasColumn('service_checks', 'observations')) {
                $table->renameColumn('observations', 'commentaire');
            }
            
            // Supprimer le champ notes
            if (Schema::hasColumn('service_checks', 'notes')) {
                $table->dropColumn('notes');
            }
        });
        
        // Revenir à l'enum original
        try {
            DB::statement("ALTER TABLE service_checks MODIFY COLUMN statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        } catch (\Exception $e) {
            DB::statement("ALTER TABLE service_checks CHANGE statut statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        }
    }
}; 