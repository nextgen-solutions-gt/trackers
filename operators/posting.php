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

class posting
{
	protected $auth;
	protected $container;
	protected $language;
	protected $helper;
	protected $request;
	protected $template;
	protected $user;
	protected $files_upload;
	protected $db;
	protected $root_path;
	protected $php_ext;	
	protected $tables;
	protected $config;

	public function __construct(\phpbb\auth\auth $auth, ContainerInterface $container, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\files\upload $files_upload, $root_path, $php_ext, $table_prefix)
	{
		$this->auth = $auth;
		$this->container = $container;
		$this->language = $language;
		$this->helper = $helper;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->files_upload = $files_upload;
		$this->root_path = $root_path;
        $this->php_ext = $php_ext;
		$this->db = $container->get('dbal.conn');
		$this->config = $container->get('config');


		$this->tables = [
			'trackers_tracker'      => $table_prefix . 'trackers_tracker',
			'trackers_project'      => $table_prefix . 'trackers_project',
			'trackers_ticket'       => $table_prefix . 'trackers_ticket',
			'trackers_post'         => $table_prefix . 'trackers_post',
			'trackers_severity'     => $table_prefix . 'trackers_severity',
			'trackers_status'       => $table_prefix . 'trackers_status',
			'trackers_component'    => $table_prefix . 'trackers_component',
			'trackers_relations'    => $table_prefix . 'trackers_relations',
			'trackers_attachments'  => $table_prefix . 'trackers_attachments',
			'smilies'               => $table_prefix . 'smilies',
		];
	}

	public function display($tracker_id = 0, $project_id = 0, $ticket_id = 0)
	{
		$mode = $this->request->variable('mode', 'post');
		
		$post_id = $this->request->variable('tracker_edit_id', 0);
		if (!$post_id) $post_id = $this->request->variable('post', 0);

		$tracker_id = ($tracker_id) ? $tracker_id : $this->request->variable('t', 0);
		$project_id = ($project_id) ? $project_id : $this->request->variable('p', 0);
		$ticket_id  = ($ticket_id)  ? $ticket_id  : $this->request->variable('ticket', 0);

		$functions = $this->container->get('nextgen.trackers.functions');

		if ($this->request->is_set_post('cancel'))
		{
			$redirect_url = $this->helper->route('nextgen_trackers_controller', [
				'page'   => 'viewticket',
				't'      => (int) $tracker_id,
				'p'      => (int) $project_id,
				'ticket' => (int) $ticket_id
			]);
			redirect($redirect_url);
		}        

		// --- LÓGICA DE BORRADO SEGURO ---
		if ($mode == 'delete')
		{
			$post_author_id = 0;
			if ($post_id)
			{
				$sql = 'SELECT p.ticket_id, t.project_id, pr.tracker_id, p.user_id 
						FROM ' . $this->tables['trackers_post'] . ' p
						JOIN ' . $this->tables['trackers_ticket'] . ' t ON p.ticket_id = t.ticket_id
						JOIN ' . $this->tables['trackers_project'] . ' pr ON t.project_id = pr.project_id
						WHERE p.post_id = ' . (int) $post_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				if ($row)
				{
					$ticket_id = (int) $row['ticket_id'];
					$project_id = (int) $row['project_id'];
					$tracker_id = (int) $row['tracker_id'];
					$post_author_id = (int) $row['user_id'];
				}
			}

			// SEGURIDAD: Identidad vs Moderación
			$is_author = ($this->user->data['user_id'] != ANONYMOUS && $this->user->data['user_id'] == $post_author_id);
			$is_moderator = ($this->auth->acl_get('a_trackers') || $this->auth->acl_getf_global('m_') || $this->auth->acl_get('m_tracker_delete'));
			$is_team_user = $functions->is_team_user($project_id);

			if (!$is_moderator && !$is_team_user)
			{
				if (!$is_author || !$this->auth->acl_get('u_tracker_delete'))
				{
					trigger_error('NOT_AUTHORISED');
				}
			}

			if (confirm_box(true))
			{
				$sql = 'SELECT post_id FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . (int) $ticket_id;
				$result = $this->db->sql_query($sql);
				$main_post_id = (int) $this->db->sql_fetchfield('post_id');
				$this->db->sql_freeresult($result);

				if ($post_id == $main_post_id || (!$post_id && $ticket_id))
				{
					$this->delete_ticket_attachments($ticket_id);
					$this->db->sql_query('DELETE FROM ' . $this->tables['trackers_post'] . ' WHERE ticket_id = ' . (int) $ticket_id);
					$this->db->sql_query('DELETE FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . (int) $ticket_id);
					
					$redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project_id]);
					meta_refresh(3, $redirect);
					trigger_error($this->language->lang('TICKET_DELETED_SUCCESS'));
				}
				else
				{
					$this->delete_post_attachments($post_id);
					$this->db->sql_query('DELETE FROM ' . $this->tables['trackers_post'] . ' WHERE post_id = ' . (int) $post_id);
					
					$redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket' => (int) $ticket_id]);
					meta_refresh(3, $redirect);
					trigger_error($this->language->lang('POST_DELETED_SUCCESS'));
				}
			}
			else
			{
				$s_hidden_fields = build_hidden_fields([
					't'      => (int) $tracker_id,
					'p'      => (int) $project_id,
					'ticket' => (int) $ticket_id,
					'post'   => (int) $post_id,
					'mode'   => 'delete',
				]);
				confirm_box(false, $this->language->lang('CONFIRM_DELETE'), $s_hidden_fields);
			}
		}

