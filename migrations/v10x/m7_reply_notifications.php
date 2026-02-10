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

class m7_reply_notifications extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m6_notifications'];
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'install_reply_notification']]],
		];
	}

	public function install_reply_notification()
	{
		$notification_type_name = 'nextgen.trackers.notification.type.ticket_reply';
		$sql = 'SELECT notification_type_id FROM ' . NOTIFICATION_TYPES_TABLE . " WHERE notification_type_name = '" . $this->db->sql_escape($notification_type_name) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row) {
			$this->db->sql_query('INSERT INTO ' . NOTIFICATION_TYPES_TABLE . ' ' . $this->db->sql_build_array('INSERT', [
				'notification_type_name'    => $notification_type_name,
				'notification_type_enabled' => 1,
			]));
		}
	}
}
