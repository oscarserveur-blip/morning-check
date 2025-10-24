<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supprimer les anciens jours fériés
        Holiday::truncate();

        $currentYear = Carbon::now()->year;
        
        $holidays = [
            ['date' => "{$currentYear}-01-01", 'label' => 'Jour de l\'an'],
            ['date' => "{$currentYear}-04-01", 'label' => 'Lundi de Pâques'],
            ['date' => "{$currentYear}-05-01", 'label' => 'Fête du Travail'],
            ['date' => "{$currentYear}-05-08", 'label' => 'Victoire 1945'],
            ['date' => "{$currentYear}-05-09", 'label' => 'Ascension'],
            ['date' => "{$currentYear}-05-20", 'label' => 'Lundi de Pentecôte'],
            ['date' => "{$currentYear}-07-14", 'label' => 'Fête Nationale'],
            ['date' => "{$currentYear}-08-15", 'label' => 'Assomption'],
            ['date' => "{$currentYear}-11-01", 'label' => 'Toussaint'],
            ['date' => "{$currentYear}-11-11", 'label' => 'Armistice 1918'],
            ['date' => "{$currentYear}-12-25", 'label' => 'Noël'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }
}
