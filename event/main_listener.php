<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Trackers event listener
 */
class main_listener implements EventSubscriberInterface
{
    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\language\language */
    protected $language;

    /** @var \phpbb\controller\helper */
    protected $helper;

    /** @var \phpbb\template\template */
    protected $template;

    /** @var string */
    protected $tracker_table; // <--- CAMBIO: Ya no es un array, es un string

    /**
     * Constructor
     * Ahora recibe el prefijo de la tabla en lugar del array de tablas
     */
    public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\template\template $template, $table_prefix)
    {
        $this->db = $db;
        $this->language = $language;
        $this->helper = $helper;
        $this->template = $template;
        
        // <--- CAMBIO: Construimos el nombre de la tabla aquí mismo
        $this->tracker_table = $table_prefix . 'trackers_tracker'; 
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup'    => 'load_language_on_setup',
            'core.page_header'   => 'add_page_header_link',
            'core.permissions'   => 'add_permissions',
        ];
    }

    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'nextgen/trackers',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function add_page_header_link()
    {
        // <--- CAMBIO: Usamos la propiedad string directa
        $sql = 'SELECT tracker_id, tracker_name
            FROM ' . $this->tracker_table; 
            
        $result = $this->db->sql_query($sql);
        while ($row = $this->db->sql_fetchrow($result))
        {
            $this->template->assign_block_vars('trackers', [
                'TRACKER_NAME'    => $row['tracker_name'],
                'U_VIEWTRACKER'   => $this->helper->route('nextgen_trackers_controller', ['page' => 'viewtracker', 't' => (int) $row['tracker_id']]),
            ]);
        }
        $this->db->sql_freeresult($result);
    }

    public function add_permissions($event)
    {
        $permissions = $event['permissions'];
        
        // Registrar la categoría
        $event->update_subarray('categories', 'trackers', 'ACL_CAT_TRACKERS');

        // Permisos de usuario
        $permissions['u_tracker_post'] = ['lang' => 'ACL_U_TRACKER_POST', 'cat' => 'trackers'];
        $permissions['u_tracker_edit'] = ['lang' => 'ACL_U_TRACKER_EDIT', 'cat' => 'trackers'];
        $permissions['u_tracker_delete'] = ['lang' => 'ACL_U_TRACKER_DELETE', 'cat' => 'trackers'];
        $permissions['u_tracker_reply'] = ['lang' => 'ACL_U_TRACKER_REPLY', 'cat' => 'trackers'];

        // Permisos de moderador
        $permissions['m_tracker_edit'] = ['lang' => 'ACL_M_TRACKER_EDIT', 'cat' => 'trackers'];
        $permissions['m_tracker_delete'] = ['lang' => 'ACL_M_TRACKER_DELETE', 'cat' => 'trackers'];

        $event['permissions'] = $permissions;
    }
}