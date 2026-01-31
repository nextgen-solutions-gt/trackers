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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

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
        $operator_service = 'nextgen.trackers.operator.' . $page;

        if (!$this->container->has($operator_service))
        {
            throw new \phpbb\exception\http_exception(404, $this->language->lang('NO_PAGE_MODE'));
        }

        // Cargamos el operador dinámicamente desde el contenedor
        $operator = $this->container->get($operator_service);

        return $operator->display();
    }

    /**
     * Gestión de descargas seguras de adjuntos
     * Ruta: /trackers/download/{attach_id}
     */
    public function download($attach_id)
    {
        $attach_id = (int) $attach_id;

        // 1. Obtener información del adjunto
        // Corregido: eliminamos el prefijo repetido para que use phpbb_trackers_attachments
        $sql = 'SELECT * FROM ' . $this->table_prefix . 'attachments 
                WHERE attach_id = ' . $attach_id;
        $result = $this->db->sql_query($sql);
        $attachment = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!$attachment)
        {
            throw new \phpbb\exception\http_exception(404, $this->language->lang('ATTACHMENT_NOT_FOUND'));
        }

        // 2. Validar permisos de acceso al proyecto/ticket
        $functions = $this->container->get('nextgen.trackers.functions');

        // Corregido: nombre de tabla ticket (phpbb_trackers_ticket)
        $sql = 'SELECT project_id FROM ' . $this->table_prefix . 'ticket 
                WHERE ticket_id = ' . (int) $attachment['ticket_id'];
        $result = $this->db->sql_query($sql);
        $project_id = (int) $this->db->sql_fetchfield('project_id');
        $this->db->sql_freeresult($result);

        // Validar si el ticket es privado
        $ticket_data = $functions->get_ticket_data($attachment['ticket_id']);
        if ($ticket_data['ticket_private'] && !$functions->is_team_user($project_id) && $this->user->data['user_id'] != $ticket_data['user_id'])
        {
            throw new \phpbb\exception\http_exception(403, $this->language->lang('NOT_AUTHORISED'));
        }

        // 3. Preparar ruta del archivo físico
        $upload_path = rtrim($this->config['trackers_attach_path'], '/') . '/';
        $file_path = $this->root_path . $upload_path . $attachment['physical_filename'];

        if (!file_exists($file_path))
        {
            throw new \phpbb\exception\http_exception(404, $this->language->lang('FILE_NOT_FOUND_ON_DISK'));
        }

        // 4. Enviar el archivo usando BinaryFileResponse (Symfony)
        // Esto corrige el error de "undefined method send_file"
        $response = new BinaryFileResponse($file_path);
        
        // DISPOSITION_INLINE permite que las imágenes se vean en el navegador
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment['real_filename']
        );

        return $response;
    }
}