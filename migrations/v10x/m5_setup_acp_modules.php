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

class m5_setup_acp_modules extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m4_final_fix'];
    }

    public function update_data()
    {
        return [
            // 1. Create the main category
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_TRACKERS'
            ]],

            // 2. Add the Dashboard explicitly (so that it uses the correct language key)
            ['module.add', [
                'acp',
                'ACP_TRACKERS',
                [
                    'module_basename'   => '\nextgen\trackers\acp\trackers_module',
                    'module_langname'   => 'ACP_TRACKERS_DASHBOARD',
                    'module_mode'       => 'dashboard',
                    'module_auth'       => 'ext_nextgen/trackers && acl_a_board',
                ],
            ]],

            // 3. Add the rest of the modes in a group
            ['module.add', [
                'acp',
                'ACP_TRACKERS',
                [
                    'module_basename'   => '\nextgen\trackers\acp\trackers_module',
                    'modes'             => ['settings', 'projects', 'severities', 'statuses'],
                ],
            ]],
        ];
    }
}
