<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Check;
use App\Models\Holiday;
use App\Models\Service;
use App\Models\ServiceCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CreateChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checks:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create checks for all clients at their specified time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info("Vérification à : " . $now->format('H:i:s'));
        
        // Vérifier si c'est un weekend
        if ($now->isWeekend()) {
            $this->info('Weekend - No checks to create');
            return;
        }

        // Vérifier si c'est un jour férié
        $isHoliday = Holiday::whereDate('date', $now->toDateString())->exists();
        if ($isHoliday) {
            $this->info('Holiday - No checks to create');
            return;
        }

        $clients = Client::all();
        $createdCount = 0;

        foreach ($clients as $client) {
            try {
                // Convertir l'heure du client en objet Carbon
                $clientTime = Carbon::parse($client->check_time);
                $this->info("Client {$client->label} - Heure de check : " . $clientTime->format('H:i'));
                
                // Vérifier si c'est l'heure de création pour ce client
                if ($now->hour === $clientTime->hour && $now->minute < 5) {
                    $this->info("Création du check pour {$client->label}");
                    
                    // Vérifier si un check existe déjà pour aujourd'hui
                    $existingCheck = Check::where('client_id', $client->id)
                        ->whereDate('date_time', $now->toDateString())
                        ->first();

                    if (!$existingCheck) {
                        // Créer le check
                        $check = Check::create([
                            'client_id' => $client->id,
                            'date_time' => $now,
                            'created_by' => 1, // ID de l'utilisateur système
                            'statut' => 'pending'
                        ]);

                        // Récupérer tous les services du client
                        $services = Service::where('client_id', $client->id)->get();
                        $this->info("Services trouvés : " . $services->count());

                        // Créer un ServiceCheck pour chaque service
                        foreach ($services as $service) {
                            ServiceCheck::create([
                                'check_id' => $check->id,
                                'service_id' => $service->id,
                                'statut' => 'pending'
                            ]);
                        }

                        $createdCount++;
                        Log::info("Check created for client {$client->label} at {$now} with {$services->count()} services");
                    } else {
                        $this->info("Un check existe déjà pour aujourd'hui");
                    }
                } else {
                    $this->info("Pas encore l'heure pour {$client->label}");
                }
            } catch (\Exception $e) {
                $this->error("Error creating check for client {$client->label}: " . $e->getMessage());
                Log::error("Error creating check for client {$client->label}: " . $e->getMessage());
            }
        }

        $this->info("Created {$createdCount} new checks");
    }
}
