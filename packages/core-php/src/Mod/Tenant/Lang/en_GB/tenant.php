<?php

declare(strict_types=1);

/**
 * Tenant module translations (en_GB).
 *
 * Multi-tenant workspace management translations.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Workspace Home
    |--------------------------------------------------------------------------
    */
    'workspace' => [
        'welcome' => 'Welcome',
        'powered_by' => 'Powered by :name\'s creator toolkit. Manage, publish, and grow your online presence.',
        'manage_content' => 'Manage Content',
        'get_early_access' => 'Get early access',
        'view_content' => 'View Content',
        'latest_posts' => 'Latest Posts',
        'pages' => 'Pages',
        'read_more' => 'Read more',
        'untitled' => 'Untitled',
        'no_content' => [
            'title' => 'No content yet',
            'message' => 'This workspace doesn\'t have any published content.',
        ],
        'create_content' => 'Create Content',
        'part_of_toolkit' => 'Part of the :name Toolkit',
        'toolkit_description' => 'Access all creator services from one unified platform',
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Deletion
    |--------------------------------------------------------------------------
    */
    'deletion' => [
        'invalid' => [
            'title' => 'Link Invalid or Expired',
            'message' => 'This deletion link is no longer valid. It may have been cancelled or already used.',
        ],
        'verify' => [
            'title' => 'Verify Your Identity',
            'description' => 'Enter your password to confirm immediate account deletion for :name',
            'password_label' => 'Password',
            'password_placeholder' => 'Enter your password',
            'submit' => 'Verify & Continue',
            'changed_mind' => 'Changed your mind?',
            'cancel_link' => 'Cancel deletion',
        ],
        'confirm' => [
            'title' => 'Final Confirmation',
            'warning' => 'This action is permanent and irreversible.',
            'will_delete' => 'The following will be permanently deleted:',
            'items' => [
                'profile' => 'Your profile and personal data',
                'workspaces' => 'All workspaces you own',
                'content' => 'All content, media, and settings',
                'social' => 'Social connections and scheduled posts',
            ],
            'cancel' => 'Cancel',
            'delete_forever' => 'Delete Forever',
        ],
        'deleting' => [
            'title' => 'Deleting Account',
            'messages' => [
                'social' => 'Disconnecting social accounts...',
                'posts' => 'Removing scheduled posts...',
                'media' => 'Deleting media files...',
                'workspaces' => 'Removing workspaces...',
                'personal' => 'Erasing personal data...',
                'final' => 'Finalizing deletion...',
            ],
        ],
        'goodbye' => [
            'title' => 'F.I.N.',
            'deleted' => 'Your account has been deleted.',
            'thanks' => 'Thank you for being part of our journey.',
        ],
        'cancelled' => [
            'title' => 'Deletion Cancelled',
            'message' => 'Your account deletion has been cancelled. Your account is safe and will remain active.',
            'go_to_profile' => 'Go to Profile',
        ],
        'cancel_invalid' => [
            'title' => 'Link Invalid',
            'message' => 'This cancellation link is no longer valid. The deletion may have already been cancelled or completed.',
        ],
        'processing' => 'Processing...',
        'return_home' => 'Return Home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin - Workspace Manager
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'title' => 'Workspace Manager',
        'subtitle' => 'Manage workspaces and transfer resources',
        'hades_only' => 'Hades Only',
        'stats' => [
            'total' => 'Total Workspaces',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'search_placeholder' => 'Search workspaces by name or slug...',
        'table' => [
            'workspace' => 'Workspace',
            'owner' => 'Owner',
            'bio' => 'Bio',
            'social' => 'Social',
            'analytics' => 'Analytics',
            'trust' => 'Trust',
            'notify' => 'Notify',
            'commerce' => 'Commerce',
            'status' => 'Status',
            'actions' => 'Actions',
            'no_owner' => 'No owner',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'empty' => 'No workspaces found matching your criteria.',
        ],
        'actions' => [
            'view_details' => 'View details',
            'edit' => 'Edit workspace',
            'change_owner' => 'Change owner',
            'transfer' => 'Transfer resources',
            'delete' => 'Delete workspace',
            'provision' => 'Provision new',
        ],
        'confirm_delete' => 'Are you sure you want to delete this workspace? This cannot be undone.',
        'edit_modal' => [
            'title' => 'Edit Workspace',
            'name' => 'Name',
            'name_placeholder' => 'Workspace name',
            'slug' => 'Slug',
            'slug_placeholder' => 'workspace-slug',
            'active' => 'Active',
            'cancel' => 'Cancel',
            'save' => 'Save Changes',
        ],
        'transfer_modal' => [
            'title' => 'Transfer Resources',
            'source' => 'Source',
            'target_workspace' => 'Target Workspace',
            'select_target' => 'Select target workspace...',
            'resources_label' => 'Resources to Transfer',
            'warning' => 'Warning: This will move all selected resource types from the source workspace to the target workspace. This action cannot be undone.',
            'cancel' => 'Cancel',
            'transfer' => 'Transfer Resources',
        ],
        'owner_modal' => [
            'title' => 'Change Workspace Owner',
            'workspace' => 'Workspace',
            'new_owner' => 'New Owner',
            'select_owner' => 'Select new owner...',
            'warning' => 'The current owner will be demoted to a member. If the new owner is not already a member, they will be added to the workspace.',
            'cancel' => 'Cancel',
            'change' => 'Change Owner',
        ],
        'resources_modal' => [
            'in' => 'in',
            'select_all' => 'Select All',
            'deselect_all' => 'Deselect All',
            'selected' => ':count selected',
            'no_resources' => 'No resources found.',
            'transfer_selected' => 'Transfer Selected',
            'select_workspace' => 'Select workspace...',
            'transfer_items' => 'Transfer :count Item|Transfer :count Items',
            'close' => 'Close',
        ],
        'provision_modal' => [
            'create' => 'Create :type',
            'workspace' => 'Workspace',
            'name' => 'Name',
            'name_placeholder' => 'Enter name...',
            'slug' => 'Slug',
            'slug_placeholder' => 'my-page',
            'url' => 'URL',
            'url_placeholder' => 'https://example.com',
            'cancel' => 'Cancel',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Emails
    |--------------------------------------------------------------------------
    */
    'emails' => [
        'deletion_requested' => [
            'subject' => 'Account Deletion Scheduled',
            'greeting' => 'Hi :name,',
            'scheduled' => 'Your :app account has been scheduled for permanent deletion.',
            'auto_delete' => 'Your account will be automatically deleted on :date (in :days days).',
            'will_delete' => 'What will be deleted:',
            'items' => [
                'profile' => 'Your profile and personal information',
                'workspaces' => 'All workspaces you own',
                'content' => 'All content, media, and settings',
                'social' => 'Social media connections and scheduled posts',
            ],
            'delete_now' => 'Want to delete immediately?',
            'delete_now_description' => 'Click the button below to delete your account right now:',
            'delete_button' => 'Delete Now',
            'changed_mind' => 'Changed your mind?',
            'changed_mind_description' => 'Click below to cancel the deletion and keep your account:',
            'cancel_button' => 'Cancel Deletion',
            'not_requested' => 'Did not request this?',
            'not_requested_description' => 'If you did not request account deletion, click the cancel button above immediately and change your password.',
        ],
    ],
];
