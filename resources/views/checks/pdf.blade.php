@php
    $statusLabels = [
        'success' => 'Validé',
        'pending' => 'En attente',
        'error' => 'Erreur',
        'warning' => 'Avertissement',
        'in_progress' => 'En cours',
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Check #{{ $check->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; }
        h1, h2, h3 { margin-bottom: 0.5em; }
        .header { margin-bottom: 2em; }
        .badge { display: inline-block; padding: 0.2em 0.7em; border-radius: 0.5em; font-size: 0.95em; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef9c3; color: #92400e; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2em; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f3f4f6; }
        .category-title { background: #e5e7eb; font-weight: bold; padding: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport de Check #{{ $check->id }}</h1>
        <p><strong>Date :</strong> {{ $check->date_time->format('d/m/Y H:i') }}</p>
        <p><strong>Client :</strong> {{ $check->client->label }}</p>
        <p><strong>Créé par :</strong> {{ $check->creator->name }}</p>
        <p>
            <strong>Statut global :</strong>
            <span class="badge badge-{{ $check->statut }}">{{ $statusLabels[$check->statut] ?? ucfirst($check->statut) }}</span>
        </p>
        @if($check->notes)
            <p><strong>Notes :</strong> {{ $check->notes }}</p>
        @endif
    </div>

    @php
        $allOk = $check->serviceChecks->every(fn($sc) => $sc->statut === 'success');
    @endphp
    @foreach($check->serviceChecks->groupBy('service.category.title') as $category => $services)
        <h2 class="category-title">{{ $category }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Statut</th>
                    @unless($allOk)
                        <th>Observations</th>
                        <th>Intervenant</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @foreach($services as $serviceCheck)
                    <tr>
                        <td>{{ $serviceCheck->service->title }}</td>
                        <td>
                            <span class="badge badge-{{ $serviceCheck->statut }}">
                                {{ $statusLabels[$serviceCheck->statut] ?? ucfirst($serviceCheck->statut) }}
                            </span>
                        </td>
                        @unless($allOk)
                            <td>{{ $serviceCheck->observations }}</td>
                            <td>
                                @if($serviceCheck->intervenant && $serviceCheck->intervenantUser)
                                    {{ $serviceCheck->intervenantUser->name }}
                                @endif
                            </td>
                        @endunless
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html> 