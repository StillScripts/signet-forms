<?php

namespace App\Http\Controllers;

use App\Enums\TeamPermission;
use App\Models\SubmissionExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDownloadController extends Controller
{
    public function __invoke(Request $request, SubmissionExport $export): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $isRequester = $export->requested_by === $user->id;
        $hasAuditPermission = $user->hasTeamPermission($export->team, TeamPermission::ViewAudit);

        abort_unless($isRequester || $hasAuditPermission, 403);

        abort_unless($export->isDownloadable(), 410);

        $filename = 'submissions-'.$export->form->slug.'.csv';

        return Storage::disk('local')->download($export->file_path, $filename);
    }
}
