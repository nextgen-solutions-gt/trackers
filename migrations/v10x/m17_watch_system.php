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

class m17_watch_system extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m16_mcp_and_logs'];
    }

    public function update_schema()
    {
        return [
            'add_tables' => [
                $this->table_prefix . 'trackers_watch' => [
                    'COLUMNS' => [
                        'ticket_id'      => ['UINT:10', 0],
                        'user_id'        => ['UINT:10', 0],
                        'notify_status'  => ['TINT:1', 0],
                    ],
                    // FIX: We define a composite primary key to avoid duplicates.
                    'PRIMARY_KEY' => [
                        'ticket_id',
                        'user_id',
                    ],
                ],
            ],
        ];
    }

	public function update_data()
    {
        return [
            // 1. Permission for watching
            ['permission.add', ['u_tracker_watch', true]],

            // 2. Add the main tab to the UCP
            ['module.add', [
                'ucp',
                0,
                'UCP_TRACKERS_WATCH'
            ]],

            // 3. Add the watch list section (Matching MCP Style)
            ['module.add', [
                'ucp',
                'UCP_TRACKERS_WATCH',
                [
                    'module_basename'    => '\nextgen\trackers\ucp\trackers_ucp_module',
                    'modes'              => ['watch'],
                ]
            ]],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_tables' => [$this->table_prefix . 'trackers_watch'],
        ];
    }
}
