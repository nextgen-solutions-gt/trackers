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

class m3_add_assigned_user extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m2_initial_data'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'trackers_ticket' => [
                    'assigned_user' => ['UINT', 0], // Unsigned integer, default 0 (Unassigned)
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                $this->table_prefix . 'trackers_ticket' => [
                    'assigned_user',
                ],
            ],
        ];
    }
}
