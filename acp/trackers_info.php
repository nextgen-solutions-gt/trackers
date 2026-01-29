<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\acp;

class trackers_info
{
    public function module_info()
    {
        return [
            'filename'  => '\nextgen\trackers\acp\trackers_module',
            'title'     => 'ACP_TRACKERS',
            'modes'     => [
                'settings' => [
                    'title' => 'ACP_TRACKERS_SETTINGS', 
                    'auth'  => 'ext_nextgen/trackers && acl_a_board', 
                    'cat'   => ['ACP_TRACKERS']
                ],
                'projects' => [
                    'title' => 'ACP_TRACKERS_PROJECTS', 
                    'auth'  => 'ext_nextgen/trackers && acl_a_board', 
                    'cat'   => ['ACP_TRACKERS']
                ],
                // NUEVO: Gestión de Severidades
                'severities' => [
                    'title' => 'ACP_TRACKERS_SEVERITIES', 
                    'auth'  => 'ext_nextgen/trackers && acl_a_board', 
                    'cat'   => ['ACP_TRACKERS']
                ],
                // NUEVO: Gestión de Estados
                'statuses' => [
                    'title' => 'ACP_TRACKERS_STATUSES', 
                    'auth'  => 'ext_nextgen/trackers && acl_a_board', 
                    'cat'   => ['ACP_TRACKERS']
                ],
            ],
        ];
    }

    public function module()
    {
        return $this->module_info();
    }
}