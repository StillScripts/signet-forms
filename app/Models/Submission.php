<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'form_id', 'data', 'status', 'metadata', 'form_version',
    'assigned_reviewer_id', 'respondent_email', 'respondent_name',
    'resume_token', 'resume_token_expires_at', 'resume_page_index', 'is_draft',
])]
class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, HasUuids;

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
    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }

    public function transitionTo(SubmissionStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$this->status->value} to {$status->value}"
            );
        }

        $this->update(['status' => $status]);
    }

    public function isResumable(): bool
    {
        return $this->is_draft
            && filled($this->resume_token)
            && ($this->resume_token_expires_at?->isFuture() ?? false);
    }

    public static function generateResumeToken(): string
    {
        return Str::random(64);
    }

    /**
     * @param  Builder<Submission>  $query
     */
    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->where('is_draft', false);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'metadata' => 'array',
            'status' => SubmissionStatus::class,
            'form_version' => 'integer',
            'resume_token_expires_at' => 'datetime',
            'resume_page_index' => 'integer',
            'is_draft' => 'boolean',
        ];
    }
}
