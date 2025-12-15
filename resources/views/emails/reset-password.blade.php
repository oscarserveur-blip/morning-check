<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4A90E2; padding: 20px; text-align: center; margin-bottom: 30px;">
        <h1 style="color: #ffffff; margin: 0;">Check du Matin</h1>
    </div>
    
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 5px;">
        <h2 style="color: #333; margin-top: 0;">Bonjour {{ $user->name }} !</h2>
        
        <p>Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #4A90E2; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                Réinitialiser mon mot de passe
            </a>
        </div>
        
        <p style="color: #666; font-size: 14px;">Ce lien de réinitialisation de mot de passe expirera dans {{ $count }} minutes.</p>
        
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px;"><strong>⚠️ Sécurité :</strong> Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action n'est requise. Ignorez simplement cet email.</p>
        </div>
        
        <p style="color: #666; font-size: 12px; margin-top: 30px;">Si vous ne parvenez pas à cliquer sur le bouton, copiez et collez l'URL ci-dessous dans votre navigateur :</p>
        <p style="color: #4A90E2; font-size: 12px; word-break: break-all;">{{ $url }}</p>
        
        <p style="margin-top: 30px;">Cordialement,<br>L'équipe Check du Matin</p>
    </div>
    
    <div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
        <p style="margin: 0; font-size: 12px; color: #666;">Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
    </div>
</body>
</html>

