<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <https://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
	protected $tracker_table;

	/**
	 * Constructor
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\template\template $template, $table_prefix)
	{
		$this->db = $db;
		$this->language = $language;
		$this->helper = $helper;
		$this->template = $template;
		$this->tracker_table = $table_prefix . 'tracker'; 
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'    => 'load_language_on_setup',
			'core.page_header'   => 'add_page_header_link',
			'core.permissions'   => 'add_permissions',
			'core.page_header_user_menu' => 'add_my_tickets_link',
		];
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'nextgen/trackers',
			'lang_set' => 'common',
		];

		// --- NEW RC4: We are loading the report language file ---
		$lang_set_ext[] = [
			'ext_name' => 'nextgen/trackers',
			'lang_set' => 'report',
		];

		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function add_page_header_link()
	{
		// FIX: Assign the global variable U_TRACKERS_MAIN so that the header link works
		$this->template->assign_vars([
			'U_TRACKERS_MAIN' => $this->helper->route('nextgen_trackers_page', ['page' => 'main']),
		]);

		$this->template->assign_vars([
            'U_MY_TICKETS' => $this->helper->route('nextgen_trackers_page', ['page' => 'my_tickets']),
        ]);
		
		$sql = 'SELECT tracker_id, tracker_name FROM ' . $this->tracker_table; 
			
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('trackers', [
				'TRACKER_NAME'    => $row['tracker_name'],
				'U_VIEWTRACKER'   => $this->helper->route('nextgen_trackers_page', ['page' => 'viewtracker', 't' => (int) $row['tracker_id']]),
			]);
		}
		$this->db->sql_freeresult($result);
	}

	public function add_permissions($event)
	{
		$permissions = $event['permissions'];
		
		// Register the category so that it appears in the ACP
		$categories = $event['categories'];
		$categories['trackers'] = 'ACL_CAT_TRACKERS';
		$event['categories'] = $categories;

		// --- User permissions ---
		$permissions['u_tracker_view']         = ['lang' => 'ACL_U_TRACKER_VIEW', 'cat' => 'trackers'];
		$permissions['u_tracker_create']       = ['lang' => 'ACL_U_TRACKER_CREATE', 'cat' => 'trackers'];
		$permissions['u_tracker_edit']         = ['lang' => 'ACL_U_TRACKER_EDIT', 'cat' => 'trackers'];
		$permissions['u_tracker_delete']       = ['lang' => 'ACL_U_TRACKER_DELETE', 'cat' => 'trackers'];
		$permissions['u_tracker_reply']        = ['lang' => 'ACL_U_TRACKER_REPLY', 'cat' => 'trackers'];
		$permissions['u_tracker_view_private'] = ['lang' => 'ACL_U_TRACKER_VIEW_PRIVATE', 'cat' => 'trackers'];
		$permissions['u_tracker_close']        = ['lang' => 'ACL_U_TRACKER_CLOSE', 'cat' => 'trackers'];
		
		// --- NEW RC4: Watch & Report permissions ---
		$permissions['u_tracker_watch']        = ['lang' => 'ACL_U_TRACKER_WATCH', 'cat' => 'trackers'];
		$permissions['u_tracker_report']       = ['lang' => 'ACL_U_TRACKER_REPORT', 'cat' => 'trackers'];
		$permissions['m_tracker_report']       = ['lang' => 'ACL_M_TRACKER_REPORT', 'cat' => 'trackers'];

		// --- Moderator permissions ---
		$permissions['m_tracker_edit']     = ['lang' => 'ACL_M_TRACKER_EDIT', 'cat' => 'trackers'];
		$permissions['m_tracker_delete']   = ['lang' => 'ACL_M_TRACKER_DELETE', 'cat' => 'trackers'];
		$permissions['m_tracker_status']   = ['lang' => 'ACL_M_TRACKER_STATUS', 'cat' => 'trackers'];
		$permissions['m_tracker_assign']   = ['lang' => 'ACL_M_TRACKER_ASSIGN', 'cat' => 'trackers'];
		$permissions['m_tracker_logs']     = ['lang' => 'ACL_M_TRACKER_LOGS', 'cat' => 'trackers'];
		
		// --- NEW RC4 PERMISSIONS: Moderation ---
		$permissions['m_tracker_move']     = ['lang' => 'ACL_M_TRACKER_MOVE', 'cat' => 'trackers'];
		$permissions['m_tracker_unassign'] = ['lang' => 'ACL_M_TRACKER_UNASSIGN', 'cat' => 'trackers'];

		// --- Administrator permissions ---
		$permissions['a_trackers']         = ['lang' => 'ACL_A_TRACKERS', 'cat' => 'trackers'];

		$event['permissions'] = $permissions;
	}	
}
