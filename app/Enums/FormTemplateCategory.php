<?php

namespace App\Enums;

use Filament\Support\Icons\Heroicon;

enum FormTemplateCategory: string
{
    case General = 'general';
    case Business = 'business';
    case Education = 'education';
    case Events = 'events';
    case Government = 'government';
    case Healthcare = 'healthcare';
    case Nonprofit = 'nonprofit';
    case Feedback = 'feedback';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Purpose',
            self::Business => 'Business & Corporate',
            self::Education => 'Education',
            self::Events => 'Events & Registration',
            self::Government => 'Government & Public Sector',
            self::Healthcare => 'Healthcare & Medical',
            self::Nonprofit => 'Non-Profit & Community',
            self::Feedback => 'Feedback & Surveys',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::General => Heroicon::OutlinedGlobeAlt,
            self::Business => Heroicon::OutlinedBriefcase,
            self::Education => Heroicon::OutlinedAcademicCap,
            self::Events => Heroicon::OutlinedCalendarDays,
            self::Government => Heroicon::OutlinedBuildingLibrary,
            self::Healthcare => Heroicon::OutlinedHeart,
            self::Nonprofit => Heroicon::OutlinedHandRaised,
            self::Feedback => Heroicon::OutlinedChatBubbleLeftRight,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::General => 'gray',
            self::Business => 'primary',
            self::Education => 'info',
            self::Events => 'warning',
            self::Government => 'danger',
            self::Healthcare => 'success',
            self::Nonprofit => 'purple',
            self::Feedback => 'amber',
        };
    }
}
