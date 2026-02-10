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

class m4_final_fix extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m3_add_assigned_user'];
    }

    public function update_schema()
    {
        return [
            'add_columns' => [
                $this->table_prefix . 'trackers_ticket' => [
                    'severity_id'    => ['UINT', 1], 
                    'ticket_private' => ['BOOL', 0], 
                ],
            ],
        ];
    }

    public function update_data()
    {
        return [
            ['custom', [[$this, 'sync_main_posts']]],
        ];
    }

    /**
    * This function searches for the first post of each ticket and 
    * ensures that the ticket knows what its initial post is.
    */
    public function sync_main_posts()
    {
        $sql = 'SELECT ticket_id FROM ' . $this->table_prefix . 'trackers_ticket';
        $result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {
            $ticket_id = (int) $row['ticket_id'];

            // We search for the oldest post for this ticket.
            $sql_post = 'SELECT post_id FROM ' . $this->table_prefix . 'trackers_post
                WHERE ticket_id = ' . (int) $ticket_id . '
                ORDER BY post_timestamp ASC, post_id ASC';
            $result_post = $this->db->sql_query_limit($sql_post, 1);
            $post_id = (int) $this->db->sql_fetchfield('post_id');
            $this->db->sql_freeresult($result_post);

            if ($post_id)
            {
                $sql_update = 'UPDATE ' . $this->table_prefix . 'trackers_ticket
                    SET post_id = ' . $post_id . '
                    WHERE ticket_id = ' . (int) $ticket_id;
                $this->db->sql_query($sql_update);
            }
        }
        $this->db->sql_freeresult($result);
    }

    public function revert_schema()
    {
        return [
            'drop_columns' => [
                $this->table_prefix . 'trackers_ticket' => [
                    'severity_id',
                    'ticket_private',
                ],
            ],
        ];
    }
}
