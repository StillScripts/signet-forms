@component('mail::message')
# Resume your {{ $form->name }} submission

We have saved your progress. Click the button below to pick up where you left off.

@component('mail::button', ['url' => $resumeUrl])
Resume Submission
@endcomponent

This link will expire on **{{ $expiresAt?->toDayDateTimeString() }}**. If you did not request this, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
