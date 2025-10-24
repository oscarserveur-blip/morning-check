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
        Schema::table('checks', function (Blueprint $table) {
            // Ajouter le champ notes
            $table->text('notes')->nullable()->after('statut');
            
            // Modifier l'enum statut pour inclure in_progress
            // Note: MySQL ne permet pas de modifier un enum directement, 
            // nous devons le faire en plusieurs étapes
        });
        
        // Mettre à jour l'enum statut pour inclure in_progress
        DB::statement("ALTER TABLE checks MODIFY COLUMN statut ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checks', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
        
        // Revenir à l'enum original
        DB::statement("ALTER TABLE checks MODIFY COLUMN statut ENUM('pending', 'completed', 'failed') DEFAULT 'pending'");
    }
}; 