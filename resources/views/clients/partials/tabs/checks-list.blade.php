@forelse($checks as $check)
    <tr>
        <td>
            <span class="badge bg-secondary">#{{ $check->id }}</span>
        </td>
        <td>
            <div>
                <strong>{{ $check->date_time->format('d/m/Y') }}</strong>
                <br>
                <small class="text-muted">{{ $check->date_time->format('H:i') }}</small>
            </div>
        </td>
        <td>
            @php
                $statusConfig = [
                    'completed' => ['class' => 'bg-success', 'text' => 'Terminé', 'icon' => 'bi-check-circle-fill'],
                    'pending' => ['class' => 'bg-warning', 'text' => 'En attente', 'icon' => 'bi-clock-fill'],
                    'failed' => ['class' => 'bg-danger', 'text' => 'Échoué', 'icon' => 'bi-x-circle-fill'],
                    'in_progress' => ['class' => 'bg-info', 'text' => 'En cours', 'icon' => 'bi-arrow-clockwise']
                ];
                $status = $statusConfig[$check->statut] ?? ['class' => 'bg-secondary', 'text' => $check->statut, 'icon' => 'bi-question-circle-fill'];
            @endphp
            <span class="badge {{ $status['class'] }}">
                <i class="bi {{ $status['icon'] }} me-1"></i>
                {{ $status['text'] }}
            </span>
        </td>
        <td>
            @php
                $totalServices = $check->serviceChecks->count();
                $successServices = $check->serviceChecks->where('statut', 'success')->count();
                $warningServices = $check->serviceChecks->where('statut', 'warning')->count();
                $errorServices = $check->serviceChecks->where('statut', 'error')->count();
            @endphp
            @if($totalServices > 0)
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <small class="text-success">{{ $successServices }} ✅</small>
                        @if($warningServices > 0)
                            <small class="text-warning ms-1">{{ $warningServices }} ⚠️</small>
                        @endif
                        @if($errorServices > 0)
                            <small class="text-danger ms-1">{{ $errorServices }} ❌</small>
                        @endif
                    </div>
                    <div class="progress flex-grow-1" style="height: 6px;">
                        @if($totalServices > 0)
                            <div class="progress-bar bg-success" style="width: {{ ($successServices / $totalServices) * 100 }}%"></div>
                            <div class="progress-bar bg-warning" style="width: {{ ($warningServices / $totalServices) * 100 }}%"></div>
                            <div class="progress-bar bg-danger" style="width: {{ ($errorServices / $totalServices) * 100 }}%"></div>
                        @endif
                    </div>
                </div>
                <small class="text-muted">{{ $totalServices }} service(s)</small>
            @else
                <span class="text-muted">Aucun service</span>
            @endif
        </td>
        <td>
            @if($check->creator)
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" 
                         style="width: 24px; height: 24px;">
                        <i class="bi bi-person text-white" style="font-size: 0.75rem;"></i>
                    </div>
                    <span>{{ $check->creator->name }}</span>
                </div>
            @else
                <span class="text-muted">Système</span>
            @endif
        </td>
        <td>
            <div>
                <small>{{ $check->updated_at->format('d/m/Y') }}</small>
                <br>
                <small class="text-muted">{{ $check->updated_at->format('H:i') }}</small>
            </div>
        </td>
        <td class="text-end">
            <div class="btn-group">
                <a href="#" class="btn btn-sm btn-outline-info" title="Voir les détails" onclick="viewCheck({{ $check->id }})">
                    <i class="bi bi-eye"></i>
                </a>
                @if($check->client->template && $check->statut !== 'pending')
                    <a href="{{ route('checks.export', ['check' => $check, 'tab' => 'checks']) }}" 
                       class="btn btn-sm btn-outline-success" 
                       title="Exporter">
                        <i class="bi bi-download"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-info" title="Envoyer par email" onclick="sendCheck({{ $check->id }})">
                        <i class="bi bi-send"></i>
                    </button>
                @elseif($check->client->template && $check->statut === 'pending')
                    <span class="btn btn-sm btn-outline-secondary disabled" 
                          title="Export disponible une fois tous les services vérifiés">
                        <i class="bi bi-download"></i>
                    </span>
                @endif
                @if(auth()->user()->isAdmin())
                    <form action="{{ route('checks.destroy', ['check' => $check, 'tab' => 'checks']) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce check ?')" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center text-muted">Aucun check trouvé.</td>
    </tr>
@endforelse 