<?php

namespace App\Models;

use App\Enums\SubmissionExportStatus;
use Database\Factories\SubmissionExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id', 'form_id', 'requested_by', 'status', 'format',
    'filters', 'reason', 'row_count', 'file_path', 'file_size',
    'error_message', 'expires_at', 'completed_at', 'failed_at',
])]
class SubmissionExport extends Model
{
    /** @use HasFactory<SubmissionExportFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isDownloadable(): bool
    {
        return $this->status === SubmissionExportStatus::Completed
            && $this->file_path !== null
            && $this->expires_at?->isFuture() === true;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmissionExportStatus::class,
            'filters' => 'array',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
