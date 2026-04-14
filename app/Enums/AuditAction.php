<?php

namespace App\Enums;

enum AuditAction: string
{
    case FormCreated = 'form.created';
    case FormUpdated = 'form.updated';
    case FormDeleted = 'form.deleted';
    case FormPublished = 'form.published';
    case FormUnpublished = 'form.unpublished';

    case SubmissionViewed = 'submission.viewed';
    case SubmissionStatusChanged = 'submission.status_changed';
    case SubmissionReviewerAssigned = 'submission.reviewer_assigned';
    case SubmissionDeleted = 'submission.deleted';
    case SubmissionExported = 'submission.exported';

    case MemberInvited = 'member.invited';
    case MemberRoleChanged = 'member.role_changed';
    case MemberRemoved = 'member.removed';
    case InvitationCancelled = 'invitation.cancelled';

    case TeamUpdated = 'team.updated';
    case TeamDeleted = 'team.deleted';

    case AuthLogin = 'auth.login';
    case AuthLoginFailed = 'auth.login_failed';
    case AuthLogout = 'auth.logout';

    public function label(): string
    {
        return match ($this) {
            self::FormCreated => 'Form created',
            self::FormUpdated => 'Form updated',
            self::FormDeleted => 'Form deleted',
            self::FormPublished => 'Form published',
            self::FormUnpublished => 'Form unpublished',
            self::SubmissionViewed => 'Submission viewed',
            self::SubmissionStatusChanged => 'Submission status changed',
            self::SubmissionReviewerAssigned => 'Reviewer assigned',
            self::SubmissionDeleted => 'Submission deleted',
            self::SubmissionExported => 'Submissions exported',
            self::MemberInvited => 'Member invited',
            self::MemberRoleChanged => 'Member role changed',
            self::MemberRemoved => 'Member removed',
            self::InvitationCancelled => 'Invitation cancelled',
            self::TeamUpdated => 'Team updated',
            self::TeamDeleted => 'Team deleted',
            self::AuthLogin => 'Logged in',
            self::AuthLoginFailed => 'Login failed',
            self::AuthLogout => 'Logged out',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FormDeleted, self::SubmissionDeleted, self::MemberRemoved,
            self::TeamDeleted, self::AuthLoginFailed => 'danger',
            self::FormPublished, self::AuthLogin => 'success',
            self::FormUnpublished, self::InvitationCancelled => 'warning',
            self::SubmissionExported, self::MemberInvited => 'info',
            default => 'gray',
        };
    }
}
