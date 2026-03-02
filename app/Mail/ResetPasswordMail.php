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

    public string $resetToken;
    public string $resetUrl;

    public function __construct(string $token, string $frontendUrl)
    {
        $this->resetToken = $token;
        $this->resetUrl = $frontendUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password - Alumni Tracer Study',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
        );
    }
}
