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
use nextgen\trackers\constants;

/**
 * Viewticket operator
 */
class viewticket
{

    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\auth\auth */
    protected $auth;

    /** @var ContainerInterface */
    protected $container;

    /** @var \phpbb\language\language */
    protected $language;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \phpbb\request\request */
    protected $request;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var \phpbb\user */
    protected $user;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \nextgen\trackers\functions */
    protected $functions;

    /** @var string */
    protected $table_prefix;

    public function __construct(\phpbb\config\config $config, \phpbb\auth\auth $auth, ContainerInterface $container, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
    {
        $this->config = $config;
        $this->auth = $auth;
        $this->container = $container;
        $this->language = $language;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->db = $container->get('dbal.conn');
        $this->functions = $container->get('nextgen.trackers.functions');
        $this->table_prefix = $container->getParameter('core.table_prefix');
    }

    public function display()
    {
        $is_enabled = (isset($this->config['trackers_enabled'])) ? (bool) $this->config['trackers_enabled'] : true;

        $ticket_id  = $this->request->variable('ticket', 0);
        $tracker_id = $this->request->variable('t', 0);
        $project_id = $this->request->variable('p', 0);
        $start      = $this->request->variable('start', 0);
        $mode        = $this->request->variable('mode', '');

        // LÓGICA DE AUTO-DESCUBRIMIENTO
        if ($ticket_id > 0 && ($tracker_id == 0 || $project_id == 0))
        {
            try {
                $ticket_data = $this->functions->get_ticket_data($ticket_id);
                $project_id  = (int) $ticket_data['project_id'];

                $project_data = $this->functions->get_project_data($project_id);
                $tracker_id   = (int) $project_data['tracker_id'];
            } catch (\phpbb\exception\http_exception $e) {
                throw $e;
            }
        }

        // SEGURIDAD: Permiso global de lectura
        if (!$this->auth->acl_get('u_tracker_view'))
        {
            if ($this->user->data['user_id'] == ANONYMOUS)
            {
                login_box('', $this->language->lang('LOGIN_REQUIRED'));
            }
            trigger_error('NOT_AUTHORISED');
        }

        $tracker = $this->functions->get_tracker_data($tracker_id);
        $project = $this->functions->get_project_data($project_id);
        $ticket  = $this->functions->get_ticket_data($ticket_id);

        // SEGURIDAD: Tickets Privados
        if ($ticket['ticket_private'] && !$this->auth->acl_get('u_tracker_view_private'))
        {
            if ($this->user->data['user_id'] != $ticket['user_id'] && !$this->functions->is_team_user($project_id))
            {
                trigger_error('NOT_AUTHORISED');
            }
        }

        $is_team_user = $this->functions->is_team_user($project_id);
        $is_moderator = ($this->auth->acl_get('a_trackers') || $this->auth->acl_getf_global('m_') || $this->auth->acl_get('m_tracker_edit'));
        
        // Sincronización con permisos de m8
        $can_change_status = ($is_moderator || $this->auth->acl_get('m_tracker_status') || $is_team_user);
        $can_assign        = ($is_moderator || $this->auth->acl_get('m_tracker_assign') || $is_team_user);
        $can_view_logs     = ($is_moderator || $this->auth->acl_get('m_tracker_logs') || $is_team_user);
        $can_close         = ($is_moderator || $is_team_user || ($this->auth->acl_get('u_tracker_close') && $this->user->data['user_id'] == $ticket['user_id']));

        // Modo Cierre de Ticket
        if ($mode == 'close' && $can_close && !$ticket['ticket_closed'])
        {
            $sql = 'SELECT status_id FROM ' . $this->table_prefix . 'trackers_status WHERE ticket_closed = 1 ORDER BY status_order ASC';
            $result = $this->db->sql_query_limit($sql, 1);
            $closed_status_id = (int) $this->db->sql_fetchfield('status_id');
            $this->db->sql_freeresult($result);

            if ($closed_status_id)
            {
                $this->functions->set_status($ticket_id, $closed_status_id); 
                $redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 'ticket' => $ticket_id]);
                meta_refresh(3, $redirect);
                trigger_error($this->language->lang('TICKET_CLOSED_SUCCESS'));
            }
        }

        // 1. Cambiar Estado
        if ($can_change_status && $this->request->is_set_post('change_status'))
        {
            $new_status = $this->request->variable('change_status', 0);
            if ($new_status > 0)
            {
                $this->functions->set_status($ticket_id, $new_status);
                $ticket = $this->functions->get_ticket_data($ticket_id);
            }
        }

        // 2. Cambiar Severidad
        if ($can_change_status && $this->request->is_set_post('change_severity'))
        {
            $new_sev = $this->request->variable('change_severity', 0);
            if ($new_sev > 0)
            {
                $this->functions->set_severity($ticket_id, $new_sev);
                $ticket = $this->functions->get_ticket_data($ticket_id);
            }
        }

