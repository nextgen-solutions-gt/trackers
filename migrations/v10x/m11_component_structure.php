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

class m11_component_structure extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m10_many_to_many_relations'];
	}

	/**
	 * We created the component table and added the relationship in tickets.
	 */
	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'trackers_component' => [
					'COLUMNS' => [
						'component_id'    => ['UINT', null, 'auto_increment'],
						'component_name'  => ['VCHAR_UNI:255', ''],
						'component_order' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'component_id',
				],
			],
			'add_columns' => [
				$this->table_prefix . 'trackers_ticket' => [
					'component_id' => ['UINT', 0],
				],
			],
		];
	}

	/**
	 * We register the ‘components’ mode within the ‘ACP_TRACKERS’ category created in m5.
	 */
	public function update_data()
	{
		return [
			// We add the components mode to the existing basename under the m5 category.
			['module.add', [
				'acp',
				'ACP_TRACKERS',
				[
					'module_basename' => '\nextgen\trackers\acp\trackers_module',
					'modes'           => ['components'],
				],
			]],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'trackers_ticket' => [
					'component_id',
				],
			],
			'drop_tables' => [
				$this->table_prefix . 'trackers_component',
			],
		];
	}
}
