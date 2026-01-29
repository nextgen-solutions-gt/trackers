<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\migrations\v10x;

/**
 * Migration for adding notification types
 */
class m6_notifications extends \phpbb\db\migration\migration
{
	/**
	 * Depende de la migración m5 (ACP Modules)
	 */
	public static function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m5_setup_acp_modules'];
	}

	/**
	 * Insertamos manualmente para evitar el error "Undefined migration tool"
	 */
	public function update_data()
	{
		return [
			['custom', [[$this, 'install_notifications']]],
		];
	}

	/**
	 * Función personalizada para insertar el tipo de notificación por SQL
	 */
	public function install_notifications()
	{
		$notification_type_name = 'nextgen.trackers.notification.type.ticket_assigned';

		// 1. Verificar si ya existe para no duplicar
		$sql = 'SELECT notification_type_id 
				FROM ' . NOTIFICATION_TYPES_TABLE . " 
				WHERE notification_type_name = '" . $this->db->sql_escape($notification_type_name) . "'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			// 2. Insertar directamente en la tabla de tipos de notificación
			$sql_ary = [
				'notification_type_name'    => $notification_type_name,
				'notification_type_enabled' => 1,
			];

			$sql = 'INSERT INTO ' . NOTIFICATION_TYPES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Limpieza al desinstalar
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