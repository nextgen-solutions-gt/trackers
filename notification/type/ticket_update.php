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

class ticket_update extends \phpbb\notification\type\base
{
    protected $db;
    protected $auth;
    protected $config;
    protected $language;
    protected $helper;
    protected $user;
    protected $notification_manager;
    protected $root_path;
    protected $php_ext;
    protected $table_prefix;
    protected $trackers_prefix;

    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\user $user, \phpbb\notification\manager $notification_manager, $root_path, $php_ext, $table_prefix, $trackers_prefix)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->config = $config;
        $this->language = $language;
        $this->helper = $helper;
        $this->user = $user;
        $this->notification_manager = $notification_manager;
        $this->root_path = $root_path;
        $this->php_ext = $php_ext;
        $this->table_prefix = $table_prefix;
        $this->trackers_prefix = $trackers_prefix;

        // Core tables use the standard phpBB prefix
        $this->notifications_table = $table_prefix . 'notifications';
        $this->notification_types_table = $table_prefix . 'notification_types';
        $this->user_notifications_table = $table_prefix . 'user_notifications';
    }
    
    public function get_type()
    {
        return 'nextgen.trackers.notification.type.ticket_update';
    }

    public static $notification_option = array(
        'lang'    => 'NOTIFICATION_TYPE_NEXTGEN_TRACKERS_TICKET_UPDATE',
        'group'   => 'NOTIFICATION_GROUP_TRACKERS',
    );    

    static public function get_item_id($ticket_data)
    {
        return (int) $ticket_data['ticket_id'];
    }

    static public function get_item_parent_id($ticket_data)
    {
        return (int) $ticket_data['project_id'];
    }

    public function users_to_query()
    {
        return [
            (int) $this->get_data('author_id'),
        ];
    }

    static public function get_item_id_column()
    {
        return 'ticket_id';
    }

    public function is_available()
    {
        return $this->auth->acl_get('u_tracker_view');
    }

    static public function get_item_parent_id_column()
    {
        return 'project_id';
    }

    public function get_title()
    {
        return $this->language->lang('NOTIFICATION_TICKET_UPDATE', $this->get_data('ticket_title'));
    }

    public function get_url()
    {
        // FIX: Pure Symfony route generation to avoid index.php error
        return $this->helper->route('nextgen_trackers_ticket', [
            'page'   => 'viewticket', 
            'ticket' => (int) $this->get_data('ticket_id')
        ]);
    }

    public function get_email_template()
    {
        return '@nextgen_trackers/ticket_update_notification';
    }

    public function get_email_template_variables()
    {
        return [
            'TICKET_TITLE' => htmlspecialchars_decode($this->get_data('ticket_title')),
            'AUTHOR_NAME'  => $this->get_data('author_username'),
            // FIX: Generation of clean absolute URLs for emails
            'U_TICKET'     => generate_board_url() . '/' . str_replace('./', '', $this->get_url()),
        ];
    }

    public function create_insert_array($ticket_data, $pre_create_data = [])
    {
        $this->set_data('ticket_id', (int) $ticket_data['ticket_id']);
        $this->set_data('project_id', (int) $ticket_data['project_id']);
        $this->set_data('ticket_title', (string) $ticket_data['ticket_title']);
        $this->set_data('author_username', (string) $ticket_data['author_username']);
        $this->set_data('author_id', (int) $ticket_data['author_id']);

        return parent::create_insert_array($ticket_data, $pre_create_data);
    }

    public function find_users_for_notification($ticket_data, $options = [])
    {
        $options = array_merge([
            'ignore_users' => [(int) $this->user->data['user_id']],
        ], $options);

        $users = [];
        // WE USE THE EXTENSION PREFIX (e.g., phpbb_trackers_)
        $watch_table = $this->trackers_prefix . 'watch';

        $sql = 'SELECT user_id 
                FROM ' . $watch_table . ' 
                WHERE ticket_id = ' . (int) $ticket_data['ticket_id'] . '
                    AND notify_status = 1';
        
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $users[] = (int) $row['user_id'];
        }
        $this->db->sql_freeresult($result);

        if (empty($users))
        {
            return [];
        }

        return $this->check_user_notification_options($users, $options);
    }

    public static function is_enabled_by_default()
    {
        return true;
    }
}
