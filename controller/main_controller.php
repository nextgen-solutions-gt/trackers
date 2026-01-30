<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trackers main controller
 */
class main_controller
{
	protected $auth;
	protected $config;
	protected $content_visibility;
	protected $helper;
	protected $db;
	protected $language;
	protected $request;
	protected $template;
	protected $user;
	protected $container;
	protected $root_path;
	protected $php_ext;
	protected $table_prefix;

	/**
	 * Constructor
	 * El orden de los argumentos DEBE ser idéntico al de services.yml
	 */
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\content_visibility $content_visibility,
		\phpbb\controller\helper $helper,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		ContainerInterface $container,
		$root_path,
		$php_ext,
		$table_prefix
	) {
		$this->auth = $auth;
		$this->config = $config;
		$this->content_visibility = $content_visibility;
		$this->helper = $helper;
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->container = $container;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->table_prefix = $table_prefix;
	}

	/**
	 * Controller handler for route /trackers/{page}
	 */
	public function display($page = 'viewtracker')
	{
		// CORRECCIÓN: Añadimos '.operator.' para coincidir con services.yml
		// Antes buscaba: nextgen.trackers.statistics (incorrecto)
		// Ahora busca:   nextgen.trackers.operator.statistics (correcto)
		$operator_service = 'nextgen.trackers.operator.' . $page;

		if (!$this->container->has($operator_service))
		{
			throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_PAGE_MODE'));
		}

		// Cargamos el operador dinámicamente desde el contenedor
		$operator = $this->container->get($operator_service);

		return $operator->display();
	}
}