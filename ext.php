<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers;

/**
 * Trackers extension base
 */
class ext extends \phpbb\extension\base
{
	/**
	 * Verificar si la extensión se puede habilitar
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		$phpbb_check = phpbb_version_compare($config['version'], '3.3.0', '>=');
		$php_check = version_compare(PHP_VERSION, '7.1.3', '>=');

		return $phpbb_check && $php_check;
	}

	/**
	 * Se ejecuta al hacer clic en "Habilitar"
	 */
	public function enable_step($old_state)
	{
		$this->set_notifications_state(1);
		return parent::enable_step($old_state);
	}

	/**
	 * Se ejecuta al hacer clic en "Desactivar"
	 */
	public function disable_step($old_state)
	{
		$this->set_notifications_state(0);
		return parent::disable_step($old_state);
	}

	/**
	 * Función auxiliar para activar/desactivar los tipos de notificación
	 * Esto evita el ServiceNotFoundException sin borrar los datos del usuario.
	 */
	protected function set_notifications_state($state)
	{
		$db = $this->container->get('dbal.conn');
		
		$types = [
			'nextgen.trackers.notification.type.ticket_assigned',
			'nextgen.trackers.notification.type.ticket_reply'
		];

		$sql = 'UPDATE ' . NOTIFICATION_TYPES_TABLE . '
			SET notification_type_enabled = ' . (int) $state . '
			WHERE ' . $db->sql_in_set('notification_type_name', $types);
		
		$db->sql_query($sql);
	}
}