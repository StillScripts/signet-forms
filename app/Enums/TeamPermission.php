<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case CreateProject = 'project:create';
    case UpdateProject = 'project:update';
    case DeleteProject = 'project:delete';

    case CreateForm = 'form:create';
    case UpdateForm = 'form:update';
    case DeleteForm = 'form:delete';
    case PublishForm = 'form:publish';

    case ViewSubmission = 'submission:view';
    case DeleteSubmission = 'submission:delete';
    case ExportSubmission = 'submission:export';
    case ReviewSubmission = 'submission:review';

    case ManageBilling = 'billing:manage';
    case ViewAudit = 'audit:view';
    case ManageIntegration = 'integration:manage';
}
