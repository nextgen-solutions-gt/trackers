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

class m12_attachments_and_auth extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m11_component_structure'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                // New plural attachment table
                $this->table_prefix . 'trackers_attachments' => [
                    'COLUMNS' => [
                        'attach_id'         => ['UINT', null, 'auto_increment'],
                        'post_id'           => ['UINT', 0],
                        'ticket_id'         => ['UINT', 0],
                        'user_id'           => ['UINT', 0],
                        'physical_filename' => ['VCHAR:255', ''],
                        'real_filename'     => ['VCHAR:255', ''],
                        'extension'         => ['VCHAR:20', ''],
                        'mimetype'          => ['VCHAR:100', ''],
                        'filesize'          => ['UINT:20', 0],
                        'filetime'          => ['UINT:11', 0],
                    ],
                    'PRIMARY_KEY' => 'attach_id',
                    'KEYS' => [
                        'post_id'   => ['INDEX', ['post_id']],
                        'ticket_id' => ['INDEX', ['ticket_id']],
                    ],
                ],
                // Granular permissions table
                $this->table_prefix . 'trackers_attachments_auth' => [
                    'COLUMNS' => [
                        'group_id'    => ['UINT', 0],
                        'project_id'  => ['UINT', 0], // 0 = Global for all projects
                        'can_attach'  => ['BOOL', 0],
                    ],
                    'KEYS' => [
                        'group_project' => ['UNIQUE', ['group_id', 'project_id']],
                    ],
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
            // We insert the default values that the ACP module expects to find.
            ['config.add', ['trackers_enabled', 1]],
            ['config.add', ['trackers_per_page', 15]],
            ['config.add', ['trackers_attachments', 1]],
            ['config.add', ['trackers_attach_max_size', 2048]], // KiB
            ['config.add', ['trackers_attach_extensions', 'jpg,jpeg,png,gif,zip,pdf']],
            ['config.add', ['trackers_attach_path', 'files/trackers/']],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [
                $this->table_prefix . 'trackers_attachments',
                $this->table_prefix . 'trackers_attachments_auth',
            ],
        ];
    }
}
