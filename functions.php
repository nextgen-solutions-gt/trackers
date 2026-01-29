<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use nextgen\trackers\constants;

/**
 * Tracker functions
 */
class functions
{
	protected $auth;
	protected $config;
	protected $container;
	protected $db;
	protected $language;
	protected $helper;
	protected $request;
	protected $template;
	protected $user;
	protected $php_ext;

	/** @var array Nombres de tablas con prefijo */
	protected $tables = [];

	/**
	 * Constructor
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, ContainerInterface $container, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $php_ext, $table_prefix)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->container = $container;
		$this->db = $db;
		$this->language = $language;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->php_ext = $php_ext;

		$this->tables = [
			'trackers_tracker'      => $table_prefix . 'tracker',
			'trackers_project'      => $table_prefix . 'project',
			'trackers_status'       => $table_prefix . 'status',
			'trackers_severity'     => $table_prefix . 'severity',
			'trackers_ticket'       => $table_prefix . 'ticket',
			'trackers_post'         => $table_prefix . 'post',
			'trackers_history'      => $table_prefix . 'history',
			'trackers_component'    => $table_prefix . 'component',
			'trackers_project_auth' => $table_prefix . 'project_auth',

			'users'                 => USERS_TABLE,
			'groups'                => GROUPS_TABLE,
			'user_group'            => USER_GROUP_TABLE,
			'ranks'                 => RANKS_TABLE,
		];
	}

	public function get_tracker_data($tracker_id)
	{
		$sql = 'SELECT * FROM ' . $this->tables['trackers_tracker'] . ' WHERE tracker_id = ' . (int) $tracker_id;
		$result = $this->db->sql_query($sql);
		$tracker = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (empty($tracker)) {
			throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_TRACKER'));
		}
		return $tracker;
	}

	public function get_projects($tracker_id)
	{
		$sql = 'SELECT * FROM ' . $this->tables['trackers_project'] . ' WHERE tracker_id = ' . (int) $tracker_id;
		$result = $this->db->sql_query($sql);
		$projects = [];
		while ($row = $this->db->sql_fetchrow($result)) {
			$projects[$row['project_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		return $projects;
	}

	public function get_project_data($project_id)
	{
		$sql = 'SELECT * FROM ' . $this->tables['trackers_project'] . ' WHERE project_id = ' . (int) $project_id;
		$result = $this->db->sql_query($sql);
		$project = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (empty($project)) {
			throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_PROJECT'));
		}
		return $project;
	}

	public function get_status($tracker_id)
	{
		$status = [];
		$sql = 'SELECT * FROM ' . $this->tables['trackers_status'] . ' WHERE tracker_id = ' . (int) $tracker_id . ' ORDER BY status_order ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result)) {
			$status[$row['status_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		return $status;
	}

	public function get_status_data($status_id)
	{
		$sql = 'SELECT * FROM ' . $this->tables['trackers_status'] . ' WHERE status_id = ' . (int) $status_id;
		$result = $this->db->sql_query($sql);
		$status = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $status;
	}

	public function get_severities($tracker_id)
	{
		$severities = [];
		$sql = 'SELECT * FROM ' . $this->tables['trackers_severity'] . ' WHERE tracker_id = ' . (int) $tracker_id . ' ORDER BY severity_order ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result)) {
			$severities[$row['severity_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		
		return $severities;
	}

	public function get_severity_data($severity_id)
	{
		$sql = 'SELECT * FROM ' . $this->tables['trackers_severity'] . ' WHERE severity_id = ' . (int) $severity_id;
		$result = $this->db->sql_query($sql);
		$severity = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return $severity;
	}

	public function get_total_tickets($tracker, $project_id, $ticket_status)
	{
		$result = $this->db->sql_query('SELECT t.user_id AS ticket_user_id ' . $this->tickets_sql($tracker, $project_id, $ticket_status));
		$total_tickets = $this->db->sql_affectedrows();
		$this->db->sql_freeresult($result);
		return $total_tickets;
	}

	public function get_total_posts($ticket_id)
	{
		$sql = 'SELECT COUNT(post_id) AS posts_total FROM ' . $this->tables['trackers_post'] . ' WHERE ticket_id = ' . (int) $ticket_id;
		
		if (!$this->is_team_user()) {
			$sql .= ' AND post_private = 0';
		}
		
		$result = $this->db->sql_query($sql);
		$posts_total = (int) $this->db->sql_fetchfield('posts_total', 0, $result);
		$this->db->sql_freeresult($result);
		
		return ($posts_total > 0) ? $posts_total - 1 : 0;
	}

	public function get_tickets($tracker, $project_id, $ticket_status, $start, $status_new)
	{
		$sql = 'SELECT t.ticket_id, t.status_id, t.ticket_private, t.ticket_title, t.duplicate_id, st.status_name, st.status_id, st.ticket_duplicate, se.severity_colour, c.component_name, t.timestamp_created, t.assigned_user, t.assigned_group,
			t.user_id AS reporter_id, r.username AS reporter_username, r.user_colour AS reporter_colour,
			t.user_last_id AS lposter_id, lp.username AS lposter_username, lp.user_colour AS lposter_colour, MAX(tp.post_timestamp) AS lpost_time ' . $this->tickets_sql($tracker, $project_id, $ticket_status);
		
		$result = $this->db->sql_query_limit($sql, $this->config['tickets_per_page'], $start);
		
		while ($row = $this->db->sql_fetchrow($result)) {
			$assigned_to = '';
			if (!empty($row['assigned_user'])) {
				$assigned_to = $this->get_user_data($row['assigned_user']);
			} else if (!empty($row['assigned_group'])) {
				$assigned_to = $this->get_group_data($row['assigned_group']);
			}

			$this->template->assign_block_vars('tickets', [
				'S_NEW' => ($row['status_id'] == $status_new) ? true : false,
				'S_PRIVATE' => (bool) $row['ticket_private'],
				'U_VIEWTICKET' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker['tracker_id'], 'p' => (int) $project_id, 'ticket' => $row['ticket_id']]),
				'U_DUPLICATE' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker['tracker_id'], 'p' => (int) $project_id, 'ticket' => $row['duplicate_id']]),
				'ID' => $row['ticket_id'],
				'REPORTER' => $this->get_user_data($row['reporter_id']),
				'LPOSTER' => ($row['lposter_id'] != 0) ? $this->get_user_data($row['lposter_id']) : '',
				'LTIMESTAMP' => ($row['lposter_id'] != 0) ? $this->user->format_date($row['lpost_time']) : '',
				'COMPONENT' => $row['component_name'],
				'ASSIGNED' => $assigned_to,
				'TITLE' => $row['ticket_title'],
				'STATUS' => $row['status_name'],
				'DUPLICATE_ID' => ($row['ticket_duplicate']) ? $row['duplicate_id'] : false,
				'TIMESTAMP' => $this->user->format_date($row['timestamp_created']),
				'SEV_COLOUR' => $row['severity_colour'],
			]);
		}
		$this->db->sql_freeresult($result);
	}

	public function tickets_sql($tracker, $project_id, $ticket_status)
	{
		$sql = 'FROM (' . $this->tables['trackers_ticket'] . ' t, ' . $this->tables['trackers_status'] . ' st)
			LEFT JOIN ' . $this->tables['users'] . ' r ON r.user_id = t.user_id
			LEFT JOIN ' . $this->tables['users'] . ' lp ON lp.user_id = t.user_last_id
			LEFT JOIN ' . $this->tables['users'] . ' au ON au.user_id = t.assigned_user
			LEFT JOIN ' . $this->tables['groups'] . ' ag ON ag.group_id = t.assigned_group
			LEFT JOIN ' . $this->tables['trackers_severity'] . ' se ON se.severity_id = t.severity_id
			LEFT JOIN ' . $this->tables['trackers_component'] . ' c ON c.component_id = t.component_id
			LEFT JOIN ' . $this->tables['trackers_post'] . ' tp ON tp.ticket_id = t.ticket_id
			WHERE st.status_id = t.status_id AND t.project_id = ' . (int) $project_id;

		if ($ticket_status == constants::STATUS_OPEN) {
			$sql .= ' AND st.ticket_closed = 0';
		} else if ($ticket_status == constants::STATUS_CLOSED) {
			$sql .= ' AND st.ticket_closed = 1';
		} else if ($ticket_status != constants::STATUS_ALL && ($tracker['allow_view_all'] || $this->is_team_user())) {
			$sql .= ' AND t.status_id = ' . (int) $ticket_status;
		}

		if (!$tracker['allow_view_all'] && !$this->is_team_user()) {
			$sql .= ' AND t.user_id = ' . (int) $this->user->data['user_id'];
		}

		if (!$this->is_team_user() && !$this->can_report_private()) {
			$sql .= ' AND (t.ticket_private = 0 OR t.user_id = ' . (int) $this->user->data['user_id'] . ')';
		}

		$sql .= ' GROUP BY t.ticket_id ORDER BY t.ticket_id DESC';
		return $sql;
	}

	public function get_ticket_data($ticket_id)
	{
		$sql = 'SELECT t.*, st.status_name, st.ticket_closed, st.ticket_duplicate, sv.*, r.username AS ticket_user, r.user_colour AS ticket_colour,
				c.component_name, au.username AS assigned_user_name, au.user_colour AS assigned_user_colour, ag.group_type AS assigned_group_type,
				ag.group_name AS assigned_group_name, ag.group_colour AS assigned_group_colour
			FROM (' . $this->tables['trackers_ticket'] . ' t, ' . $this->tables['users'] . ' r)
			LEFT JOIN ' . $this->tables['trackers_status'] . ' st ON st.status_id = t.status_id
			LEFT JOIN ' . $this->tables['trackers_severity'] . ' sv ON sv.severity_id = t.severity_id
			LEFT JOIN ' . $this->tables['trackers_component'] . ' c ON c.component_id = t.component_id
			LEFT JOIN ' . $this->tables['users'] . ' au ON au.user_id = t.assigned_user
			LEFT JOIN ' . $this->tables['groups'] . ' ag ON ag.group_id = t.assigned_group
			WHERE r.user_id = t.user_id AND t.ticket_id = ' . (int) $ticket_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (empty($row)) {
			throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_TICKET'));
		}

		if (!empty($row['assigned_user'])) {
			$row['assigned'] = $this->get_user_data($row['assigned_user']);
		} else if (!empty($row['assigned_group'])) {
			$row['assigned'] = $this->get_group_data($row['assigned_group']);
		}
		return $row;
	}

	public function get_ticket_text($ticket_id, $decode_message = false)
	{
		// CORRECCIÓN: Concatenación de (int) $ticket_id fuera de las comillas simples
		$sql = 'SELECT post_id, post_text, bbcode_bitfield, bbcode_uid, bbcode_flags
			FROM ' . $this->tables['trackers_post'] . '
			WHERE ticket_id = ' . (int) $ticket_id;
		
		if (!$this->is_team_user()) {
			$sql .= ' AND post_private = 0';
		}
			
		$sql .= ' ORDER BY post_id ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$post_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$post_data) return '';

		$post_text = $post_data['post_text'];
		if (!$decode_message) {
			$post_text = generate_text_for_display($post_text, $post_data['bbcode_uid'], $post_data['bbcode_bitfield'], $post_data['bbcode_flags']);
		} else {
			decode_message($post_text, $post_data['bbcode_uid']);
		}
		return $post_text;
	}

	public function get_ticket_details($tracker_id, $project, $ticket)
	{
		$ticket_details = [];
		$ticket_details[$this->language->lang('TICKET_ID')] = $ticket['ticket_id'];
		$ticket_details[$this->language->lang('PROJECT')] = $project['project_name'];
		$ticket_details[$this->language->lang('STATUS')] = $ticket['status_name'];

		if ($ticket['ticket_duplicate'] && $ticket['duplicate_id'] > 0) {
			$ticket_details[$this->language->lang('STATUS')] .= ' » <a href="' . $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker_id, 'p' => (int) $project['project_id'], 'ticket' => (int) $ticket['duplicate_id']]) . '" style="color: #ff0000">#' . (int) $ticket['duplicate_id'] . '</a>';
		}

		$ticket_details[$this->language->lang('SEVERITY')] = (isset($ticket['severity_name']) && !empty($ticket['severity_name'])) ? $ticket['severity_name'] : $this->language->lang('UNCATEGORISED');
		$ticket_details[$this->language->lang('COMPONENT')] = (isset($ticket['component_name']) && !empty($ticket['component_name'])) ? $ticket['component_name'] : ucfirst($this->language->lang('UNKNOWN'));

		$assigned_user_full = $this->language->lang('UNASSIGNED');
		if (!empty($ticket['assigned_user'])) {
			$sql = 'SELECT username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $ticket['assigned_user'];
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			if ($row) {
				$assigned_user_full = get_username_string('full', $ticket['assigned_user'], $row['username'], $row['user_colour']);
			}
		}
		$ticket_details[$this->language->lang('ASSIGNED')] = $assigned_user_full;

		$reported_by = $this->get_user_data($ticket['user_id']);
		$send_pm = generate_board_url() . '/' . append_sid("ucp.{$this->php_ext}", 'i=pm&amp;mode=compose&amp;u=' . (int) $ticket['user_id']);
		$reported_by .= ' (<a href="' . $send_pm . '">' . $this->language->lang('SEND_PM') . '</a>)';
		$ticket_details[$this->language->lang('REPORTED_BY')] = $reported_by;

		if (!empty($ticket['reporter_ip']) && $this->is_team_user()) {
			$ticket_details[$this->language->lang('REPORTED_FROM')] = $ticket['reporter_ip'];
		}
		$ticket_details[$this->language->lang('REPORTED_ON')] = $this->user->format_date($ticket['timestamp_created']);

		foreach ($ticket_details as $detail_name => $detail_value) {
			$this->template->assign_block_vars('ticket_details', [
				'NAME' => $detail_name,
				'VALUE' => $detail_value,
			]);
		}
	}

	public function get_duplicate_tickets($ticket_id)
	{
		$tickets = [];
		$sql = 'SELECT t.ticket_id, t.ticket_title FROM ' . $this->tables['trackers_ticket'] . ' t
			LEFT JOIN ' . $this->tables['trackers_status'] . ' st ON st.status_id = t.status_id
			WHERE st.ticket_duplicate = 1 AND t.duplicate_id = ' . (int) $ticket_id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result)) {
			$id = (int) $row['ticket_id'];
			$tickets[$id] = $row;
		}
		$this->db->sql_freeresult($result);
		return $tickets;
	}

	public function get_posts_history($ticket, $start)
	{
		$sql = 'SELECT p.*, u.user_id, u.username, u.user_colour, r.rank_title
			FROM ' . $this->tables['trackers_post'] . ' p
			INNER JOIN ' . $this->tables['users'] . ' u ON p.user_id = u.user_id
			LEFT JOIN ' . $this->tables['ranks'] . ' r ON r.rank_id = u.user_rank
			WHERE p.ticket_id = ' . (int) $ticket['ticket_id'] . ' 
			AND p.post_id <> ' . (int) $ticket['post_id'];

		if (!$this->is_team_user((int) $ticket['project_id'])) {
			$sql .= ' AND p.post_private = 0';
		}

		$sql .= ' ORDER BY p.post_timestamp ASC, p.post_id ASC';
		$posts_result = $this->db->sql_query_limit($sql, $this->config['posts_per_page'] + 1, $start);
		
		$posts = [];
		while ($post_data = $this->db->sql_fetchrow($posts_result)) {
			$posts[] = $post_data;
		}
		$this->db->sql_freeresult($posts_result);

		$post_min_timestamp = (count($posts)) ? $posts[0]['post_timestamp'] : 0;
		$post_max_timestamp = (count($posts)) ? $posts[count($posts) - 1]['post_timestamp'] : PHP_INT_MAX;

		if (count($posts) == $this->config['posts_per_page'] + 1) {
			array_pop($posts);
		} else {
			$post_max_timestamp = PHP_INT_MAX;
		}

		if (!$start) $post_min_timestamp = 0;

		$history_type = $this->get_history_type();
		$sql = 'SELECT h.*, u.user_id, u.username, u.user_colour, r.rank_title
			FROM ' . $this->tables['trackers_history'] . ' h
			INNER JOIN ' . $this->tables['users'] . ' u ON h.user_id = u.user_id
			LEFT JOIN ' . $this->tables['ranks'] . ' r ON r.rank_id = u.user_rank
			WHERE h.ticket_id = ' . (int) $ticket['ticket_id'] . '
				AND h.history_timestamp < ' . (int) $post_max_timestamp . '
				AND h.history_timestamp >= ' . (int) $post_min_timestamp . '
				AND h.history_type <= ' . $history_type . '
			ORDER BY h.history_timestamp ASC';
		
		$history_result = $this->db->sql_query($sql);
		$history_entries = [];
		while ($history_data = $this->db->sql_fetchrow($history_result)) {
			$history_entries[] = $history_data;
		}
		$this->db->sql_freeresult($history_result);

		$p = $h = null;
		while (count($posts) || count($history_entries) || $p || $h) {
			if (!$p) $p = array_shift($posts);
			if (!$h) $h = array_shift($history_entries);

			if ($p && (!$h || ($p['post_timestamp'] <= $h['history_timestamp']))) {
				$post_text = generate_text_for_display($p['post_text'], $p['bbcode_uid'], $p['bbcode_bitfield'], $p['bbcode_flags']);
				
				$this->template->assign_block_vars('ticket_posts', [
					'S_TYPE'    => 'POST',
					'S_PRIVATE' => (bool) $p['post_private'],
					'ID'        => $p['post_id'],
					'TEXT'      => censor_text($post_text),
					'USER'      => get_username_string('full', $p['user_id'], $p['username'], $p['user_colour']),
					'USER_RANK' => $p['rank_title'],
					'TIMESTAMP' => $this->user->format_date($p['post_timestamp']),
					'U_EDIT'    => $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'edit', 'post' => (int) $p['post_id']]),
					'U_DELETE'  => $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => 'delete', 'post' => (int) $p['post_id']]),
				]);
				$p = null;
			} else if ($h) {
				$this->template->assign_block_vars('ticket_posts', [
					'S_TYPE'    => 'HISTORY',
					'ID'        => $h['history_id'],
					'USER'      => get_username_string('full', $h['user_id'], $h['username'], $h['user_colour']),
					'USER_RANK' => $h['rank_title'],
					'TEXT'      => censor_text($h['history_text']),
					'TIMESTAMP' => $this->user->format_date($h['history_timestamp']),
				]);
				$h = null;
			}
		}
	}

	public function get_history_type($project_id = 0)
	{
		if (empty($this->user->data) || empty($this->user->data['is_registered']) || !empty($this->user->data['is_bot'])) {
			return constants::TYPE_PUBLIC;
		}
		if ($this->is_team_user()) {
			return constants::TYPE_TEAM;
		}
		return constants::TYPE_PUBLIC;
	}

	public function generate_stats($tpl_block_var, $timespan_start = -1, $timespan_end = -1, $tracker_id = 0)
	{
		$projects = $this->get_projects($tracker_id);

		if (empty($projects))
		{
			throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_STATS'));
		}

		$project_stats = [];
		$project_stats[0]['tickets_open'] = 0;
		$project_stats[0]['tickets_closed'] = 0;

		if ($timespan_end > 0)
		{
			$timespan_end = ($timespan_end == 0) ? time() : $timespan_end;
			$timespan_end = mktime(0, 0, 0, date('n', $timespan_end) + 1, 1, date('Y', $timespan_end));
			$sql_timespan = 'AND t.timestamp_created BETWEEN ' . (int) $timespan_start . ' AND ' . (int) $timespan_end;
		}
		else
		{
			$sql_timespan = '';
		}

		// Open tickets
		$sql = 'SELECT t.project_id, COUNT(t.ticket_id) AS tickets_open, SUM(t.ticket_private) AS tickets_private
			FROM (' . $this->tables['trackers_ticket'] . ' t, ' . $this->tables['trackers_status'] . ' s)
			WHERE s.status_id = t.status_id
				AND s.ticket_closed = 0
				AND ' . $this->db->sql_in_set('t.project_id', array_keys($projects)) . '
				' . $sql_timespan . '
			GROUP BY t.project_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$this->is_team_user($row['project_id'])) {
				$row['tickets_open'] -= $row['tickets_private'];
			}
			$project_stats[0]['tickets_open'] += $row['tickets_open'];
			$project_stats[$row['project_id']]['tickets_open'] = $row['tickets_open'];
		}
		$this->db->sql_freeresult($result);

		// Closed tickets
		$sql = 'SELECT t.project_id, COUNT(t.ticket_id) AS tickets_closed, SUM(t.ticket_private) AS tickets_private
			FROM (' . $this->tables['trackers_ticket'] . ' t, ' . $this->tables['trackers_status'] . ' s)
			WHERE s.status_id = t.status_id
				AND s.ticket_closed = 1
				AND ' . $this->db->sql_in_set('t.project_id', array_keys($projects)) . '
				' . $sql_timespan . '
			GROUP BY t.project_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$this->is_team_user($row['project_id'])) {
				$row['tickets_closed'] -= $row['tickets_private'];
			}
			$project_stats[0]['tickets_closed'] += $row['tickets_closed'];
			$project_stats[$row['project_id']]['tickets_closed'] = $row['tickets_closed'];
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_block_vars($tpl_block_var, [
			'NAME'      => $this->language->lang('PROJECTS_ALL'),
			'OPEN'      => $project_stats[0]['tickets_open'],
			'CLOSED'    => $project_stats[0]['tickets_closed'],
		]);

		foreach ($projects as $project_id => $project_data)
		{
			$tickets_open = (isset($project_stats[$project_id]['tickets_open'])) ? $project_stats[$project_id]['tickets_open'] : 0;
			$tickets_closed = (isset($project_stats[$project_id]['tickets_closed'])) ? $project_stats[$project_id]['tickets_closed'] : 0;

			$this->template->assign_block_vars($tpl_block_var, [
				'U_PROJECT' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]),
				'NAME'      => $project_data['project_name'],
				'OPEN'      => $tickets_open,
				'CLOSED'    => $tickets_closed,
			]);
		}
	}

	public function set_status($ticket_id, $status_id)
	{
		$sql = 'SELECT status_name FROM ' . $this->tables['trackers_status'] . ' st LEFT JOIN ' . $this->tables['trackers_ticket'] . ' t ON t.status_id = st.status_id WHERE t.ticket_id = ' . (int) $ticket_id;
		$result = $this->db->sql_query($sql);
		$old_status_name = (string) $this->db->sql_fetchfield('status_name');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT status_name FROM ' . $this->tables['trackers_status'] . ' WHERE status_id = ' . (int) $status_id;
		$result = $this->db->sql_query($sql);
		$new_status_name = (string) $this->db->sql_fetchfield('status_name');
		$this->db->sql_freeresult($result);

		$sql = 'UPDATE ' . $this->tables['trackers_ticket'] . ' SET status_id = ' . (int) $status_id . ' WHERE ticket_id = ' . (int) $ticket_id;
		$this->db->sql_query($sql);

		$this->add_history($this->language->lang('CHANGED_STATUS', $old_status_name, $new_status_name), $ticket_id, $this->user->data['user_id']);
	}

	public function set_severity($ticket_id, $severity_id)
	{
		$sql = 'UPDATE ' . $this->tables['trackers_ticket'] . ' SET severity_id = ' . (int) $severity_id . ' WHERE ticket_id = ' . (int) $ticket_id;
		$this->db->sql_query($sql);

		$this->add_history($this->language->lang('CHANGED_SEVERITY'), $ticket_id, $this->user->data['user_id']);
	}

	public function can_report_private()
	{
		if ($this->user->data['user_id'] != ANONYMOUS) {
			if ($this->auth->acl_get('a_') || $this->auth->acl_getf_global('m_')) return true;
		}
		if ($this->is_team_user()) return true;
		return false;
	}

	public function can_set_severity()
	{
		if ($this->is_team_user() || $this->auth->acl_getf_global('m_')) return true;
		return false;
	}

	public function get_group_data($id)
	{
		$group_helper = $this->container->get('group_helper');
		$sql = 'SELECT * FROM ' . $this->tables['groups'] . ' WHERE group_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$group_data = '';
		while ($row = $this->db->sql_fetchrow($result)) {
			$group_name = $group_helper->get_name($row['group_name']);
			$group_data = '<span style="font-weight: bold;">' . $group_name . '</span>';
		}
		$this->db->sql_freeresult($result);
		return $group_data;
	}

	public function get_user_data($id)
	{
		$sql = 'SELECT username, user_colour FROM ' . $this->tables['users'] . ' WHERE user_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$user_data = '';
		while ($row = $this->db->sql_fetchrow($result)) {
			$user_data = get_username_string('full', $id, $row['username'], $row['user_colour']);
		}
		$this->db->sql_freeresult($result);
		return $user_data;
	}

	public function is_team_user($project_id = 0, $user_id = 0)
	{
		if (!$user_id) {
			$user_id = (int) $this->user->data['user_id'];
			if ($this->user->data['is_bot'] || $user_id == ANONYMOUS) return false;
		}
		if ($this->auth->acl_get('a_')) return true;

		return in_array((int) $user_id, $this->get_team_users($project_id));
	}

	public function get_team_users($project_id = 0)
	{
		$sql_where = '';
		$users = $auth_groups = [];

		if ($project_id > 0)
		{
			$sql_where = ' AND pa.project_id = ' . (int) $project_id;
		}

		$sql = 'SELECT u.user_id
			FROM ' . $this->tables['trackers_project_auth'] . ' pa, ' . $this->tables['users'] . ' u
			WHERE u.user_id = pa.user_id' . $sql_where;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$users[] = (int) $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		$sql = 'SELECT group_id FROM ' . $this->tables['trackers_project_auth'];
		if ($project_id > 0) $sql .= ' WHERE project_id = ' . (int) $project_id;

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$auth_groups[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($auth_groups))
		{
			$sql = 'SELECT u.user_id FROM ' . $this->tables['users'] . ' u, ' . $this->tables['user_group'] . ' ug
				WHERE u.user_id = ug.user_id AND ug.user_pending = 0 AND ' . $this->db->sql_in_set('ug.group_id', $auth_groups) . '
				GROUP BY u.user_id';
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result)) $users[] = (int) $row['user_id'];
			$this->db->sql_freeresult($result);
		}

		return array_unique($users);
	}

	public function add_history($history_text, $ticket_id, $user_id = 0)
	{
		$user_id = ($user_id === 0) ? (int) $this->user->data['user_id'] : (int) $user_id;

		$sql_data = [
			'ticket_id'         => (int) $ticket_id,
			'user_id'           => (int) $user_id,
			'history_text'      => (string) $history_text,
			'history_timestamp' => time(),
			'history_type'      => 0,
		];

		$this->db->sql_query('INSERT INTO ' . $this->tables['trackers_history'] . ' ' . $this->db->sql_build_array('INSERT', $sql_data));
	}

	public function generate_navlinks($navlinks)
	{
		if ($navlinks) {
			foreach ($navlinks as $navlink) {
				$this->template->assign_block_vars('navlinks', [
					'FORUM_NAME'    => $navlink['FORUM_NAME'],
					'U_VIEW_FORUM'  => $navlink['U_VIEW_FORUM'],
				]);
			}
		}
	}
	
	public function set_assignee($ticket_id, $user_id)
	{
		$ticket_id = (int) $ticket_id;
		$user_id   = (int) $user_id;

		// 1. Obtener datos del ticket para la notificación
		$sql = 'SELECT ticket_id, project_id, ticket_title FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . (int) $ticket_id;
		$result = $this->db->sql_query($sql);
		$ticket_data = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$ticket_data) return;

		// 2. Actualizar el asignado en la DB
		$sql = 'UPDATE ' . $this->tables['trackers_ticket'] . ' SET assigned_user = ' . $user_id . ' WHERE ticket_id = ' . (int) $ticket_id;
		$this->db->sql_query($sql);

		$assigned_name = $this->language->lang('UNASSIGNED');
		if ($user_id > 0) {
			$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $user_id;
			$result = $this->db->sql_query($sql);
			$assigned_name = (string) $this->db->sql_fetchfield('username');
			$this->db->sql_freeresult($result);

			// 3. DISPARAR NOTIFICACIÓN
			$ticket_data['assigned_user'] = $user_id;
			$ticket_data['user_from']     = (int) $this->user->data['user_id'];

			$notification_manager = $this->container->get('notification_manager');
			$notification_manager->add_notifications('nextgen.trackers.notification.type.ticket_assigned', $ticket_data);
		}

		$history_entry = sprintf($this->language->lang('TICKET_ASSIGNED_TO'), $assigned_name);
		$this->add_history($history_entry, $ticket_id);
	}
	
/**
 * Envía notificaciones cuando se publica una respuesta en un ticket
 *
 * @param array $ticket_data Datos del ticket original
 * @param array $post_data   Datos del nuevo post/comentario
 * @return void
 */
public function notify_reply($ticket_data, $post_data)
{
    $notification_manager = $this->container->get('notification_manager');

    $notification_data = [
        'ticket_id'     => (int) $ticket_data['ticket_id'],
        'project_id'    => (int) $ticket_data['project_id'],
        'ticket_title'  => $ticket_data['ticket_title'],
        'ticket_author' => (int) $ticket_data['user_id'],
        'assigned_user' => (int) $ticket_data['assigned_user'],
        'user_from'     => (int) $this->user->data['user_id'],
    ];

    $notification_manager->add_notifications('nextgen.trackers.notification.type.ticket_reply', $notification_data);
}
}