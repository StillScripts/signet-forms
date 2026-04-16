<?php

namespace App\Mail;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubmissionResumeLink extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Submission $submission) {}

    public function envelope(): Envelope
    {
        $formName = $this->submission->form->name;

        return new Envelope(
            subject: "Resume your {$formName} submission",
        );
    }

    public function content(): Content
    {
        $form = $this->submission->form;
        $team = $form->project->team;

        $resumeUrl = route('forms.resume', [
            'team' => $team->slug,
            'formSlug' => $form->slug,
            'token' => $this->submission->resume_token,
        ]);

        return new Content(
            view: 'emails.submission-resume-link',
            with: [
                'form' => $form,
                'resumeUrl' => $resumeUrl,
                'expiresAt' => $this->submission->resume_token_expires_at,
            ],
        );
    }
}
