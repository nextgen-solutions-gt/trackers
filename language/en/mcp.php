<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// MCP Trackers
$lang = array_merge($lang, array(	
	// Form actions and success messages
	'CLOSE_REPORTS'             => 'Close Selected Reports',
	'REPORTS_CLOSED_SUCCESS'    => 'The selected reports have been closed successfully.',
	'NO_REPORTED_TICKETS'       => 'There are no reported tickets to display.',
	
	// Table columns
	'TICKET_TITLE'              => 'Ticket Title',
	'REPORTER'                  => 'Reporter',
	'REPORT_REASON'             => 'Reason for Report',
	'REPORT_TIME'               => 'Date',
	'VIEW_DETAILS'              => 'View Ticket',
	'MARK'                      => 'Mark',
	
	// Navigation and Actions
	'REPORTS'                   => 'Reports',
	'MARK_ALL'                  => 'Mark all',
	'UNMARK_ALL'                => 'Unmark all',
	'RETURN_PAGE'               => 'Click %shere%s to return to the previous page.',
	
	// Logs
	'LOG_REPORT_CLOSED_MCP'     => '<strong>Ticket report closed via MCP</strong><br />» %s',
));
