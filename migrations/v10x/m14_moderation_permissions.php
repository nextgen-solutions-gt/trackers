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

class m14_moderation_permissions extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m13_add_project_counter'];
	}

	public function update_data()
	{
		return [
			['permission.add', ['m_tracker_move', true, 'm_trackers']],
			['permission.add', ['m_tracker_unassign', true, 'm_trackers']],
		];
	}
}
