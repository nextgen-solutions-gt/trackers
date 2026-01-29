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

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config      $config
	 * @param ContainerInterface        $container
	 * @param \phpbb\language\language  $language
	 * @param \phpbb\controller\helper  $helper
	 * @param \phpbb\request\request    $request
	 * @param \phpbb\template\template  $template
	 * @param \phpbb\user               $user
	 */
	public function __construct(\phpbb\config\config $config, ContainerInterface $container, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->config = $config;
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
		$project_id = $this->request->variable('p', 0);

		$start = $this->request->variable('start', 0);
		$ticket_status = $this->request->variable('ticket_status', 0);

		$pagination = $this->container->get('pagination');

		$s_hidden_fields = build_hidden_fields(['t' => (int) $tracker_id, 'p' => (int) $project_id]);

		$tracker = $this->container->get('nextgen.trackers.functions')->get_tracker_data($tracker_id);
		$project = $this->container->get('nextgen.trackers.functions')->get_project_data($project_id);
		$status = $this->container->get('nextgen.trackers.functions')->get_status($tracker_id);

		foreach ($status as $status_id => $status_data)
		{
			$this->template->assign_block_vars('status_ary', [
				'ID'	=> $status_id,
				'NAME'	=> $status_data['status_name'],
			]);

			if ($status_data['ticket_new'])
			{
				$status_new = $status_id;
			}
		}

		$total_tickets = $this->container->get('nextgen.trackers.functions')->get_total_tickets($tracker, $project_id, $ticket_status);

		// Handle pagination
		$start = $pagination->validate_start($start, $this->config['tickets_per_page'], $total_tickets);
		$base_url = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket_status' => (int) $ticket_status]);
		$pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_tickets, $this->config['tickets_per_page'], $start);

		$this->container->get('nextgen.trackers.functions')->get_tickets($tracker, $project_id, $ticket_status, $start, $status_new);

		switch ($ticket_status)
		{
			case 0:
				$status_name = $this->language->lang('ALL_OPEN');
			break;

			case -1:
				$status_name = $this->language->lang('ALL_TICKETS');
			break;

			case -2:
				$status_name = $this->language->lang('ALL_CLOSED');
			break;

			default:
				$status = $this->container->get('nextgen.trackers.functions')->get_status_data($ticket_status);
				$status_name = $status['status_name'];
			break;
		}

		$this->template->assign_vars([
			'TRACKER_NAME'	=> $tracker['tracker_name'],

			'STATUS_ID'	=> $ticket_status,
			'STATUS'	=> $status_name,

			'TOTAL_TICKETS'	=> $this->language->lang('TOTAL_TICKETS', $total_tickets),

			'U_ACTION'			=> $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]),
			'U_POST_NEW_TICKET'	=> $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'post', 't' => (int) $tracker_id, 'p' => (int) $project_id]),

			'S_HIDDEN_FIELDS'	=> $s_hidden_fields,
		]);

		$navlinks = [
			[
				'FORUM_NAME'	=> $tracker['tracker_name'],
				'U_VIEW_FORUM'	=> $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => (int) $tracker_id]),
			],

			[
				'FORUM_NAME'	=> $project['project_name'],
				'U_VIEW_FORUM'	=> $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]),
			],
		];

		$this->container->get('nextgen.trackers.functions')->generate_navlinks($navlinks);

		return $this->helper->render('viewproject_body.html', $tracker['tracker_name']);
	}
}
