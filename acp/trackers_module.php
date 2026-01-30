<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\acp;

class trackers_module
{
	public $u_action;

	public function main($id, $mode)
	{
		global $request, $template, $user, $db, $table_prefix;

		$user->add_lang_ext('nextgen/trackers', 'common');

		$this->page_title = $user->lang('ACP_TRACKERS_TITLE');
		$this->tpl_name = 'acp_trackers_body';
		$this->u_action = append_sid($this->u_action);

		$tables = [
			'projects'   => $table_prefix . 'trackers_project',
			'trackers'   => $table_prefix . 'trackers_tracker',
			'severities' => $table_prefix . 'trackers_severity',
			'statuses'   => $table_prefix . 'trackers_status',
			'components' => $table_prefix . 'trackers_component',
			'relations'  => $table_prefix . 'trackers_relations', 
		];

		add_form_key('acp_trackers');

		switch ($mode)
		{
			case 'settings':
				$this->manage_settings();
			break;

			case 'projects':
				$this->manage_projects($tables['projects'], $tables['trackers']);
			break;

			case 'statuses':
				$this->manage_items($tables['statuses'], 'status', $tables);
			break;

			case 'severities':
				$this->manage_items($tables['severities'], 'severity', $tables);
			break;

			case 'components':
				$this->manage_items($tables['components'], 'component', $tables);
			break;
		}
	}

	protected function manage_projects($project_table, $tracker_table)
	{
		global $template, $request, $user, $db;

		$action = $request->variable('action', '');
		$project_id = $request->variable('id', 0);

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);

			$project_type = $request->variable('project_type', 0);
			$sql_ary = [
				'project_name'        => $request->variable('project_name', '', true),
				'project_description' => $request->variable('project_desc', '', true),
				'project_note'        => $request->variable('project_note', '', true),
				'parent_id'           => $request->variable('parent_id', 0),
				'project_type'        => $project_type,
				'tracker_id'          => ($project_type == 1) ? $request->variable('tracker_id', 0) : 0,
				'project_private'     => $request->variable('project_private', 0),
				'project_active'      => 1,
			];

