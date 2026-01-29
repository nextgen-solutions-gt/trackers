<?php
/**
 *
 * Trackers extension for the phpBB Forum Software package
 *
 * @copyright (c) 2026 nextgen <http://nextgen.gt>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace nextgen\trackers\migrations\v10x;

class m5_setup_acp_modules extends \phpbb\db\migration\migration
{
    /**
     * Esta migración depende de m4 para asegurar que los datos 
     * y las tablas estén listos antes de mostrar los menús.
     */
    public static function depends_on()
    {
        return ['\nextgen\trackers\migrations\v10x\m4_final_fix'];
    }

    /**
     * Añadimos la categoría principal y todos los modos del módulo
     */
    public function update_data()
    {
        return [
            // 1. Crear la categoría "TRACKERS" dentro de la pestaña "Extensiones" (ACP_CAT_DOT_MODS)
            ['module.add', [
                'acp',
                'ACP_CAT_DOT_MODS',
                'ACP_TRACKERS'
            ]],

            // 2. Añadir todos los modos bajo el mismo basename
            // Esto agrupa todas las opciones en un menú lateral limpio
            ['module.add', [
                'acp',
                'ACP_TRACKERS',
                [
                    'module_basename' => '\nextgen\trackers\acp\trackers_module',
                    'modes'           => [
                        'settings',   // Configuración General
                        'projects',   // Gestión de Proyectos
                        'severities', // Gestión de Severidades (Colores)
                        'statuses',   // Gestión de Estados
                    ],
                ],
            ]],
        ];
    }
}