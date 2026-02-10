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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Statistics operator
 */
class statistics
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var ContainerInterface */
    protected $container;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

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

    /** @var \phpbb\auth\auth */
    protected $auth;

    /** @var array */
    protected $tables;

    /**
     * Constructor
     */
    public function __construct(\phpbb\config\config $config, ContainerInterface $container, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\auth\auth $auth, $table_prefix)
    {
        $this->config = $config;
        $this->container = $container;
        $this->db = $db;
        $this->language = $language;
        $this->helper = $helper;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->auth = $auth;

        $this->tables = [
            'trackers_tracker'      => $table_prefix . 'tracker',
            'trackers_project'      => $table_prefix . 'project',
            'trackers_status'       => $table_prefix . 'status',
            'trackers_ticket'       => $table_prefix . 'ticket',
        ];
    }

    public function display()
    {
        // 1. Immediate verification of overall status
        $is_enabled = (isset($this->config['trackers_enabled'])) ? (bool) $this->config['trackers_enabled'] : true;
        
        $tracker_id = $this->request->variable('t', 0);
        $project_id = $this->request->variable('p', 0);

        $functions = $this->container->get('nextgen.trackers.includes.functions');
        $tracker = $functions->get_tracker_data($tracker_id);

        // SECURITY: Verify global permission to view tracker (m8)
        if (!$this->auth->acl_get('u_tracker_view'))
        {
            if ($this->user->data['user_id'] == ANONYMOUS)
            {
                login_box('', $this->language->lang('LOGIN_REQUIRED'));
            }
            trigger_error('NOT_AUTHORISED');
        }

        if (!$tracker['allow_view_all'] && !$functions->is_team_user())
        {
            throw new \phpbb\exception\http_exception(403, $this->language->lang('NOT_AUTHORISED'));
        }

        $timespan_start = $this->request->variable('start', 0);
        $timespan_end = $this->request->variable('end', 0);

        if ($timespan_start == 0)
        {
            $timespan_start = (int) mktime(0, 0, 0, date('n'), 1);
        }

        if ($timespan_start == -1)
        {
            $timespan_start = 0;
        }

        if ($timespan_end == 0)
        {
            $timespan_end = (int) mktime(0, 0, 0, date('n', $timespan_start) + 1, 1, date('Y', $timespan_start));
        }
        else if ($timespan_end == -1)
        {
            $timespan_end = time();
        }

        $this->template->assign_vars([
            'TRACKER_NAME'    => $tracker['tracker_name'],
        ]);

        $navlinks = [
            [
                'FORUM_NAME'    => $tracker['tracker_name'],
                // RC4 FIX: Use of user-friendly path for breadcrumbs
                'U_VIEW_FORUM'    => $this->helper->route('nextgen_trackers_page', ['page' => 'viewtracker', 't' => (int) $tracker_id]),
            ],
        ];

        $functions->generate_navlinks($navlinks);

        // Tracker statistics (Overview)
        if (!$project_id)
        {
            $sql = 'SELECT tracker_id, tracker_name
                FROM ' . $this->tables['trackers_tracker'];
            $result = $this->db->sql_query($sql);
            while ($row = $this->db->sql_fetchrow($result))
            {
                $this->template->assign_block_vars('trackers_stats', [
                    'TRACKER_NAME'        => $row['tracker_name'],
                    // RC4 FIX: Change path to nextgen_trackers_page for statistics
                    'U_TRACKER_STATS'    => $this->helper->route('nextgen_trackers_page', ['page' => 'statistics', 't' => (int) $row['tracker_id']]),
                ]);
            }
            $this->db->sql_freeresult($result);

            $this->template->assign_vars([
                'TIMESPAN_TICKETS'    => $this->language->lang('TIMESPAN_TICKETS', $this->user->format_date($timespan_start, 'F jS, Y'), $this->user->format_date($timespan_end, 'F jS, Y')),

                'STATISTICS_EXPLAIN'    => $this->language->lang('STATISTICS_TRACKER_EXPLAIN', $tracker['tracker_name'], $this->config['sitename']),
            ]);

            // Internal generate_stats functions already handle privacy using is_team_user
            $functions->generate_stats('projects', $timespan_start, $timespan_end, $tracker_id);
            $functions->generate_stats('projects_total', 0, 0, $tracker_id);

            // We always pass the enabled status for HTML.
            $this->template->assign_vars([
                'S_TRACKER_ENABLED'  => $is_enabled,
            ]);
        
            return $this->helper->render('statistics_tracker_body.html', $tracker['tracker_name']);
        }
        // Project statistics (Specific Project View)
        else
        {
            $project = $functions->get_project_data($project_id);

            $sql_where = 'project_id = ' . (int) $project_id . '
                AND timestamp_created BETWEEN ' . (int) $timespan_start . ' AND ' . (int) $timespan_end;
            
            // SECURITY: Privacy logic for counts (m8)
            $can_see_private = ($this->auth->acl_get('a_') || $this->auth->acl_getf_global('m_') || $this->auth->acl_get('u_tracker_view_private') || $functions->is_team_user($project_id));
            
            if (!$can_see_private)
            {
                $sql_where .= ' AND (ticket_private = 0 OR user_id = ' . (int) $this->user->data['user_id'] . ')';
            }

            if ($timespan_start > 0 && $timespan_end > 0)
            {
                $search_filter = $this->language->lang('FILTER_BETWEEN', $this->user->format_date($timespan_start, 'F jS, Y'), $this->user->format_date($timespan_end, 'F jS, Y'));
            }
            else if ($timespan_start > 0)
            {
                $search_filter = $this->language->lang('FILTER_AFTER', $this->user->format_date($timespan_start, 'F jS, Y'));
            }
            else if ($timespan_end > 0)
            {
                $search_filter = $this->language->lang('FILTER_BEFORE', $this->user->format_date($timespan_end, 'F jS, Y'));
            }

            $status_ids = [];
            $statuses = $functions->get_status($tracker_id);

            foreach ($statuses as $status)
            {
                $status_ids[] = $status['status_id'];
            }

            $sql = 'SELECT status_id, COUNT(ticket_id) AS tickets_count
                FROM ' . $this->tables['trackers_ticket'] . '
                WHERE ' . $this->db->sql_in_set('status_id', $status_ids) . '
                    AND ' . $sql_where . '
                GROUP BY status_id';
            $result = $this->db->sql_query($sql);
            $tickets_count = [];
            while ($row = $this->db->sql_fetchrow($result))
            {
                $tickets_count[$row['status_id']] = $row['tickets_count'];
            }
            $this->db->sql_freeresult($result);

            foreach ($statuses as $status)
            {
                $status_tickets = (isset($tickets_count[$status['status_id']])) ? $tickets_count[$status['status_id']] : 0;

                $this->template->assign_block_vars('statuses', [
                    'S_CLOSED'        => $status['ticket_closed'],
                    'NAME'            => $status['status_name'],
                    'TICKETS'        => $status_tickets,
                    // RC4 FIX: Use of friendly path for status filters
                    'U_STATUS_FILTER' => $this->helper->route('nextgen_trackers_page', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket_status' => (int) $status['status_id']]),
                ]);
            }

            $this->template->assign_vars([
                'STATISTICS_EXPLAIN'    => $this->language->lang('STATISTICS_PROJECT_EXPLAIN', $project['project_name'], $this->config['sitename'], $tracker['tracker_name']),
                'SEARCH_FILTER'        => $search_filter,
                // RC4 FIX: Use user-friendly path to return to general statistics
                'U_TRACKER_STATS'    => $this->helper->route('nextgen_trackers_page', ['page' => 'statistics', 't' => (int) $tracker_id]),
            ]);

            // We always pass the enabled status for HTML.
            $this->template->assign_vars([
                'S_TRACKER_ENABLED'  => $is_enabled,
            ]);
        
            return $this->helper->render('statistics_project_body.html', $project['project_name']);
        }
    }
}
