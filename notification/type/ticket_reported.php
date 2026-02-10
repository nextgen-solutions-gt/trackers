<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\notification\type;

class ticket_reported extends \phpbb\notification\type\base
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /**
     * Method injected by the service container for configuration
     */
    public function set_config(\phpbb\config\config $config)
    {
        $this->config = $config;
    }

    /**
     * Method injected by the service container for routes
     */
    public function set_helper(\phpbb\controller\helper $helper)
    {
        $this->helper = $helper;
    }

    public function get_type()
    {
        return 'nextgen.trackers.notification.type.ticket_reported';
    }

    /**
     * Notification options for the user control panel (UCP)
     */
    public static $notification_option = array(
        'lang'    => 'NOTIFICATION_TYPE_TRACKERS_REPORT',
        'group'    => 'NOTIFICATION_GROUP_TRACKERS',
    );

    public function is_available()
    {
        return true;
    }

    /**
     * We search for the users who should receive the notification.
     */
    public function find_users_for_notification($data, $options = array())
    {
        // We are looking for users with administrative permissions in Trackers or moderation.
        $auth_approve = $this->auth->acl_get_list(false, array('a_trackers', 'm_tracker_edit'), false);
        
        $users = array();
        foreach ($auth_approve as $forum_id => $acl_data)
        {
            foreach ($acl_data as $permission => $user_ids)
            {
                $users = array_merge($users, $user_ids);
            }
        }

        $users = array_unique($users);

        if (empty($users))
        {
            return array();
        }

        return $this->check_user_notification_options($users, $options);
    }

    public function get_title()
    {
        return $this->language->lang('NOTIFICATION_TICKET_REPORTED', $this->get_data('reporter_name'), $this->get_data('ticket_title'));
    }

    public function get_url()
    {
        // FIX: The path to nextgen_trackers_ticket is updated to use the friendly pattern
        // and the parameters are passed as an array to prevent index.php injection.
        return $this->helper->route('nextgen_trackers_ticket', array(
            'page'   => 'viewticket',
            'ticket' => (int) $this->item_id,
        ));
    }

    public function get_email_template()
    {
        return '@nextgen_trackers/ticket_reported_notification';
    }

    /**
     * REQUIRED METHOD: Define the variables for the email
     */
    public function get_email_template_variables()
    {
        // FIX: Cleaning the path to generate a valid absolute link in the email
        $u_ticket = str_replace('./', '', $this->get_url());

        return array(
            'TICKET_TITLE'  => htmlspecialchars_decode($this->get_data('ticket_title')),
            'U_TICKET'      => generate_board_url() . '/' . $u_ticket,
            'REPORTER_NAME' => $this->get_data('reporter_name'),
        );
    }

    /**
     * REQUIRED METHOD: Based on ticket_reply, must be INSTANCE
     */
    public function users_to_query()
    {
        return array();
    }

    /**
     * REQUIRED METHODS: Based on ticket_reply, they must be STATIC
     */
    static public function get_item_id($data)
    {
        return (int) $data['ticket_id'];
    }

    static public function get_item_parent_id($data)
    {
        return 0;
    }

    public function create_insert_array($data, $pre_create_data = array())
    {
        $this->set_data('ticket_id', (int) $data['ticket_id']);
        $this->set_data('ticket_title', $data['ticket_title']);
        $this->set_data('reporter_name', $data['reporter_name']);

        return parent::create_insert_array($data, $pre_create_data);
    }
}
