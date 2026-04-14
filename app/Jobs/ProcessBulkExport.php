<?php

namespace App\Jobs;

use App\Enums\AuditAction;
use App\Enums\SubmissionExportStatus;
use App\Enums\SubmissionStatus;
use App\Mail\SubmissionExportReady;
use App\Models\Submission;
use App\Models\SubmissionExport;
use App\Services\AuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessBulkExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function __construct(public string $exportId) {}

    public function handle(): void
    {
        /** @var SubmissionExport $export */
        $export = SubmissionExport::with(['form', 'requester', 'team'])->findOrFail($this->exportId);

        $export->update([
            'status' => SubmissionExportStatus::Processing,
        ]);

        try {
            $this->writeCsv($export);
        } catch (Throwable $e) {
            $export->update([
                'status' => SubmissionExportStatus::Failed,
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            throw $e;
        }
    }

    protected function writeCsv(SubmissionExport $export): void
    {
        $disk = Storage::disk('local');
        $path = "exports/{$export->team_id}/{$export->id}.csv";
        $fullPath = $disk->path($path);

        File::ensureDirectoryExists(dirname($fullPath));

        $handle = fopen($fullPath, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open export file for writing: {$fullPath}");
        }

        try {
            $fieldKeys = $this->extractFieldKeys($export);
            $headers = array_merge(
                ['id', 'submitted_at', 'status', 'respondent_name', 'respondent_email', 'ip_address'],
                $fieldKeys,
            );
            fputcsv($handle, $headers);

            $rowCount = 0;

            foreach ($this->buildQuery($export)->lazyById(500) as $submission) {
                /** @var Submission $submission */
                $row = [
                    $submission->id,
                    $submission->created_at?->toDateTimeString(),
                    $submission->status?->value,
                    $submission->respondent_name,
                    $submission->respondent_email,
                    $submission->metadata['ip_address'] ?? null,
                ];

                $data = is_array($submission->data) ? $submission->data : [];

                foreach ($fieldKeys as $key) {
                    $value = $data[$key] ?? null;
                    $row[] = is_array($value) ? json_encode($value) : $value;
                }

                fputcsv($handle, array_map([$this, 'sanitiseCell'], $row));
                $rowCount++;
            }
        } finally {
            fclose($handle);
        }

        $fileSize = $disk->size($path);

        $export->update([
            'status' => SubmissionExportStatus::Completed,
            'file_path' => $path,
            'file_size' => $fileSize,
            'row_count' => $rowCount,
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        AuditService::log(
            action: AuditAction::SubmissionExported,
            resource: $export,
            details: [
                'form_id' => $export->form_id,
                'reason' => $export->reason,
                'row_count' => $rowCount,
                'filters' => $export->filters,
            ],
            actor: $export->requester,
            team: $export->team,
        );

        Mail::to($export->requester->email)
            ->send(new SubmissionExportReady($export->fresh()));
    }

    /**
     * @return Builder<Submission>
     */
    protected function buildQuery(SubmissionExport $export): Builder
    {
        $filters = $export->filters ?? [];

        return Submission::query()
            ->where('form_id', $export->form_id)
            ->whereHas('form.project', fn (Builder $q) => $q->where('team_id', $export->team_id))
            ->when($filters['statuses'] ?? null, function (Builder $query, array $statuses) {
                $query->whereIn('status', array_map(
                    fn (string $value) => SubmissionStatus::from($value)->value,
                    $statuses,
                ));
            })
            ->when($filters['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy('id');
    }

    /**
     * Prevent CSV formula injection by prefixing any cell starting with
     * =, +, -, @, tab, or CR with a single quote so spreadsheet apps
     * treat the content as text.
     */
    protected function sanitiseCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)
            ? "'".$value
            : $value;
    }

    /**
     * @return array<int, string>
     */
    protected function extractFieldKeys(SubmissionExport $export): array
    {
        $schema = is_array($export->form->schema) ? $export->form->schema : [];
        $keys = [];

        foreach ($schema['pages'] ?? [] as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                if (! empty($field['key'])) {
                    $keys[] = $field['key'];
                }
            }
        }

        return array_values(array_unique($keys));
    }
}
