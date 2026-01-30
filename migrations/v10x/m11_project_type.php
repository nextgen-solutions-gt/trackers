<?php
namespace nextgen\trackers\migrations\v10x;

class m11_project_type extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m10_many_to_many_relations'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'trackers_project' => [
					'project_type' => ['BOOL', 0], // 0 = Categoría, 1 = Tracker
				],
			],
		];
	}
}