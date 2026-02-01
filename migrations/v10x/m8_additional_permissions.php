<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\migrations\v10x;

class m8_additional_permissions extends \phpbb\db\migration\migration
{
    public static function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m7_reply_notifications'];
    }

    public function update_data()
    {
        return [
            // 1. ELIMINACIÓN DE RASTROS
            ['permission.remove', ['u_tracker_post']],

            // 2. PERMISOS DE USUARIO (Visibles en la categoría 'u_trackers')
            ['permission.add', ['u_tracker_view', true, 'u_trackers']],
            ['permission.add', ['u_tracker_create', true, 'u_trackers']],
            ['permission.add', ['u_tracker_edit', true, 'u_trackers']],
            ['permission.add', ['u_tracker_delete', true, 'u_trackers']],
            ['permission.add', ['u_tracker_reply', true, 'u_trackers']],
            ['permission.add', ['u_tracker_close', true, 'u_trackers']],
            ['permission.add', ['u_tracker_view_private', true, 'u_trackers']],

            // 3. PERMISOS DE MODERADOR (Visibles en la categoría 'm_trackers')
            ['permission.add', ['m_tracker_status', true, 'm_trackers']],
            ['permission.add', ['m_tracker_assign', true, 'm_trackers']],
            ['permission.add', ['m_tracker_logs', true, 'm_trackers']],
            ['permission.add', ['m_tracker_edit', true, 'm_trackers']],
            ['permission.add', ['m_tracker_delete', true, 'm_trackers']],

            // 4. PERMISO ADMINISTRATIVO
            ['permission.add', ['a_trackers', true, 'a_board']],
        ];
    }
}