<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';
    case Reviewer = 'reviewer';
    case Viewer = 'viewer';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get the description for this role.
     */
    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Full access including billing',
            self::Admin => 'Full access except billing',
            self::Editor => 'Build and publish forms, view submissions',
            self::Reviewer => 'Review and act on submissions',
            self::Viewer => 'Read-only access to submissions',
        };
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::UpdateTeam,
                TeamPermission::AddMember,
                TeamPermission::UpdateMember,
                TeamPermission::RemoveMember,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
                TeamPermission::CreateProject,
                TeamPermission::UpdateProject,
                TeamPermission::DeleteProject,
                TeamPermission::CreateForm,
                TeamPermission::UpdateForm,
                TeamPermission::DeleteForm,
                TeamPermission::PublishForm,
                TeamPermission::ViewSubmission,
                TeamPermission::DeleteSubmission,
                TeamPermission::ExportSubmission,
                TeamPermission::ReviewSubmission,
                TeamPermission::ViewAudit,
                TeamPermission::ManageIntegration,
            ],
            self::Editor => [
                TeamPermission::CreateProject,
                TeamPermission::UpdateProject,
                TeamPermission::CreateForm,
                TeamPermission::UpdateForm,
                TeamPermission::PublishForm,
                TeamPermission::ViewSubmission,
                TeamPermission::ExportSubmission,
                TeamPermission::ReviewSubmission,
                TeamPermission::ManageIntegration,
            ],
            self::Reviewer => [
                TeamPermission::ViewSubmission,
                TeamPermission::ReviewSubmission,
            ],
            self::Viewer => [
                TeamPermission::ViewSubmission,
            ],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 5,
            self::Admin => 4,
            self::Editor => 3,
            self::Reviewer => 2,
            self::Viewer => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
