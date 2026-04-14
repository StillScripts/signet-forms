@component('mail::message')
# Your submission export is ready

Your export of submissions for **{{ $form->name }}** is ready to download.

- **Rows exported:** {{ number_format($rowCount ?? 0) }}
- **Expires:** {{ $expiresAt?->toDayDateTimeString() }}

@component('mail::button', ['url' => $downloadUrl])
Download Export
@endcomponent

For security, this link is signed and will stop working after the expiry date above.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
