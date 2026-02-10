<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class LoginSecurityKeyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $securityKey,
        public readonly Carbon $expiresAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua chave de segurança (2ª etapa de login)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-security-key',
            with: [
                'securityKey' => $this->securityKey,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}