		// --- LÓGICA DE GUARDADO ---
		if ($this->request->is_set_post('post'))
		{
			if ($mode == 'post' && !$ticket_id && !$this->auth->acl_get('u_tracker_create'))
			{
				trigger_error('NOT_AUTHORISED');
			}
			elseif (($mode == 'reply' || ($mode == 'post' && $ticket_id)) && !$this->auth->acl_get('u_tracker_reply'))
			{
				trigger_error('NOT_AUTHORISED');
			}

			$message = $this->request->variable('message', '', true);
			$subject = $this->request->variable('subject', '', true);
			$private = $this->request->variable('private', 0);
			$severity = $this->request->variable('severity', 0);
			$component = $this->request->variable('component_id', 0);

			$uid = $bitfield = $flags = '';
			$allow_bbcode = $allow_urls = $allow_smilies = true;
			generate_text_for_storage($message, $uid, $bitfield, $flags, $allow_bbcode, $allow_urls, $allow_smilies, $allow_bbcode, $allow_bbcode, true, $allow_urls);

			if ($mode == 'post' && !$ticket_id)
			{
				$sql = 'SELECT s.status_id FROM ' . $this->tables['trackers_status'] . ' s
						INNER JOIN ' . $this->tables['trackers_relations'] . ' r ON s.status_id = r.item_id
						WHERE r.project_id = ' . (int) $project_id . " AND r.item_type = 'status' AND s.ticket_new = 1";
				$result = $this->db->sql_query_limit($sql, 1);
				$status_id = (int) $this->db->sql_fetchfield('status_id');
				$this->db->sql_freeresult($result);

				if (!$status_id)
				{
					$sql = 'SELECT item_id FROM ' . $this->tables['trackers_relations'] . " WHERE project_id = " . (int) $project_id . " AND item_type = 'status' ORDER BY item_id ASC";
					$result = $this->db->sql_query_limit($sql, 1);
					$status_id = (int) $this->db->sql_fetchfield('item_id');
					$this->db->sql_freeresult($result);
				}

				$sql_ary = [
					'project_id'        => (int) $project_id,
					'user_id'           => (int) $this->user->data['user_id'],
					'reporter_ip'       => $this->user->ip,
					'status_id'         => (int) $status_id,
					'severity_id'       => (int) $severity,
					'component_id'      => (int) $component,
					'ticket_private'    => (int) $private,
					'ticket_title'      => (string) $subject,
					'timestamp_created' => (int) time(),
				];
				$this->db->sql_query('INSERT INTO ' . $this->tables['trackers_ticket'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
				$ticket_id = $this->db->sql_nextid();

				$sql_ary_post = [
					'ticket_id'       => (int) $ticket_id,
					'user_id'         => (int) $this->user->data['user_id'],
					'post_private'    => (int) $private,
					'ticket_title'    => (string) $subject,
					'post_text'       => (string) $message,
					'post_timestamp'  => (int) time(),
					'bbcode_uid'      => (string) $uid,
					'bbcode_bitfield' => (string) $bitfield,
					'bbcode_flags'    => (int) $flags,
					'post_ip'         => $this->user->ip,
				];
				$this->db->sql_query('INSERT INTO ' . $this->tables['trackers_post'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary_post));
				$new_post_id = $this->db->sql_nextid();
				$this->db->sql_query('UPDATE ' . $this->tables['trackers_ticket'] . ' SET post_id = ' . (int) $new_post_id . ' WHERE ticket_id = ' . (int) $ticket_id);
				
				$this->process_attachments($ticket_id, $new_post_id);
			}
			elseif ($mode == 'reply' || ($mode == 'post' && $ticket_id))
			{
				$sql_ary = [
					'ticket_id'       => (int) $ticket_id,
					'user_id'         => (int) $this->user->data['user_id'],
					'post_private'    => (int) $private,
					'post_text'       => (string) $message,
					'post_timestamp'  => (int) time(),
					'bbcode_uid'      => (string) $uid,
					'bbcode_bitfield' => (string) $bitfield,
					'bbcode_flags'    => (int) $flags,
					'post_ip'         => $this->user->ip,
				];
				$this->db->sql_query('INSERT INTO ' . $this->tables['trackers_post'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
				$new_post_id = $this->db->sql_nextid();
				$this->db->sql_query('UPDATE ' . $this->tables['trackers_ticket'] . ' SET user_last_id = ' . (int) $this->user->data['user_id'] . ' WHERE ticket_id = ' . (int) $ticket_id);

				$ticket_data = $functions->get_ticket_data($ticket_id);
				$functions->notify_reply($ticket_data, $sql_ary);
				
				$this->process_attachments($ticket_id, $new_post_id);
			}
			elseif ($mode == 'edit' && $post_id)
			{
				// SEGURIDAD: Propiedad Estricta
				$sql = 'SELECT user_id, ticket_id FROM ' . $this->tables['trackers_post'] . ' WHERE post_id = ' . (int) $post_id;
				$result = $this->db->sql_query($sql);
				$row_edit = $this->db->sql_fetchrow($result);
				$this->db->sql_freeresult($result);

				$post_author_id = (int) $row_edit['user_id'];
				$tid = (int) $row_edit['ticket_id'];

				$is_author = ($this->user->data['user_id'] != ANONYMOUS && $this->user->data['user_id'] == $post_author_id);
				$is_moderator = ($this->auth->acl_get('a_trackers') || $this->auth->acl_getf_global('m_') || $this->auth->acl_get('m_tracker_edit'));
				$is_team_user = $functions->is_team_user($project_id);
				
				if (!$is_moderator && !$is_team_user)
				{
					if (!$is_author || !$this->auth->acl_get('u_tracker_edit'))
					{
						 trigger_error('NOT_AUTHORISED');
					}
				}

				$sql_ary = [
					'post_text'       => (string) $message, 
					'post_private'    => (int) $private, 
					'bbcode_uid'      => (string) $uid, 
					'bbcode_bitfield' => (string) $bitfield, 
					'bbcode_flags'    => (int) $flags
				];
				$this->db->sql_query('UPDATE ' . $this->tables['trackers_post'] . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . ' WHERE post_id = ' . (int) $post_id);

				if ($tid)
				{
					$sql = 'SELECT post_id FROM ' . $this->tables['trackers_ticket'] . ' WHERE ticket_id = ' . $tid;
					$result = $this->db->sql_query($sql);
					$main_post_id_check = (int) $this->db->sql_fetchfield('post_id');
					$this->db->sql_freeresult($result);

					if ($main_post_id_check === (int) $post_id)
					{
						$sql_ary_ticket = [
							'ticket_title'   => (string) $subject,
							'severity_id'    => (int) $severity,
							'component_id'   => (int) $component,
							'ticket_private' => (int) $private,
						];
						$this->db->sql_query('UPDATE ' . $this->tables['trackers_ticket'] . ' SET ' . $this->db->sql_build_array('UPDATE', $sql_ary_ticket) . ' WHERE ticket_id = ' . $tid);
					}
				}
			}

			$this->recover_ids($ticket_id, $tracker_id, $project_id);
			$redirect = $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => (int) $tracker_id, 'p' => (int) $project_id, 'ticket' => (int) $ticket_id]);
			meta_refresh(3, $redirect);
			trigger_error($this->language->lang('POST_STORED_SUCCESS'));
		}

		// Lógica de Smilies
		$sql = 'SELECT * FROM ' . $this->tables['smilies'] . ' WHERE display_on_posting = 1 ORDER BY smiley_order';
		$result = $this->db->sql_query($sql);
		$board_url = generate_board_url() . '/';
		$shown_images = [];

		while ($row = $this->db->sql_fetchrow($result))
		{
			if (in_array($row['smiley_url'], $shown_images)) continue;
			$this->template->assign_block_vars('smiley', [
				'SMILEY_CODE'    => $row['code'],
				'A_SMILEY_CODE'  => addslashes($row['code']),
				'SMILEY_IMG'     => $board_url . $this->config['smilies_path'] . '/' . $row['smiley_url'],
				'SMILEY_WIDTH'   => $row['smiley_width'],
				'SMILEY_HEIGHT'  => $row['smiley_height'],
				'SMILEY_DESC'    => $row['emotion'],
			]);
			$shown_images[] = $row['smiley_url'];
		}
		$this->db->sql_freeresult($result);
		
		$current_message = $current_subject = '';
		$current_severity = $current_private = $current_component = 0;
		$is_first_post = false;

		if ($mode == 'edit' && $post_id)
		{
			$sql = 'SELECT p.*, t.ticket_title as t_title, t.severity_id, t.component_id, t.ticket_private as t_private, t.post_id as main_id, t.project_id, pr.tracker_id
					FROM ' . $this->tables['trackers_post'] . ' p
					INNER JOIN ' . $this->tables['trackers_ticket'] . ' t ON p.ticket_id = t.ticket_id
					INNER JOIN ' . $this->tables['trackers_project'] . ' pr ON t.project_id = pr.project_id
					WHERE p.post_id = ' . (int) $post_id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			if ($row)
			{
				$current_message = $row['post_text'];
				decode_message($current_message, $row['bbcode_uid']);
				$ticket_id = (int) $row['ticket_id'];
				$project_id = (int) $row['project_id'];
				$tracker_id = (int) $row['tracker_id'];
				$current_private = (int) $row['post_private'];
				if ((int) $row['main_id'] === (int) $post_id)
				{
					$is_first_post = true;
					$current_subject = $row['t_title'];
					$current_severity = (int) $row['severity_id'];
					$current_component = (int) $row['component_id'];
					$current_private = (int) $row['t_private'];
				}
			}
		}

		// Carga de Componentes
		$sql = 'SELECT c.component_id, c.component_name FROM ' . $this->tables['trackers_component'] . ' c
				INNER JOIN ' . $this->tables['trackers_relations'] . ' r ON c.component_id = r.item_id
				WHERE r.project_id = ' . (int) $project_id . " AND r.item_type = 'component' ORDER BY c.component_name ASC";
		$result = $this->db->sql_query($sql);
		$c_count = 0;
		while ($comp = $this->db->sql_fetchrow($result))
		{
			$c_count++;
			$this->template->assign_block_vars('components', [
				'ID'         => $comp['component_id'], 
				'NAME'       => $comp['component_name'],
				'S_SELECTED' => ($comp['component_id'] == $current_component)
			]);
		}
		$this->db->sql_freeresult($result);

		// Carga de Severidades
		$sql = 'SELECT s.severity_id, s.severity_name, s.severity_colour FROM ' . $this->tables['trackers_severity'] . ' s
				INNER JOIN ' . $this->tables['trackers_relations'] . ' r ON s.severity_id = r.item_id
				WHERE r.project_id = ' . (int) $project_id . " AND r.item_type = 'severity' ORDER BY s.severity_order ASC";
		$result = $this->db->sql_query($sql);
		$s_count = 0;
		while ($sev = $this->db->sql_fetchrow($result))
		{
			$s_count++;
			$this->template->assign_block_vars('severities', [
				'ID'     => $sev['severity_id'], 
				'NAME'   => $sev['severity_name'],
				'COLOUR' => $sev['severity_colour']
			]);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'TRACKER_ID'        => (int) $tracker_id,
			'PROJECT_ID'        => (int) $project_id,
			'TICKET_ID'         => (int) $ticket_id,
			'POST_ID'           => (int) $post_id,
			'MESSAGE'           => $current_message,
			'SUBJECT'           => $current_subject,
			'SEVERITY_ID'       => $current_severity,
			'COMPONENT_ID'      => $current_component,
			'S_PRIVATE'         => $current_private,
			'S_HAS_SEVERITIES'  => ($s_count > 0),
			'S_HAS_COMPONENTS'  => ($c_count > 0),
			'S_EDIT_POST'       => ($mode == 'edit'),
			'S_NEW_TICKET'      => ($mode == 'post' && !$ticket_id),
			'S_REPLY_TICKET'    => ($mode == 'reply' || ($mode == 'post' && $ticket_id)),
			'S_FIRST_POST'      => $is_first_post,
			'L_POSTING_TITLE'   => ($mode == 'edit') ? (($is_first_post) ? $this->language->lang('EDIT_TICKET') : $this->language->lang('EDIT_COMMENT')) : (($ticket_id) ? $this->language->lang('REPLY_TICKET') : $this->language->lang('NEW_TICKET')),
			'U_ACTION'          => $this->helper->route('nextgen_trackers_controller', ['page' => 'posting', 'mode' => $mode, 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id, 'post' => $post_id]),
			'U_CANCEL'             => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewticket', 't' => $tracker_id, 'p' => $project_id, 'ticket' => $ticket_id]),
			'S_BBCODE_ALLOWED'  => true,
			'S_SMILIES_ALLOWED' => true,
			'S_LINKS_ALLOWED'   => true,
			'S_CAN_ATTACH'      => $functions->can_user_attach($project_id),
			'MAX_ATTACH_SIZE'   => (int) $this->config['trackers_attach_max_size'],
			'ALLOWED_EXT'       => (string) $this->config['trackers_attach_extensions'],
			'U_BACK_TRACKER' => $this->helper->route('nextgen_trackers_controller', [
				'page' => 'viewproject', 
				't'    => (int) $tracker_id, 
				'p'    => (int) $project_id
			]),
		]);

		return $this->helper->render('posting_body.html', $this->language->lang('POSTING'));
	}

