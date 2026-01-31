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

/**
 * Viewproject operator
 */
class viewproject
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

	/** @var string */
	protected $table_prefix;

	/**
	 * Constructor
	 */
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
		$this->table_prefix = $container->getParameter('core.table_prefix');
	}

	public function display()
	{
		$tracker_id = $this->request->variable('t', 0);
		$project_id = $this->request->variable('p', 0);
		$start = $this->request->variable('start', 0);
		$ticket_status = $this->request->variable('ticket_status', 0);

		// SEGURIDAD: Verificar si el usuario tiene permiso para ver el tracker
		if (!$this->auth->acl_get('u_tracker_view'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$functions = $this->container->get('nextgen.trackers.functions');
		$tracker = $functions->get_tracker_data($tracker_id);
		$project = $functions->get_project_data($project_id);

		// --- MEJORA: Obtener Estados filtrados por Relación con el Proyecto ---
		$sql = 'SELECT s.status_id, s.status_name, s.ticket_new 
				FROM ' . $this->table_prefix . 'trackers_status s
				INNER JOIN ' . $this->table_prefix . 'trackers_relations r ON s.status_id = r.item_id
				WHERE r.project_id = ' . (int) $project_id . " 
					AND r.item_type = 'status' 
				ORDER BY s.status_order ASC";
		
		$result = $this->db->sql_query($sql);
		$status_new = 0;
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('status_ary', [
				'ID'   => $row['status_id'],
				'NAME' => $row['status_name'],
			]);

			if ($row['ticket_new'])
			{
				$status_new = $row['status_id'];
			}
		}
		$this->db->sql_freeresult($result);

		$total_tickets = $functions->get_total_tickets($tracker, $project_id, $ticket_status);

		// Manejo de paginación
		$pagination = $this->container->get('pagination');
		$start = $pagination->validate_start($start, $this->config['tickets_per_page'], $total_tickets);
		$base_url = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket_status' => (int) $ticket_status]);
		$pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_tickets, $this->config['tickets_per_page'], $start);

		// --- CARGAR TICKETS ---
		// NOTA: Para que los colores funcionen, la función get_tickets debe incluir 'status_colour' en su SELECT
		$functions->get_tickets($tracker, $project_id, $ticket_status, $start, $status_new);

		// Determinar nombre del filtro de estado para la cabecera
		switch ($ticket_status)
		{
			case 0:  $status_name = $this->language->lang('ALL_OPEN'); break;
			case -1: $status_name = $this->language->lang('ALL_TICKETS'); break;
			case -2: $status_name = $this->language->lang('ALL_CLOSED'); break;
			default:
				$status_data_single = $functions->get_status_data($ticket_status);
				$status_name = $status_data_single['status_name'];
			break;
		}

		$can_post = ($this->auth->acl_get('u_tracker_post') || $this->auth->acl_get('m_') || $functions->is_team_user($project_id));

		$this->template->assign_vars([
			'TRACKER_NAME'       => $tracker['tracker_name'],
			'PROJECT_NAME'       => $project['project_name'],
			'PROJECT_DESC'       => $project['project_description'], // Añadido para el header
			'STATUS_ID'          => $ticket_status,
			'STATUS'             => $status_name,
			'TOTAL_TICKETS'      => $this->language->lang('TOTAL_TICKETS', $total_tickets),
			'U_ACTION'           => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]),
			'U_POST_NEW_TICKET'  => ($can_post) ? $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'post', 't' => (int) $tracker_id, 'p' => (int) $project_id]) : '',
			'U_VIEWTRACKER'      => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => (int) $tracker_id]),
			'S_CAN_POST'         => $can_post,
			'S_HIDDEN_FIELDS'    => build_hidden_fields(['t' => (int) $tracker_id, 'p' => (int) $project_id]),
		]);

		$navlinks = [
			[
				'FORUM_NAME'   => $tracker['tracker_name'],
				'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => (int) $tracker_id]),
			],
			[
				'FORUM_NAME'   => $project['project_name'],
				'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]),
			],
		];

		$functions->generate_navlinks($navlinks);

		return $this->helper->render('viewproject_body.html', $tracker['tracker_name']);
	}
}