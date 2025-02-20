<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FileDownloaded extends Mailable
{
    use Queueable, SerializesModels;

    public $noLink;
    public $downloadLinks;

    /**
     * Create a new message instance.
     */
    public function __construct($downloadLinks, $noLink=false)
    {
        $this->noLink = $noLink;
        $this->downloadLinks = $downloadLinks;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Staples Diversity Data Downloaded')
                    ->view('mail.file_downloaded')
                    ->with(['no_link_check' => $this->noLink, 'links' => $this->downloadLinks]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'File Downloaded',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.file_downloaded',
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
