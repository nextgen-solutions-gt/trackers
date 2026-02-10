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

/**
 * Migration for adding notification types
 */
class m6_notifications extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m5_setup_acp_modules'];
	}

	/**
	 * We insert manually to avoid the “Undefined migration tool” error.
	 */
	public function update_data()
	{
		return [
			['custom', [[$this, 'install_notifications']]],
		];
	}

	/**
	 * Custom function to insert notification type via SQL
	 */
	public function install_notifications()
	{
		$notification_type_name = 'nextgen.trackers.notification.type.ticket_assigned';

		// 1. Check if it already exists to avoid duplication
		$sql = 'SELECT notification_type_id 
				FROM ' . NOTIFICATION_TYPES_TABLE . " 
				WHERE notification_type_name = '" . $this->db->sql_escape($notification_type_name) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			// 2. Insert directly into the notification type table
			$sql_ary = [
				'notification_type_name'    => $notification_type_name,
				'notification_type_enabled' => 1,
			];

			$sql = 'INSERT INTO ' . NOTIFICATION_TYPES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Cleanup upon uninstallation
	 */
	public function revert_data()
	{
		return [
			['custom', [[$this, 'uninstall_notifications']]],
		];
	}

	public function uninstall_notifications()
	{
		$sql = 'DELETE FROM ' . NOTIFICATION_TYPES_TABLE . " 
				WHERE notification_type_name = 'nextgen.trackers.notification.type.ticket_assigned'";
		$this->db->sql_query($sql);
	}
}
