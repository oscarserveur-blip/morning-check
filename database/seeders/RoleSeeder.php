<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ce seeder n'est plus utilisé car les rôles sont stockés
        // directement comme chaînes dans la colonne 'role' de la table users
        // Les rôles disponibles sont : 'admin' et 'gestionnaire'
        
        $this->command->info('RoleSeeder désactivé - Les rôles sont gérés directement dans la table users');
    }
}