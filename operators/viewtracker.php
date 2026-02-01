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
        // 1. Verificación inmediata del estado global
        $is_enabled = (isset($this->config['trackers_enabled'])) ? (bool) $this->config['trackers_enabled'] : true;

        $tracker_id = $this->request->variable('t', 0);
        $functions = $this->container->get('nextgen.trackers.functions');
        $auth = $this->container->get('auth');

        // Solo ejecutamos la lógica pesada si el tracker está habilitado
        if ($is_enabled)
        {
            // Verificación de permiso para ver el tracker (Nuevo permiso m8)
            if (!$auth->acl_get('u_tracker_view'))
            {
                if ($this->user->data['user_id'] == ANONYMOUS)
                {
                    login_box('', $this->language->lang('LOGIN_REQUIRED'));
                }
                trigger_error('NOT_AUTHORISED');
            }

            $tracker = $functions->get_tracker_data($tracker_id);

            // Verificación de acceso para anónimos basada en la configuración del tracker
            if (!$tracker['allow_view_all'] && $this->user->data['user_id'] == ANONYMOUS)
            {
                login_box('', $this->language->lang('LOGIN_REQUIRED'));
            }

            $projects = $functions->get_projects($tracker_id);
            $db = $this->container->get('dbal.conn');
            $table_prefix = $this->container->getParameter('core.table_prefix');

            foreach ($projects as $project)
            {
                $sql = 'SELECT COUNT(ticket_id) as total_tickets 
                        FROM ' . $table_prefix . 'trackers_ticket 
                        WHERE project_id = ' . (int) $project['project_id'];
                
                $result = $db->sql_query($sql);
                $total_tickets = (int) $db->sql_fetchfield('total_tickets');
                $db->sql_freeresult($result);

                $this->template->assign_block_vars('projects', [
                    'PROJECT_NAME'  => $project['project_name'],
                    'DESCRIPTION'   => $project['project_description'],
                    'TOTAL_TICKETS' => $total_tickets,
                    'U_VIEWPROJECT' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewproject', 't' => (int) $tracker_id, 'p' => (int) $project['project_id']]),
                ]);
            }

            $this->template->assign_vars([
                'TRACKER_NAME'      => $tracker['tracker_name'],
                'U_STATISTICS'      => $this->helper->route('nextgen_trackers_controller', ['page' => 'statistics', 't' => (int) $tracker_id]),
                'S_TRACKER_PRIVATE' => !$tracker['allow_view_all'],
            ]);

            // Breadcrumbs
            $navlinks = [
                [
                    'FORUM_NAME'   => $tracker['tracker_name'],
                    'U_VIEW_FORUM' => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => (int) $tracker_id]),
                ],
            ];
            $functions->generate_navlinks($navlinks);
        }

        // Esta variable siempre se envía para que el HTML sepa qué mostrar
        $this->template->assign_vars([
            'S_TRACKER_ENABLED' => $is_enabled,
        ]);

        return $this->helper->render('viewtracker_body.html', $is_enabled ? $tracker['tracker_name'] : $this->language->lang('TRACKER_DISABLED'));
    }
}