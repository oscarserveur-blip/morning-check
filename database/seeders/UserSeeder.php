<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur
        $admin = User::firstOrCreate(
            ['email' => 'admin@checkdumatin.com'],
            [
                'name' => 'Administrateur Principal',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Créer des gestionnaires
        $gestionnaire1 = User::firstOrCreate(
            ['email' => 'gestionnaire1@checkdumatin.com'],
            [
                'name' => 'Jean Dupont',
                'password' => Hash::make('password'),
                'role' => 'gestionnaire',
                'email_verified_at' => now(),
            ]
        );

        $gestionnaire2 = User::firstOrCreate(
            ['email' => 'gestionnaire2@checkdumatin.com'],
            [
                'name' => 'Marie Martin',
                'password' => Hash::make('password'),
                'role' => 'gestionnaire',
                'email_verified_at' => now(),
            ]
        );

        // Assigner des clients aux gestionnaires (si des clients existent)
        $clients = Client::all();
        
        if ($clients->count() > 0) {
            // Diviser les clients entre les gestionnaires
            $clientsChunks = $clients->chunk(ceil($clients->count() / 2));
            
            if ($clientsChunks->count() > 0) {
                $gestionnaire1->clients()->sync($clientsChunks[0]->pluck('id'));
            }
            
            if ($clientsChunks->count() > 1) {
                $gestionnaire2->clients()->sync($clientsChunks[1]->pluck('id'));
            }
        }

        $this->command->info('Utilisateurs créés avec succès :');
        $this->command->info('- Admin: admin@checkdumatin.com / password');
        $this->command->info('- Gestionnaire 1: gestionnaire1@checkdumatin.com / password');
        $this->command->info('- Gestionnaire 2: gestionnaire2@checkdumatin.com / password');
    }
} 