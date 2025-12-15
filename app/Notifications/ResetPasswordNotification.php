<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Mail\ResetPasswordMail;

class ResetPasswordNotification extends ResetPasswordNotification
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Mail\Mailable
     */
    public function toMail($notifiable)
    {
        $email = method_exists($notifiable, 'getEmailForPasswordReset') 
            ? $notifiable->getEmailForPasswordReset() 
            : $notifiable->email;
            
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $email,
        ], false));

        $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        // Utiliser un Mailable personnalisé
        return new ResetPasswordMail($url, $notifiable, $count);
    }
}

