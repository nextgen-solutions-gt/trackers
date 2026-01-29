<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\operators;

use Symfony\Component\DependencyInjection\ContainerInterface;

class posting
{
    protected $auth;
    protected $container;
    protected $language;
    protected $helper;
    protected $request;
    protected $template;
    protected $user;
    protected $db;
    protected $tables;

    public function __construct(\phpbb\auth\auth $auth, ContainerInterface $container, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $table_prefix)
    {
        $this->auth = $auth;
        $this->container = $container;
        $this->language = $language;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->db = $container->get('dbal.conn');

        $this->tables = [
            'trackers_tracker'  => $table_prefix . 'trackers_tracker',
            'trackers_project'  => $table_prefix . 'trackers_project',
            'trackers_ticket'   => $table_prefix . 'trackers_ticket',
            'trackers_post'     => $table_prefix . 'trackers_post',
        ];
    }

    public function display($tracker_id = 0, $project_id = 0, $ticket_id = 0)
    {
        $mode = $this->request->variable('mode', 'post');
        
        $post_id = $this->request->variable('tracker_edit_id', 0);
        if (!$post_id) $post_id = $this->request->variable('post', 0);

        $tracker_id = ($tracker_id) ? $tracker_id : $this->request->variable('t', 0);
        $project_id = ($project_id) ? $project_id : $this->request->variable('p', 0);
        $ticket_id  = ($ticket_id)  ? $ticket_id  : $this->request->variable('ticket', 0);

        if ($mode == 'delete')
        {
            if ($post_id && (!$ticket_id || !$project_id))
            {
                $sql = 'SELECT p.ticket_id, t.project_id, pr.tracker_id 
                        FROM ' . $this->tables['trackers_post'] . ' p
                        JOIN ' . $this->tables['trackers_ticket'] . ' t ON p.ticket_id = t.ticket_id
                        JOIN ' . $this->tables['trackers_project'] . ' pr ON t.project_id = pr.project_id
                        WHERE p.post_id = ' . (int) $post_id;
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if ($row)
                {
                    $ticket_id = (int) $row['ticket_id'];
                    $project_id = (int) $row['project_id'];
                    $tracker_id = (int) $row['tracker_id'];
                }
            }

            if (!$this->auth->acl_get('a_') && !$this->auth->acl_get('m_') && !$this->container->get('nextgen.trackers.functions')->is_team_user($project_id))
            {
                trigger_error('NOT_AUTHORISED');
            }

            if (confirm_box(true))
            {
                $sql = 'SELECT post_id FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . (int) $ticket_id;
                $result = $this->db->sql_query($sql);
                $main_post_id = (int) $this->db->sql_fetchfield('post_id');
                $this->db->sql_freeresult($result);

                if ($post_id == $main_post_id || (!$post_id && $ticket_id))
                {
                    $this->db->sql_query('DELETE FROM ' . $this->tables['trackers_post'] . ' WHERE ticket_id = ' . (int) $ticket_id);
                    $this->db->sql_query('DELETE FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . (int) $ticket_id);
                    
                    $redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]);
                    meta_refresh(3, $redirect);
                    trigger_error($this->language->lang('TICKET_DELETED_SUCCESS'));
                }
                else
                {
                    $this->db->sql_query('DELETE FROM ' . $this->tables['trackers_post'] . ' WHERE post_id = ' . (int) $post_id);
                    
                    $redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket' => (int) $ticket_id]);
                    meta_refresh(3, $redirect);
                    trigger_error($this->language->lang('POST_DELETED_SUCCESS'));
                }
            }
            else
            {
                $s_hidden_fields = build_hidden_fields([
                    't'      => (int) $tracker_id,
                    'p'      => (int) $project_id,
                    'ticket' => (int) $ticket_id,
                    'post'   => (int) $post_id,
                    'mode'   => 'delete',
                ]);

                confirm_box(false, $this->language->lang('CONFIRM_DELETE'), $s_hidden_fields);
            }
        }

