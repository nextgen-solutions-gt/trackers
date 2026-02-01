<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
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
    $lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [
    'ACL_CAT_TRACKERS'      => 'Trackers',

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

    // Admin permissions (a_)
    'ACL_A_TRACKERS'       => 'Can manage tracker settings and structures',
]);