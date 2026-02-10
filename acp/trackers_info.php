<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\acp;

class trackers_info
{
    /**
     * Module information
     *
     * @return array
     */
    public function module()
    {
        return [
            'filename'  => '\nextgen\trackers\acp\trackers_module',
            'title'     => 'ACP_TRACKERS_TITLE',
            'modes'     => [
                'settings'  => [
                    'title' => 'ACP_TRACKERS_SETTINGS',
                    'auth'  => 'ext_nextgen/trackers && acl_a_trackers',
                    'cat'   => ['ACP_TRACKERS_TITLE']
                ],
                'projects'  => [
                    'title' => 'ACP_TRACKERS_PROJECTS',
                    'auth'  => 'ext_nextgen/trackers && acl_a_trackers',
                    'cat'   => ['ACP_TRACKERS_TITLE']
                ],
                'statuses'  => [
                    'title' => 'ACP_TRACKERS_STATUSES',
                    'auth'  => 'ext_nextgen/trackers && acl_a_trackers',
                    'cat'   => ['ACP_TRACKERS_TITLE']
                ],
                'severities' => [
                    'title' => 'ACP_TRACKERS_SEVERITIES',
                    'auth'  => 'ext_nextgen/trackers && acl_a_trackers',
                    'cat'   => ['ACP_TRACKERS_TITLE']
                ],
                'components' => [
                    'title' => 'ACP_TRACKERS_COMPONENTS',
                    'auth'  => 'ext_nextgen/trackers && acl_a_trackers',
                    'cat'   => ['ACP_TRACKERS_TITLE']
                ],
            ],
        ];
    }
}
