<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationCreateAccountEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $storeName;
    public string $rawPassword;
    public string $loginUrl;
    public string $username;

    public function __construct(string $username, string $storeName, string $rawPassword, string $loginUrl)
    {
        $this->username = $username;
        $this->storeName = $storeName;
        $this->rawPassword = $rawPassword;
        $this->loginUrl = $loginUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Creation Invitation',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation-create-account',
            with: [
                'username' => $this->username,
                'storeName' => $this->storeName,
                'rawPassword' => $this->rawPassword,
                'loginUrl' => $this->loginUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
