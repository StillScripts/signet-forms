<?php

namespace App\Mail;

use App\Models\SubmissionExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SubmissionExportReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SubmissionExport $export) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your submission export is ready',
        );
    }

    public function content(): Content
    {
        $downloadUrl = URL::temporarySignedRoute(
            'exports.download',
            $this->export->expires_at,
            ['export' => $this->export->id],
        );

        return new Content(
            view: 'emails.submission-export-ready',
            with: [
                'export' => $this->export,
                'form' => $this->export->form,
                'downloadUrl' => $downloadUrl,
                'expiresAt' => $this->export->expires_at,
                'rowCount' => $this->export->row_count,
            ],
        );
    }
}
