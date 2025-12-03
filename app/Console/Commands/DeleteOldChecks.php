<?php

namespace App\Console\Commands;

use App\Models\Check;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteOldChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checks:delete-old {--days=30 : Nombre de jours avant suppression (défaut: 30)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supprime les checks de plus d\'un mois (30 jours par défaut)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Suppression des checks créés avant le {$cutoffDate->format('d/m/Y H:i')}...");

        // Récupérer les checks à supprimer
        $checksToDelete = Check::where('created_at', '<', $cutoffDate)->get();
        $count = $checksToDelete->count();

        if ($count === 0) {
            $this->info('Aucun check à supprimer.');
            return Command::SUCCESS;
        }

        $this->info("{$count} check(s) trouvé(s) à supprimer.");

        // Utiliser une transaction pour garantir l'intégrité
        DB::transaction(function () use ($checksToDelete) {
            foreach ($checksToDelete as $check) {
                // Supprimer les service_checks associés
                $check->serviceChecks()->delete();
                
                // Supprimer le check
                $check->delete();
            }
        });

        $this->info("✓ {$count} check(s) supprimé(s) avec succès.");

        return Command::SUCCESS;
    }
}

