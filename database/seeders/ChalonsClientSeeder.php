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
            $this->command->warn('Aucun utilisateur trouvé. Veuillez d\'abord exécuter UserSeeder.');
            return;
        }

        // Créer un template PNG spécifique pour Châlons
        $template = Template::firstOrCreate(
            ['name' => 'Template Châlons PNG'],
            [
                'description' => 'Template PNG pour le client Châlons',
                'type' => 'png',
                'header_title' => 'Bulletin de Santé IT',
                'header_color' => '#0B5AA0',
                'footer_color' => '#C00000',
                'footer_text' => 'EXPLOITATION, Connecte Châlons : https://glpi.connecte-chalons.fr',
                'config' => [
                    'ok_color' => '#00B050',
                    'nok_color' => '#FF0000',
                    'warning_color' => '#FFC000',
                ],
            ]
        );

        // Mettre à jour le template si nécessaire (pour s'assurer qu'il est en PNG)
        if ($template->type !== 'png') {
            $template->update([
                'type' => 'png',
                'header_color' => $template->header_color ?? '#0B5AA0',
                'footer_color' => $template->footer_color ?? '#C00000',
                'config' => array_merge([
                    'ok_color' => '#00B050',
                    'nok_color' => '#FF0000',
                    'warning_color' => '#FFC000',
                ], $template->config ?? []),
            ]);
        }

        // Create or retrieve the client
        $client = Client::firstOrCreate(
            ['label' => 'Châlons'],
            [
                'logo' => null,
                'template_id' => $template->id,
                'check_time' => '09:00',
            ]
        );

        // Mettre à jour le template_id si nécessaire
        if ($client->template_id !== $template->id) {
            $client->update(['template_id' => $template->id]);
        }

        // Associer l'utilisateur admin au client Châlons
        if ($user->isAdmin()) {
            $user->clients()->syncWithoutDetaching([$client->id]);
        }

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


