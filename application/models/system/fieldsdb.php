<?php defined('SYSPATH') || die('No direct script access allowed.');

class System_FieldsDB_Model extends Model
{
	public function getFieldProperties()
	{
		$options = array(
			'min_length' => array(
				'label' => __('config_min_length', 'system_fields'),
				'keyword' => 'min_length',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 0, 'max_value' => 255),
			),
			'max_length' => array(
				'label' => __('config_max_length', 'system_fields'),
				'keyword' => 'max_length',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 1, 'max_value' => 255),
			),
			'max_length_textarea' => array(
				'label' => __('config_max_length', 'system_fields'),
				'keyword' => 'max_length',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 1, 'max_value' => 65535),
			),
			'min_value' => array(
				'label' => __('config_min_value', 'system_fields'),
				'keyword' => 'min_value',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 0, 'min_value' => -1000000000),
			),
			'max_value' => array(
				'label' => __('config_max_value', 'system_fields'),
				'keyword' => 'max_value',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 1, 'max_value' => 1000000000),
			),
			'min_age' => array(
				'label' => __('config_min_age', 'system_fields'),
				'keyword' => 'min_age',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 13, 'max_value' => 100),
			),
			'max_age' => array(
				'label' => __('config_max_age', 'system_fields'),
				'keyword' => 'max_age',
				'type' => 'text',
				'class' => 'input-small',
				'default' => '',
				'required' => true,
				'rules' => array('required', 'intval', 'min_value' => 13, 'max_value' => 100),
			),
		);

		$properties = array(
			'text' => array(
				$options['min_length'],
				$options['max_length'],
			),
			'textarea' => array(
				$options['min_length'],
				$options['max_length_textarea'],
			),
			'number' => array(
				$options['min_value'],
				$options['max_value'],
			),
			'price' => array(
				$options['min_value'],
				$options['max_value'],
			),
			'birthday' => array(
				$options['min_age'],
				$options['max_age'],
			),
		);

		return $properties;
	}

	public function saveField($plugin, $table, $categoryID, $fieldID, $field, $items = array())
	{
		// Get old field
		$fieldOld = array();
		if ( $fieldID )
		{
			$fieldOld = $this->fields_model->getField($fieldID);
		}

		// Alter the database table
		if ( !$this->alterDatabase($table, $fieldID, $field, $fieldOld) )
		{
			return false;
		}

		// Assign vars
		$field['plugin'] = $plugin;
		$field['category_id'] = $categoryID;
		$field['config'] = json_encode($field['config']);

		// Is this a new field?
		if ( !$fieldID )
		{
			// Count existing fields
			$row = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:core_fields` WHERE `plugin`=? AND `category_id`=?", array($plugin, $categoryID))->row();
			$field['order_id'] = $row['totalrows'] + 1;

			// Save field
			$fieldID = $this->db->insert('core_fields', $field);

			// Save items
			$this->saveItems($plugin, $table, $field['keyword'], $fieldID, $categoryID, $items);
		}
		else
		{
			// Update field
			$this->db->update('core_fields', $field, array('field_id' => $fieldID), 1);

			// Save items
			$this->saveItems($plugin, $table, $field['keyword'], $fieldID, $categoryID, $items, $field, $fieldOld);
		}

		$this->cache->cleanup();

		return $fieldID;
	}

	public function saveFieldOrderID($fieldID, $orderID)
	{
		// Update field
		$retval = $this->db->update('core_fields', array('order_id' => $orderID), array('field_id' => $fieldID), 1);

		$this->cache->cleanup();

		return $retval;
	}

	protected function alterDatabase($table, $fieldID, $field, $fieldOld)
	{
		loader::library('dbforge');

		// Column name
		$column = array();

		// Select field type
		switch ( $field['type'] )
		{
			case 'textarea':
				$column = array(
					'type' => 'text',
					'null' => true,
				);
				break;

			case 'text':
				$column = array(
					'type' => 'varchar',
					'constraint' => isset($field['config']['max_length']) ? $field['config']['max_length'] : 255,
					'null' => true,
				);

				break;

			case 'section':
			case 'website':
				$column = array(
					'type' => 'varchar',
					'constraint' => 255,
					'null' => true,
				);

				break;

			case 'birthday':
				$column = array(
					'type' => 'int',
					'constraint' => 8,
					'null' => false,
					'default' => 0,
				);

				break;

			case 'country':
			case 'location':
				$column = array(
					'type' => 'smallint',
					'constraint' => 3,
					'null' => false,
					'default' => 0,
				);

				break;

			case 'select':
			case 'radio':
			case 'number':
				$column = array(
					'type' => 'int',
					'constraint' => 10,
					'null' => false,
					'default' => 0,
				);

				break;

			case 'price':
				$column = array(
					'type' => 'double',
					'constraint' => '10,2',
					'null' => false,
					'unsigned' => true,
					'default' => 0,
				);

				break;

			case 'checkbox':
				break;

			default: return true;
		}

		// Is this a new field?
		if ( !$fieldID )
		{
			// Is this a single value field?
			if ( $this->fields_model->getValueFormat($field['type']) != 'multiple' )
			{
				// Is multi language enabled?
				if ( $field['multilang'] )
				{
					// Loop through languages
					foreach ( config::item('languages', 'core', 'keywords') as $lang )
					{
						$column['name'] = 'data_' . $field['keyword'] . '_' . $lang;

						// Add table column
						if ( !$this->dbforge->addColumn(':prefix:' . $table, $column, '', true) )
						{
							return false;
						}
					}
				}
				else
				{
					$column['name'] = 'data_' . $field['keyword'];

					// Add table column
					if ( !$this->dbforge->addColumn(':prefix:' . $table, $column, '', true) )
					{
						return false;
					}
				}
			}
		}
		else
		{
			// Do we have a new column type?
			if ( $fieldOld['type'] != $field['type'] && !$this->compareValueFormats($fieldOld['type'], $field['type']) )
			{
				// Is multi language enabled in the old field?
				if ( $fieldOld['multilang'] )
				{
					// Loop through languages
					foreach ( config::item('languages', 'core', 'keywords') as $lang )
					{
						// Drop old table column
						$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $fieldOld['keyword'] . '_' . $lang);

						if ( $this->fields_model->isValueColumn($field['type']) )
						{
							$column['name'] = 'data_' . $field['keyword'] . '_' . $lang;

							// Add table column
							if ( !$this->dbforge->addColumn(':prefix:' . $table, $column, '', true) )
							{
								return false;
							}
						}
					}
				}
				else
				{
					// Drop table column
					$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $fieldOld['keyword']);

					if ( $this->fields_model->isValueColumn($field['type']) )
					{
						$column['name'] = 'data_' . $field['keyword'];

						// Add table column
						if ( !$this->dbforge->addColumn(':prefix:' . $table, $column, '', true) )
						{
							return false;
						}
					}
				}
			}
			elseif ( $this->fields_model->isValueColumn($field['type']) )
			{
				// Is multi language enabled in the old field?
				if ( $fieldOld['multilang'] )
				{
					// Loop through languages
					foreach ( config::item('languages', 'core', 'keywords') as $lang )
					{
						$column['change'] = 'data_' . $fieldOld['keyword'] . '_' . $lang;
						$column['name'] = 'data_' . $field['keyword'] . '_' . $lang;

						// Modify table column
						if ( !$this->dbforge->changeColumn(':prefix:' . $table, $column) )
						{
							return false;
						}
					}
				}
				else
				{
					$column['change'] = 'data_' . $fieldOld['keyword'];
					$column['name'] = 'data_' . $field['keyword'];

					// Modify table column
					if ( !$this->dbforge->changeColumn(':prefix:' . $table, $column) )
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	public function adjustColumn($table, $keyword, $fieldID, $field)
	{
		// Is this a select or radio column?
		if ( $this->fields_model->getValueFormat($field['type']) == 'number' )
		{
			if ( isset($field['config']['max_value']) )
			{
				$min = isset($field['config']['min_value']) ? $field['config']['min_value'] : 0;
				$max = $field['config']['max_value'];
			}
			else
			{
				$row = $this->db->query("SELECT MAX(`order_id`) AS max_id FROM `:prefix:core_fields_items` WHERE `field_id`=? LIMIT 1", array($fieldID))->row();
				$min = 0;
				$max = $row ? $row['max_id'] : 1;
			}

			$column = array(
				'change' => 'data_' . $keyword,
				'name' => 'data_' . $keyword,
				'null' => false,
				'unsigned' => $min >= 0 ? true : false,
				'default' => 0,
			);

			if ( $max < 100 ) { $column['type'] = 'tinyint'; $column['constraint'] = 2; }
			elseif ( $max < 255 ) { $column['type'] = 'tinyint'; $column['constraint'] = 3; }
			elseif ( $max < 1000 ) { $column['type'] = 'smallint'; $column['constraint'] = 3; }
			elseif ( $max < 10000 ) { $column['type'] = 'smallint'; $column['constraint'] = 4; }
			elseif ( $max < 65535 ) { $column['type'] = 'smallint'; $column['constraint'] = 5; }
			elseif ( $max < 100000 ) { $column['type'] = 'int'; $column['constraint'] = 5; }
			elseif ( $max < 1000000 ) { $column['type'] = 'int'; $column['constraint'] = 6; }
			elseif ( $max < 10000000 ) { $column['type'] = 'int'; $column['constraint'] = 7; }
			elseif ( $max < 100000000 ) { $column['type'] = 'int'; $column['constraint'] = 8; }
			elseif ( $max < 1000000000 ) { $column['type'] = 'int'; $column['constraint'] = 9; }
			else  { $column['type'] = 'int'; $column['constraint'] = 10; };

			$this->dbforge->changeColumn(':prefix:' . $table, $column);
		}
		// Is this a price column?
		elseif ( $this->fields_model->getValueFormat($field['type']) == 'double' )
		{
			$max = isset($field['config']['max_value']) ? $field['config']['max_value'] : 10;

			$column = array(
				'change' => 'data_' . $keyword,
				'name' => 'data_' . $keyword,
				'null' => false,
				'unsigned' => true,
				'default' => 0,
				'type' => 'double',
				'constraint' => ( isset($field['config']['max_value']) ? strlen($field['config']['max_value']) + 2 : 10 ).',2',
			);

			$this->dbforge->changeColumn(':prefix:' . $table, $column);
		}
		// Is this a location column?
		elseif ( $this->fields_model->getValueFormat($field['type']) == 'location' )
		{
			// Does the column exist?
			if ( !$this->dbforge->columnExists(':prefix:' . $table, 'data_' . $field['keyword'] . '_state') )
			{
				$column = array(
					'name' => 'data_' . $keyword . '_state',
					'type' => 'int',
					'constraint' => 5,
					'null' => false,
					'default' => 0,
				);

				// Add column
				$this->dbforge->addColumn(':prefix:' . $table, $column, 'data_' . $keyword);
			}

			// Does the column exist?
			if ( !$this->dbforge->columnExists(':prefix:' . $table, 'data_' . $field['keyword'] . '_city') )
			{
				$column = array(
					'name' => 'data_' . $keyword . '_city',
					'type' => 'int',
					'constraint' => 6,
					'null' => false,
					'default' => 0,
				);

				// Add column
				$this->dbforge->addColumn(':prefix:' . $table, $column, 'data_' . $keyword);
			}
		}
	}

	public function compareValueFormats($from, $to)
	{
		if ( $this->fields_model->isValueColumn($from) != $this->fields_model->isValueColumn($to) )
		{
			return false;
		}

		$from = $this->fields_model->getValueFormat($from);
		$to = $this->fields_model->getValueFormat($to);

		switch ( $from )
		{
			case 'number':
				if ( $to == 'number' || $to == 'double' || $to == 'text' ) return true;
				break;

			case 'double':
				if ( $to == 'text' ) return true;
				break;

			case 'birthday':
				if ( $to == 'birthday' ) return true;
				break;

			case 'location':
				if ( $to == 'location' ) return true;
				break;

			case 'country':
				if ( $to == 'country' ) return true;
				break;

			default:
				if ( $to == 'text' ) return true;
		}

		return false;
	}

	public function saveItems($plugin, $table, $keyword, $fieldID, $categoryID, $items, $field = array(), $fieldOld = array())
	{
		if ( ( !$fieldOld || !$fieldOld['items'] ) && !$items )
		{
			return true;
		}

		// Get previous max order ID
		$maxOldItemID = 0;
		if ( $fieldOld )
		{
			foreach ( $fieldOld['items'] as $item )
			{
				if ( $item['order_id'] > $maxOldItemID )
				{
					$maxOldItemID = $item['order_id'];
				}
			}
		}

		// Loop through items
		foreach ( $items as $itemID => $item )
		{
			$item['field_id'] = $fieldID;

			// Does this item exist?
			if ( $fieldOld && isset($fieldOld['items'][$itemID]) )
			{
				$this->db->update('core_fields_items', $item, array('item_id' => $itemID, 'field_id' => $fieldID), 1);
				unset($fieldOld['items'][$itemID]);
			}
			else
			{
				$itemID = $this->db->insert('core_fields_items', $item);
			}
		}

		// Get remaining item IDs
		$itemIDs = array();
		if ( $fieldOld )
		{
			foreach ( $fieldOld['items'] as $itemID => $item )
			{
				$itemIDs[] = $itemID;
			}
		}

		// Do we have any items left?
		if ( $itemIDs )
		{
			// Remove remaining items
			$this->db->query("DELETE FROM `:prefix:core_fields_items` WHERE `field_id`=? AND `item_id` IN (" . implode(',', $itemIDs) . ")", array($fieldID), count($itemIDs));

			// Reset deleted item selections
			if ( $this->fields_model->isValueColumn($field['type']) )
			{
				// Is multi language enabled?
				if ( $field['multilang'] )
				{
					// Loop through languages
					foreach ( config::item('languages', 'core', 'keywords') as $lang )
					{
						// Reset item data
						$this->db->query("UPDATE `:prefix:" . $table . "` SET `data_" . $keyword . "_" . $lang . "`=0 WHERE `data_" . $keyword . "_" . $lang . "` IN (" . implode(',', $itemIDs) . ")");
					}
				}
				else
				{
					// Reset item data
					$this->db->query("UPDATE `:prefix:" . $table . "` SET `data_" . $keyword . "`=0 WHERE `data_" . $keyword . "` IN (" . implode(',', $itemIDs) . ")");
				}
			}
		}
	}

	public function updateItemsIDs($table, $keyword, $itemsOld, $itemsNew)
	{
		$column = array(
			'name' => 'data_' . $keyword . '__temp',
			'type' => 'int',
			'constraint' => 10,
			'null' => false,
			'unsigned' => true,
			'default' => 0,
		);

		// Create new temporary column
		if ( !$this->dbforge->addColumn(':prefix:' . $table, $column, '', true) )
		{
			return false;
		}

		// Loop through items IDs
		foreach ( $itemsOld as $itemID => $orderID )
		{
			$this->db->update($table, array('data_' . $keyword . '__temp' => isset($itemsNew[$itemID]) ? $itemsNew[$itemID] : 0), array('data_' . $keyword => $orderID));
		}

		// Drop old table column
		if ( !$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $keyword) )
		{
			return false;
		}

		$column['change'] = 'data_' . $keyword . '__temp';
		$column['name'] = 'data_' . $keyword;

		// Rename temp column
		if ( !$this->dbforge->changeColumn(':prefix:' . $table, $column) )
		{
			return false;
		}

		return true;
	}

	public function getTypes($flat = false, $restrict = array())
	{
		$general = array(
			'text' => __('type_text', 'system_fields'),
			'textarea' => __('type_textarea', 'system_fields'),
			'number' => __('type_number', 'system_fields'),
			'select' => __('type_select', 'system_fields'),
			'radio' => __('type_radio', 'system_fields'),
			'checkbox' => __('type_checkbox', 'system_fields'),
		);

		$custom = array(
			'section' => __('type_section', 'system_fields'),
			'location' => __('type_location', 'system_fields'),
			'country' => __('type_country', 'system_fields'),
			'price' => __('type_price', 'system_fields'),
			'birthday' => __('type_birthday', 'system_fields'),
			'website' => __('type_website', 'system_fields'),
		);

		if ( $restrict )
		{
			foreach ( $general as $key => $value )
			{
				if ( !in_array($key, $restrict) )
				{
					unset($general[$key]);
				}
			}
			foreach ( $custom as $key => $value )
			{
				if ( !in_array($key, $restrict) )
				{
					unset($custom[$key]);
				}
			}
		}

		if ( $flat )
		{
			return array_merge($general, $custom);
		}
		else
		{
			$types = array();
			if ( $general )
			{
				$types[__('type_general', 'system_fields')] = $general;
			}
			if ( $custom )
			{
				$types[__('type_specific', 'system_fields')] = $custom;
			}
			return $types;
		}
	}

	public function deleteField($plugin, $table, $fieldID, $field)
	{
		loader::library('dbforge');

		// Is multi language enabled?
		if ( $field['multilang'] )
		{
			// Loop through languages
			foreach ( config::item('languages', 'core', 'keywords') as $lang )
			{
				// Drop table column
				$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $field['keyword'] . '_' . $lang);
			}
		}
		else
		{
			$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $field['keyword']);

			if ( $field['type'] == 'location' )
			{
				$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $field['keyword'] . '_state');
				$this->dbforge->dropColumn(':prefix:' . $table, 'data_' . $field['keyword'] . '_city');
			}
		}

		// Delete field items
		$this->db->delete('core_fields_items', array('field_id' => $fieldID));

		// Delete field
		if ( $retval = $this->db->delete('core_fields', array('plugin' => $plugin, 'category_id' => $field['category_id'], 'field_id' => $fieldID), 1) )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:core_fields` SET `order_id`=`order_id`-1 WHERE `plugin`=? AND `category_id`=? AND `order_id`>?", array($plugin, $field['category_id'], $field['order_id']));
		}

		$this->cache->cleanup();

		return $retval;
	}

	protected function isTableColumn($table, $keyword)
	{
		// Get table column
		return $this->db->query("SHOW COLUMNS FROM `:prefix:" . $table . "` LIKE 'data_" . $keyword . "'")->row();
	}
}
