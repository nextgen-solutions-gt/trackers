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

class m15_report_system extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m14_moderation_permissions'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				// New table for detailed reports
				$this->table_prefix . 'trackers_reports' => [
					'COLUMNS' => [
						'report_id'     => ['UINT', null, 'auto_increment'],
						'ticket_id'     => ['UINT', 0],
						'user_id'       => ['UINT', 0], // User submitting the report
						'report_reason' => ['MTEXT_UNI', ''],
						'report_time'   => ['TIMESTAMP', 0],
						'report_closed' => ['BOOL', 0], // If the moderator has already reviewed it
					],
					'PRIMARY_KEY' => 'report_id',
					'KEYS' => [
						'ticket_id' => ['INDEX', 'ticket_id'],
					],
				],
			],
			'add_columns' => [
				// We add a quick flag to the ticket table to avoid heavy JOINs in the listing.
				$this->table_prefix . 'trackers_ticket' => [
					'ticket_reported' => ['BOOL', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'trackers_reports',
			],
			'drop_columns' => [
				$this->table_prefix . 'trackers_ticket' => [
					'ticket_reported',
				],
			],
		];
	}
}
