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

class trackers_mcp_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;
    protected $table_prefix;

    public function main($id, $mode)
    {
        global $request, $template, $user, $language, $phpbb_container;

        // We load the base prefix
        $this->table_prefix = $phpbb_container->getParameter('core.table_prefix');
        
        // We load the specific language file for the MCP.
        $language->add_lang('mcp', 'nextgen/trackers');

        // Load dependencies from the container
        $functions = $phpbb_container->get('nextgen.trackers.includes.functions');
        $db = $phpbb_container->get('dbal.conn');
        $helper = $phpbb_container->get('controller.helper');

        $this->page_title = $language->lang('MCP_TRACKERS_TITLE');

        switch ($mode)
        {
            case 'report_list':
                $this->tpl_name = 'mcp_trackers_reports';
                $this->display_report_list($db, $functions, $request, $template, $user, $helper);
            break;
        }
    }

    /**
     * Lists reported tickets and allows them to be managed
     */
    protected function display_report_list($db, $functions, $request, $template, $user, $helper)
    {
        global $phpbb_container;
        $tp = $this->table_prefix;

        // Process report closure if the form has been submitted
        if ($request->is_set_post('close_report'))
        {
            $report_ids = $request->variable('report_id_list', array(0));
            
            if (!empty($report_ids))
            {
                if (!check_form_key('mcp_trackers'))
                {
                    trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);
                }

                // 1. Obtain the ticket_ids associated with these reports.
                // IMPORTANT: Since your notification class uses ticket_id as item_id, 
                // we need these IDs to clear the bell.
                $sql = 'SELECT DISTINCT ticket_id 
                        FROM ' . $tp . 'trackers_reports 
                        WHERE ' . $db->sql_in_set('report_id', $report_ids);
                $result = $db->sql_query($sql);
                
                $tickets_to_check = [];
                while ($row = $db->sql_fetchrow($result))
                {
                    $tickets_to_check[] = (int) $row['ticket_id'];
                }
                $db->sql_freeresult($result);

                // 2. REMOVE VISUAL NOTIFICATIONS
                if (!empty($tickets_to_check))
                {
                    $notification_manager = $phpbb_container->get('notification_manager');
                    
                    // NOW YES: We use tickets_to_check because that is the actual item_id in the notifications table.
                    $notification_manager->delete_notifications(
                        'nextgen.trackers.notification.type.ticket_reported', 
                        $tickets_to_check
                    );

                    // Manual backup cleaning in case the manager fails
                    $sql = 'SELECT notification_type_id 
                            FROM ' . \NOTIFICATION_TYPES_TABLE . " 
                            WHERE notification_type_name = 'nextgen.trackers.notification.type.ticket_reported'";
                    $result = $db->sql_query($sql);
                    $nt_id = (int) $db->sql_fetchfield('notification_type_id');
                    $db->sql_freeresult($result);

                    if ($nt_id)
                    {
                        $sql = 'DELETE FROM ' . \NOTIFICATIONS_TABLE . ' 
                                WHERE notification_type_id = ' . (int) $nt_id . ' 
                                AND ' . $db->sql_in_set('item_id', $tickets_to_check);
                        $db->sql_query($sql);
                    }
                }

                // 3. PHYSICAL DELETION of reports
                $sql = 'DELETE FROM ' . $tp . 'trackers_reports
                        WHERE ' . $db->sql_in_set('report_id', $report_ids);
                $db->sql_query($sql);

                // 4. Update the ticket flag and log the entry
                foreach ($tickets_to_check as $tid)
                {
                    $sql = 'SELECT COUNT(report_id) as total 
                            FROM ' . $tp . 'trackers_reports
                            WHERE ticket_id = ' . (int) $tid;
                    $result = $db->sql_query($sql);
                    $total_restantes = (int) $db->sql_fetchfield('total');
                    $db->sql_freeresult($result);

                    if ($total_restantes === 0)
                    {
                        $db->sql_query('UPDATE ' . $tp . 'trackers_ticket SET ticket_reported = 0 WHERE ticket_id = ' . (int) $tid);
                    }
                    
                    // Record in the audit log
                    $functions->add_log($tid, 'LOG_REPORT_CLOSED_MCP');
                }

                meta_refresh(3, $this->u_action);
                
                $message = $user->lang('REPORTS_CLOSED_SUCCESS') . '<br /><br />' . sprintf($user->lang('RETURN_PAGE'), '<a href="' . $this->u_action . '">', '</a>');
                trigger_error($message);
            }
        }

        // --- Listing logic ---
        $start = $request->variable('start', 0);
        $limit = 20;

        $sql = 'SELECT COUNT(report_id) as total FROM ' . $tp . 'trackers_reports';
        $result = $db->sql_query($sql);
        $total_reports = (int) $db->sql_fetchfield('total');
        $db->sql_freeresult($result);

        $sql_ary = [
            'SELECT'    => 'r.report_id, r.ticket_id, r.user_id, r.report_reason, r.report_time, 
                            t.ticket_title, 
                            u.username, u.user_colour',
            'FROM'      => [$tp . 'trackers_reports' => 'r'],
            'LEFT_JOIN' => [
                [
                    'FROM' => [$tp . 'trackers_ticket' => 't'], 
                    'ON'   => 'r.ticket_id = t.ticket_id'
                ],
                [
                    'FROM' => [USERS_TABLE => 'u'], 
                    'ON'   => 'r.user_id = u.user_id'
                ],
            ],
            'ORDER_BY'  => 'r.report_time DESC',
        ];

        $sql = $db->sql_build_query('SELECT', $sql_ary);
        $result = $db->sql_query_limit($sql, $limit, $start);

        while ($row = $db->sql_fetchrow($result))
        {
            $user_id = (isset($row['user_id'])) ? (int) $row['user_id'] : ANONYMOUS;
            $username = (isset($row['username'])) ? $row['username'] : '';
            $user_colour = (isset($row['user_colour'])) ? $row['user_colour'] : '';

            $template->assign_block_vars('tracker_reports_list', [
                'REPORT_ID'     => (int) $row['report_id'],
                'TICKET_ID'     => (int) $row['ticket_id'],
                'TICKET_TITLE'  => ($row['ticket_title']) ? (string) $row['ticket_title'] : 'Ticket #' . $row['ticket_id'],
                'REPORTER'      => get_username_string('full', $user_id, $username, $user_colour),
                'REASON'        => (string) $row['report_reason'],
                'TIME'          => $user->format_date($row['report_time']),
                // FIX: Migration to friendly route nextgen_trackers_ticket
                'U_VIEW_TICKET' => $helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => (int) $row['ticket_id']]),
            ]);
        }
        $db->sql_freeresult($result);

        $pagination = $phpbb_container->get('pagination');
        $pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $total_reports, $limit, $start);

        add_form_key('mcp_trackers');

        $template->assign_vars([
            'S_HAS_REPORTS' => ($total_reports > 0),
            'TOTAL_REPORTS' => (int) $total_reports,
            'U_ACTION'      => $this->u_action,
        ]);
    }
}
