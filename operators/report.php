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
 * Report operator
 */
class report
{
    protected $auth;
    protected $db;
    protected $helper;
    protected $language;
    protected $request;
    protected $template;
    protected $user;
    protected $container;
    protected $table_prefix;

    /**
     * Constructor
     */
    public function __construct(\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, ContainerInterface $container, $table_prefix)
    {
        $this->auth = $auth;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->container = $container;
        $this->table_prefix = $table_prefix;
    }

    public function display()
    {
        // Load the report-specific language file
        $this->language->add_lang('report', 'nextgen/trackers');

        $ticket_id = $this->request->variable('ticket', 0);

        if ($this->request->is_set_post('cancel'))
        {
            // FIX: Migration of route to nextgen_trackers_ticket
            redirect($this->helper->route('nextgen_trackers_ticket', [
                'page'   => 'viewticket',
                'ticket' => (int) $ticket_id
            ]));
        }

        if (!$ticket_id)
        {
            return $this->helper->error($this->language->lang('NO_TICKET_SELECTED'), 404);
        }

        // Obtain ticket data for notification and verification
        $sql = 'SELECT ticket_title, ticket_reported FROM ' . $this->table_prefix . 'ticket
                WHERE ticket_id = ' . (int) $ticket_id;
        $result = $this->db->sql_query($sql);
        $ticket_data = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$ticket_data)
        {
            return $this->helper->error($this->language->lang('NO_TRACKER'), 404);
        }

        // Avoid duplicate reports if it is already marked as reported
        if ($ticket_data['ticket_reported'])
        {
            return $this->helper->error($this->language->lang('ALREADY_REPORTED'), 403);
        }

        if ($this->request->is_set_post('submit'))
        {
            if (!check_form_key('report_ticket'))
            {
                return $this->helper->error($this->language->lang('FORM_INVALID'));
            }

            $reason = $this->request->variable('report_reason', '', true);

            if (empty($reason))
            {
                return $this->helper->error($this->language->lang('REPORT_REASON_REQUIRED'));
            }

            // 1. Insert the report into the trackers_reports table.
            $sql_ary = [
                'ticket_id'     => (int) $ticket_id,
                'user_id'       => (int) $this->user->data['user_id'],
                'report_reason' => (string) $reason,
                'report_time'   => (int) time(),
                'report_closed' => 0,
            ];

            $this->db->sql_query('INSERT INTO ' . $this->table_prefix . 'reports ' . $this->db->sql_build_array('INSERT', $sql_ary));

            // 2. Mark the ticket as reported
            $this->db->sql_query('UPDATE ' . $this->table_prefix . 'ticket SET ticket_reported = 1 WHERE ticket_id = ' . (int) $ticket_id);

            // 3. Trigger notification to moderators (Lazy Loading via Container)
            $notification_manager = $this->container->get('notification_manager');
            
            $notification_manager->add_notifications([
                'nextgen.trackers.notification.type.ticket_reported'
            ], [
                'ticket_id'     => $ticket_id,
                'ticket_title'  => $ticket_data['ticket_title'],
                'reporter_name' => $this->user->data['username'],
            ]);

            // FIX: Migration of route to nextgen_trackers_ticket
            $redirect = $this->helper->route('nextgen_trackers_ticket', ['page' => 'viewticket', 'ticket' => $ticket_id]);
            meta_refresh(3, $redirect);
            return trigger_error($this->language->lang('TICKET_REPORTED_SUCCESS'));
        }

        add_form_key('report_ticket');

        $this->template->assign_vars([
            'TICKET_TITLE' => $ticket_data['ticket_title'],
            // FIX: Migration of route to nextgen_trackers_ticket in form action
            'S_REPORT_ACTION' => $this->helper->route('nextgen_trackers_ticket', ['page' => 'report', 'ticket' => $ticket_id]),
        ]);

        return $this->helper->render('report_body.html', $this->language->lang('REPORT_TICKET'));
    }
}
