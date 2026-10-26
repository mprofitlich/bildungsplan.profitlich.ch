<?php

namespace App\Mail;

use App\Models\LoginLink;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoginLinkMail extends Mailable
{
    public function __construct(public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Dein Link zum Einloggen');
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.login-link',
            with: ['minuten' => LoginLink::GUELTIG_MINUTEN],
        );
    }
}
