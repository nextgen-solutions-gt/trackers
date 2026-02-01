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

class m14_add_project_counter extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		// Depende de la m13 para mantener la secuencia lógica
		return ['\nextgen\trackers\migrations\v10x\m13_attachments_and_auth'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				// Añadimos la columna faltante a la tabla de proyectos
				$this->table_prefix . 'trackers_project' => [
					'project_total_tickets' => ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'trackers_project' => [
					'project_total_tickets',
				],
			],
		];
	}
}