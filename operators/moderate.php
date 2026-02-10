<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\operators;

class moderate
{
    protected $db;
    protected $auth;
    protected $request;
    protected $template;
    protected $helper;
    protected $language;
    protected $user;
    protected $tables;

    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\controller\helper $helper, \phpbb\language\language $language, \phpbb\user $user, $table_prefix) 
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->request = $request;
        $this->template = $template;
        $this->helper = $helper;
        $this->language = $language;
        $this->user = $user;

        $this->tables = [
            'projects' => $table_prefix . 'project',
            'tickets'  => $table_prefix . 'ticket',
            'posts'    => $table_prefix . 'post',
            'status'   => $table_prefix . 'status',
        ];
    }

    public function display()
    {
        $action = $this->request->variable('action', '');
        $ticket_id = $this->request->variable('ticket', 0);

        if (!$ticket_id)
        {
            return $this->helper->error('NO_TICKET_SELECTED', 404);
        }

        switch ($action)
        {
            case 'unassign':
                return $this->unassign($ticket_id);

            case 'move':
                return $this->move($ticket_id);

            case 'close':
                return $this->close($ticket_id);

            case 'reopen':
                return $this->reopen($ticket_id);

            default:
                return $this->helper->error('NO_MODE', 404);
        }
    }

    protected function unassign($ticket_id)
    {
        if (!$this->auth->acl_get('m_tracker_unassign'))
        {
            return $this->helper->error('NOT_AUTHORISED', 403);
        }

        if ($this->request->is_set_post('cancel'))
        {
            $redirect = $this->helper->route('nextgen_trackers_ticket', [
                'page'   => 'viewticket', 
                'ticket' => (int) $ticket_id
            ]);
            return redirect($redirect);
        }

        if (confirm_box(true))
        {
            $sql = 'UPDATE ' . $this->tables['tickets'] . '
                SET assigned_user = 0
                WHERE ticket_id = ' . (int) $ticket_id;
            $this->db->sql_query($sql);

            $this->log_history($ticket_id, 'Ticket was unassigned by moderator.');

            $redirect = $this->helper->route('nextgen_trackers_ticket', [
                'page'   => 'viewticket', 
                'ticket' => (int) $ticket_id
            ]);
            
            meta_refresh(3, $redirect);
            return trigger_error($this->language->lang('TICKET_UNASSIGNED'));
        }
        else
        {
            $s_hidden_fields = build_hidden_fields([
                'ticket' => (int) $ticket_id,
                'action' => 'unassign',
            ]);
            return confirm_box(false, $this->language->lang('CONFIRM_UNASSIGN_TICKET'), $s_hidden_fields);
        }
    }

    protected function move($ticket_id)
    {
        if (!$this->auth->acl_get('m_tracker_move'))
        {
            return $this->helper->error('NOT_AUTHORISED', 403);
        }

        $new_project_id = $this->request->variable('new_project', 0);

        if ($this->request->is_set_post('confirm'))
        {
            if (!check_form_key('moderate_move'))
            {
                return $this->helper->error('FORM_INVALID');
            }

            $sql = 'SELECT t.project_id, p.project_total_tickets 
                    FROM ' . $this->tables['tickets'] . ' t
                    INNER JOIN ' . $this->tables['projects'] . ' p ON t.project_id = p.project_id
                    WHERE t.ticket_id = ' . (int) $ticket_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if ($row)
            {
                $old_project_id = (int) $row['project_id'];
                $current_total  = (int) $row['project_total_tickets'];

                if ($old_project_id !== $new_project_id && $new_project_id > 0)
                {
                    $this->db->sql_query('UPDATE ' . $this->tables['tickets'] . ' SET project_id = ' . (int) $new_project_id . ' WHERE ticket_id = ' . (int) $ticket_id);

                    if ($current_total > 0)
                    {
                        $this->db->sql_query('UPDATE ' . $this->tables['projects'] . ' SET project_total_tickets = project_total_tickets - 1 WHERE project_id = ' . (int) $old_project_id);
                    }
                    
                    $this->db->sql_query('UPDATE ' . $this->tables['projects'] . ' SET project_total_tickets = project_total_tickets + 1 WHERE project_id = ' . (int) $new_project_id);

                    $this->log_history($ticket_id, 'Ticket moved to project ID: ' . (int) $new_project_id);
                    
                    $redirect = $this->helper->route('nextgen_trackers_ticket', [
                        'page'   => 'viewticket', 
                        'ticket' => (int) $ticket_id
                    ]);

                    meta_refresh(3, $redirect);
                    return trigger_error($this->language->lang('TICKET_MOVED'));
                }
            }
            
            $redirect = $this->helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => (int) $ticket_id]);
            return redirect($redirect);
        }

        if ($this->request->is_set_post('cancel'))
        {
            $redirect = $this->helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => (int) $ticket_id]);
            return redirect($redirect);
        }

        $sql = 'SELECT project_id, project_name 
                FROM ' . $this->tables['projects'] . ' 
                ORDER BY project_name ASC';
        $result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {
            $this->template->assign_block_vars('projects', [
                'ID'   => (int) $row['project_id'],
                'NAME' => $row['project_name'],
            ]);
        }
        $this->db->sql_freeresult($result);

        add_form_key('moderate_move');

        $this->template->assign_vars([
            'S_CONFIRM_ACTION' => $this->helper->route('nextgen_trackers_ticket', ['page' => 'moderate', 'action' => 'move', 'ticket' => (int) $ticket_id]),
        ]);

        return $this->helper->render('moderate_move_body.html', $this->language->lang('MOVE_TICKET'));
    }

    protected function close($ticket_id)
    {
        if (!$this->auth->acl_get('m_tracker_edit'))
        {
            return $this->helper->error('NOT_AUTHORISED', 403);
        }

        // Get tracker_id
        $sql = 'SELECT p.tracker_id FROM ' . $this->tables['tickets'] . ' t 
                INNER JOIN ' . $this->tables['projects'] . ' p ON t.project_id = p.project_id 
                WHERE t.ticket_id = ' . (int) $ticket_id;
        $result = $this->db->sql_query($sql);
        $tracker_id = (int) $this->db->sql_fetchfield('tracker_id');
        $this->db->sql_freeresult($result);

        // PLAN A: Search for closed status of specific tracker
        $sql = 'SELECT status_id FROM ' . $this->tables['status'] . ' 
                WHERE tracker_id = ' . (int) $tracker_id . ' 
                AND ticket_closed = 1 
                ORDER BY status_order ASC';
        $result = $this->db->sql_query_limit($sql, 1);
        $status_id = (int) $this->db->sql_fetchfield('status_id');
        $this->db->sql_freeresult($result);

        // PLAN B: If there are no statuses for that tracker, search for any closed status in the system.
        if ($status_id <= 0)
        {
            $sql = 'SELECT status_id FROM ' . $this->tables['status'] . ' 
                    WHERE ticket_closed = 1 
                    ORDER BY status_order ASC';
            $result = $this->db->sql_query_limit($sql, 1);
            $status_id = (int) $this->db->sql_fetchfield('status_id');
            $this->db->sql_freeresult($result);
        }

        if ($status_id > 0)
        {
            $this->db->sql_query('UPDATE ' . $this->tables['tickets'] . ' SET status_id = ' . $status_id . ' WHERE ticket_id = ' . (int) $ticket_id);
            $this->log_history($ticket_id, 'Ticket was closed by moderator.');

            $redirect = $this->helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => (int) $ticket_id]);
            meta_refresh(3, $redirect);
            return trigger_error($this->language->lang('TICKET_CLOSED'));
        }

        return $this->helper->error('NO_CLOSED_STATUS_FOUND', 404);
    }

    protected function reopen($ticket_id)
    {
        if (!$this->auth->acl_get('m_tracker_edit'))
        {
            return $this->helper->error('NOT_AUTHORISED', 403);
        }

        // Get tracker_id
        $sql = 'SELECT p.tracker_id FROM ' . $this->tables['tickets'] . ' t 
                INNER JOIN ' . $this->tables['projects'] . ' p ON t.project_id = p.project_id 
                WHERE t.ticket_id = ' . (int) $ticket_id;
        $result = $this->db->sql_query($sql);
        $tracker_id = (int) $this->db->sql_fetchfield('tracker_id');
        $this->db->sql_freeresult($result);

        // PLAN A: Find the first open state for this tracker
        $sql = 'SELECT status_id FROM ' . $this->tables['status'] . ' 
                WHERE tracker_id = ' . (int) $tracker_id . ' 
                AND ticket_closed = 0 
                ORDER BY status_order ASC';
        $result = $this->db->sql_query_limit($sql, 1);
        $status_id = (int) $this->db->sql_fetchfield('status_id');
        $this->db->sql_freeresult($result);

        // PLAN B: If the tracker has no open statuses, we look for ANY open status in the system.
        if ($status_id <= 0)
        {
            $sql = 'SELECT status_id FROM ' . $this->tables['status'] . ' 
                    WHERE ticket_closed = 0 
                    ORDER BY status_order ASC';
            $result = $this->db->sql_query_limit($sql, 1);
            $status_id = (int) $this->db->sql_fetchfield('status_id');
            $this->db->sql_freeresult($result);
        }

        if ($status_id > 0)
        {
            $this->db->sql_query('UPDATE ' . $this->tables['tickets'] . ' SET status_id = ' . $status_id . ' WHERE ticket_id = ' . (int) $ticket_id);
            $this->log_history($ticket_id, 'Ticket was reopened by moderator.');

            $redirect = $this->helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => (int) $ticket_id]);
            meta_refresh(3, $redirect);
            return trigger_error($this->language->lang('TICKET_REOPENED'));
        }

        return $this->helper->error('NO_OPEN_STATUS_FOUND', 404);
    }

    protected function log_history($ticket_id, $message)
    {
        $sql_ary = [
            'ticket_id'      => (int) $ticket_id,
            'user_id'        => (int) $this->user->data['user_id'],
            'post_text'      => (string) $message,
            'post_timestamp' => (int) time(),
            'post_ip'        => $this->user->ip,
            'post_private'   => 1,
            'bbcode_uid'     => '',
            'bbcode_bitfield'=> '',
            'bbcode_flags'   => 0,
        ];
        
        $this->db->sql_query('INSERT INTO ' . $this->tables['posts'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
    }
}
