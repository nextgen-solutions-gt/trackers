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

class trackers_ucp_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    public function main($id, $mode)
    {
        global $request, $template, $user, $language, $phpbb_container;

        $db = $phpbb_container->get('dbal.conn');
        $helper = $phpbb_container->get('controller.helper');
        $table_prefix = $phpbb_container->getParameter('core.table_prefix');

        $user->add_lang_ext('nextgen/trackers', 'common');
        $this->page_title = $user->lang('UCP_TRACKERS_WATCH_LIST');

        add_form_key('ucp_trackers_watch');

        switch ($mode)
        {
            case 'watch':
                $this->tpl_name = 'ucp_trackers_watch';
                $this->display_watch_list($db, $request, $template, $user, $helper, $table_prefix);
            break;
        }
    }

    /**
     * Lists watched tickets and handles unwatch actions
     */
    protected function display_watch_list($db, $request, $template, $user, $helper, $table_prefix)
    {
        // Process Unwatch Action
        if ($request->is_set_post('unwatch'))
        {
            $ticket_ids = $request->variable('t_ids', [0]);

            if (!empty($ticket_ids))
            {
                if (!check_form_key('ucp_trackers_watch'))
                {
                    trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);
                }

                $sql = 'DELETE FROM ' . $table_prefix . 'trackers_watch
                    WHERE user_id = ' . (int) $user->data['user_id'] . '
                        AND ' . $db->sql_in_set('ticket_id', $ticket_ids);
                $db->sql_query($sql);

                meta_refresh(3, $this->u_action);
                
                $message = $user->lang('TICKET_WATCH_UPDATED') . '<br /><br />' . sprintf($user->lang('RETURN_PAGE'), '<a href="' . $this->u_action . '">', '</a>');
                trigger_error($message);
            }
        }

        // List Watched Tickets
        $sql = 'SELECT w.ticket_id, t.ticket_title, t.project_id, pr.tracker_id
            FROM ' . $table_prefix . 'trackers_watch w
            INNER JOIN ' . $table_prefix . 'trackers_ticket t ON w.ticket_id = t.ticket_id
            INNER JOIN ' . $table_prefix . 'trackers_project pr ON t.project_id = pr.project_id
            WHERE w.user_id = ' . (int) $user->data['user_id'] . '
            ORDER BY t.timestamp_created DESC';
        
        $result = $db->sql_query($sql);
        $has_watch = false;

        while ($row = $db->sql_fetchrow($result))
        {
            $has_watch = true;
            $template->assign_block_vars('watch', [
                'TICKET_ID'    => (int) $row['ticket_id'],
                'TICKET_TITLE' => (string) $row['ticket_title'],
                // FIX: Migration to friendly route nextgen_trackers_ticket to avoid index.php
                'U_TICKET'     => $helper->route('nextgen_trackers_ticket', [
                    'page'   => 'viewticket',
                    'ticket' => (int) $row['ticket_id']
                ]),
            ]);
        }
        $db->sql_freeresult($result);

        $template->assign_vars([
            'S_HAS_WATCH' => $has_watch,
            'U_ACTION'    => $this->u_action,
        ]);
    }
}
