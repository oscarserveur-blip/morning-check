<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Service;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChalonsClientSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            return;
        }

        // Ensure a template exists
        $template = Template::first() ?? Template::create([
            'name' => 'Template par défaut',
            'description' => 'Template initial pour les clients',
            'type' => 'excel',
        ]);

        // Create or retrieve the client
        $client = Client::firstOrCreate(
            ['label' => 'Châlons'],
            [
                'logo' => null,
                'template_id' => $template->id,
                'check_time' => '09:00',
            ]
        );

        $categoriesDefinition = [
            'Applications' => [
                'GLPI',
                'GMAO',
                'CITYLINX',
                'HYPERVISEUR',
                'ACTILITY',
                'PARKKI',
                'SYMART',
            ],
            'Informatique' => [
                'Systancia',
                'Keycloak',
                'SharePoint Châlons',
                'Trustbuilder',
            ],
            'Réseaux et Sites Distants' => [
                'Internet (www.google.fr)',
                'Microsoft Online (login.microsoftonline.com)',
            ],
            'Environnement Infrastructure Châlons' => [
                'PowerProtect Datamanager (Sauvegarde VM)',
                'Oxidized (Sauvegarde Réseaux)',
                'Active Directory',
                'Relais SMTP (HDV-003)',
                'Zabbix',
                'VMware vSphere',
                'WSUS',
            ],
        ];

        foreach ($categoriesDefinition as $categoryTitle => $services) {
            $category = Category::firstOrCreate([
                'title' => $categoryTitle,
                'client_id' => $client->id,
            ], [
                'category_pk' => null,
                'status' => true,
                'created_by' => $user->id,
            ]);

            foreach ($services as $serviceTitle) {
                Service::firstOrCreate([
                    'title' => $serviceTitle,
                    'category_id' => $category->id,
                ], [
                    'status' => true,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}


