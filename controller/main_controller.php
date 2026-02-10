<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class main_controller
{
    protected $auth;
    protected $config;
    protected $content_visibility;
    protected $helper;
    protected $db;
    protected $language;
    protected $request;
    protected $template;
    protected $user;
    protected $container;
    protected $root_path;
    protected $php_ext;
    protected $table_prefix;

    /**
     * Constructor
     */
    public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\content_visibility $content_visibility, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, ContainerInterface $container, $root_path, $php_ext, $table_prefix) 
    {
        $this->auth = $auth;
        $this->config = $config;
        $this->content_visibility = $content_visibility;
        $this->helper = $helper;
        $this->db = $db;
        $this->language = $language;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->container = $container;
        $this->root_path = $root_path;
        $this->php_ext = $php_ext;
        $this->table_prefix = $table_prefix;
    }

    /**
     * Controller handler
     * @param string $page
     * @param int $ticket Captura el ID desde la ruta de Symfony {ticket}
     */
    public function display($page = 'main', $ticket = 0)
    {
        // RC4.83: If no page is specified or it is ‘main’, we load the global list.
        if ($page === 'main' || empty($page))
        {
            return $this->display_main_page();
        }

        $operator_service = 'nextgen.trackers.operators.' . $page;

        if (!$this->container->has($operator_service))
        {
            throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_PAGE_MODE'));
        }

        // --- CRITICAL RESOLUTION OF IDs ---
        $ticket_id = ($ticket > 0) ? (int) $ticket : $this->request->variable('ticket', 0);
        
        // We inject the ID into the request so that operators see it as a ‘ticket’.
        $this->request->overwrite('ticket', $ticket_id);

        $tracker_id = $this->request->variable('t', 0);
        $project_id = $this->request->variable('p', 0);

        // Table variables
        $t_tracker = $this->table_prefix . 'tracker';
        $t_project = $this->table_prefix . 'project';
        $t_ticket  = $this->table_prefix . 'ticket';
        $t_watch   = $this->table_prefix . 'watch';

        // RC4 FIX: Recovery of IDs and relationships
        if ($ticket_id > 0 && ($tracker_id <= 0 || $project_id <= 0))
        {
            $sql = 'SELECT t.project_id, p.tracker_id 
                    FROM ' . $t_ticket . ' t
                    INNER JOIN ' . $t_project . ' p ON t.project_id = p.project_id
                    WHERE t.ticket_id = ' . (int) $ticket_id;
            
            $result = $this->db->sql_query($sql);
            $row = $this->db->sql_fetchrow($result);
            $this->db->sql_freeresult($result);

            if ($row)
            {
                $tracker_id = (int) $row['tracker_id'];
                $project_id = (int) $row['project_id'];
                
                $this->request->overwrite('t', $tracker_id);
                $this->request->overwrite('p', $project_id);
            }
        }
        // If we have a project but no tracker (e.g., direct viewproject)
        elseif ($project_id > 0 && $tracker_id <= 0)
        {
            $sql = 'SELECT tracker_id FROM ' . $t_project . ' WHERE project_id = ' . (int) $project_id;
            $result = $this->db->sql_query($sql);
            $tracker_id = (int) $this->db->sql_fetchfield('tracker_id');
            $this->db->sql_freeresult($result);
            
            $this->request->overwrite('t', $tracker_id);
        }

        // --- BREADCRUMBS SYSTEM (Level 1 Only) ---
        // This places “Trackers” as the root. Operators will add the children.
        $this->language->add_lang('common', 'nextgen/trackers');

        $this->template->assign_block_vars('navlinks', [
            'BREADCRUMB_NAME' => $this->language->lang('TRACKERS'),
            'U_BREADCRUMB'    => $this->helper->route('nextgen_trackers_page', ['page' => 'main']),
        ]);
        // ---------------------------------------------------

        $s_is_assigned = false;
        if ($ticket_id > 0)
        {
            $sql = 'SELECT assigned_user FROM ' . $t_ticket . ' 
                    WHERE ticket_id = ' . (int) $ticket_id;
            $result = $this->db->sql_query($sql);
            $assigned_id = (int) $this->db->sql_fetchfield('assigned_user');
            $this->db->sql_freeresult($result);
            $s_is_assigned = ($assigned_id > 0);
        }

        $can_move = $this->auth->acl_get('m_tracker_move');
        $can_unassign = $this->auth->acl_get('m_tracker_unassign');
        $can_close = $this->auth->acl_get('m_tracker_close');

        // --- SUBSCRIPTION SYSTEM ---
        $watch = $this->request->variable('watch', '');
        $user_id = (int) $this->user->data['user_id'];
        $s_watching_ticket = false;

        if ($ticket_id > 0 && $user_id != ANONYMOUS)
        {
            if ($watch == 'subscribe')
            {
                $sql = 'SELECT user_id FROM ' . $t_watch . '
                        WHERE ticket_id = ' . (int) $ticket_id . '
                        AND user_id = ' . (int) $user_id;
                $result = $this->db->sql_query($sql);
                $row = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if (!$row)
                {
                    $sql_ary = [
                        'ticket_id'     => (int) $ticket_id,
                        'user_id'       => (int) $user_id,
                        'notify_status' => 0,
                    ];
                    $this->db->sql_query('INSERT INTO ' . $t_watch . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
                }
            }
            elseif ($watch == 'unsubscribe')
            {
                $sql = 'DELETE FROM ' . $t_watch . '
                        WHERE ticket_id = ' . (int) $ticket_id . '
                        AND user_id = ' . (int) $user_id;
                $this->db->sql_query($sql);
            }

            if ($page == 'viewticket')
            {
                $sql = 'UPDATE ' . $t_watch . '
                        SET notify_status = 0
                        WHERE ticket_id = ' . (int) $ticket_id . '
                        AND user_id = ' . (int) $user_id . '
                        AND notify_status = 1';
                $this->db->sql_query($sql);
            }

            $sql = 'SELECT notify_status FROM ' . $t_watch . '
                    WHERE ticket_id = ' . (int) $ticket_id . '
                    AND user_id = ' . (int) $user_id;
            $result = $this->db->sql_query($sql);
            $s_watching_ticket = ($this->db->sql_fetchrow($result)) ? true : false;
            $this->db->sql_freeresult($result);
        }

        $quote_id = $this->request->variable('quote', 0);
        $s_has_quote = ($quote_id > 0);

        // --- ASSIGNING VARIABLES TO THE TEMPLATE ---
        $this->template->assign_vars([
            'S_CAN_MODERATE'    => ($can_move || $can_unassign || $can_close),
            'S_CAN_MOVE'        => $can_move,
            'S_CAN_UNASSIGN'    => $can_unassign,
            'S_CAN_CLOSE'       => $can_close,
            'S_IS_ASSIGNED'     => $s_is_assigned,
            'U_MOVE'            => ($ticket_id) ? $this->helper->route('nextgen_trackers_ticket', ['page' => 'moderate', 'ticket' => (int) $ticket_id, 'action' => 'move']) : '',
            'U_UNASSIGN'        => ($ticket_id) ? $this->helper->route('nextgen_trackers_ticket', ['page' => 'moderate', 'ticket' => (int) $ticket_id, 'action' => 'unassign']) : '',
            'U_CLOSE'           => ($ticket_id) ? $this->helper->route('nextgen_trackers_ticket', ['page' => 'moderate', 'ticket' => (int) $ticket_id, 'action' => 'close']) : '',
            'U_REOPEN'          => ($ticket_id) ? $this->helper->route('nextgen_trackers_ticket', ['page' => 'moderate', 'ticket' => (int) $ticket_id, 'action' => 'reopen']) : '',
            'S_WATCHING_TICKET' => $s_watching_ticket,
            'U_WATCH'           => ($ticket_id) ? $this->helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => (int) $ticket_id, 'watch' => ($s_watching_ticket ? 'unsubscribe' : 'subscribe')]) : '',
            'S_HAS_QUOTE'       => $s_has_quote,
            'L_TICKET_TOOLS'    => $this->language->lang('TICKET_TOOLS'),
            'U_TRACKERS_MAIN'   => $this->helper->route('nextgen_trackers_page', ['page' => 'main']),
        ]);

        $operator = $this->container->get($operator_service);
        return $operator->display();
    }
    
    /**
     * Renders the global list of available trackers
     */
    protected function display_main_page()
    {
		$is_enabled = (isset($this->config['trackers_enabled'])) ? (bool) $this->config['trackers_enabled'] : true;
		
        $this->language->add_lang('common', 'nextgen/trackers');

        // BREADCRUMBS: Direct injection into the Controller
        $this->template->assign_block_vars('navlinks', [
            'BREADCRUMB_NAME' => $this->language->lang('TRACKERS'),
            'U_BREADCRUMB'    => $this->helper->route('nextgen_trackers_page', ['page' => 'main']),
        ]);

        $sql = 'SELECT t.tracker_id, t.tracker_name, t.tracker_icon, t.tracker_color,
                        COUNT(DISTINCT p.project_id) as total_projects, 
                        COUNT(DISTINCT tk.ticket_id) as total_tickets
                FROM ' . $this->table_prefix . 'tracker t
                LEFT JOIN ' . $this->table_prefix . 'project p ON t.tracker_id = p.tracker_id
                LEFT JOIN ' . $this->table_prefix . 'ticket tk ON p.project_id = tk.project_id
                GROUP BY t.tracker_id, t.tracker_name, t.tracker_icon, t.tracker_color
                ORDER BY t.tracker_id ASC';
        
        $result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {
            $this->template->assign_block_vars('trackers_list', [
                'TRACKER_ID'     => (int) $row['tracker_id'],
                'TRACKER_NAME'   => (string) $row['tracker_name'],
                'TRACKER_DESC'   => (string) $row['tracker_name'], 
                'TRACKER_ICON'   => (string) ($row['tracker_icon'] ?: 'fa-file-text-o'),
                'TRACKER_COLOR'  => (string) ($row['tracker_color'] ?: '#536482'),
                'TOTAL_PROJECTS' => (int) $row['total_projects'],
                'TOTAL_TICKETS'  => (int) $row['total_tickets'],
                'U_VIEW_TRACKER' => $this->helper->route('nextgen_trackers_page', [
                    'page' => 'viewtracker', 
                    't'    => (int) $row['tracker_id']
                ]),
            ]);
        }
        $this->db->sql_freeresult($result);
		
        $this->template->assign_vars([
            'S_TRACKER_ENABLED'  => $is_enabled,
        ]);
		
        return $this->helper->render('trackers_main.html', $this->language->lang('TRACKERS'));

    }

    public function download($attach_id)
    {
        $attach_id = (int) $attach_id;
        $t_attach  = $this->table_prefix . 'attachments';
        $t_ticket  = $this->table_prefix . 'ticket';

        $sql = 'SELECT * FROM ' . $t_attach . ' WHERE attach_id = ' . (int) $attach_id;
        $result = $this->db->sql_query($sql);
        $attachment = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$attachment)
        {
            throw new \phpbb\exception\http_exception(404, $this->language->lang('ATTACHMENT_NOT_FOUND'));
        }

        $functions = $this->container->get('nextgen.trackers.includes.functions');
        $sql = 'SELECT project_id FROM ' . $t_ticket . ' WHERE ticket_id = ' . (int) $attachment['ticket_id'];
        $result = $this->db->sql_query($sql);
        $project_id = (int) $this->db->sql_fetchfield('project_id');
        $this->db->sql_freeresult($result);

        $ticket_data = $functions->get_ticket_data($attachment['ticket_id']);
        if ($ticket_data['ticket_private'] && !$functions->is_team_user($project_id) && $this->user->data['user_id'] != $ticket_data['user_id'])
        {
            throw new \phpbb\exception\http_exception(403, $this->language->lang('NOT_AUTHORISED'));
        }

        $upload_path = rtrim($this->config['trackers_attach_path'], '/') . '/';
        $file_path = $this->root_path . $upload_path . $attachment['physical_filename'];

        if (!file_exists($file_path))
        {
            throw new \phpbb\exception\http_exception(404, $this->language->lang('FILE_NOT_FOUND_ON_DISK'));
        }

        $response = new BinaryFileResponse($file_path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $attachment['real_filename']);
        return $response;
    }
}
