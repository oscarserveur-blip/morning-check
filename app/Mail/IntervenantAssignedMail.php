<?php

namespace App\Mail;

use App\Models\ServiceCheck;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IntervenantAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $serviceCheck;
    public $intervenant;
    public $check;
    public $client;
    public $service;

    /**
     * Create a new message instance.
     */
    public function __construct(ServiceCheck $serviceCheck, User $intervenant)
    {
        $this->serviceCheck = $serviceCheck;
        $this->intervenant = $intervenant;
        $this->serviceCheck->load(['check.client', 'service']);
        $this->check = $serviceCheck->check;
        $this->client = $serviceCheck->check->client;
        $this->service = $serviceCheck->service;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Utiliser l'expéditeur du client si disponible, sinon config globale
        $fromEmail = config('mail.from.address', 'no_reply@bouyguestelecom-solution.fr');
        $fromName = config('mail.from.name', 'Check du Matin');
        
        // Si le client a un expéditeur configuré, l'utiliser
        if ($this->client && $this->client->mailings) {
            $sender = $this->client->mailings()->where('type', 'sender')->first();
            if ($sender) {
                $fromEmail = $sender->email;
            }
        }
        
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($fromEmail, $fromName),
            subject: 'Tâche assignée - ' . ($this->service->title ?? 'Service') . ' - ' . ($this->client->label ?? 'Client'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.intervenant-assigned',
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
