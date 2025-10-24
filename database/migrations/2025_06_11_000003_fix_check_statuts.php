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
        // Mettre à jour les statuts existants pour correspondre aux nouvelles valeurs
        DB::statement("UPDATE checks SET statut = 'success' WHERE statut = 'completed'");
        DB::statement("UPDATE checks SET statut = 'error' WHERE statut = 'failed'");
        DB::statement("UPDATE service_checks SET statut = 'success' WHERE statut = 'completed'");
        DB::statement("UPDATE service_checks SET statut = 'error' WHERE statut = 'failed'");

        // Modifier l'enum de la table checks
        try {
            DB::statement("ALTER TABLE checks MODIFY COLUMN statut ENUM('pending', 'in_progress', 'success', 'warning', 'error') DEFAULT 'pending'");
        } catch (\Exception $e) {
            DB::statement("ALTER TABLE checks CHANGE statut statut ENUM('pending', 'in_progress', 'success', 'warning', 'error') DEFAULT 'pending'");
        }

        // Modifier l'enum de la table service_checks
        try {
            DB::statement("ALTER TABLE service_checks MODIFY COLUMN statut ENUM('pending', 'in_progress', 'success', 'warning', 'error') DEFAULT 'pending'");
        } catch (\Exception $e) {
            DB::statement("ALTER TABLE service_checks CHANGE statut statut ENUM('pending', 'in_progress', 'success', 'warning', 'error') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remettre à jour les statuts
        DB::statement("UPDATE checks SET statut = 'completed' WHERE statut = 'success'");
        DB::statement("UPDATE checks SET statut = 'failed' WHERE statut = 'error'");
        DB::statement("UPDATE service_checks SET statut = 'completed' WHERE statut = 'success'");
        DB::statement("UPDATE service_checks SET statut = 'failed' WHERE statut = 'error'");

        // Restaurer l'enum original de la table checks
        try {
            DB::statement("ALTER TABLE checks MODIFY COLUMN statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        } catch (\Exception $e) {
            DB::statement("ALTER TABLE checks CHANGE statut statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        }

        // Restaurer l'enum original de la table service_checks
        try {
            DB::statement("ALTER TABLE service_checks MODIFY COLUMN statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        } catch (\Exception $e) {
            DB::statement("ALTER TABLE service_checks CHANGE statut statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
        }
    }
}; 