import { usePage } from '@inertiajs/vue3';

const page = usePage<{
    auth: {
        permissions: string[];
    };
}>();

function currentUserHasPermission(permission: string) {
    if (Array.isArray(page.props.auth.permissions)) {
        return page.props.auth.permissions.includes(permission);
    }
    return false;
}

export function canUpdateOrganization() {
    return currentUserHasPermission('organizations:update');
}

export function canViewProjects() {
    return currentUserHasPermission('projects:view');
}

export function canCreateProjects() {
    return currentUserHasPermission('projects:create');
}

export function canUpdateProjects() {
    return currentUserHasPermission('projects:update');
}

export function canDeleteProjects() {
    return currentUserHasPermission('projects:delete');
}

export function canViewProjectMembers() {
    return currentUserHasPermission('project-members:view');
}

export function canCreateTasks() {
    return currentUserHasPermission('tasks:create');
}

export function canUpdateTasks() {
    return currentUserHasPermission('tasks:update');
}

export function canDeleteTasks() {
    return currentUserHasPermission('tasks:delete');
}

export function canCreateClients() {
    return currentUserHasPermission('clients:create');
}

export function canUpdateClients() {
    return currentUserHasPermission('clients:update');
}

export function canDeleteClients() {
    return currentUserHasPermission('clients:delete');
}

export function canViewClients() {
    return currentUserHasPermission('clients:view');
}

export function canViewMembers() {
    return currentUserHasPermission('members:view');
}

export function canUpdateMembers() {
    return currentUserHasPermission('members:update');
}

export function canDeleteMembers() {
    return currentUserHasPermission('members:delete');
}

export function canMergeMembers() {
    return currentUserHasPermission('members:merge-into');
}

export function canMakeMembersPlaceholders() {
    return currentUserHasPermission('members:make-placeholder');
}

export function canInvitePlaceholderMembers() {
    return currentUserHasPermission('members:invite-placeholder');
}

export function canCreateInvitations() {
    return currentUserHasPermission('invitations:create');
}

export function canViewTags() {
    return currentUserHasPermission('tags:view');
}

export function canCreateTags() {
    return currentUserHasPermission('tags:create');
}

export function canUpdateTags() {
    return currentUserHasPermission('tags:update');
}

export function canDeleteTags() {
    return currentUserHasPermission('tags:delete');
}

export function canManageBilling() {
    return currentUserHasPermission('billing');
}

export function canViewReport() {
    return currentUserHasPermission('reports:view');
}
export function canUpdateReport() {
    return currentUserHasPermission('reports:update');
}
export function canDeleteReport() {
    return currentUserHasPermission('reports:delete');
}

export function canViewAllTimeEntries() {
    return currentUserHasPermission('time-entries:view:all');
}
export function canViewInvoices() {
    return currentUserHasPermission('invoices:view');
}
export function canCreateInvoices() {
    return currentUserHasPermission('invoices:create');
}
export function canUpdateInvoices() {
    return currentUserHasPermission('invoices:update');
}
export function canDeleteInvoices() {
    return currentUserHasPermission('invoices:delete');
}
export function canDownloadInvoices() {
    return currentUserHasPermission('invoices:download');
}
export function canViewInvoiceRecipients() {
    return currentUserHasPermission('invoice-recipients:view');
}
export function canCreateInvoiceRecipients() {
    return currentUserHasPermission('invoice-recipients:create');
}
export function canUpdateInvoiceRecipients() {
    return currentUserHasPermission('invoice-recipients:update');
}
export function canDeleteInvoiceRecipients() {
    return currentUserHasPermission('invoice-recipients:delete');
}
export function canViewInvoiceSettings() {
    return currentUserHasPermission('invoice-settings:view');
}
export function canUpdateInvoiceSettings() {
    return currentUserHasPermission('invoice-settings:update');
}
export function canCreateReports() {
    return currentUserHasPermission('reports:create');
}
