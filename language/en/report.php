<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
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
	'REPORT_TICKET'			=> 'Report ticket',
	'REPORT_TICKET_EXPLAIN'	=> 'Use this form to report the selected ticket to the moderators. Reporting should generally be used only if the ticket breaks tracker rules or contains inappropriate content.',
	'REPORT_REASON'			=> 'Reason for reporting',
	'REPORT_TEXT'			=> 'Further information',
	'REPORT_TEXT_EXPLAIN'	=> 'Please provide more details if necessary to explain why you are reporting this ticket.',
	
	'TICKET_REPORTED_SUCCESS'	=> 'The ticket has been reported successfully.',
	'ALREADY_REPORTED'			=> 'This ticket has already been reported.',
	
	'CLOSE_REPORT'			=> 'Close report',
	'REPORT_CLOSED_SUCCESS'	=> 'The report has been closed.',
	
	'NOTIFICATION_TICKET_REPORTED'	=> '<strong>Ticket reported</strong><br />A ticket has been reported by %1$s: <em>%2$s</em>',
	
	'REPORTED' => 'This ticket has been reported.',
]);
