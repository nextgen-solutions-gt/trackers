<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\ucp;

class trackers_ucp_module_info
{
	public function module()
    {
        return [
            'filename'    => '\nextgen\trackers\ucp\trackers_ucp_module',
            'title'       => 'UCP_TRACKERS_WATCH',
            'modes'       => [
                'watch' => [
                    'title'    => 'UCP_TRACKERS_WATCH_LIST',
                    'auth'     => 'ext_nextgen/trackers && acl_u_tracker_watch',
                    'cat'      => ['UCP_TRACKERS_WATCH'],
                ],
            ],
        ];
    }
}
