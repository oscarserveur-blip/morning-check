@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- En-tête du Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Tableau de Bord</h1>
                    <p class="text-muted mb-0">Vue d'ensemble de votre activité Check du Matin</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary fs-6">{{ now()->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques principales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Clients
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_clients'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Checks Totaux
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_checks'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-square-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Services
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_services'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-gear-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Templates
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_templates'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-file-earmark-text-fill fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques des checks -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Checks Terminés
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $checksByStatus['completed'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle-fill fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Checks en Attente
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $checksByStatus['pending'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clock-fill fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Checks Échoués
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $checksByStatus['failed'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-x-circle-fill fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Checks Aujourd'hui
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $todayChecks }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-calendar-day-fill fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Statistiques avancées -->
    <div class="row">
        <!-- Taux de réussite -->
        <div class="col-xl-4 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-graph-up me-2"></i>Taux de Réussite
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="position-relative">
                        <div class="progress-circle" style="width: 120px; height: 120px; margin: 0 auto;">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#e9ecef" stroke-width="8"/>
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#1cc88a" stroke-width="8" 
                                        stroke-dasharray="{{ 2 * pi() * 50 }}" 
                                        stroke-dashoffset="{{ 2 * pi() * 50 * (1 - $successRate / 100) }}"
                                        transform="rotate(-90 60 60)"/>
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <h3 class="mb-0">{{ $successRate }}%</h3>
                                <small class="text-muted">Réussite</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-success fw-bold">{{ $checksByStatus['completed'] }}</div>
                                <small class="text-muted">Terminés</small>
                            </div>
                            <div class="col-4">
                                <div class="text-warning fw-bold">{{ $checksByStatus['pending'] }}</div>
                                <small class="text-muted">En attente</small>
                            </div>
                            <div class="col-4">
                                <div class="text-danger fw-bold">{{ $checksByStatus['failed'] }}</div>
                                <small class="text-muted">Échoués</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activité des 7 derniers jours -->
        <div class="col-xl-4 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-calendar-week me-2"></i>Activité (7 jours)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 200px;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top catégories -->
        <div class="col-xl-4 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-tags me-2"></i>Top Catégories
                    </h6>
                </div>
                <div class="card-body">
                    @if($categoriesStats->count() > 0)
                        @foreach($categoriesStats as $category)
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="mb-0">{{ $category->title }}</h6>
                                    <small class="text-muted">{{ $category->services_count }} services</small>
                                </div>
                                <div class="progress" style="width: 60px; height: 6px;">
                                    @php
                                        $maxServices = $categoriesStats->max('services_count');
                                        $percentage = $maxServices > 0 ? ($category->services_count / $maxServices) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-primary" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-tags text-muted fs-1 d-block mb-2"></i>
                            <p class="text-muted mb-0">Aucune catégorie trouvée</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique mensuel -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-bar-chart me-2"></i>Évolution des Checks (6 mois)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.progress-circle {
    position: relative;
}

.chart-container {
    position: relative;
    height: 300px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Graphique d'activité des 7 derniers jours
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: @json(array_column($last7Days, 'date')),
            datasets: [{
                label: 'Checks',
                data: @json(array_column($last7Days, 'checks')),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Clients',
                data: @json(array_column($last7Days, 'clients')),
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Graphique mensuel
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: @json(array_column($monthlyChecks, 'month')),
            datasets: [{
                label: 'Checks',
                data: @json(array_column($monthlyChecks, 'checks')),
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderColor: '#4e73df',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
@endsection
