<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\notification\type;

/**
* Ticket Assigned Notification
*/
class ticket_assigned extends \phpbb\notification\type\base
{
	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\user_loader */
	protected $user_loader;

	public function set_user_loader(\phpbb\user_loader $user_loader)
	{
		$this->user_loader = $user_loader;
	}

	public function set_controller_helper(\phpbb\controller\helper $helper)
	{
		$this->helper = $helper;
	}

	public function get_type()
	{
		return 'nextgen.trackers.notification.type.ticket_assigned';
	}

	public static $notification_option = array(
		'lang'	=> 'NOTIFICATION_TYPE_TRACKERS_ASSIGNED',
		'group'	=> 'NOTIFICATION_GROUP_TRACKERS',
	);

	public function is_available()
{
    // Solo está disponible si la extensión está activa en el gestor de extensiones
    return $this->container->get('ext.manager')->is_enabled('nextgen/trackers');
}

	static public function get_item_id($data)
	{
		return (int) $data['ticket_id'];
	}

	static public function get_item_parent_id($data)
	{
		return (int) $data['project_id'];
	}

	public function find_users_for_notification($data, $options = array())
	{
		$users = array((int) $data['assigned_user']);
		return $this->check_user_notification_options($users, $options);
	}

	public function get_avatar()
	{
		return $this->user_loader->get_avatar($this->get_data('user_from'), false, true);
	}

	public function get_title()
	{
		$ticket_title = $this->get_data('ticket_title');
		$username = $this->user_loader->get_username($this->get_data('user_from'), 'no_profile');

		return $this->language->lang('NOTIFICATION_TICKET_ASSIGNED', $username, $ticket_title);
	}

	public function get_email_template()
	{
		return '@nextgen_trackers/ticket_assigned_notification';
	}

	public function get_email_template_variables()
	{
		return array(
			'TICKET_TITLE' => htmlspecialchars_decode($this->get_data('ticket_title')),
			'U_TICKET'     => $this->get_url(),
		);
	}

	public function get_url()
	{
		return $this->helper->route('nextgen_trackers_controller', array(
			'page'   => 'viewticket',
			'ticket' => (int) $this->item_id,
		));
	}

	public function users_to_query()
	{
		return array((int) $this->get_data('user_from'));
	}

	public function create_insert_array($data, $pre_create_data = array())
	{
		$this->set_data('ticket_title', $data['ticket_title']);
		$this->set_data('project_id', (int) $data['project_id']);
		
		// Corrección para evitar el Undefined array key
		$user_from = (isset($data['user_from'])) ? (int) $data['user_from'] : (int) $this->user->data['user_id'];
		$this->set_data('user_from', $user_from);

		parent::create_insert_array($data, $pre_create_data);
	}
}