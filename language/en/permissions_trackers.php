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
    'ACL_CAT_TRACKERS'      	 => 'Trackers',

    // User permissions (u_)
    'ACL_U_TRACKER_VIEW'         => 'Can view the tracker and project lists',
    'ACL_U_TRACKER_CREATE'       => 'Can create new tickets',
    'ACL_U_TRACKER_REPLY'        => 'Can reply to tickets',
    'ACL_U_TRACKER_EDIT'         => 'Can edit own tickets',
    'ACL_U_TRACKER_DELETE'       => 'Can delete own tickets',
    'ACL_U_TRACKER_CLOSE'        => 'Can close own tickets',
    'ACL_U_TRACKER_VIEW_PRIVATE' => 'Can view private tickets',

    // Moderator permissions (m_)
    'ACL_M_TRACKER_EDIT'   => 'Can edit any ticket',
    'ACL_M_TRACKER_DELETE' => 'Can delete any ticket',
    'ACL_M_TRACKER_STATUS' => 'Can change ticket status or severity',
    'ACL_M_TRACKER_ASSIGN' => 'Can assign tickets to users/groups',
    'ACL_M_TRACKER_LOGS'   => 'Can view ticket history logs',
    'ACL_M_TRACKER_MOVE'     => 'Can move tickets',
    'ACL_M_TRACKER_UNASSIGN' => 'Can unassign tickets',

    // Admin permissions (a_)
    'ACL_A_TRACKERS'       => 'Can manage tracker settings and structures',
]);
