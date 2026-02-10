<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\acp;

class trackers_module
{
    public $u_action;
    public $tpl_name;
    public $page_title;

    /**
     * Main entry point for the ACP module
     */
    public function main($id, $mode)
    {
        global $request, $template, $user, $db, $table_prefix, $phpbb_root_path, $config;

        $user->add_lang_ext('nextgen/trackers', 'common');

        $this->page_title = $user->lang('ACP_TRACKERS_TITLE');
        $this->tpl_name = 'acp_trackers_body';
        $this->u_action = append_sid($this->u_action);

        // Definition of system tables
        $tables = [
            'attachments'    => $table_prefix . 'trackers_attachments',
            'attach_auth'    => $table_prefix . 'trackers_attachments_auth',
            'components'    => $table_prefix . 'trackers_component',
            'posts'         => $table_prefix . 'trackers_post',
            'projects'      => $table_prefix . 'trackers_project',
            'severities'    => $table_prefix . 'trackers_severity',
            'statuses'      => $table_prefix . 'trackers_status',
            'tickets'       => $table_prefix . 'trackers_ticket',
            'trackers'      => $table_prefix . 'trackers_tracker',
            'relations'     => $table_prefix . 'trackers_relations',
        ];

        add_form_key('acp_trackers');

        // Reset mode variables to ensure clean rendering
        $template->assign_vars([
            'S_MODE_DASHBOARD' => false,
            'S_MODE_SETTINGS'  => false,
            'S_MODE_PROJECTS'  => false,
        ]);

        switch ($mode)
        {
            case 'dashboard':
                $this->manage_dashboard($tables);
            break;

            case 'settings':
                $this->manage_settings($tables);
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

    /**
     * Get the Changelog content from GitHub
     */
    private function get_github_changelog()
    {
        $url = 'https://raw.githubusercontent.com/nextgen-solutions-gt/trackers/3.3/CHANGELOG.md';
        $client = new \GuzzleHttp\Client(['timeout' => 5.0]);
        try {
            $response = $client->get($url);
            return (string) $response->getBody()->getContents();
        } catch (\Exception $e) {
            return ''; 
        }
    }

    /**
     * Main Dashboard Management
     */
    protected function manage_dashboard($tables)
    {
        global $template, $user, $db, $phpbb_root_path, $request;

        // Force dashboard mode to TRUE
        $template->assign_var('S_MODE_DASHBOARD', true);

        $action = $request->variable('action', '');
        $tracker_id = $request->variable('t_id', 0);

        // --- 1. Ticket Synchronization ---
        if ($request->is_set_post('sync'))
        {
            if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

            $sql = 'SELECT project_id FROM ' . $tables['projects'];
            $result = $db->sql_query($sql);
            while ($row = $db->sql_fetchrow($result))
            {
                $p_id = (int) $row['project_id'];
                $sql_count = 'SELECT COUNT(ticket_id) as total FROM ' . $tables['tickets'] . ' WHERE project_id = ' . (int) $p_id;
                $res_count = $db->sql_query($sql_count);
                $total = (int) $db->sql_fetchfield('total');
                $db->sql_freeresult($res_count);

                $db->sql_query('UPDATE ' . $tables['projects'] . ' SET project_total_tickets = ' . $total . ' WHERE project_id = ' . (int) $p_id);
            }
            $db->sql_freeresult($result);
            
            trigger_error($user->lang('TRACKERS_SYNC_COMPLETE') . adm_back_link($this->u_action));
        }

        // --- 2. Tracker Management (CENTRALIZED Icons & Colors) ---
        if ($request->is_set_post('submit_tracker'))
        {
            if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

            $sql_ary = [
                'tracker_name'  => $request->variable('tracker_name', '', true),
                'tracker_icon'  => $request->variable('tracker_icon', 'fa-file-text-o'),
                'tracker_color' => $request->variable('tracker_color', '#536482'),
            ];

            if ($tracker_id)
            {
                $db->sql_query('UPDATE ' . $tables['trackers'] . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE tracker_id = ' . (int) $tracker_id);
                trigger_error($user->lang('TRACKER_UPDATED') . adm_back_link($this->u_action));
            }
            else
            {
                $db->sql_query('INSERT INTO ' . $tables['trackers'] . ' ' . $db->sql_build_array('INSERT', $sql_ary));
                trigger_error($user->lang('TRACKER_ADDED') . adm_back_link($this->u_action));
            }
        }

        if ($action == 'delete_tracker' && $tracker_id)
        {
            if (confirm_box(true))
            {
                $db->sql_query('DELETE FROM ' . $tables['trackers'] . ' WHERE tracker_id = ' . (int) $tracker_id);
                trigger_error($user->lang('TRACKER_DELETED') . adm_back_link($this->u_action));
            }
            else
            {
                confirm_box(false, 'DELETE_TRACKER', build_hidden_fields(['t_id' => $tracker_id, 'action' => 'delete_tracker']));
            }
        }

        // Load Trackers for the list with visual fields
        $sql = 'SELECT tracker_id, tracker_name, tracker_icon, tracker_color FROM ' . $tables['trackers'] . ' ORDER BY tracker_id ASC';
        $result = $db->sql_query($sql);
        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('trackers_list_dashboard', [
                'ID'    => $row['tracker_id'],
                'NAME'  => $row['tracker_name'],
                'ICON'  => $row['tracker_icon'],
                'COLOR' => $row['tracker_color'],
                'U_EDIT'   => $this->u_action . '&amp;action=edit_tracker&amp;t_id=' . $row['tracker_id'],
                'U_DELETE' => $this->u_action . '&amp;action=delete_tracker&amp;t_id=' . $row['tracker_id'],
            ]);
        }
        $db->sql_freeresult($result);

        // Edit Tracker View (With Styling Inputs)
        if ($action == 'edit_tracker' || $request->is_set_post('add_tracker'))
        {
            $t_row = [
                'tracker_name'  => '', 
                'tracker_icon'  => 'fa-file-text-o', 
                'tracker_color' => '#536482'
            ];
            
            if ($tracker_id && $action == 'edit_tracker')
            {
                $result = $db->sql_query('SELECT * FROM ' . $tables['trackers'] . ' WHERE tracker_id = ' . (int) $tracker_id);
                $t_row = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);
            }

            $template->assign_vars([
                'S_EDIT_TRACKER' => true,
                'TRACKER_ID'     => $tracker_id,
                'TRACKER_NAME'   => $t_row['tracker_name'],
                'TRACKER_ICON'   => $t_row['tracker_icon'],
                'TRACKER_COLOR'  => $t_row['tracker_color'],
            ]);
        }

        // --- 3. Statistics ---
        $tickets_table  = (string) $tables['tickets'];
        $statuses_table = (string) $tables['statuses'];
        $posts_table    = (string) $tables['posts'];

        $sql_closed = "SELECT COUNT(t.ticket_id) as res 
            FROM $tickets_table t 
            JOIN $statuses_table s ON t.status_id = s.status_id 
            WHERE s.ticket_closed = 1";

        $sql_unanswered = "SELECT COUNT(*) as res 
            FROM (SELECT ticket_id 
                FROM $posts_table 
                GROUP BY ticket_id 
                HAVING COUNT(post_id) = 1) sub";

        $stats_queries = [
            'total'      => 'SELECT COUNT(ticket_id) as res FROM ' . $tickets_table,
            'open'       => 'SELECT COUNT(t.ticket_id) as res FROM ' . $tickets_table . ' t JOIN ' . $statuses_table . ' s ON t.status_id = s.status_id WHERE s.ticket_closed = 0',
            'closed'     => $sql_closed,
            'unanswered' => $sql_unanswered,
        ];

        $counts = [];
        foreach ($stats_queries as $key => $sql)
        {
            $result = $db->sql_query($sql);
            $counts[$key] = (int) $db->sql_fetchfield('res');
            $db->sql_freeresult($result);
        }

        // --- 4. Manual Version Logic ---
        $composer_path = $phpbb_root_path . 'ext/nextgen/trackers/composer.json';
        $current_version = '0.0.0';

        if (file_exists($composer_path))
        {
            $composer_data = json_decode(file_get_contents($composer_path), true);
            $current_version = (isset($composer_data['version'])) ? $composer_data['version'] : '0.0.0';
        }

        $remote_url = 'https://raw.githubusercontent.com/nextgen-solutions-gt/trackers/3.3/trackers_versions.json';
        $latest_version = $current_version;
        $download_url = '';

        $remote_file = @file_get_contents($remote_url);
        if ($remote_file)
        {
            $versions_data = json_decode($remote_file, true);
            if (isset($versions_data['unstable']['3.3']['current']))
            {
                $latest_version = $versions_data['unstable']['3.3']['current'];
                $download_url = $versions_data['unstable']['3.3']['download'];
            }
        }

        // Assigning variables to the template
        $template->assign_vars([
            'U_ACTION'            => $this->u_action,
            'TOTAL_TICKETS_COUNT' => $counts['total'],
            'OPEN_TICKETS'        => $counts['open'],
            'CLOSED_TICKETS'      => $counts['closed'],
            'UNANSWERED_TICKETS'  => $counts['unanswered'],
            'CURRENT_VERSION'     => $current_version,
            'LATEST_VERSION'      => $latest_version,
            'U_DOWNLOAD_LATEST'   => $download_url,
            'S_UP_TO_DATE'        => version_compare($current_version, $latest_version, '>='),
            'CHANGELOG_CONTENT'   => $this->get_github_changelog(),
        ]);
    }

    /**
     * General configuration of the extension
     */
    protected function manage_settings($tables)
    {
        global $template, $user, $config, $request, $db, $phpbb_root_path;

        $template->assign_var('S_MODE_SETTINGS', true);

        $composer_path = $phpbb_root_path . 'ext/nextgen/trackers/composer.json';
        $current_version = '0.0.0';
        if (file_exists($composer_path))
        {
            $composer_data = json_decode(file_get_contents($composer_path), true);
            $current_version = $composer_data['version'] ?? '0.0.0';
        }

        $settings = [
            'trackers_enabled'           => 1,
            'trackers_per_page'          => 15,
            'trackers_attachments'       => 1,
            'trackers_attach_max_size'   => 2048,
            'trackers_attach_extensions' => 'jpg,jpeg,png,gif,zip,pdf',
            'trackers_attach_path'       => 'files/trackers/',
        ];

        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);
            
            foreach ($settings as $key => $default)
            {
                $value = $request->variable($key, $default, true);
                $config->set($key, $value);
            }

            $auth_attach = $request->variable('auth_attach', [0 => 0]); 
            $db->sql_query('DELETE FROM ' . $tables['attach_auth'] . ' WHERE project_id = 0');
            
            foreach ($auth_attach as $g_id => $can_attach)
            {
                if ($can_attach)
                {
                    $db->sql_query('INSERT INTO ' . $tables['attach_auth'] . ' ' . $db->sql_build_array('INSERT', [
                        'group_id'   => (int) $g_id,
                        'project_id' => 0, 
                        'can_attach' => 1
                    ]));
                }
            }
            trigger_error($user->lang('SETTINGS_UPDATED') . adm_back_link($this->u_action));
        }

