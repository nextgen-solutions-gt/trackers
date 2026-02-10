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

class m16_mcp_and_logs extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m15_report_system'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'trackers_log' => [
					'COLUMNS' => [
						'log_id'          => ['UINT:10', null, 'auto_increment'],
						'ticket_id'       => ['UINT:10', 0],
						'user_id'         => ['UINT:10', 0],
						'log_ip'          => ['VCHAR:40', ''],
						'log_time'        => ['UINT:11', 0],
						'log_operation'   => ['VCHAR:255', ''],
						'log_data'        => ['MTEXT', ''],
					],
					'PRIMARY_KEY' => 'log_id',
				],
			],
		];
	}

	public function update_data()
	{
		return [
			// 1. We add the missing permission.
			['permission.add', ['m_tracker_report', true, 'm_trackers']],

			// 2. Add the main tab to the MCP
			['module.add', [
				'mcp',
				0,
				'MCP_TRACKERS'
			]],

			// 3. We add the report list section
			['module.add', [
				'mcp',
				'MCP_TRACKERS',
				[
					'module_basename'	=> '\nextgen\trackers\mcp\trackers_mcp_module',
					'modes'				=> ['report_list'],
				]
			]],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [$this->table_prefix . 'trackers_log'],
		];
	}
}
