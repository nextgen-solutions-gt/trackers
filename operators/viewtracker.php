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
 * Viewtracker operator
 */
class viewtracker
{
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

	/**
	 * Constructor
	 *
	 * @param ContainerInterface        $container
	 * @param \phpbb\language\language  $language
	 * @param \phpbb\controller\helper  $helper
	 * @param \phpbb\request\request    $request
	 * @param \phpbb\template\template  $template
	 * @param \phpbb\user               $user
	 */
	public function __construct(ContainerInterface $container, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->container = $container;
		$this->language = $language;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
	}

	public function display()
	{
		$tracker_id = $this->request->variable('t', 0);

		// Obtenemos las funciones y datos del tracker
		$functions = $this->container->get('nextgen.trackers.functions');
		$tracker = $functions->get_tracker_data($tracker_id);

		if (!$tracker['allow_view_all'] && $this->user->data['user_id'] == ANONYMOUS)
		{
			login_box('', $this->language->lang('LOGIN_REQUIRED'));
		}

		$projects = $functions->get_projects($tracker_id);

		// Preparamos DB y prefijo para el conteo sin modificar constructor
		$db = $this->container->get('dbal.conn');
		$table_prefix = $this->container->getParameter('core.table_prefix');

		foreach ($projects as $project)
		{
			// Lógica de conteo de tickets añadida aquí
			$sql = 'SELECT COUNT(ticket_id) as total_tickets 
					FROM ' . $table_prefix . 'trackers_ticket 
					WHERE project_id = ' . (int) $project['project_id'];
			
			$result = $db->sql_query($sql);
			$total_tickets = (int) $db->sql_fetchfield('total_tickets');
			$db->sql_freeresult($result);

			$this->template->assign_block_vars('projects', [
				'PROJECT_NAME'  => $project['project_name'],
				'DESCRIPTION'   => $project['project_description'],
				'TOTAL_TICKETS' => $total_tickets, // Variable necesaria para el HTML

				'U_VIEWPROJECT' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project['project_id']]),
			]);
		}

		$this->template->assign_vars([
			'TRACKER_NAME' => $tracker['tracker_name'],

			'U_STATISTICS' => $this->helper->route('nextgen_trackers_controller', ['page' => 'statistics', 't' => (int) $tracker_id]),

			'S_TRACKER_PRIVATE' => !$tracker['allow_view_all'] ? true : false,
		]);

		$navlinks = [
			[
				'FORUM_NAME'   => $tracker['tracker_name'],
				'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => (int) $tracker_id]),
			],
		];

		$functions->generate_navlinks($navlinks);

		return $this->helper->render('viewtracker_body.html', $tracker['tracker_name']);
	}
}