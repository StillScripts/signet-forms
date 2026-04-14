<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class QrCodeDownloadController
{
    /**
     * Download a QR code for a published form in the specified format.
     */
    public function __invoke(Form $form, string $format, QrCodeService $qrCodeService): Response
    {
        Gate::authorize('view', $form);

        abort_unless($form->is_published, 404);
        abort_unless(in_array($format, ['png', 'svg'], true), 404);

        $publicUrl = route('forms.show', [
            'team' => $form->project->team,
            'formSlug' => $form->slug,
        ]);

        $filename = "qr-code-{$form->slug}.{$format}";

        if ($format === 'svg') {
            $content = $qrCodeService->generateSvg($publicUrl);

            return response($content)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        $content = $qrCodeService->generatePng($publicUrl);

        return response($content)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
