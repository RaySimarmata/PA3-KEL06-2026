<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderUploadMateriMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $messageContent;
    public $dosenName;
    public $prodiName;
    public $prodiKode;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $messageContent, $dosenName, $prodiName = 'TRPL', $prodiKode = 'TRPL')
    {
        $this->subject = $subject;
        $this->messageContent = $messageContent;
        $this->dosenName = $dosenName;
        $this->prodiName = $prodiName;
        $this->prodiKode = $prodiKode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                'Sistem GKM ' . $this->prodiKode
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder-upload-materi',
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
