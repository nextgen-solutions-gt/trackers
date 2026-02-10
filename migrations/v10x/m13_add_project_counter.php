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

class m13_add_project_counter extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m12_attachments_and_auth'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				// We add the missing column to the projects table.
				$this->table_prefix . 'trackers_project' => [
					'project_total_tickets' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'trackers_project' => [
					'project_total_tickets',
				],
			],
		];
	}
}
