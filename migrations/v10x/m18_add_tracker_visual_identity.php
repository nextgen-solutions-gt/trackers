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

class m18_add_tracker_visual_identity extends \phpbb\db\migration\migration
{
    static public function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m17_watch_system'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'trackers_tracker' => [
                    'tracker_icon'  => ['VCHAR:50', 'fa-file-text-o'],
                    'tracker_color' => ['VCHAR:7', '#536482'],
                ],
                $this->table_prefix . 'trackers_status' => [
                    'status_new'         => ['TINT:1', 0],
                    'status_closed'      => ['TINT:1', 0],
                    'status_description' => ['TEXT_UNI', ''],
                ],
                $this->table_prefix . 'trackers_severity' => [
                    'severity_description' => ['TEXT_UNI', ''],
                ],
                $this->table_prefix . 'trackers_component' => [
                    'component_description' => ['TEXT_UNI', ''],
                ],
            ],
        ];
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                $this->table_prefix . 'trackers_tracker' => [
                    'tracker_icon', 
                    'tracker_color'
                ],
                $this->table_prefix . 'trackers_status'  => [
                    'status_new', 
                    'status_closed', 
                    'status_description'
                ],
                $this->table_prefix . 'trackers_severity' => [
                    'severity_description',
                ],
                $this->table_prefix . 'trackers_component' => [
                    'component_description',
                ],
            ],
        ];
    }
}
