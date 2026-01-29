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

        // Definición de tablas de forma limpia
        $tables = [
            'projects'   => $table_prefix . 'trackers_project',
            'trackers'   => $table_prefix . 'trackers_tracker',
            'severities' => $table_prefix . 'trackers_severity',
            'statuses'   => $table_prefix . 'trackers_status',
        ];

        switch ($mode)
        {
            case 'settings':
                $this->manage_settings();
            break;

            case 'projects':
                $this->manage_projects($tables['projects'], $tables['trackers']);
            break;

            case 'severities':
                $this->simple_manage_list($tables['severities'], 'severity_id', 'severity_name', 'S_MODE_SEVERITIES');
            break;

            case 'statuses':
                $this->simple_manage_list($tables['statuses'], 'status_id', 'status_name', 'S_MODE_STATUSES');
            break;
        }
    }

    protected function manage_settings()
    {
        global $template, $user, $config, $request;

        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('acp_trackers'))
            {
                trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
            }

            $config->set('trackers_enable', $request->variable('trackers_enable', 0));
            $config->set('trackers_items_per_page', $request->variable('trackers_items_per_page', 10));

            trigger_error($user->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
        }

        add_form_key('acp_trackers');

        $template->assign_vars([
            'S_MODE_SETTINGS'   => true,
            'L_TITLE'           => $user->lang('ACP_TRACKERS_SETTINGS'),
            'U_ACTION'          => $this->u_action,
            'TRACKERS_ENABLE'   => $config['trackers_enable'] ?? 1,
            'TRACKERS_PER_PAGE' => $config['trackers_items_per_page'] ?? 10,
        ]);
    }

    protected function manage_projects($project_table, $tracker_table)
    {
        global $template, $request, $user, $db;

        $action = $request->variable('action', '');
        $project_id = $request->variable('id', 0);

        // Eliminar
        if ($action == 'delete' && $project_id)
        {
            if (confirm_box(true))
            {
                $db->sql_query('DELETE FROM ' . $project_table . ' WHERE project_id = ' . (int) $project_id);
                trigger_error($user->lang('PROJECT_DELETED') . adm_back_link($this->u_action));
            }
            else
            {
                $s_hidden_fields = build_hidden_fields(['id' => $project_id, 'action' => 'delete']);
                confirm_box(false, $user->lang('CONFIRM_DELETE_PROJECT'), $s_hidden_fields);
            }
        }

        // Guardar/Editar
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('acp_trackers'))
            {
                trigger_error($user->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
            }

            // CORRECCIÓN: Se agregan todos los campos necesarios para evitar el error SQL 1364
            $sql_ary = [
                'project_name'        => $request->variable('project_name', '', true),
                'project_description' => $request->variable('project_desc', '', true),
                'project_note'        => $request->variable('project_note', '', true), // Se añade nota
                'project_private'     => $request->variable('project_private', 0),     // Se añade privacidad
                'tracker_id'          => $request->variable('tracker_id', 0),
            ];

            if ($project_id)
            {
                $db->sql_query('UPDATE ' . $project_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE project_id = ' . (int) $project_id);
                $msg = 'PROJECT_UPDATED';
            }
            else
            {
                // Al insertar, aseguramos que el proyecto nazca activo
                $sql_ary['project_active'] = 1;
                $db->sql_query('INSERT INTO ' . $project_table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
                $msg = 'PROJECT_ADDED';
            }
            trigger_error($user->lang($msg) . adm_back_link($this->u_action));
        }

        // Formulario
        if ($request->is_set_post('add') || $action == 'edit')
        {
            // Valores por defecto ampliados para evitar warnings
            $row = [
                'project_name' => '', 
                'project_description' => '', 
                'project_note' => '', 
                'project_private' => 0, 
                'tracker_id' => 0
            ];

            if ($action == 'edit' && $project_id)
            {
                $result = $db->sql_query('SELECT * FROM ' . $project_table . ' WHERE project_id = ' . (int) $project_id);
                $row_db = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);
                
                // Fusionamos los datos de la DB con los valores por defecto
                if ($row_db) {
                    $row = array_merge($row, $row_db);
                }
            }

            // Cargar select de trackers
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

            add_form_key('acp_trackers');
            $template->assign_vars([
                'S_EDIT_PROJECT'    => true,
                'L_TITLE'           => $user->lang(($action == 'edit') ? 'EDIT_PROJECT' : 'ADD_PROJECT'),
                'PROJECT_NAME'      => $row['project_name'],
                'PROJECT_DESC'      => $row['project_description'],
                'PROJECT_NOTE'      => $row['project_note'],    // Enviamos la nota al template
                'S_PROJECT_PRIVATE' => $row['project_private'], // Enviamos estado privado al template
                'U_BACK'            => $this->u_action,
            ]);
            return;
        }

        // Listado
        $sql = 'SELECT p.*, t.tracker_name 
                FROM ' . $project_table . ' p
                LEFT JOIN ' . $tracker_table . ' t ON p.tracker_id = t.tracker_id
                ORDER BY p.project_name ASC';
        $result = $db->sql_query($sql);
        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('projects', [
                'NAME'         => $row['project_name'],
                'TRACKER_NAME' => $row['tracker_name'] ?: '---',
                'U_EDIT'       => $this->u_action . '&amp;action=edit&amp;id=' . (int) $row['project_id'],
                'U_DELETE'     => $this->u_action . '&amp;action=delete&amp;id=' . (int) $row['project_id'],
            ]);
        }
        $db->sql_freeresult($result);

        $template->assign_vars([
            'S_MODE_PROJECTS' => true,
            'L_TITLE'         => $user->lang('ACP_TRACKERS_PROJECTS'),
            'U_ACTION'        => $this->u_action,
        ]);
    }

    protected function simple_manage_list($table, $id_field, $name_field, $tpl_var)
    {
        global $db, $request, $template, $user;

        $action = $request->variable('action', '');
        $id = $request->variable('id', 0);

        if ($action == 'delete' && $id)
        {
            if (confirm_box(true))
            {
                $db->sql_query('DELETE FROM ' . $table . ' WHERE ' . $id_field . ' = ' . (int) $id);
                trigger_error($user->lang('ITEM_DELETED') . adm_back_link($this->u_action));
            }
            else
            {
                confirm_box(false, $user->lang('CONFIRM_DELETE'), build_hidden_fields(['id' => $id, 'action' => 'delete']));
            }
        }

        if ($request->is_set_post('submit'))
        {
            $sql_ary = [$name_field => $request->variable('name', '', true)];
            if ($id)
            {
                $db->sql_query('UPDATE ' . $table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE ' . $id_field . ' = ' . (int) $id);
            }
            else
            {
                $db->sql_query('INSERT INTO ' . $table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
            }
            trigger_error($user->lang('ITEM_UPDATED') . adm_back_link($this->u_action));
        }

        if ($request->is_set_post('add') || $action == 'edit')
        {
            $name = '';
            if ($id)
            {
                $result = $db->sql_query('SELECT ' . $name_field . ' FROM ' . $table . ' WHERE ' . $id_field . ' = ' . (int) $id);
                $name = (string) $db->sql_fetchfield($name_field);
                $db->sql_freeresult($result);
            }

            $template->assign_vars([
                'S_EDIT_ITEM' => true,
                'ITEM_NAME'   => $name,
                'L_TITLE'     => $user->lang(($id) ? 'EDIT' : 'ADD'),
            ]);
            return;
        }

        $result = $db->sql_query('SELECT * FROM ' . $table . ' ORDER BY ' . $name_field . ' ASC');
        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('items', [
                'ID'       => $row[$id_field],
                'NAME'     => $row[$name_field],
                'U_EDIT'   => $this->u_action . '&amp;action=edit&amp;id=' . $row[$id_field],
                'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id=' . $row[$id_field],
            ]);
        }
        $db->sql_freeresult($result);

        $template->assign_vars([$tpl_var => true, 'U_ACTION' => $this->u_action]);
    }
}