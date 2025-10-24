<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Check;
use App\Models\Service;
use App\Models\Template;
use App\Models\Category;
use App\Models\Mailing;
use App\Models\RappelDestinataire;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Déterminer les clients accessibles à l'utilisateur
        if ($user->isGestionnaire()) {
            $clientIds = $user->clients->pluck('id');
            $isGestionnaire = true;
        } else {
            $clientIds = null; // Admin voit tout
            $isGestionnaire = false;
        }
        
        // Générer les statistiques selon le rôle
        $stats = $this->generateStats($clientIds, $isGestionnaire);
        $checksByStatus = $this->generateChecksByStatus($clientIds, $isGestionnaire);
        $servicesByStatus = $this->generateServicesByStatus($clientIds, $isGestionnaire);
        $recentData = $this->generateRecentData($clientIds, $isGestionnaire);
        $todayChecks = $this->generateTodayChecks($clientIds, $isGestionnaire);
        $thisWeekChecks = $this->generateWeekChecks($clientIds, $isGestionnaire);
        $thisMonthChecks = $this->generateMonthChecks($clientIds, $isGestionnaire);
        $topClients = $this->generateTopClients($clientIds, $isGestionnaire);
        $pendingChecks = $this->generatePendingChecks($clientIds, $isGestionnaire);
        $last7Days = $this->generateLast7Days($clientIds, $isGestionnaire);
        $categoriesStats = $this->generateCategoriesStats($clientIds, $isGestionnaire);
        $monthlyChecks = $this->generateMonthlyChecks($clientIds, $isGestionnaire);
        
        // Taux de réussite des checks
        $totalChecks = $stats['total_checks'];
        $successRate = $totalChecks > 0 ? round(($checksByStatus['completed'] / $totalChecks) * 100, 1) : 0;

        return view('dashboard', compact(
            'stats',
            'checksByStatus',
            'servicesByStatus',
            'recentData',
            'todayChecks',
            'thisWeekChecks',
            'thisMonthChecks',
            'topClients',
            'pendingChecks',
            'last7Days',
            'categoriesStats',
            'monthlyChecks',
            'successRate'
        ));
    }

    private function generateStats($clientIds, $isGestionnaire)
    {
        if ($isGestionnaire) {
            return [
                'total_clients' => Client::whereIn('id', $clientIds)->count(),
                'total_checks' => Check::whereIn('client_id', $clientIds)->count(),
                'total_services' => Service::whereHas('category.client', function($query) use ($clientIds) {
                    $query->whereIn('id', $clientIds);
                })->count(),
                'total_templates' => Template::count(), // Les templates sont globaux
                'total_categories' => Category::whereIn('client_id', $clientIds)->count(),
                'total_mailings' => Mailing::count(), // Les mailings sont globaux
                'total_destinataires' => RappelDestinataire::count(), // Les destinataires sont globaux
            ];
        }
        
        return [
            'total_clients' => Client::count(),
            'total_checks' => Check::count(),
            'total_services' => Service::count(),
            'total_templates' => Template::count(),
            'total_categories' => Category::count(),
            'total_mailings' => Mailing::count(),
            'total_destinataires' => RappelDestinataire::count(),
        ];
    }

    private function generateChecksByStatus($clientIds, $isGestionnaire)
    {
        $query = Check::query();
        if ($isGestionnaire) {
            $query->whereIn('client_id', $clientIds);
        }
        
        return [
            'completed' => (clone $query)->where('statut', 'completed')->count(),
            'pending' => (clone $query)->where('statut', 'pending')->count(),
            'failed' => (clone $query)->where('statut', 'failed')->count(),
        ];
    }

    private function generateServicesByStatus($clientIds, $isGestionnaire)
    {
        $query = Service::query();
        if ($isGestionnaire) {
            $query->whereHas('category.client', function($q) use ($clientIds) {
                $q->whereIn('id', $clientIds);
            });
        }
        
        return [
            'active' => (clone $query)->where('status', true)->count(),
            'inactive' => (clone $query)->where('status', false)->count(),
        ];
    }

    private function generateRecentData($clientIds, $isGestionnaire)
    {
        if ($isGestionnaire) {
            return [
                'recent_clients' => Client::whereIn('id', $clientIds)->with('template')->latest()->take(5)->get(),
                'recent_checks' => Check::whereIn('client_id', $clientIds)->with(['client', 'creator'])->latest()->take(5)->get(),
                'recent_services' => Service::whereHas('category.client', function($q) use ($clientIds) {
                    $q->whereIn('id', $clientIds);
                })->with(['category', 'creator'])->latest()->take(5)->get(),
                'recent_templates' => Template::latest()->take(5)->get(),
            ];
        }
        
        return [
            'recent_clients' => Client::with('template')->latest()->take(5)->get(),
            'recent_checks' => Check::with(['client', 'creator'])->latest()->take(5)->get(),
            'recent_services' => Service::with(['category', 'creator'])->latest()->take(5)->get(),
            'recent_templates' => Template::latest()->take(5)->get(),
        ];
    }

    private function generateTodayChecks($clientIds, $isGestionnaire)
    {
        $query = Check::whereDate('created_at', Carbon::today());
        if ($isGestionnaire) {
            $query->whereIn('client_id', $clientIds);
        }
        return $query->count();
    }

    private function generateWeekChecks($clientIds, $isGestionnaire)
    {
        $query = Check::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        if ($isGestionnaire) {
            $query->whereIn('client_id', $clientIds);
        }
        return $query->count();
    }

    private function generateMonthChecks($clientIds, $isGestionnaire)
    {
        $query = Check::whereMonth('created_at', Carbon::now()->month);
        if ($isGestionnaire) {
            $query->whereIn('client_id', $clientIds);
        }
        return $query->count();
    }

    private function generateTopClients($clientIds, $isGestionnaire)
    {
        $query = Client::withCount('checks');
        if ($isGestionnaire) {
            $query->whereIn('id', $clientIds);
        }
        return $query->orderBy('checks_count', 'desc')->take(5)->get();
    }

    private function generatePendingChecks($clientIds, $isGestionnaire)
    {
        $query = Check::with(['client', 'creator'])->where('statut', 'pending');
        if ($isGestionnaire) {
            $query->whereIn('client_id', $clientIds);
        }
        return $query->latest()->take(10)->get();
    }

    private function generateLast7Days($clientIds, $isGestionnaire)
    {
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $checksQuery = Check::whereDate('created_at', $date);
            if ($isGestionnaire) {
                $checksQuery->whereIn('client_id', $clientIds);
            }
            
            $last7Days[] = [
                'date' => $date->format('d/m'),
                'checks' => $checksQuery->count(),
                'clients' => $isGestionnaire ? 0 : Client::whereDate('created_at', $date)->count(),
            ];
        }
        return $last7Days;
    }

    private function generateCategoriesStats($clientIds, $isGestionnaire)
    {
        $query = Category::withCount('services');
        if ($isGestionnaire) {
            $query->whereIn('client_id', $clientIds);
        }
        return $query->orderBy('services_count', 'desc')->take(5)->get();
    }

    private function generateMonthlyChecks($clientIds, $isGestionnaire)
    {
        $monthlyChecks = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $checksQuery = Check::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month);
            if ($isGestionnaire) {
                $checksQuery->whereIn('client_id', $clientIds);
            }
            
            $monthlyChecks[] = [
                'month' => $date->format('M Y'),
                'checks' => $checksQuery->count(),
            ];
        }
        return $monthlyChecks;
    }
} 