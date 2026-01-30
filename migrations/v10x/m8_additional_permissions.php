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

class m8_additional_permissions extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m7_reply_notifications'];
	}

	public function update_data()
	{
		return [
			// Añadimos los nuevos permisos de usuario
			['permission.add', ['u_tracker_view', true]],
			['permission.add', ['u_tracker_view_private', true]],
			['permission.add', ['u_tracker_close', true]],

			// Añadimos los nuevos permisos de moderador
			['permission.add', ['m_tracker_status', true]],
			['permission.add', ['m_tracker_assign', true]],
			['permission.add', ['m_tracker_logs', true]],
		];
	}
}