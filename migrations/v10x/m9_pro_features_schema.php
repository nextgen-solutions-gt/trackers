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

class m9_pro_features_schema extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return ['\nextgen\trackers\migrations\v10x\m8_additional_permissions'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				// Tabla de componentes
				$this->table_prefix . 'trackers_component' => [
					'COLUMNS' => [
						'component_id'   => ['UINT', null, 'auto_increment'],
						'tracker_id'     => ['UINT', 0],
						'component_name' => ['VCHAR_UNI:255', ''],
					],
					'PRIMARY_KEY' => 'component_id',
				],
			],
			'add_columns' => [
				// Columnas para personalización visual
				$this->table_prefix . 'trackers_status' => [
					'status_colour'    => ['VCHAR:6', 'CCCCCC'],
					'ticket_duplicate' => ['BOOL', 0],
				],
				$this->table_prefix . 'trackers_severity' => [
					'severity_colour'  => ['VCHAR:6', '000000'],
				],
			],
		];
	}

	// --- AÑADIDO: Función para pintar los datos existentes ---
	public function update_data()
	{
		return [
			['custom', [[$this, 'update_existing_colors']]],
		];
	}

	public function update_existing_colors()
	{
		// 1. Pintar Severidades
		$sev_colors = [
			'Severe'           => 'ECD5D8',
			'High'             => 'FF9999',
			'Medium'           => 'E1E1E1',
			'Low'              => 'CCFFCC',
			'Possibly invalid' => 'FDE8A2',
		];

		foreach ($sev_colors as $name => $color)
		{
			$sql = 'UPDATE ' . $this->table_prefix . "trackers_severity 
					SET severity_colour = '" . $this->db->sql_escape($color) . "' 
					WHERE severity_name = '" . $this->db->sql_escape($name) . "'";
			$this->db->sql_query($sql);
		}

		// 2. Pintar Estados (Mapeo completo de tu m2)
		$status_colors = [
			// Básicos
			'New'                  => '4CAF50', // Verde
			'Pending'              => 'FFC107', // Ambar
			'Reviewed'             => '2196F3', // Azul
			'Closed'               => '9E9E9E', // Gris
			
			// Resoluciones
			'Fixed'                => '009688', // Teal
			'Duplicate'            => '795548', // Café
			'Invalid'              => 'F44336', // Rojo
			'Unreproducible'       => '607D8B', // Gris Azulado
			'Will not fix'         => '333333', // Gris oscuro
			'Already fixed'        => '8BC34A', // Verde claro

			// Intermedios / Desarrollo
			'Possible bug'            => 'FF9800',
			'Possible security issue' => 'E91E63',
			'Awaiting information'    => '9C27B0',
			'Awaiting team input'     => '673AB7',
			'Support request'         => '00BCD4',
			'Review later'            => 'CDDC39',
			'Bug'                     => 'D32F2F',
			'Implementing'            => '03A9F4',
			'Researching'             => '00BCD4',
			'Patching in progress'    => '8BC34A',
			'Patch written'           => 'CDDC39',
			'Fix in progress'         => 'FFEB3B',
			'Fix completed in VCS'    => '4CAF50',
			'Implemented in VCS'      => '4CAF50',
			'Will not implement'      => '795548',
			'Not a bug'               => '9E9E9E',
		];

		foreach ($status_colors as $name => $color)
		{
			$sql = 'UPDATE ' . $this->table_prefix . "trackers_status 
					SET status_colour = '" . $this->db->sql_escape($color) . "' 
					WHERE status_name = '" . $this->db->sql_escape($name) . "'";
			$this->db->sql_query($sql);
		}
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'trackers_component',
			],
			'drop_columns' => [
				$this->table_prefix . 'trackers_status'   => ['status_colour', 'ticket_duplicate'],
				$this->table_prefix . 'trackers_severity' => ['severity_colour'],
			],
		];
	}
}