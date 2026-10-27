<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    public function __construct(public string $name, public string $url, public int $minutes = 20) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Atur ulang password PRISMA RT 04');
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Halo '.e($this->name).',</p>'
            .'<p>Kami menerima permintaan atur ulang password akun PRISMA RT 04 Anda. Klik tautan berikut (berlaku '.$this->minutes.' menit, sekali pakai):</p>'
            .'<p><a href="'.e($this->url).'">'.e($this->url).'</a></p>'
            .'<p>Jika Anda tidak meminta ini, abaikan email ini — password Anda tidak berubah.</p>');
    }
}
