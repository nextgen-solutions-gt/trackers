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
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

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

	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, ContainerInterface $container, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->container = $container;
		$this->language = $language;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->db = $container->get('dbal.conn');
		$this->functions = $container->get('nextgen.trackers.functions');
	}

	public function display()
	{
		$ticket_id  = $this->request->variable('ticket', 0);
		$tracker_id = $this->request->variable('t', 0);
		$project_id = $this->request->variable('p', 0);
		$start      = $this->request->variable('start', 0);

		// LÓGICA DE AUTO-DESCUBRIMIENTO (Deep Linking Fix)
		// Si venimos de una notificación, solo tenemos el ticket_id. 
		// Recuperamos el contexto para evitar errores de "Tracker no encontrado".
		if ($ticket_id > 0 && ($tracker_id == 0 || $project_id == 0))
		{
			try {
				$ticket_data = $this->functions->get_ticket_data($ticket_id);
				$project_id  = (int) $ticket_data['project_id'];

				$project_data = $this->functions->get_project_data($project_id);
				$tracker_id   = (int) $project_data['tracker_id'];
			} catch (\phpbb\exception\http_exception $e) {
				// Si el ticket no existe, lanzamos el 404
				throw $e;
			}
		}

		// Ahora cargamos los datos con los IDs garantizados
		$tracker = $this->functions->get_tracker_data($tracker_id);
		$project = $this->functions->get_project_data($project_id);
		$ticket  = $this->functions->get_ticket_data($ticket_id);

		// LÓGICA DE PERMISOS UNIFICADA
		$is_team_user = $this->functions->is_team_user($project_id);
		$can_manage   = ($is_team_user || $this->auth->acl_get('a_') || $this->auth->acl_get('m_'));

		// PROCESAR FORMULARIOS DE GESTIÓN
		if ($can_manage)
		{
			// 1. Cambiar Estado
			if ($this->request->is_set_post('change_status'))
			{
				$new_status = $this->request->variable('change_status', 0);
				$this->functions->set_status($ticket_id, $new_status);
				$ticket = $this->functions->get_ticket_data($ticket_id); // Recargar
			}

			// 2. Cambiar Severidad
			if ($this->request->is_set_post('change_severity'))
			{
				$new_sev = $this->request->variable('change_severity', 0);
				$this->functions->set_severity($ticket_id, $new_sev);
				$ticket = $this->functions->get_ticket_data($ticket_id); // Recargar
			}

			// 3. Asignar Usuario
			if ($this->request->is_set_post('assign_user'))
			{
				$username_input = $this->request->variable('username', '', true);
				
				if (!empty($username_input)) {
					$clean_name = utf8_clean_string($username_input);
					
					$sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username_clean = '" . $this->db->sql_escape($clean_name) . "'";
					$result = $this->db->sql_query($sql);
					$found_user_id = (int) $this->db->sql_fetchfield('user_id');
					$this->db->sql_freeresult($result);

					if (!$found_user_id) {
						$sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username = '" . $this->db->sql_escape($username_input) . "'";
						$result = $this->db->sql_query($sql);
						$found_user_id = (int) $this->db->sql_fetchfield('user_id');
						$this->db->sql_freeresult($result);
					}

					if ($found_user_id > 0) {
						$this->functions->set_assignee($ticket_id, $found_user_id);
						$ticket['assigned_user'] = $found_user_id; 
					}
				}
			}
		}

		// Preparación de datos de vista
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
		$this->prepare_selects($tracker_id, $ticket);

		// LÓGICA DE PERMISOS PARA BOTONES DE ACCIÓN
		$s_edit = $this->user->data['is_registered'] && (
			($this->auth->acl_get('u_tracker_edit') && $this->user->data['user_id'] == $ticket['user_id']) || 
			$can_manage
		);

		$s_delete = $this->user->data['is_registered'] && (
			($this->auth->acl_get('u_tracker_delete') && $this->user->data['user_id'] == $ticket['user_id']) || 
			$can_manage
		);

		$s_quote = $can_manage || ($this->user->data['user_id'] != ANONYMOUS || $this->auth->acl_get('u_tracker_reply'));

		// Nombre del usuario asignado
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

		$this->template->assign_vars([
			'ASSIGNED_USER_FULL' => $assigned_user_link,
			'ASSIGNED_USER_ID'   => (int) $ticket['assigned_user'],
			'S_CAN_MANAGE'       => $can_manage,
			
			'U_FIND_USER'        => append_sid("{$this->container->getParameter('core.root_path')}memberlist.{$this->container->getParameter('core.php_ext')}", [
				'mode'          => 'searchuser',
				'form'          => 'viewticket_form',
				'field'         => 'username', 
				'select_single' => true,
			]),

			'TRACKER_NAME'     => $tracker['tracker_name'],
			'TICKET_TITLE'     => $ticket['ticket_title'],
			'TICKET_TEXT'      => $this->functions->get_ticket_text($ticket_id),
			'STATUS'           => $ticket['status_name'],
			'STATUS_ID'        => $ticket['status_id'],
			'SEVERITY_ID'      => $ticket['severity_id'],
			'S_CLOSED'         => $ticket['ticket_closed'],
			'S_TICKET_LOCKED'  => (isset($ticket['ticket_locked'])) ? $ticket['ticket_locked'] : false,
			'S_TICKET_PRIVATE' => $ticket['ticket_private'],
			'U_ACTION'         => $base_url,
			'U_EDIT'           => $s_edit ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'edit', 'post' => (int) $ticket['post_id']]) : '',
			'U_DELETE'         => $s_delete ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'delete', 'post' => (int) $ticket['post_id']]) : '',
			'U_QUOTE'          => $s_quote ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'reply', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id]) : '',
			'U_POST_REPLY_TOPIC' => $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'reply', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id]),
			'TOTAL_POSTS'      => $this->language->lang('PAGE_TOTAL_POSTS', $total_posts),
			'S_HIDDEN_FIELDS'  => build_hidden_fields(['t' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket' => (int) $ticket_id]),
		]);

		$this->functions->get_posts_history($ticket, $start);
		$this->generate_breadcrumbs($tracker, $project, $ticket);

		return $this->helper->render('viewticket_body.html', $tracker['tracker_name'] . ' - ' . $ticket['ticket_title']);
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

	protected function prepare_selects($tracker_id, $ticket)
	{
		// Status select
		$status = $this->functions->get_status($tracker_id);
		foreach ($status as $id => $data) {
			$this->template->assign_block_vars('statuses', [
				'ID'   => $id,
				'NAME' => $data['status_name'],
			]);
		}
		
		// Severity select
		$severities = $this->functions->get_severities($tracker_id);
		if (!empty($severities)) {
			foreach ($severities as $id => $data) {
				$this->template->assign_block_vars('severities', [
					'ID'   => $id,
					'NAME' => $data['severity_name'],
				]);
			}
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