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

class m2_initial_data extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m1_initial_schema'];
    }

    public function update_data()
    {
        return [
            // We only insert data from the tables
            ['custom', [[$this, 'insert_data']]],
        ];
    }

    public function insert_data()
    {
        // 1. Severities
        $severity_data = [
            ['severity_name' => 'Severe',            'severity_order' => 1],
            ['severity_name' => 'High',              'severity_order' => 2],
            ['severity_name' => 'Medium',            'severity_order' => 3],
            ['severity_name' => 'Low',               'severity_order' => 4],
            ['severity_name' => 'Possibly invalid',  'severity_order' => 5],
        ];

        // 2. States
        $status_data = [
            ['status_name' => 'New',                   'ticket_new' => 1, 'ticket_closed' => 0, 'status_order' => 1],
            ['status_name' => 'Pending',               'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 2],
            ['status_name' => 'Reviewed',              'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 3],
            ['status_name' => 'Closed',                'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 10],
            ['status_name' => 'Fixed',                 'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 11],
            ['status_name' => 'Duplicate',             'ticket_new' => 0, 'ticket_closed' => 1, 'ticket_duplicate' => 1, 'status_order' => 12],
            ['status_name' => 'Invalid',               'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 13],
            ['status_name' => 'Unreproducible',        'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 14],
            ['status_name' => 'Will not fix',          'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 15],
            ['status_name' => 'Already fixed',         'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 16],
            ['status_name' => 'Possible bug',          'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 4],
            ['status_name' => 'Possible security issue','ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 5],
            ['status_name' => 'Awaiting information',  'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 6],
            ['status_name' => 'Awaiting team input',   'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 7],
            ['status_name' => 'Support request',       'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 8],
            ['status_name' => 'Review later',          'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 9],
            ['status_name' => 'Bug',                   'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 20],
            ['status_name' => 'Implementing',          'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 21],
            ['status_name' => 'Researching',           'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 22],
            ['status_name' => 'Patching in progress',  'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 23],
            ['status_name' => 'Patch written',         'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 24],
            ['status_name' => 'Fix in progress',       'ticket_new' => 0, 'ticket_closed' => 0, 'status_order' => 25],
            ['status_name' => 'Fix completed in VCS',  'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 26],
            ['status_name' => 'Implemented in VCS',    'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 27],
            ['status_name' => 'Will not implement',    'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 28],
            ['status_name' => 'Not a bug',             'ticket_new' => 0, 'ticket_closed' => 1, 'status_order' => 29],
        ];

        // 3. Tracker types
        $tracker_data = [
            ['tracker_name' => 'Incident tracker', 'allow_view_all' => 0],
            ['tracker_name' => 'Security tracker', 'allow_view_all' => 0],
            ['tracker_name' => 'Bug tracker',      'allow_view_all' => 1],
            ['tracker_name' => 'Feature tracker',  'allow_view_all' => 1],
        ];

        foreach ($severity_data as $row) {
            $row['tracker_id'] = 0; 
            $this->db->sql_query('INSERT INTO ' . $this->table_prefix . 'trackers_severity ' . $this->db->sql_build_array('INSERT', $row));
        }

        foreach ($status_data as $row) {
            $row['tracker_id'] = 0; 
            $defaults = ['status_description' => '', 'ticket_new' => 0, 'ticket_reviewed' => 0, 'ticket_closed' => 0, 'ticket_fixed' => 0, 'ticket_duplicate' => 0];
            $row = array_merge($defaults, $row);
            $this->db->sql_query('INSERT INTO ' . $this->table_prefix . 'trackers_status ' . $this->db->sql_build_array('INSERT', $row));
        }

        $this->db->sql_multi_insert($this->table_prefix . 'trackers_tracker', $tracker_data);
    }
}
