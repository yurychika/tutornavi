<?php

class Users_Types_Model extends Model
{
	public function saveType($typeID, $type)
	{
		// Is this a new user type?
		if ( !$typeID )
		{
			// Get existing user types and update order ID
			$types = $this->getTypes();
			$type['order_id'] = count($types) + 1;

			loader::library('dbforge');

			$engines = $this->dbforge->getEngines();
			$engine = in_array('InnoDB', $engines) ? 'InnoDB' : 'MyISAM';

			$this->dbforge->dropTable(':prefix:users_data_' . $type['keyword']);
			$this->dbforge->createTable(':prefix:users_data_' . $type['keyword'],
				array(
					array('name' => 'profile_id', 'type' => 'bigint', 'constraint' => 12, 'unsigned' => true, 'null' => false, 'auto_increment' => true),
				),
				array('profile_id'),
				array(),
				array(),
				false,
				$engine
			);

			// Insert user type
			$typeID = $this->db->insert('users_types', $type);

			// Action hook
			hook::action('users/types/insert', $typeID, $type);
		}
		else
		{
			// Get the old user type
			$typeDataOld = $this->getType($typeID);

			// Do we have a new keyword?
			if ( strcmp($typeDataOld['keyword'], $type['keyword']) != 0 )
			{
				loader::library('dbforge');

				$this->dbforge->renameTable(':prefix:users_data_' . $typeDataOld['keyword'], ':prefix:users_data_' . $type['keyword']);
			}

			// Update user type
			$this->db->update('users_types', $type, array('type_id' => $typeID), 1);

			// Action hook
			hook::action('users/types/update', $typeID, $type);
		}

		$this->cache->cleanup();

		return $typeID;
	}

	public function getType($typeID, $escape = true)
	{
		// Get user type
		$type = $this->db->query("SELECT * FROM `:prefix:users_types` WHERE `type_id`=? LIMIT 1", array($typeID))->row();

		if ( $type )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$type['name_' . $language] = text_helper::entities($type['name_' . $language]);
				}
			}

			$type['name'] = $type['name_' . session::item('language')];
		}

		return $type;
	}

	public function getTypes($escape = true)
	{
		// Get user types
		$types = $this->db->query("SELECT * FROM `:prefix:users_types` ORDER BY order_id ASC")->result();

		foreach ( $types as $index => $type )
		{
			$types[$index]['name'] = $escape ? text_helper::entities($type['name_' . session::item('language')]) : $type['name_' . session::item('language')];
		}

		return $types;
	}

	public function deleteType($typeID, $type)
	{
		loader::library('dbforge');
		$this->dbforge->dropTable(':prefix:users_data_' . $type['keyword']);

		// Delete user type
		if ( $retval = $this->db->delete('users_types', array('type_id' => $typeID), 1) )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:users_types` SET `order_id`=`order_id`-1 WHERE `order_id`>?", array($type['order_id']));

			// Select fields IDs
			$fieldIDs = array();
			foreach ( $this->db->query("SELECT `field_id`, `category_id`, `keyword` FROM `:prefix:core_fields` WHERE `category_id`=?", array($typeID))->result() as $field )
			{
				$fieldIDs[] = $field['field_id'];
			}

			// Do we have any field IDs?
			if ( $fieldIDs )
			{
				// Delete field items
				$this->db->query("DELETE FROM `:prefix:core_fields_items` WHERE `field_id` IN (" . implode(',', $fieldIDs) . ")");
			}

			// Delete fields
			$this->db->delete('core_fields', array('category_id' => $typeID));

			// Action hook
			hook::action('users/types/delete', $typeID, $type);
		}

		$this->cache->cleanup();

		return $retval;
	}

	public function isUsers($typeID)
	{
		$user = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:users` WHERE `type_id`=? LIMIT 1", array($typeID))->row();

		return $user['totalrows'] ? true : false;
	}

	public function updateNames($typeID, $field1, $field2)
	{
		$type = config::item('usertypes', 'core', 'keywords', $typeID);

		$retval = false;
		if ( $type )
		{
			$retval = $this->db->query("UPDATE `:prefix:users` AS `u`, `:prefix:users_data_" . $type . "` AS `d`
				SET `u`.`name1`=`d`.`data_" . $field1 . "`, `u`.`name2`=" . ( $field2 ? "`d`.`data_" . $field2 . "`" : "NULL") . "
				WHERE `u`.`user_id`=`d`.`profile_id`");
		}

		return $retval;
	}
}
