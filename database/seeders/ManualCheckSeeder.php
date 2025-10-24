<?php

namespace Database\Seeders;

use App\Models\Check;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceCheck;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ManualCheckSeeder extends Seeder
{
    public function run(): void
    {
        // S'assurer qu'il y a au moins un template
        $template = \App\Models\Template::first() ?? \App\Models\Template::create([
            'name' => 'Template par défaut',
            'description' => 'Template initial',
            'type' => 'excel',
        ]);

        // Récupérer le premier client uniquement (ne pas en créer pour éviter autre client)
        $client = Client::first();

        // Récupérer le premier utilisateur
        $user = User::first();

        if (!$client || !$user) {
            $this->command->error('Client ou utilisateur non trouvé');
            return;
        }

        // Pour chaque client, créer/rafraîchir un check avec tous ses services
        $clients = Client::with('categories.services')->get();
        foreach ($clients as $client) {
            if (!$user) continue;
            $dateTime = Carbon::now();
            $check = Check::firstOrCreate(
                [
                    'client_id' => $client->id,
                    'date_time' => $dateTime,
                ],
                [
                    'created_by' => $user->id,
                    'statut' => 'pending'
                ]
            );

            // Synchroniser les service_checks avec tous les services actuels
            $existing = $check->serviceChecks()->pluck('service_id')->toArray();
            $createdCount = 0;
            foreach ($client->categories as $category) {
                foreach ($category->services as $service) {
                    if (!in_array($service->id, $existing)) {
                        ServiceCheck::create([
                            'check_id' => $check->id,
                            'service_id' => $service->id,
                            'statut' => 'pending',
                            'observations' => null,
                            'notes' => null
                        ]);
                        $createdCount++;
                    }
                }
            }
            $this->command->info("Check pour {$client->label} prêt. Nouveaux services ajoutés: {$createdCount}");
        }
    }
} 