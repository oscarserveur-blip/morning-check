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
        return new Envelope(
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