        // 3. Asignar Usuario
        if ($can_assign && $this->request->is_set_post('assign_user'))
        {
            $username_input = $this->request->variable('username', '', true);
            if (!empty($username_input)) {
                $clean_name = utf8_clean_string($username_input);
                $sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username_clean = '" . $this->db->sql_escape($clean_name) . "'";
                $result = $this->db->sql_query($sql);
                $found_user_id = (int) $this->db->sql_fetchfield('user_id');
                $this->db->sql_freeresult($result);

                if ($found_user_id > 0) {
                    $this->functions->set_assignee($ticket_id, $found_user_id);
                    $ticket['assigned_user'] = $found_user_id; 
                }
            }
        }

        $this->functions->get_ticket_details($tracker_id, $project, $ticket);
        $total_posts = $this->functions->get_total_posts($ticket_id);
        $pagination  = $this->container->get('pagination');
        $start       = $pagination->validate_start($start, $this->config['posts_per_page'], $total_posts);

        $base_url = $this->helper->route('nextgen_trackers_controller', [
            'page'   => 'viewticket',
            't'      => (int) $tracker_id,
            'p'      => (int) $project_id,
            'ticket' => (int) $ticket_id
        ]);

        $pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_posts, $this->config['posts_per_page'], $start);
        
        $this->prepare_duplicates($tracker_id, $project_id, $ticket_id, $ticket);
        $this->prepare_selects($project_id, $ticket);

        // SEGURIDAD: Identidad vs Moderación para Botones Principales
        $is_author = ($this->user->data['user_id'] != ANONYMOUS && $this->user->data['user_id'] == $ticket['user_id']);
        
        $s_edit = $this->user->data['is_registered'] && (
            ($this->auth->acl_get('u_tracker_edit') && $is_author) || 
            $this->auth->acl_get('m_tracker_edit') || $is_team_user || $is_moderator
        );

        $s_delete = $this->user->data['is_registered'] && (
            ($this->auth->acl_get('u_tracker_delete') && $is_author) || 
            $this->auth->acl_get('m_tracker_delete') || $is_team_user || $is_moderator
        );

        $s_quote = ($this->user->data['is_registered'] && $this->auth->acl_get('u_tracker_reply')) || $is_moderator || $is_team_user;

        $assigned_user_link = $this->language->lang('UNASSIGNED');
        if ($ticket['assigned_user'])
        {
            $sql = 'SELECT username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $ticket['assigned_user'];
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);
            if ($row) {
                $assigned_user_link = get_username_string('full', $ticket['assigned_user'], $row['username'], $row['user_colour']);
            }
        }

        $attachments = [];
        $sql = 'SELECT * FROM ' . $this->table_prefix . 'trackers_attachments 
                WHERE ticket_id = ' . (int) $ticket_id;
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $attachments[$row['post_id']][] = $row;
        }
        $this->db->sql_freeresult($result);

        if (!empty($attachments[$ticket['post_id']]))
        {
            foreach ($attachments[$ticket['post_id']] as $attach)
            {
                $download_url = $this->helper->route('nextgen_trackers_download', ['attach_id' => $attach['attach_id']]);
                $ext = strtolower(pathinfo($attach['real_filename'], PATHINFO_EXTENSION));
                $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                $display_html = '';
                if ($is_image) {
                    $display_html = '<div class="inline-attachment"><dl class="file"><dt class="attach-image"><a href="' . $download_url . '" target="_blank"><img src="' . $download_url . '" class="postimage" alt="' . $attach['real_filename'] . '" /></a></dt><dd>' . $attach['real_filename'] . ' (' . ($attach['filesize'] / 1024 > 1024 ? sprintf("%.2f MB", $attach['filesize'] / 1048576) : sprintf("%.2f KB", $attach['filesize'] / 1024)) . ')</dd></dl></div>';
                } else {
                    $display_html = '<dl class="file"><dt><i class="icon fa-paperclip fa-fw" aria-hidden="true"></i> <a class="postlink" href="' . $download_url . '">' . $attach['real_filename'] . '</a></dt><dd>(' . ($attach['filesize'] / 1024 > 1024 ? sprintf("%.2f MB", $attach['filesize'] / 1048576) : sprintf("%.2f KB", $attach['filesize'] / 1024)) . ')</dd></dl>';
                }

                $this->template->assign_block_vars('ticket_attachments', [
                    'DISPLAY_ATTACHMENT' => $display_html,
                ]);
            }
        }

        $this->template->assign_vars([
            'ASSIGNED_USER_FULL' => $assigned_user_link,
            'ASSIGNED_USER_ID'   => (int) $ticket['assigned_user'],
            'S_CAN_MANAGE'       => ($is_moderator || $is_team_user),
            'S_CAN_CHANGE_STATUS'=> $can_change_status,
            'S_CAN_ASSIGN'       => $can_assign,
            'S_CAN_VIEW_LOGS'    => $can_view_logs,
            'S_CAN_CLOSE'        => $can_close,
            'U_FIND_USER'        => append_sid("{$this->container->getParameter('core.root_path')}memberlist.{$this->container->getParameter('core.php_ext')}", [
                'mode'          => 'searchuser',
                'form'          => 'viewticket_form',
                'field'         => 'username', 
                'select_single' => true,
            ]),
            'TRACKER_NAME'     => $tracker['tracker_name'],
            'TICKET_TITLE'     => $ticket['ticket_title'],
            'TICKET_TEXT'      => $this->functions->get_ticket_text($ticket_id),
            'STATUS_NAME'      => $ticket['status_name'],
            'STATUS_ID'        => $ticket['status_id'],
            'SEVERITY_ID'      => $ticket['severity_id'],
            'S_CLOSED'         => $ticket['ticket_closed'],
            'S_TICKET_PRIVATE' => $ticket['ticket_private'],
            'U_ACTION'         => $base_url,
            'U_EDIT'           => $s_edit ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'edit', 'post' => (int) $ticket['post_id']]) : '',
            'U_DELETE'         => $s_delete ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'delete', 'post' => (int) $ticket['post_id']]) : '',
            'U_QUOTE'          => $s_quote ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'reply', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id]) : '',
            'U_POST_REPLY_TOPIC' => $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'reply', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id]),
            'TOTAL_POSTS'      => $this->language->lang('PAGE_TOTAL_POSTS', $total_posts),
            'S_HIDDEN_FIELDS'  => build_hidden_fields(['t' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket' => (int) $ticket_id]),
            'TICKET_HAS_ATTACHMENTS' => (!empty($attachments[$ticket['post_id']])),
            'POST_ID'          => (int) $ticket['post_id'],
        ]);

        $this->functions->get_posts_history($ticket, $start, $attachments);
        $this->generate_breadcrumbs($tracker, $project, $ticket);

        $this->template->assign_vars([
            'S_TRACKER_ENABLED'  => $is_enabled,
        ]);
        
        return $this->helper->render('viewticket_body.html', $tracker['tracker_name'] . ' - ' . $ticket['ticket_title']);
    }

    protected function prepare_selects($project_id, $ticket)
    {
        $sql = 'SELECT s.status_id, s.status_name 
                FROM ' . $this->table_prefix . 'trackers_status s
                INNER JOIN ' . $this->table_prefix . 'trackers_relations r ON s.status_id = r.item_id
                WHERE r.project_id = ' . (int) $project_id . " 
                    AND r.item_type = 'status' 
                ORDER BY s.status_order ASC";
        
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $this->template->assign_block_vars('statuses', [
                'ID'   => $row['status_id'],
                'NAME' => $row['status_name'],
            ]);
        }
        $this->db->sql_freeresult($result);

        $sql = 'SELECT s.severity_id, s.severity_name, s.severity_colour 
                FROM ' . $this->table_prefix . 'trackers_severity s
                INNER JOIN ' . $this->table_prefix . 'trackers_relations r ON s.severity_id = r.item_id
                WHERE r.project_id = ' . (int) $project_id . " 
                    AND r.item_type = 'severity' 
                ORDER BY s.severity_order ASC";

        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $this->template->assign_block_vars('severities', [
                'ID'     => $row['severity_id'],
                'NAME'   => $row['severity_name'],
                'COLOUR' => $row['severity_colour'],
            ]);
        }
        $this->db->sql_freeresult($result);
    }

    protected function prepare_duplicates($tracker_id, $project_id, $ticket_id, $ticket)
    {
        foreach ($this->functions->get_duplicate_tickets($ticket_id) as $_id => $row) {
            $this->template->assign_block_vars('duplicates', [
                'ID'    => $_id,
                'TITLE' => $row['ticket_title'],
                'U_TICKET' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $_id]),
            ]);
        }
        if ($ticket['ticket_duplicate'] && $ticket['duplicate_id'] > 0) {
            foreach ($this->functions->get_duplicate_tickets($ticket['duplicate_id']) as $_id => $row) {
                $this->template->assign_block_vars('duplicates_other', [
                    'ID'    => $_id,
                    'TITLE' => $row['ticket_title'],
                    'U_TICKET' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $_id]),
                ]);
            }
            $this->template->assign_var('DUPLICATE_ID', $ticket['duplicate_id']);
        }
    }

    protected function generate_breadcrumbs($tracker, $project, $ticket)
    {
        $navlinks = [
            ['FORUM_NAME' => $tracker['tracker_name'], 'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => $tracker['tracker_id']])],
            ['FORUM_NAME' => $project['project_name'], 'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => $tracker['tracker_id'], 'p' => $project['project_id']])],
            ['FORUM_NAME' => $ticket['ticket_title'], 'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => $tracker['tracker_id'], 'p' => $project['project_id'], 'ticket' => $ticket['ticket_id']])],
        ];
        $this->functions->generate_navlinks($navlinks);
    }
}