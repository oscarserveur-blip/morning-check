<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Template;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        // S'assurer qu'il y a au moins un template
        $template = Template::first() ?? Template::create([
            'name' => 'Template par défaut',
            'description' => 'Template initial pour les clients',
            'type' => 'excel',
        ]);

        Client::create([
            'label' => 'Client Test 1',
            'logo' => null,
            'template_id' => $template->id,
            'check_time' => '09:00',
        ]);
    }
} 