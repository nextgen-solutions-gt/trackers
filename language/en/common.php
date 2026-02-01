<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

$lang = array_merge($lang, [
	// Version Check
	'VERSION_UP_TO_DATE'        => 'Extension is up to date',
	'VERSION_NOT_UP_TO_DATE'    => 'A new version is available',
	'CURRENT_VERSION'           => 'Current version',
	'LATEST_VERSION'            => 'Latest version',
	'DOWNLOAD_LATEST'           => 'Download latest version',
	'VIEW_ANNOUNCEMENT'         => 'View announcement',

    // ACP entries
	'ACP_TRACKERS_DASHBOARD' 	 => 'Dashboard',
    'ACP_TRACKERS'               => 'Trackers',
    'ACP_TRACKERS_TITLE'         => 'Tracker Management',
    'ACP_TRACKERS_SETTINGS'      => 'General Settings',
    'TRACKERS_ENABLE'            => 'Enable Trackers',
    'TRACKERS_ENABLE_EXPLAIN'    => 'Enable or disable the tracker system globally.',
    'TRACKERS_ENABLED'           => 'Enable Trackers', 
    'TRACKERS_ENABLED_EXPLAIN'   => 'Enable or disable the tracker system globally.',
    'TRACKERS_PER_PAGE'          => 'Tickets per page',
    'TRACKERS_PER_PAGE_EXPLAIN'  => 'Number of tickets to display on the project view.',
    'GENERAL_SETTINGS'           => 'General Configuration',
    'ACP_TRACKERS_PROJECTS'      => 'Manage Projects',
    'ACP_TRACKERS_EXPLAIN'       => 'Here you can configure your bug trackers, manage specific projects, and define statuses or severities.',
    'ACP_TRACKERS_SEVERITIES'    => 'Manage Severities',
    'ACP_TRACKERS_STATUSES'      => 'Manage Statuses',
    'ACP_TRACKERS_COMPONENTS'    => 'Manage Components',
    'ITEM_DELETED'               => 'Item successfully deleted.',
    'ITEM_UPDATED'               => 'Item successfully updated.',
    'SETTINGS_UPDATED'           => 'Settings successfully updated.',
    'ADD'                        => 'Add',
    'EDIT'                       => 'Edit',
    'DELETE'                     => 'Delete',
    'NO_PAGE_MODE'               => 'Invalid or no page mode specified.',
    'NO_ITEMS'                   => 'No items found.',

    // ACP - Projects & Relations
    'PROJECT_TYPE'               => 'Project Type',
    'ASSIGN_TO_PROJECTS'         => 'Assign to Projects',
    'ASSIGN_TO_PROJECTS_EXPLAIN' => 'Select which projects will use this item.',

    // ACP - Attachment Settings
    'ATTACHMENT_SETTINGS'        => 'Attachment Settings',
    'ALLOW_ATTACHMENTS'          => 'Allow Attachments',
    'TRACKERS_ATTACHMENTS'       => 'Allow file uploads', 
    'MAX_ATTACH_SIZE'            => 'Maximum file size',
    'MAX_ATTACH_SIZE_EXPLAIN'    => 'Maximum size allowed for each attached file in KiB (0 for unlimited).',
    'ALLOWED_EXTENSIONS'         => 'Allowed extensions',
    'ALLOWED_EXTENSIONS_EXPLAIN' => 'List of allowed file extensions separated by commas (e.g., jpg,png,zip).',
    'ATTACH_PATH'                => 'Attachment storage path',
    'ATTACH_PATH_EXPLAIN'        => 'Directory where files will be stored. Relative to the forum root (e.g., files/trackers/).',
    'KIB'                        => 'KiB',
    'GROUP_ATTACH_PERMISSIONS'   => 'Attachment Permissions by Group',
    'GROUP_ATTACH_EXPLAIN'       => 'Select which groups are allowed to upload files to the tracker.',
    'GROUP_NAME'                 => 'Group Name',
    'CAN_UPLOAD_ATTACH'          => 'Can Upload',

    // General & Shared
    'TRACKERS'         => 'Trackers',
    'YES'              => 'Yes',
    'NO'               => 'No',
    'COLON'            => ':',
    'STATISTICS'       => 'Statistics',
    'ACTION'           => 'Action',
    'OPTIONS'          => 'Staff Options',
    'COMMENTS'         => 'Comments',
    'HISTORY'          => 'History',
    'PRIVATE'          => 'Private',
    'STATUS'           => 'Status',
    'SEVERITY'         => 'Severity',
    'COMPONENT'        => 'Component',
    'PROJECT'          => 'Project',
    'TITLE'            => 'Title',
    'UNASSIGNED'       => 'Unassigned',
    'UNKNOWN'          => 'Unknown',
    'UNCATEGORISED'    => 'Uncategorised/Normal',
    'SUBMIT'           => 'Submit',
    'CANCEL'           => 'Cancel',

    // Viewtracker / Viewproject
    'MANAGE_PROJECTS'        => 'Manage Projects',
    'PROJECT_NAME'           => 'Project Name',
    'TRACKER_NAME'           => 'Tracker name',
    'ADD_PROJECT'            => 'Add New Project',
    'EDIT_PROJECT'           => 'Edit Project',
    'NO_PROJECTS'            => 'There are no projects available for this tracker.',
    'NO_TRACKER'             => 'The requested tracker does not exist.',
    'TRACKER_EXPLAIN'        => 'Please select the project you would like to open below.',
    'TRACKER_PRIVATE'        => 'Note: Submissions to this tracker are private; only you and team members will see the information.',
    'PROJECT_NOTE'           => 'Project Notes',
    'PROJECT_ADDED'          => 'The project has been successfully added.',
    'PROJECT_UPDATED'        => 'The project has been successfully updated.',
    'PROJECT_DELETED'        => 'The project has been deleted.',
    'CONFIRM_DELETE_PROJECT' => 'Are you sure you want to delete this project? This action cannot be undone.',
    'ALL_CLOSED'             => 'All closed tickets',
    'ALL_OPEN'               => 'All open tickets',
    'ALL_TICKETS'            => 'All tickets',
    'NO_TICKETS'             => 'There are no tickets to display.',
    'FILTER_TICKETS'         => 'Filter tickets',
    'CURRENT_STATUS'         => 'Currently showing',
    'LOGIN_REQUIRED'         => 'You must be logged in to access the tracker.',
    'TRACKER_DISABLED'       => 'The tracker system is currently disabled.',

    // Posting (New, Reply, Edit, Delete)
    'NEW_TICKET'               => 'New Ticket',
    'REPLY_TICKET'             => 'Reply to Ticket',
    'EDIT_TICKET'              => 'Edit Ticket',
    'EDIT_COMMENT'             => 'Edit Comment',
    'BUTTON_NEW_TICKET'        => 'New Ticket',
    'POST_STORED_SUCCESS'      => 'The post has been stored successfully.',
    'TICKET_DELETED_SUCCESS'   => 'The ticket has been deleted successfully.',
    'POST_DELETED_SUCCESS'     => 'The post has been deleted successfully.',
    'CONFIRM_DELETE'           => 'Are you sure you want to delete this item?',
    'CONFIRM_DELETE_TICKET'    => 'Are you sure you want to delete this ticket and all its comments?',
    'CONFIRM_DELETE_POST'      => 'Are you sure you want to delete this comment?',
    'RETURN_PROJECT'           => 'Click %1$sHERE%2$s to return to the project.',
    'RETURN_PAGE'              => 'Click %1$sHERE%2$s to return to the previous page.',
    'RETURN_INDEX'              => 'Return to index page',
    'UPLOAD_ATTACHMENT'        => 'Upload Attachment',

    // Viewticket & Attachments
    'TICKET_DETAILS'         => 'Ticket details',
    'TICKET_ID'              => 'Ticket ID',
    'REPORTED_BY'            => 'Reported by',
    'REPORTED_ON'            => 'Reported on',
    'REPORTED_FROM'          => 'Reported from (IP)',
    'ASSIGNED'               => 'Assigned to',
    'ASSIGN_TICKET'          => 'Assign Ticket',
    'FIND_USERNAME'          => 'Find a member',
    'QUICK_REPLY'            => 'Quick Reply',
    'CURRENT_ASSIGNED'       => 'Currently Assigned',
    'DUPLICATE_TICKETS'      => 'Duplicate Tickets',
    'DUP_TICKET'             => 'Duplicates of this ticket',
    'DUP_OTHER'              => 'Duplicates of ',
    'NO_ENTRIES'             => 'No comments have been made and there are no history entries.',
    'POSTED_BY'              => 'Posted by',
    'SEND_PM'                => 'Send PM',
    'CHANGE_SEVERITY'        => 'Change ticket severity',
    'CHANGE_STATUS'          => 'Change ticket status',
    'ATTACHMENTS'            => 'Attachments',
    'DOWNLOAD_ATTACHMENT'    => 'Download Attachment',
    'ATTACHMENT_NOT_FOUND'   => 'The requested attachment could not be found in the database.',
    'FILE_NOT_FOUND_ON_DISK' => 'The file was found in the database but is missing from the server storage.',
    'CLOSE_TICKET'           => 'Close Ticket',
    'TICKET_CLOSED_SUCCESS'  => 'The ticket has been closed successfully.',
    'CLOSED'                  => 'Closed',

    // History Logs
    'TICKET_ASSIGNED_TO' => 'Ticket assigned to: %s',
    'CHANGED_STATUS'     => 'Changed ticket status from "%1$s" to "%2$s"',
    'CHANGED_SEVERITY'   => 'Changed ticket severity from "%1$s" to "%2$s"',
    'CHANGED_COMPONENT'  => 'Changed ticket component from "%1$s" to "%2$s"',
    'CHANGED_ASSIGN'     => 'Assigned ticket to %1$s %2$s',

    // Totals & Statistics
    'TOTALS'             => 'Totals',
    'TRACKER_STATISTICS' => 'Tracker statistics',
    'CLOSED_TICKETS'     => 'Closed tickets',
    'OPEN_TICKETS'       => 'Open tickets',
    'NUMBER_TICKETS'     => 'Number of tickets',
    'PROJECTS_ALL'       => 'All projects',
    'NO_STATS'           => 'There are no statistics for this tracker.',
    'STATUS_OVERVIEW'    => 'Ticket status overview',

    'TOTAL_TICKETS'    => [
        0    => '0 tickets',
        1    => '1 ticket',
        2    => '%d tickets',
    ],
    'PAGE_TOTAL_POSTS'    => [
        0    => '0 posts',
        1    => '1 post',
        2    => '%d posts',
    ],

	// Dashboard Sync
    'TRACKERS_SYNC_COMPLETE'    => 'Ticket counters and projects have been successfully resynchronized.',
    'TICKET_SYNC'               => 'Ticket Synchronization',
    'TICKET_SYNC_EXPLAIN'       => 'This process recalculates the ticket counters for each project to ensure data integrity.',
    'RESYNC_TICKETS'            => 'Resynchronize now',

    // Dashboard Statistics Labels (Estáticas para evitar el error de plurales)
    'TOTAL_TICKETS_LBL'         => 'Total Tickets',
    'UNANSWERED_TICKETS_LBL'    => 'Unanswered Tickets',
    'CLOSED_TICKETS_LBL'        => 'Closed Tickets',
    'OPEN_TICKETS_LBL'          => 'Open Tickets',
    
    'NOTIFICATION_GROUP_TRACKERS'           => 'Trackers Notifications',
    'NOTIFICATION_TYPE_TRACKERS_ASSIGNED'   => 'A ticket is assigned to you',
    'NOTIFICATION_TICKET_ASSIGNED'          => '%1$s assigned a ticket to you: <strong>%2$s</strong>',
    'NOTIFICATION_TYPE_TRACKERS_REPLY'      => 'A reply is posted in a ticket you are involved in',
    'NOTIFICATION_TICKET_REPLY'             => '%1$s replied to the ticket: <strong>%2$s</strong>',

	'CHANGELOG_GITHUB' => 'GitHub Activity & Updates',
	'CHANGELOG_ERROR' => 'Could not connect to GitHub or file not found.',
	'CHANGELOG_ERROR'   => 'Error: CHANGELOG.md file not found.',
    'CHANGELOG_WAITING' => 'Waiting for remote synchronization with the GitHub repository...',
	'VIEW_ON_GITHUB' => 'Ver en GitHub',
]);