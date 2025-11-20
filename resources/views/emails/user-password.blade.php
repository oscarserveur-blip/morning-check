<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos identifiants de connexion</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #4A90E2; padding: 20px; text-align: center; margin-bottom: 30px;">
        <h1 style="color: #ffffff; margin: 0;">Check du Matin</h1>
    </div>
    
    <div style="background-color: #f9f9f9; padding: 30px; border-radius: 5px;">
        <h2 style="color: #333; margin-top: 0;">Bienvenue {{ $user->name }} !</h2>
        
        <p>Votre compte a été créé avec succès sur la plateforme Check du Matin.</p>
        
        <p>Voici vos identifiants de connexion :</p>
        
        <div style="background-color: #ffffff; padding: 20px; border: 2px solid #4A90E2; border-radius: 5px; margin: 20px 0;">
            <p style="margin: 10px 0;"><strong>Email :</strong> {{ $user->email }}</p>
            <p style="margin: 10px 0;"><strong>Mot de passe temporaire :</strong> <code style="background-color: #f0f0f0; padding: 5px 10px; border-radius: 3px; font-size: 16px; letter-spacing: 2px;">{{ $password }}</code></p>
        </div>
        
        <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>⚠️ Important :</strong> Pour des raisons de sécurité, vous devrez changer ce mot de passe lors de votre première connexion.</p>
        </div>
        
        <p>Pour vous connecter, rendez-vous sur : <a href="{{ url('/login') }}" style="color: #4A90E2;">{{ url('/login') }}</a></p>
        
        <p style="margin-top: 30px;">Cordialement,<br>L'équipe Check du Matin</p>
    </div>
    
    <div style="text-align: center; margin-top: 30px; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">
        <p style="color: #666; font-size: 12px; margin: 0;">
            Cet email a été envoyé automatiquement. Merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>

