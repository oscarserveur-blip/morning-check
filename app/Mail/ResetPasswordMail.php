<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $user;
    public $count;

    /**
     * Create a new message instance.
     */
    public function __construct($url, $user, $count)
    {
        $this->url = $url;
        $this->user = $user;
        $this->count = $count;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Utiliser la configuration globale ou un expéditeur par défaut
        $fromEmail = config('mail.from.address', 'no_reply@bouyguestelecom-solution.fr');
        $fromName = config('mail.from.name', 'Check du Matin');
        
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromEmail, $fromName),
            subject: 'Réinitialisation de votre mot de passe - Check du Matin',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

