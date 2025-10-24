<?php

namespace Database\Seeders;

use App\Models\Check;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class CheckSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        $user = User::first();
        if (!$user) return;

        $checks = [
            [
                'date_time' => now(),
                'client_id' => $clients[0]->id,
                'created_by' => $user->id,
                'statut' => 'pending'
            ],
            [
                'date_time' => now()->addDay(),
                'client_id' => $clients[0]->id,
                'created_by' => $user->id,
                'statut' => 'pending'
            ],
            [
                'date_time' => now()->addDays(2),
                'client_id' => $clients[0]->id,
                'created_by' => $user->id,
                'statut' => 'pending'
            ]
        ];

        foreach ($checks as $check) {
            if (!Check::where('client_id', $check['client_id'])->where('date_time', $check['date_time'])->exists()) {
                Check::create($check);
            }
        }
    }
} 