	protected function process_attachments($ticket_id, $post_id)
	{
		$functions = $this->container->get('nextgen.trackers.functions');
		if (!$functions->can_user_attach($this->request->variable('p', 0))) return;

		$destination = $functions->prepare_attach_path(); 
		if (!$destination) return;

		/**
		 * CORRECCIÓN: Revisamos tanto 'tracker_attachments' (Formulario normal)
		 * como 'fileupload' (Quick Reply).
		 */
		$form_name = ($this->request->file('tracker_attachments')) ? 'tracker_attachments' : 'fileupload';
		$upload_file = $this->request->file($form_name);

		if (!empty($upload_file['name']))
		{
			$upload_helper = $this->files_upload;
			$upload_helper->set_allowed_extensions(explode(',', strtolower($this->config['trackers_attach_extensions'])));
			
			$file = $upload_helper->handle_upload('files.types.form', $form_name);

			if (is_string($file))
			{
				$this->template->assign_var('ERROR', $this->language->lang($file));
				return;
			}

			if ($file->is_uploaded())
			{
				$file->clean_filename('unique', $this->user->data['user_id'] . '_');
				$real_name = $file->get('realname');

				$file->move_file($destination, false, $real_name);

				if (sizeof($file->error))
				{
					$error_msg = $file->error;
					$this->template->assign_var('ERROR', $this->language->lang(array_shift($error_msg), $error_msg));
					return;
				}

				$sql_ary = [
					'post_id'           => (int) $post_id,
					'ticket_id'         => (int) $ticket_id,
					'user_id'           => (int) $this->user->data['user_id'],
					'physical_filename' => (string) $real_name,
					'real_filename'     => (string) $file->get('uploadname'),
					'extension'         => (string) $file->get('extension'),
					'mimetype'          => (string) $file->get('mimetype'),
					'filesize'          => (int) $file->get('filesize'),
					'filetime'          => (int) time(),
				];

				$this->db->sql_query('INSERT INTO ' . $this->tables['trackers_attachments'] . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
			}
		}
	}

	protected function delete_ticket_attachments($ticket_id)
	{
		$functions = $this->container->get('nextgen.trackers.functions');
		
		$sql = 'SELECT attach_id FROM ' . $this->tables['trackers_attachments'] . ' WHERE ticket_id = ' . (int) $ticket_id;
		$result = $this->db->sql_query($sql);
		
		while ($row = $this->db->sql_fetchrow($result))
		{
			$functions->delete_attachment($row['attach_id']);
		}
		$this->db->sql_freeresult($result);
	}

	protected function delete_post_attachments($post_id)
	{
		$functions = $this->container->get('nextgen.trackers.functions');
		
		$sql = 'SELECT attach_id FROM ' . $this->tables['trackers_attachments'] . ' WHERE post_id = ' . (int) $post_id;
		$result = $this->db->sql_query($sql);
		
		while ($row = $this->db->sql_fetchrow($result))
		{
			$functions->delete_attachment($row['attach_id']);
		}
		$this->db->sql_freeresult($result);
	}

	private function recover_ids($ticket_id, &$tracker_id, &$project_id)
	{
		if (!$ticket_id) return;
		$sql = 'SELECT t.project_id, p.tracker_id FROM ' . $this->tables['trackers_ticket'] . ' t 
				JOIN ' . $this->tables['trackers_project'] . ' p ON t.project_id = p.project_id WHERE t.ticket_id = ' . (int) $ticket_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if ($row) { $project_id = (int) $row['project_id']; $tracker_id = (int) $row['tracker_id']; }
	}
}