        $sql = 'SELECT group_id, group_name, group_type FROM ' . GROUPS_TABLE . ' WHERE group_type <> ' . GROUP_SPECIAL . ' OR group_name IN ("REGISTERED", "ADMINISTRATORS", "MODERATORS") ORDER BY group_name ASC';
        $result = $db->sql_query($sql);
        
        $current_auth = [];
        $res_auth = $db->sql_query('SELECT group_id FROM ' . $tables['attach_auth'] . ' WHERE project_id = 0 AND can_attach = 1');
        while($row_a = $db->sql_fetchrow($res_auth)) $current_auth[] = $row_a['group_id'];
        $db->sql_freeresult($res_auth);

        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('groups', [
                'ID'         => $row['group_id'],
                'NAME'       => ($row['group_type'] == GROUP_SPECIAL) ? $user->lang('G_' . $row['group_name']) : $row['group_name'],
                'CAN_ATTACH' => in_array($row['group_id'], $current_auth),
            ]);
        }
        $db->sql_freeresult($result);

        $template->assign_vars([
            'L_TITLE'                    => $user->lang('ACP_TRACKERS_SETTINGS'),
            'U_ACTION'                   => $this->u_action,
            'CURRENT_VERSION'            => $current_version,
            'TRACKERS_ENABLED'           => $config['trackers_enabled'] ?? $settings['trackers_enabled'],
            'TRACKERS_PER_PAGE'          => $config['trackers_per_page'] ?? $settings['trackers_per_page'],
            'TRACKERS_ATTACHMENTS'       => $config['trackers_attachments'] ?? $settings['trackers_attachments'],
            'TRACKERS_ATTACH_MAX_SIZE'   => $config['trackers_attach_max_size'] ?? $settings['trackers_attach_max_size'],
            'TRACKERS_ATTACH_EXTENSIONS' => $config['trackers_attach_extensions'] ?? $settings['trackers_attach_extensions'],
            'TRACKERS_ATTACH_PATH'       => $config['trackers_attach_path'] ?? $settings['trackers_attach_path'],
        ]);
    }

    /**
     * Project Management
     */
    protected function manage_projects($project_table, $tracker_table)
    {
        global $template, $request, $user, $db;

        // Support for Cancel button: Redirects to the main view of the current mode
        if ($request->is_set_post('cancel'))
        {
            redirect($this->u_action);
        }

        $template->assign_var('S_MODE_PROJECTS', true);

        // Capture control variables
        $action = $request->variable('action', '');
        $project_id = $request->variable('id', 0);
        $tracker_id = $request->variable('t_id', 0);

        // --- 1. FORM PROCESSING (POST) ---

        // A. Save or Update a TRACKER TYPE (Icon and Color)
        if ($request->is_set_post('submit_tracker'))
        {
            if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

            $sql_ary = [
                'tracker_name'  => $request->variable('tracker_name', '', true),
                'tracker_icon'  => $request->variable('tracker_icon', 'fa-file-text-o'),
                'tracker_color' => $request->variable('tracker_color', '#536482'),
            ];

            if ($tracker_id)
            {
                $db->sql_query('UPDATE ' . $tracker_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE tracker_id = ' . (int) $tracker_id);
                $msg = 'TRACKER_UPDATED';
            }
            else
            {
                $db->sql_query('INSERT INTO ' . $tracker_table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
                $msg = 'TRACKER_ADDED';
            }
            trigger_error($user->lang($msg) . adm_back_link($this->u_action));
        }

        // B. Save or Update a PROJECT
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

            $sql_ary = [
                'project_name'        => $request->variable('project_name', '', true),
                'project_description' => $request->variable('project_desc', '', true),
                'project_note'        => $request->variable('project_note', '', true),
                'tracker_id'          => $request->variable('tracker_id', 0),
                'project_private'     => $request->variable('project_private', 0),
                'project_locked'      => $request->variable('project_locked', 0),
                'project_active'      => 1,
            ];

            if ($project_id)
            {
                $db->sql_query('UPDATE ' . $project_table . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' WHERE project_id = ' . (int) $project_id);
                trigger_error($user->lang('PROJECT_UPDATED') . adm_back_link($this->u_action));
            }
            else
            {
                $db->sql_query('INSERT INTO ' . $project_table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
                trigger_error($user->lang('PROJECT_ADDED') . adm_back_link($this->u_action));
            }
        }

        // --- 2. DELETION ACTIONS ---

        // Delete a TRACKER TYPE (With reassignment logic)
        if ($action == 'delete_tracker' && $tracker_id)
        {
            // We check if there are any projects associated with this tracker.
            $sql = 'SELECT COUNT(project_id) as total FROM ' . $project_table . ' WHERE tracker_id = ' . (int) $tracker_id;
            $result = $db->sql_query($sql);
            $has_projects = (int) $db->sql_fetchfield('total');
            $db->sql_freeresult($result);

            if ($has_projects > 0)
            {
                // If the user has already confirmed the smart delete action
                if ($request->is_set_post('confirm_delete'))
                {
                    if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

                    $delete_action = $request->variable('delete_action', ''); 
                    $to_tracker_id = $request->variable('to_tracker_id', 0);

                    if ($delete_action == 'move' && $to_tracker_id)
                    {
                        // Reassign projects to the newly selected tracker
                        $db->sql_query('UPDATE ' . $project_table . ' SET tracker_id = ' . (int) $to_tracker_id . ' WHERE tracker_id = ' . (int) $tracker_id);
                    }
                    else
                    {
                        // Delete all projects linked to the tracker that is going to be deleted
                        $db->sql_query('DELETE FROM ' . $project_table . ' WHERE tracker_id = ' . (int) $tracker_id);
                    }

                    // Finally, remove the tracker
                    $db->sql_query('DELETE FROM ' . $tracker_table . ' WHERE tracker_id = ' . (int) $tracker_id);
                    trigger_error($user->lang('TRACKER_DELETED') . adm_back_link($this->u_action));
                }

                // Load list of target trackers for reassignment (excluding the current one)
                $sql = 'SELECT tracker_id, tracker_name FROM ' . $tracker_table . ' WHERE tracker_id <> ' . (int) $tracker_id . ' ORDER BY tracker_name ASC';
                $result = $db->sql_query($sql);
                while ($row = $db->sql_fetchrow($result))
                {
                    $template->assign_block_vars('target_trackers', [
                        'ID'   => $row['tracker_id'],
                        'NAME' => $row['tracker_name'],
                    ]);
                }
                $db->sql_freeresult($result);

                $template->assign_vars([
                    'S_CONFIRM_DELETE_TRACKER' => true,
                    'TRACKER_ID'               => $tracker_id,
                    'U_ACTION'                 => $this->u_action . '&amp;action=delete_tracker&amp;t_id=' . $tracker_id,
                ]);
                return;
            }
            else
            {
                // Direct deletion if the tracker has no associated projects
                if (confirm_box(true))
                {
                    $db->sql_query('DELETE FROM ' . $tracker_table . ' WHERE tracker_id = ' . (int) $tracker_id);
                    trigger_error($user->lang('TRACKER_DELETED') . adm_back_link($this->u_action));
                }
                else
                {
                    confirm_box(false, 'DELETE_TRACKER', build_hidden_fields(['t_id' => $tracker_id, 'action' => 'delete_tracker']));
                }
            }
        }

        // Delete a PROJECT
        if ($action == 'delete' && $project_id)
        {
            if (confirm_box(true))
            {
                $sql = 'DELETE FROM ' . $project_table . ' WHERE project_id = ' . (int) $project_id;
                $db->sql_query($sql);
                trigger_error($user->lang('PROJECT_DELETED') . adm_back_link($this->u_action));
            }
            else
            {
                confirm_box(false, 'DELETE_PROJECT', build_hidden_fields(['id' => $project_id, 'action' => 'delete']));
            }
        }

        // --- 3. LOADING EDIT VIEWS ---

        // View: Add or Edit TRACKER TYPE
        if ($action == 'add_tracker' || $action == 'edit_tracker')
        {
            $t_row = [
                'tracker_name'  => '', 
                'tracker_icon'  => 'fa-file-text-o', 
                'tracker_color' => '#536482'
            ];

            if ($tracker_id && $action == 'edit_tracker')
            {
                $result = $db->sql_query('SELECT * FROM ' . $tracker_table . ' WHERE tracker_id = ' . (int) $tracker_id);
                $t_row = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);
            }

            $template->assign_vars([
                'S_EDIT_TRACKER' => true,
                'TRACKER_ID'     => $tracker_id,
                'TRACKER_NAME'   => $t_row['tracker_name'],
                'TRACKER_ICON'   => $t_row['tracker_icon'],
                'TRACKER_COLOR'  => $t_row['tracker_color'],
                'U_BACK'         => $this->u_action,
            ]);
            return;
        }

        // View: Add or Edit PROJECT
        if ($request->is_set_post('add') || $action == 'edit' || $action == 'add')
        {
            $row = [
                'project_name' => '', 
                'project_description' => '', 
                'project_note' => '', 
                'tracker_id' => 0, 
                'project_private' => 0, 
                'project_locked' => 0
            ];

            if ($action == 'edit' && $project_id)
            {
                $result = $db->sql_query('SELECT * FROM ' . $project_table . ' WHERE project_id = ' . (int) $project_id);
                $row = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);
            }

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
                'S_PROJECT_PRIVATE' => $row['project_private'],
                'S_PROJECT_LOCKED'  => (isset($row['project_locked']) ? $row['project_locked'] : 0),
                'U_BACK'            => $this->u_action,
            ]);
            return;
        }

        // --- 4. GENERAL LISTS (DEFAULT VIEW) ---

        // A. List of Tracker Types (Visual Templates)
        $sql = 'SELECT * FROM ' . $tracker_table . ' ORDER BY tracker_id ASC';
        $result = $db->sql_query($sql);
        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('trackers_list', [
                'ID'    => $row['tracker_id'],
                'NAME'  => $row['tracker_name'],
                'ICON'  => $row['tracker_icon'],
                'COLOR' => $row['tracker_color'],
                'U_EDIT'   => $this->u_action . '&amp;action=edit_tracker&amp;t_id=' . $row['tracker_id'],
                'U_DELETE' => $this->u_action . '&amp;action=delete_tracker&amp;t_id=' . $row['tracker_id'],
            ]);
        }
        $db->sql_freeresult($result);

        // B. List of Projects with information from the Parent Tracker (JOIN)
        $sql = 'SELECT p.*, t.tracker_name, t.tracker_icon, t.tracker_color 
                FROM ' . $project_table . ' p
                LEFT JOIN ' . $tracker_table . ' t ON p.tracker_id = t.tracker_id
                ORDER BY p.project_name ASC';
        
        $result = $db->sql_query($sql);
        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('projects', [
                'ID'                => $row['project_id'],
                'NAME'              => $row['project_name'],
                'DESC'              => $row['project_description'],
                'TRACKER_TYPE_NAME' => $row['tracker_name'],
                'ICON'              => $row['tracker_icon'],
                'COLOR'             => $row['tracker_color'],
                'S_LOCKED'          => (isset($row['project_locked']) && $row['project_locked']),
                'U_EDIT'            => $this->u_action . '&amp;action=edit&amp;id=' . (int) $row['project_id'],
                'U_DELETE'          => $this->u_action . '&amp;action=delete&amp;id=' . (int) $row['project_id'],
            ]);
        }
        $db->sql_freeresult($result);

        // Navigation variables for the “Add” buttons and the general form
        $template->assign_vars([
            'U_ACTION'      => $this->u_action,
            'U_ADD_TRACKER' => $this->u_action . '&amp;action=add_tracker',
            'U_ADD_PROJECT' => $this->u_action . '&amp;action=add',
        ]);
    }

    /**
     * Generic item management
     */
    protected function manage_items($table, $type, $tables)
    {
        global $template, $request, $user, $db;

        // Support for Cancel button: Redirects to the main list of the current mode
        if ($request->is_set_post('cancel'))
        {
            redirect($this->u_action);
        }

        $action = $request->variable('action', '');
        $id = $request->variable('id', 0);
        
        $id_field = $type . '_id';
        $name_field = $type . '_name';
        $colour_field = $type . '_colour';
        $desc_field = $type . '_description';

        // --- DELETE ACTION ---
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

        // --- SAVE PROCESSING (SUBMIT) ---
        if ($request->is_set_post('submit'))
        {
            if (!check_form_key('acp_trackers')) trigger_error($user->lang('FORM_INVALID'), E_USER_WARNING);

            // We ALWAYS capture the description to avoid error 1364 (Field doesn't have a default value)
            $item_description = $request->variable('description', '', true);

            $sql_ary = [
                $name_field => $request->variable('name', '', true),
                $desc_field => $item_description,
            ];
            
            // The components are not colored.
            if ($type != 'component') 
            { 
                $raw_colour = $request->variable('colour', 'CCCCCC');
                $sql_ary[$colour_field] = str_replace('#', '', $raw_colour);
            }

            // Capture flags for states (Synchronization of new and old columns)
            if ($type == 'status')
            {
                $val_new    = $request->variable('ticket_new', 0);
                $val_closed = $request->variable('ticket_closed', 0);

                // Columns m18
                $sql_ary['status_new']    = $val_new;
                $sql_ary['status_closed'] = $val_closed;

                // Inherited columns (according to the table structure shown)
                $sql_ary['ticket_new']    = $val_new;
                $sql_ary['ticket_closed'] = $val_closed;
            }

            if ($id)
            {
                $db->sql_query("UPDATE $table SET " . $db->sql_build_array('UPDATE', $sql_ary) . " WHERE $id_field = " . (int) $id);
            }
            else
            {
                // The INSERT now explicitly includes the description.
                $db->sql_query("INSERT INTO $table " . $db->sql_build_array('INSERT', $sql_ary));
                $id = $db->sql_nextid();
            }

            // --- PROJECT RELATIONSHIP MANAGEMENT ---
            $project_ids = $request->variable('project_ids', [0 => 0]);
            
            $db->sql_query("DELETE FROM " . $tables['relations'] . " WHERE item_id = " . (int) $id . " AND item_type = '$type'");
            
            foreach ($project_ids as $p_id)
            {
                if ($p_id > 0)
                {
                    $db->sql_query("INSERT INTO " . $tables['relations'] . " " . $db->sql_build_array('INSERT', [
                        'item_id'    => (int) $id, 
                        'project_id' => (int) $p_id, 
                        'item_type'  => $type
                    ]));
                }
            }
            
            trigger_error($user->lang('ITEM_UPDATED') . adm_back_link($this->u_action));
        }

        // --- EDIT OR ADD VIEW ---
        if ($request->is_set_post('add') || $action == 'edit')
        {
            $row = [$name_field => '', $desc_field => ''];
            $assigned_projects = [];
            $s_new = $s_closed = 0;

            if ($id)
            {
                // Item data loading
                $result = $db->sql_query("SELECT * FROM $table WHERE $id_field = " . (int) $id);
                $row = $db->sql_fetchrow($result);
                $db->sql_freeresult($result);

                // Security check for states
                if ($type == 'status')
                {
                    // Attempt to read from status_new; if it does not exist, use ticket_new (compatibility)
                    $s_new    = (isset($row['status_new'])) ? $row['status_new'] : (isset($row['ticket_new']) ? $row['ticket_new'] : 0);
                    $s_closed = (isset($row['status_closed'])) ? $row['status_closed'] : (isset($row['ticket_closed']) ? $row['ticket_closed'] : 0);
                }

                // Loading assigned projects
                $result = $db->sql_query("SELECT project_id FROM " . $tables['relations'] . " WHERE item_id = " . (int) $id . " AND item_type = '$type'");
                while ($p_row = $db->sql_fetchrow($result)) $assigned_projects[] = $p_row['project_id'];
                $db->sql_freeresult($result);
            }

            // Loading project list
            $sql = 'SELECT project_id, project_name FROM ' . $tables['projects'] . ' ORDER BY project_name ASC';
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

            $item_colour = ($type != 'component' && isset($row[$colour_field])) ? $row[$colour_field] : 'CCCCCC';

            $template->assign_vars([
                'S_EDIT_' . strtoupper($type) => true,
                'L_TITLE'     => ($id) ? $user->lang('EDIT') : $user->lang('ADD'),
                'ITEM_NAME'   => (isset($row[$name_field])) ? $row[$name_field] : '',
                'ITEM_DESC'   => (isset($row[$desc_field])) ? $row[$desc_field] : '',
                'ITEM_COLOUR' => $item_colour,
                'S_NEW'       => $s_new,
                'S_CLOSED'    => $s_closed,
                'U_BACK'      => $this->u_action,
            ]);
            return;
        }

        // --- LIST VIEW ---
        $result = $db->sql_query("SELECT * FROM $table ORDER BY $name_field ASC");
        while ($row = $db->sql_fetchrow($result))
        {
            $template->assign_block_vars('items', [
                'NAME'   => $row[$name_field],
                'COLOUR' => (isset($row[$colour_field])) ? $row[$colour_field] : '', 
                'U_EDIT' => $this->u_action . '&amp;action=edit&amp;id=' . $row[$id_field],
                'U_DELETE' => $this->u_action . '&amp;action=delete&amp;id=' . $row[$id_field],
            ]);
        }
        $db->sql_freeresult($result);

        $mode_var = 'S_MODE_' . strtoupper($type) . 'S';
        if ($type == 'status') $mode_var = 'S_MODE_STATUSES';
        if ($type == 'severity') $mode_var = 'S_MODE_SEVERITIES';
        if ($type == 'component') $mode_var = 'S_MODE_COMPONENTS';
        
        $template->assign_vars([
            $mode_var => true, 
            'L_TITLE' => $user->lang('ACP_TRACKERS_' . strtoupper($type) . 'S'), 
            'U_ACTION' => $this->u_action
        ]);
    }
}
