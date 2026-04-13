<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::InReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Archived => 'gray',
        };
    }

    /**
     * @return array<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::InReview, self::Archived],
            self::InReview => [self::Approved, self::Rejected, self::Archived],
            self::Approved => [self::Archived],
            self::Rejected => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->transitions());
    }
}