			if ($project_id)
			{
				$db->sql_query('UPDATE ' . $project_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE project_id = ' . (int) $project_id);
			}
			else
			{
				$db->sql_query('INSERT INTO ' . $project_table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
			}
			trigger_error($user->lang('PROJECT_UPDATED') . adm_back_link($this->u_action));
		}

		if ($request->is_set_post('add') || $action == 'edit')
		{
			$row = ['project_name' => '', 'project_description' => '', 'project_note' => '', 'parent_id' => 0, 'project_type' => 0, 'tracker_id' => 0, 'project_private' => 0];
			if ($action == 'edit' && $project_id)
			{
				$result = $db->sql_query('SELECT * FROM ' . $project_table . ' WHERE project_id = ' . (int) $project_id);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
			}

			$sql = 'SELECT project_id, project_name FROM ' . $project_table . ' WHERE project_type = 0 ' . ($project_id ? ' AND project_id <> ' . (int) $project_id : '') . ' ORDER BY project_name ASC';
			$result = $db->sql_query($sql);
			$has_parents = false;
			while ($p_row = $db->sql_fetchrow($result))
			{
				$has_parents = true;
				$template->assign_block_vars('parent_options', [
					'ID'       => $p_row['project_id'],
					'NAME'     => $p_row['project_name'],
					'S_SELECT' => ($p_row['project_id'] == $row['parent_id']),
				]);
			}
			$db->sql_freeresult($result);

			$result = $db->sql_query('SELECT tracker_id, tracker_name FROM ' . $tracker_table . ' ORDER BY tracker_name ASC');
			while ($t_row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('trackers', [
					'ID'       => $t_row['tracker_id'],
					'NAME'     => $t_row['tracker_name'],
					'S_SELECT' => ($t_row['tracker_id'] == $row['tracker_id']),
				]);
			}
			$db->sql_freeresult($result);

			$template->assign_vars([
				'S_EDIT_PROJECT'    => true,
				'L_TITLE'           => ($action == 'edit') ? $user->lang('EDIT_PROJECT') : $user->lang('ADD_PROJECT'),
				'PROJECT_NAME'      => $row['project_name'],
				'PROJECT_DESC'      => $row['project_description'],
				'PROJECT_NOTE'      => $row['project_note'],
				'S_HAS_PARENTS'     => $has_parents,
				'S_TYPE_CAT'        => ($row['project_type'] == 0),
				'S_TYPE_TRACKER'    => ($row['project_type'] == 1),
				'S_PROJECT_PRIVATE' => $row['project_private'],
				'U_BACK'            => $this->u_action,
			]);
			return;
		}

		$sql = 'SELECT * FROM ' . $project_table . ' ORDER BY parent_id ASC, project_name ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('projects', [
				'NAME'     => ($row['parent_id'] > 0 ? '&nbsp;&nbsp;&nbsp;» ' : '') . $row['project_name'],
				'TYPE'     => ($row['project_type'] == 0) ? $user->lang('CATEGORY') : $user->lang('TRACKER'),
				'U_EDIT'   => $this->u_action . '&amp;action=edit&amp;id=' . (int) $row['project_id'],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id=' . (int) $row['project_id'],
			]);
		}
		$db->sql_freeresult($result);

		$template->assign_vars(['S_MODE_PROJECTS' => true, 'U_ACTION' => $this->u_action]);
	}

	protected function manage_items($table, $type, $tables)
	{
		global $template, $request, $user, $db;

		$action = $request->variable('action', '');
		$id = $request->variable('id', 0);
		$id_field = $type . '_id';
		$name_field = $type . '_name';
		$colour_field = $type . '_colour';

		if ($action == 'delete' && $id)
		{
			if (confirm_box(true))
			{
				$db->sql_query("DELETE FROM $table WHERE $id_field = " . (int) $id);
				$db->sql_query("DELETE FROM " . $tables['relations'] . " WHERE item_id = " . (int) $id . " AND item_type = '$type'");
				trigger_error($user->lang('ITEM_DELETED') . adm_back_link($this->u_action));
			}
			else
			{
				confirm_box(false, $user->lang('CONFIRM_DELETE'), build_hidden_fields(['id' => $id, 'action' => 'delete']));
			}
		}

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

			$sql_ary = [$name_field => $request->variable('name', '', true)];
			
			if ($type != 'component') 
			{ 
				// Manejo del color picker: eliminamos el '#' si viene del input type="color"
				$raw_colour = $request->variable('colour', 'CCCCCC');
				$sql_ary[$colour_field] = str_replace('#', '', $raw_colour);
			}

			if ($type == 'status')
			{
				$sql_ary['ticket_closed'] = $request->variable('ticket_closed', 0);
				$sql_ary['ticket_new'] = $request->variable('ticket_new', 0);
			}

			if ($id)
			{
				$db->sql_query("UPDATE $table SET " . $db->sql_build_array('UPDATE', $sql_ary) . " WHERE $id_field = " . (int) $id);
			}
			else
			{
				$db->sql_query("INSERT INTO $table " . $db->sql_build_array('INSERT', $sql_ary));
				$id = $db->sql_nextid();
			}

			$project_ids = $request->variable('project_ids', [0 => 0]);
			$db->sql_query("DELETE FROM " . $tables['relations'] . " WHERE item_id = " . (int) $id . " AND item_type = '$type'");
			foreach ($project_ids as $p_id)
			{
				if ($p_id > 0)
				{
					$db->sql_query("INSERT INTO " . $tables['relations'] . " " . $db->sql_build_array('INSERT', [
						'item_id' => (int) $id, 'project_id' => (int) $p_id, 'item_type' => $type
					]));
				}
			}
			trigger_error($user->lang('ITEM_UPDATED') . adm_back_link($this->u_action));
		}

		if ($request->is_set_post('add') || $action == 'edit')
		{
			$row = [$name_field => '', 'ticket_closed' => 0, 'ticket_new' => 0];
			$assigned_projects = [];

			if ($id)
			{
				$result = $db->sql_query("SELECT * FROM $table WHERE $id_field = " . (int) $id);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				$result = $db->sql_query("SELECT project_id FROM " . $tables['relations'] . " WHERE item_id = " . (int) $id . " AND item_type = '$type'");
				while ($p_row = $db->sql_fetchrow($result)) $assigned_projects[] = $p_row['project_id'];
				$db->sql_freeresult($result);
			}

			$sql = 'SELECT project_id, project_name FROM ' . $tables['projects'] . ' WHERE project_type = 1 ORDER BY project_name ASC';
			$result = $db->sql_query($sql);
			while ($p_row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('projects_list', [
					'ID' => $p_row['project_id'],
					'NAME' => $p_row['project_name'],
					'S_SELECTED' => in_array($p_row['project_id'], $assigned_projects),
				]);
			}
			$db->sql_freeresult($result);

			$template->assign_vars([
				'S_EDIT_' . strtoupper($type) => true,
				'L_TITLE'     => ($id) ? $user->lang('EDIT') : $user->lang('ADD'),
				'ITEM_NAME' => $row[$name_field],
				'ITEM_COLOUR' => $row[$colour_field] ?? 'CCCCCC', // Valor sin # para la vista
				'S_CLOSED' => $row['ticket_closed'] ?? 0,
				'S_NEW' => $row['ticket_new'] ?? 0,
				'U_BACK' => $this->u_action,
			]);
			return;
		}

		$result = $db->sql_query("SELECT * FROM $table ORDER BY $name_field ASC");
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items', [
				'NAME' => $row[$name_field],
				// Enviamos el color a la lista (verificamos si existe la columna)
				'COLOUR' => isset($row[$colour_field]) ? $row[$colour_field] : '',
				'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id=' . $row[$id_field],
				'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id=' . $row[$id_field],
			]);
		}
		$db->sql_freeresult($result);

		$mode_var = 'S_MODE_' . strtoupper($type) . 'S';
		if ($type == 'status') $mode_var = 'S_MODE_STATUSES';
		if ($type == 'severity') $mode_var = 'S_MODE_SEVERITIES';
		
		$template->assign_vars([
			$mode_var => true, 
			'L_TITLE' => $user->lang('ACP_TRACKERS_' . strtoupper($type) . 'S'), 
			'U_ACTION' => $this->u_action
		]);
	}

	protected function manage_settings()
	{
		global $template, $user, $config, $request;
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);
			$config->set('trackers_enable', $request->variable('trackers_enable', 0));
			trigger_error($user->lang('ITEM_UPDATED') . adm_back_link($this->u_action));
		}
		$template->assign_vars(['S_MODE_SETTINGS' => true, 'L_TITLE' => $user->lang('ACP_TRACKERS_SETTINGS'), 'TRACKERS_ENABLE' => $config['trackers_enable'] ?? 1, 'U_ACTION' => $this->u_action]);
	}
}