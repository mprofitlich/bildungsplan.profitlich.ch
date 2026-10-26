<?php

namespace App\Mail;

use App\Models\Kurs;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FreigabeMail extends Mailable
{
    public function __construct(public Kurs $kurs) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Kurs «{$this->kurs->titel}» für dich freigegeben");
    }

    public function content(): Content
    {
        return new Content(text: 'mail.freigabe');
    }
}
