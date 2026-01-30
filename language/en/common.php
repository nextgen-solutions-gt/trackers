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
	// ACP entries
	'ACP_TRACKERS'            => 'Trackers',
	'ACP_TRACKERS_TITLE'      => 'Tracker Management',
	'ACP_TRACKERS_SETTINGS'   => 'General Settings',
	'TRACKERS_ENABLE'         => 'Enable Trackers',
	'TRACKERS_ENABLE_EXPLAIN' => 'Enable or disable the tracker system globally.',
	'ITEMS_PER_PAGE'          => 'Tickets per page',
	'ITEMS_PER_PAGE_EXPLAIN'  => 'Number of tickets to display on the project view.',
	'GENERAL_SETTINGS'        => 'General Configuration',
	'ACP_TRACKERS_PROJECTS'   => 'Manage Projects',
	'ACP_TRACKERS_EXPLAIN'    => 'Here you can configure your bug trackers, manage specific projects, and define statuses or severities.',
	'ACP_TRACKERS_SEVERITIES' => 'Manage Severities',
	'ACP_TRACKERS_STATUSES'   => 'Manage Statuses',
	'ITEM_DELETED'            => 'Item successfully deleted.',
	'ITEM_UPDATED'            => 'Item successfully updated.',
	'ADD'                     => 'Add',
	'EDIT'                    => 'Edit',
	'DELETE'                  => 'Delete',
	'NO_PAGE_MODE'            => 'Invalid or no page mode specified.',

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

	// Viewtracker / Viewproject
	'MANAGE_PROJECTS'        => 'Manage Projects',
	'PROJECT_NAME'           => 'Project Name',
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

	// Viewticket
	'TICKET_DETAILS'     => 'Ticket details',
	'TICKET_ID'          => 'Ticket ID',
	'REPORTED_BY'        => 'Reported by',
	'REPORTED_ON'        => 'Reported on',
	'REPORTED_FROM'      => 'Reported from (IP)',
	'ASSIGNED'           => 'Assigned to',
	'ASSIGN_TICKET'      => 'Assign Ticket',
	'FIND_USERNAME'      => 'Find a member',
	'QUICK_REPLY'        => 'Quick Reply',
	'CURRENT_ASSIGNED'   => 'Currently Assigned',
	'DUPLICATE_TICKETS'  => 'Duplicate Tickets',
	'DUP_TICKET'         => 'Duplicates of this ticket',
	'DUP_OTHER'          => 'Duplicates of ',
	'NO_ENTRIES'         => 'No comments have been made and there are no history entries.',
	'POSTED_BY'          => 'Posted by',
	'SEND_PM'            => 'Send PM',
	'CHANGE_SEVERITY'    => 'Change ticket severity',
	'CHANGE_STATUS'      => 'Change ticket status',

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

	'TOTAL_TICKETS'	=> [
		0	=> '0 tickets',
		1	=> '1 ticket',
		2	=> '%d tickets',
	],
	'PAGE_TOTAL_POSTS'	=> [
		0	=> '0 posts',
		1	=> '1 post',
		2	=> '%d posts',
	],
	
    'NOTIFICATION_GROUP_TRACKERS'           => 'Trackers Notifications',
    'NOTIFICATION_TYPE_TRACKERS_ASSIGNED'   => 'A ticket is assigned to you',
    'NOTIFICATION_TICKET_ASSIGNED'          => '%1$s assigned a ticket to you: <strong>%2$s</strong>',
	'NOTIFICATION_TYPE_TRACKERS_REPLY'   => 'A reply is posted in a ticket you are involved in',
    'NOTIFICATION_TICKET_REPLY'          => '%1$s replied to the ticket: <strong>%2$s</strong>',
]);