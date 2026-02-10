<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\mcp;

class trackers_mcp_module_info
{
	public function module()
	{
		return [
			'filename'	=> '\nextgen\trackers\mcp\trackers_mcp_module',
			'title'		=> 'MCP_TRACKERS',
			'modes'		=> [
				'report_list' => [
					'title'	=> 'MCP_TRACKERS_REPORT_LIST',
					'auth'	=> 'acl_m_tracker_report',
					'cat'	=> ['MCP_TRACKERS'],
				],
			],
		];
	}
}
