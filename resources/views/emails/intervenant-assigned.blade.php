<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tâche assignée</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4A90E2; padding: 20px; text-align: center; margin-bottom: 30px;">
        <h1 style="color: #ffffff; margin: 0;">Check du Matin</h1>
    </div>
    
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 5px;">
        <h2 style="color: #333; margin-top: 0;">Bonjour {{ $intervenant->name }},</h2>
        
        <p>Une tâche vous a été assignée sur la plateforme Check du Matin.</p>
        
        <div style="background-color: #ffffff; padding: 20px; border: 2px solid #4A90E2; border-radius: 5px; margin: 20px 0;">
            <h3 style="color: #4A90E2; margin-top: 0;">Détails de la tâche</h3>
            
            <p style="margin: 10px 0;"><strong>Client :</strong> {{ $client->label ?? 'N/A' }}</p>
            <p style="margin: 10px 0;"><strong>Service :</strong> {{ $service->title ?? 'N/A' }}</p>
            <p style="margin: 10px 0;"><strong>Date du check :</strong> {{ $check->date_time->format('d/m/Y à H:i') }}</p>
            
            @php
                $statusLabels = [
                    'success' => 'OK',
                    'error' => 'NOK',
                    'warning' => 'AVERTISSEMENT',
                    'pending' => 'EN ATTENTE',
                    'in_progress' => 'EN COURS',
                ];
                $statusLabel = $statusLabels[$serviceCheck->statut] ?? strtoupper($serviceCheck->statut ?? 'INCONNU');
                $statusColors = [
                    'success' => '#00B050',
                    'error' => '#FF0000',
                    'warning' => '#FFC000',
                    'pending' => '#FFC000',
                    'in_progress' => '#FFC000',
                ];
                $statusColor = $statusColors[$serviceCheck->statut] ?? '#999999';
            @endphp
            
            <p style="margin: 10px 0;">
                <strong>Statut :</strong> 
                <span style="background-color: {{ $statusColor }}; color: {{ $serviceCheck->statut === 'error' ? '#ffffff' : '#000000' }}; padding: 5px 10px; border-radius: 3px; font-weight: bold;">
                    {{ $statusLabel }}
                </span>
            </p>
            
            @if($serviceCheck->observations || $serviceCheck->notes)
                <p style="margin: 10px 0;"><strong>Observations :</strong></p>
                <div style="background-color: #f0f0f0; padding: 15px; border-radius: 3px; margin-top: 10px;">
                    <p style="margin: 0; white-space: pre-wrap;">{{ $serviceCheck->observations ?? $serviceCheck->notes ?? '' }}</p>
                </div>
            @endif
        </div>
        
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>ℹ️ Information :</strong> Veuillez vous connecter à la plateforme pour traiter cette tâche.</p>
        </div>
        
        <p style="margin-top: 30px;">Pour accéder à la plateforme, rendez-vous sur : <a href="{{ url('/') }}" style="color: #4A90E2;">{{ url('/') }}</a></p>
        
        <p style="margin-top: 30px;">Cordialement,<br>L'équipe Check du Matin</p>
    </div>
    
    <div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
        <p style="color: #666; font-size: 12px; margin: 0;">
            Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>

