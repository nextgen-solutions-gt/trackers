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

class m12_component_structure extends \phpbb\db\migration\migration
{
	/**
	 * Esta migración depende de m11 para seguir el orden lógico
	 */
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m11_project_type'];
	}

	/**
	 * Creamos la tabla de componentes y añadimos la relación en tickets
	 */
	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'trackers_component' => [
					'COLUMNS' => [
						'component_id'    => ['UINT', null, 'auto_increment'],
						'component_name'  => ['VCHAR_UNI:255', ''],
						'component_order' => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'component_id',
				],
			],
			'add_columns' => [
				$this->table_prefix . 'trackers_ticket' => [
					'component_id' => ['UINT', 0],
				],
			],
		];
	}

	/**
	 * Registramos el modo 'components' dentro de la categoría 'ACP_TRACKERS' creada en m5
	 */
	public function update_data()
	{
		return [
			// Añadimos el modo componentes al basename existente bajo la categoría de m5
			['module.add', [
				'acp',
				'ACP_TRACKERS', // El nombre de la categoría que creaste en m5
				[
					'module_basename' => '\nextgen\trackers\acp\trackers_module',
					'modes'           => ['components'],
				],
			]],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'trackers_ticket' => [
					'component_id',
				],
			],
			'drop_tables' => [
				$this->table_prefix . 'trackers_component',
			],
		];
	}
}