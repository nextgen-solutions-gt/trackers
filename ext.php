<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers;

class ext extends \phpbb\extension\base
{
	/**
	 * Check if the extension can be enabled
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		$phpbb_check = phpbb_version_compare($config['version'], '3.3.0', '>=');
		$php_check = version_compare(PHP_VERSION, '8.2.0', '>=');

		return $phpbb_check && $php_check;
	}

	/**
	 * Runs when you click “Enable.”
	 */
	public function enable_step($old_state)
	{
		$this->set_notifications_state(1);
		return parent::enable_step($old_state);
	}

	/**
	 * Runs when you click “Deactivate.”
	 */
	public function disable_step($old_state)
	{
		$this->set_notifications_state(0);
		return parent::disable_step($old_state);
	}

	/**
	 * Auxiliary function to enable/disable notification types
     * This prevents ServiceNotFoundException without deleting user data.
	 */
	protected function set_notifications_state($state)
	{
		$db = $this->container->get('dbal.conn');
		
		$types = [
			'nextgen.trackers.notification.type.ticket_assigned',
			'nextgen.trackers.notification.type.ticket_reply',
			'nextgen.trackers.notification.type.ticket_reported',
			'nextgen.trackers.notification.type.ticket_update'
		];

		$sql = 'UPDATE ' . NOTIFICATION_TYPES_TABLE . '
			SET notification_type_enabled = ' . (int) $state . '
			WHERE ' . $db->sql_in_set('notification_type_name', $types);
		
		$db->sql_query($sql);
	}
}
