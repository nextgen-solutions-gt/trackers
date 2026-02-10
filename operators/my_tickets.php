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

class my_tickets
{
    protected $auth;
    protected $config;
    protected $db;
    protected $helper;
    protected $language;
    protected $request;
    protected $template;
    protected $user;
    protected $table_prefix;

    /**
     * Constructor
     */
    public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $table_prefix)
    {
        $this->auth = $auth;
        $this->config = $config;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->table_prefix = $table_prefix;
    }

    /**
     * Display my tickets
     */
    public function display()
    {
        // Immediate verification of overall status
        $is_enabled = (isset($this->config['trackers_enabled'])) ? (bool) $this->config['trackers_enabled'] : true;

        // Force login if for some reason a guest arrives here
        if (!$this->user->data['is_registered'])
        {
            login_box('', $this->language->lang('LOGIN_EXPLAIN_TRACKERS'));
        }

        $user_id = (int) $this->user->data['user_id'];

        // Additional breadcrumbs (Level 1 “Trackers” is already set by the controller)
        $this->template->assign_block_vars('navlinks', [
            'BREADCRUMB_NAME' => $this->language->lang('MY_TICKETS'),
            'U_BREADCRUMB'    => $this->helper->route('nextgen_trackers_page', ['page' => 'my_tickets']),
        ]);

        // Tables
        $t_ticket     = $this->table_prefix . 'ticket';
        $t_status     = $this->table_prefix . 'status';
        $t_project    = $this->table_prefix . 'project';
        $t_severity   = $this->table_prefix . 'severity';
        $t_component  = $this->table_prefix . 'component';

        // Query to get my tickets with all related information
		$sql = 'SELECT t.*, s.status_name, s.status_colour, p.project_name, sev.severity_name, c.component_name
				FROM ' . $t_ticket . ' t
				LEFT JOIN ' . $t_status . ' s ON t.status_id = s.status_id
				LEFT JOIN ' . $t_project . ' p ON t.project_id = p.project_id
				LEFT JOIN ' . $t_severity . ' sev ON t.severity_id = sev.severity_id
				LEFT JOIN ' . $t_component . ' c ON t.component_id = c.component_id
				WHERE t.user_id = ' . (int) $user_id . '
				ORDER BY t.timestamp_created DESC';

        $result = $this->db->sql_query($sql);

        while ($row = $this->db->sql_fetchrow($result))
        {
            // Process the text to display smilies and BBCode in the title or description if necessary
            $this->template->assign_block_vars('ticket_row', [
                'TICKET_ID'      => $row['ticket_id'],
                'TICKET_TITLE'   => $row['ticket_title'],
                'TICKET_TIME'    => $this->user->format_date($row['timestamp_created']),
                'STATUS_NAME'    => $row['status_name'],
                'STATUS_COLOR'   => $row['status_colour'],
                'PROJECT_NAME'   => $row['project_name'],
                'SEVERITY_NAME'  => $row['severity_name'],
                'COMPONENT_NAME' => $row['component_name'],
                'U_VIEW_TICKET'  => $this->helper->route('nextgen_trackers_ticket', [
                    'page'   => 'viewticket',
                    'ticket' => (int) $row['ticket_id']
                ]),
            ]);
        }
        $this->db->sql_freeresult($result);

        $this->template->assign_vars([
            'S_TRACKER_ENABLED'  => $is_enabled,
        ]);

        return $this->helper->render('trackers_my_tickets.html', $this->language->lang('MY_TICKETS'));
    }
}
