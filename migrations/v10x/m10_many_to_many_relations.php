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

class m10_many_to_many_relations extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m9_pro_features_schema'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				// Relational table for assigning statuses/severities to multiple projects
				$this->table_prefix . 'trackers_relations' => [
					'COLUMNS' => [
						'relation_id' => ['UINT', null, 'auto_increment'],
						'project_id'  => ['UINT', 0],
						'item_id'     => ['UINT', 0],
						'item_type'   => ['VCHAR:20', ''], // 'status', 'severity' o 'component'
					],
					'PRIMARY_KEY' => 'relation_id',
					'KEYS' => [
						'proj_item' => ['INDEX', ['project_id', 'item_id', 'item_type']],
					],
				],
			],
			'add_columns' => [
				// Column required for the subproject hierarchy
				$this->table_prefix . 'trackers_project' => [
					'parent_id' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'trackers_relations',
			],
			'drop_columns' => [
				$this->table_prefix . 'trackers_project' => ['parent_id'],
			],
		];
	}
}
