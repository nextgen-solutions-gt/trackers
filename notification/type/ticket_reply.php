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
* Ticket Reply Notification
*/
class ticket_reply extends \phpbb\notification\type\base
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
		return 'nextgen.trackers.notification.type.ticket_reply';
	}

	public static $notification_option = array(
		'lang'	=> 'NOTIFICATION_TYPE_TRACKERS_REPLY',
		'group'	=> 'NOTIFICATION_GROUP_TRACKERS',
	);

	public function is_available()
	{
		return true;
	}

	public function find_users_for_notification($data, $options = array())
	{
		$users = array();
		$poster_id = (int) $data['user_from'];

		// Notificar al autor del ticket
		if ((int) $data['ticket_author'] !== $poster_id) {
			$users[] = (int) $data['ticket_author'];
		}

		// Notificar al asignado
		if (!empty($data['assigned_user']) && (int) $data['assigned_user'] !== $poster_id) {
			$users[] = (int) $data['assigned_user'];
		}

		return $this->check_user_notification_options(array_unique($users), $options);
	}

	public function get_title()
	{
		$username = $this->user_loader->get_username($this->get_data('user_from'), 'no_profile');
		return $this->language->lang('NOTIFICATION_TICKET_REPLY', $username, $this->get_data('ticket_title'));
	}

	public function get_url()
	{
		return $this->helper->route('nextgen_trackers_controller', array(
			'page'   => 'viewticket',
			'ticket' => (int) $this->item_id,
		));
	}

	/**
	* MÉTODO REQUERIDO: Define la plantilla de email
	*/
	public function get_email_template()
	{
		return '@nextgen_trackers/ticket_reply_notification';
	}

	/**
	* MÉTODO REQUERIDO: Define las variables para el email
	*/
	public function get_email_template_variables()
	{
		return array(
			'TICKET_TITLE' => htmlspecialchars_decode($this->get_data('ticket_title')),
			'U_TICKET'     => $this->get_url(),
			'AUTHOR_NAME'  => $this->user_loader->get_username($this->get_data('user_from'), 'no_profile'),
		);
	}

	public function users_to_query()
	{
		return array($this->get_data('user_from'));
	}

	static public function get_item_id($data)
	{
		return (int) $data['ticket_id'];
	}

	static public function get_item_parent_id($data)
	{
		return (int) $data['project_id'];
	}

	public function create_insert_array($data, $pre_create_data = array())
	{
		$this->set_data('ticket_title', $data['ticket_title']);
		$this->set_data('user_from', (int) $data['user_from']);

		parent::create_insert_array($data, $pre_create_data);
	}
}