        if ($this->request->is_set_post('post'))
        {
            if (!check_form_key('nextgen_trackers_posting')) trigger_error('FORM_INVALID');

            $message = $this->request->variable('message', '', true);
            $subject = $this->request->variable('subject', '', true);
            $private = $this->request->variable('private', 0);
            $severity = $this->request->variable('severity', 0);

            $uid = $bitfield = $flags = '';
            generate_text_for_storage($message, $uid, $bitfield, $flags, true, true, true);

            $functions = $this->container->get('nextgen.trackers.functions');

            if ($mode == 'post' && !$ticket_id)
            {
                $sql_ary = [
                    'project_id'        => (int) $project_id,
                    'user_id'           => (int) $this->user->data['user_id'],
                    'reporter_ip'       => $this->user->ip,
                    'status_id'         => 1,
                    'severity_id'       => (int) $severity,
                    'ticket_private'    => (int) $private,
                    'ticket_title'      => (string) $subject,
                    'timestamp_created' => (int) time(),
                ];
                $this->db->sql_query('INSERT INTO ' . $this->tables['trackers_ticket'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
                $ticket_id = $this->db->sql_nextid();

                $sql_ary_post = [
                    'ticket_id'       => (int) $ticket_id,
                    'user_id'         => (int) $this->user->data['user_id'],
                    'post_private'    => (int) $private,
                    'ticket_title'    => (string) $subject,
                    'post_text'       => (string) $message,
                    'post_timestamp'  => (int) time(),
                    'bbcode_uid'      => (string) $uid,
                    'bbcode_bitfield' => (string) $bitfield,
                    'bbcode_flags'    => (int) $flags,
                    'post_ip'         => $this->user->ip,
                ];
                $this->db->sql_query('INSERT INTO ' . $this->tables['trackers_post'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary_post));
                $new_post_id = $this->db->sql_nextid();

                $this->db->sql_query('UPDATE ' . $this->tables['trackers_ticket'] . ' SET post_id = ' . (int) $new_post_id . ' WHERE ticket_id = ' . (int) $ticket_id);
            }
            elseif ($mode == 'reply' || ($mode == 'post' && $ticket_id))
            {
                $sql_ary = [
                    'ticket_id'       => (int) $ticket_id,
                    'user_id'         => (int) $this->user->data['user_id'],
                    'post_private'    => (int) $private,
                    'post_text'       => (string) $message,
                    'post_timestamp'  => (int) time(),
                    'bbcode_uid'      => (string) $uid,
                    'bbcode_bitfield' => (string) $bitfield,
                    'bbcode_flags'    => (int) $flags,
                    'post_ip'         => $this->user->ip,
                ];
                $this->db->sql_query('INSERT INTO ' . $this->tables['trackers_post'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
                $this->db->sql_query('UPDATE ' . $this->tables['trackers_ticket'] . ' SET user_last_id = ' . (int) $this->user->data['user_id'] . ' WHERE ticket_id = ' . (int) $ticket_id);

                // OBTENER DATOS DEL TICKET PARA LÓGICA DE ESTADO
                $ticket_data = $functions->get_ticket_data($ticket_id);

                // CAMBIO DE ESTADO AUTOMÁTICO
                // Si el que responde es el usuario asignado (Staff)
                if ((int) $this->user->data['user_id'] === (int) $ticket_data['assigned_user'])
                {
                    // Cambiamos a "Esperando respuesta del usuario" (ID 3 por ejemplo)
                    $functions->set_status($ticket_id, 3);
                }
                // Si el que responde es el autor del ticket
                else if ((int) $this->user->data['user_id'] === (int) $ticket_data['user_id'])
                {
                    // Cambiamos a "Respondido / Pendiente Staff" (ID 2 por ejemplo)
                    $functions->set_status($ticket_id, 2);
                }

                // DISPARAR NOTIFICACIÓN DE RESPUESTA
                $functions->notify_reply($ticket_data, $sql_ary);
            }
            elseif ($mode == 'edit' && $post_id)
            {
                $sql_ary = ['post_text' => (string) $message, 'post_private' => (int) $private, 'bbcode_uid' => (string) $uid, 'bbcode_bitfield' => (string) $bitfield, 'bbcode_flags' => (int) $flags];
                $this->db->sql_query('UPDATE ' . $this->tables['trackers_post'] . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE post_id = ' . (int) $post_id);

                $sql = 'SELECT ticket_id FROM ' . $this->tables['trackers_post'] . ' WHERE post_id = ' . (int) $post_id;
                $result = $this->db->sql_query($sql);
                $ticket_id = (int) $this->db->sql_fetchfield('ticket_id');
                $this->db->sql_freeresult($result);

                if ($ticket_id)
                {
                    $sql = 'SELECT post_id FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . (int) $ticket_id;
                    $result = $this->db->sql_query($sql);
                    $main_post_id_edit = (int) $this->db->sql_fetchfield('post_id');
                    $this->db->sql_freeresult($result);

                    if ($main_post_id_edit == $post_id)
                    {
                        $sql_ary_ticket = ['ticket_title' => (string) $subject, 'severity_id' => (int) $severity, 'ticket_private' => (int) $private];
                        $this->db->sql_query('UPDATE ' . $this->tables['trackers_ticket'] . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary_ticket) . ' WHERE ticket_id = (int) $ticket_id');
                    }
                }
            }

            $this->recover_ids($ticket_id, $tracker_id, $project_id);
            $redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket' => (int) $ticket_id]);
            meta_refresh(3, $redirect);
            trigger_error($this->language->lang('POST_STORED_SUCCESS'));
        }

        $current_message = $current_subject = '';
        $current_severity = $current_private = 0;
        $is_first_post = false;

        if ($mode == 'edit' && $post_id)
        {
            $sql = 'SELECT p.*, t.ticket_title as t_title, t.severity_id, t.ticket_private as t_private, t.post_id as main_id, t.project_id, pr.tracker_id
                    FROM ' . $this->tables['trackers_post'] . ' p
                    INNER JOIN ' . $this->tables['trackers_ticket'] . ' t ON p.ticket_id = t.ticket_id
                    INNER JOIN ' . $this->tables['trackers_project'] . ' pr ON t.project_id = pr.project_id
                    WHERE p.post_id = ' . (int) $post_id;
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if ($row)
            {
                $current_message = $row['post_text'];
                decode_message($current_message, $row['bbcode_uid']);
                $ticket_id = (int) $row['ticket_id'];
                $project_id = (int) $row['project_id'];
                $tracker_id = (int) $row['tracker_id'];
                $current_private = (int) $row['post_private'];
                if ((int) $row['main_id'] === (int) $post_id)
                {
                    $is_first_post = true;
                    $current_subject = $row['t_title'];
                    $current_severity = (int) $row['severity_id'];
                }
            }
        }

        $functions = $this->container->get('nextgen.trackers.functions');
        foreach ($functions->get_severities($tracker_id) as $sev)
        {
            $this->template->assign_block_vars('severities', ['ID' => $sev['severity_id'], 'NAME' => $sev['severity_name']]);
        }

        add_form_key('nextgen_trackers_posting');

        $this->template->assign_vars([
            'TRACKER_ID'      => (int) $tracker_id,
            'PROJECT_ID'      => (int) $project_id,
            'TICKET_ID'       => (int) $ticket_id,
            'POST_ID'         => (int) $post_id,
            'MESSAGE'         => $current_message,
            'SUBJECT'         => $current_subject,
            'SEVERITY_ID'     => $current_severity,
            'S_PRIVATE'       => $current_private,
            'S_EDIT_POST'     => ($mode == 'edit'),
            'S_NEW_TICKET'    => ($mode == 'post' && !$ticket_id),
            'S_REPLY_TICKET'  => ($mode == 'reply' || ($mode == 'post' && $ticket_id)),
            'S_FIRST_POST'    => $is_first_post,
            'L_POSTING_TITLE' => ($mode == 'edit') ? (($is_first_post) ? $this->language->lang('EDIT_TICKET') : $this->language->lang('EDIT_COMMENT')) : (($ticket_id) ? $this->language->lang('REPLY_TICKET') : $this->language->lang('NEW_TICKET')),
            'U_ACTION'        => $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => $mode, 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id, 'post' => $post_id]),
        ]);

        return $this->helper->render('posting_body.html', $this->language->lang('POSTING'));
    }

    private function recover_ids($ticket_id, &$tracker_id, &$project_id)
    {
        if (!$ticket_id) return;
        $sql = 'SELECT t.project_id, p.tracker_id FROM ' . $this->tables['trackers_ticket'] . ' t 
                JOIN ' . $this->tables['trackers_project'] . ' p ON t.project_id = p.project_id WHERE t.ticket_id = ' . (int) $ticket_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);
        if ($row)
        {
            $project_id = (int) $row['project_id'];
            $tracker_id = (int) $row['tracker_id'];
        }
    }
}