<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\migrations\v10x;

class m1_initial_schema extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v330\v330'];
    }

    /**
     * Update database schema
     */
    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'trackers_history' => [
                    'COLUMNS' => [
                        'history_id'        => ['UINT', null, 'auto_increment'],
                        'ticket_id'         => ['UINT', 0],
                        'user_id'           => ['UINT', 0],
                        'history_text'      => ['MTEXT_UNI', ''],
                        'history_timestamp' => ['TIMESTAMP', 0],
                        'history_type'      => ['TINT:3', 0],
                    ],
                    'PRIMARY_KEY' => 'history_id',
                ],

                $this->table_prefix . 'trackers_post' => [
                    'COLUMNS' => [
                        'post_id'         => ['UINT', null, 'auto_increment'],
                        'ticket_id'       => ['UINT', 0],
                        'user_id'         => ['UINT', 0],
                        'post_private'    => ['BOOL', 0],
                        'ticket_title'    => ['VCHAR:100', ''],
                        'post_text'       => ['TEXT_UNI', ''],
                        'post_ip'         => ['VCHAR:40', ''],
                        'post_timestamp'  => ['TIMESTAMP', 0],
                        'bbcode_bitfield' => ['VCHAR:255', ''],
                        'bbcode_uid'      => ['VCHAR:8', ''],
                        'bbcode_flags'    => ['INT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'post_id',
                    'KEYS' => [
                        'ticket_id' => ['INDEX', ['ticket_id']],
                    ],
                ],

                $this->table_prefix . 'trackers_project' => [
                    'COLUMNS' => [
                        'project_id'          => ['UINT', null, 'auto_increment'],
                        'tracker_id'          => ['UINT', 0],
                        'project_name'        => ['VCHAR:50', ''],
                        'project_description' => ['VCHAR:255', ''],
                        'project_note'        => ['MTEXT_UNI', ''],
                        'project_private'     => ['BOOL', 0],
                        'project_locked'      => ['BOOL', 0],
                        'project_active'      => ['BOOL', 0],
                    ],
                    'PRIMARY_KEY' => 'project_id',
                ],

                $this->table_prefix . 'trackers_project_auth' => [
                    'COLUMNS' => [
                        'project_id' => ['UINT', 0],
                        'group_id'   => ['UINT', 0],
                        'user_id'    => ['UINT', 0],
                    ],
                ],

                $this->table_prefix . 'trackers_severity' => [
                    'COLUMNS' => [
                        'severity_id'     => ['UINT', null, 'auto_increment'],
                        'tracker_id'      => ['UINT', 0],
                        'severity_name'   => ['VCHAR:50', ''],
                        'severity_colour' => ['VCHAR:6', ''],
                        'severity_order'  => ['TINT:2', 0],
                    ],
                    'PRIMARY_KEY' => 'severity_id',
                ],

                $this->table_prefix . 'trackers_status' => [
                    'COLUMNS' => [
                        'status_id'          => ['UINT', null, 'auto_increment'],
                        'tracker_id'         => ['UINT', 0],
                        'status_name'        => ['VCHAR:50', ''],
                        'status_description' => ['MTEXT_UNI', ''],
                        'status_order'       => ['UINT', 0],
                        'ticket_new'         => ['BOOL', 0],
                        'ticket_reviewed'    => ['BOOL', 0],
                        'ticket_closed'      => ['BOOL', 0],
                        'ticket_fixed'       => ['BOOL', 0],
                        'ticket_duplicate'   => ['BOOL', 0],
                    ],
                    'PRIMARY_KEY' => 'status_id',
                ],

                $this->table_prefix . 'trackers_ticket' => [
                    'COLUMNS' => [
                        'ticket_id'         => ['UINT', null, 'auto_increment'],
                        'project_id'        => ['UINT', 0],
                        'post_id'           => ['UINT', 0],
                        'user_id'           => ['UINT', 0],
                        'reporter_ip'       => ['VCHAR:40', ''],
                        'user_last_id'      => ['UINT', 0],
                        'status_id'         => ['UINT', 0],
                        'severity_id'       => ['UINT', 0],
                        'duplicate_id'      => ['UINT', 0],
                        'ticket_private'    => ['BOOL', 0],
                        'ticket_title'      => ['VCHAR:100', ''],
                        'ticket_locked'     => ['BOOL', 0],
                        'assigned_group'    => ['UINT', 0],
                        'assigned_user'     => ['UINT', 0],
                        'timestamp_created' => ['TIMESTAMP', 0],
                    ],
                    'PRIMARY_KEY' => 'ticket_id',
                    'KEYS' => [
                        'project_id'  => ['INDEX', ['project_id']],
                        'user_id'     => ['INDEX', ['user_id']],
                        'status_id'   => ['INDEX', ['status_id']],
                        'severity_id' => ['INDEX', ['severity_id']],
                    ],
                ],

                $this->table_prefix . 'trackers_tracker' => [
                    'COLUMNS' => [
                        'tracker_id'         => ['UINT', null, 'auto_increment'],
                        'tracker_name'       => ['VCHAR:255', ''],
                        'tracker_email'      => ['VCHAR:255', ''],
                        'allow_view_all'     => ['BOOL', 0],
                        'allow_closed_reply' => ['BOOL', 0],
                    ],
                    'PRIMARY_KEY' => 'tracker_id',
                ],
            ],
        ];
    }

    /**
     * Revert database schema
     */
    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'trackers_history',
                $this->table_prefix . 'trackers_post',
                $this->table_prefix . 'trackers_project',
                $this->table_prefix . 'trackers_project_auth',
                $this->table_prefix . 'trackers_severity',
                $this->table_prefix . 'trackers_status',
                $this->table_prefix . 'trackers_ticket',
                $this->table_prefix . 'trackers_tracker',
            ],
        ];
    }
}
