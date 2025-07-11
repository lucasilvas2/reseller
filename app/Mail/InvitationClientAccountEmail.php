<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationClientAccountEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $dealershipName;
    public string $invitationUrl;
    public string $username;

    public function __construct(string $username, string $dealershipName, string $invitationUrl)
    {
        $this->username = $username;
        $this->dealershipName = $dealershipName;
        $this->invitationUrl = $invitationUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Client Invitation from ' . $this->dealershipName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation-client-account',
            with: [
                'username' => $this->username,
                'dealershipName' => $this->dealershipName,
                'invitationUrl' => $this->invitationUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
