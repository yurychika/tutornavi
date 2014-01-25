<?php

class Users_Groups_Model extends Model
{
	public function saveGroup($groupID, $group, $copyID = 0)
	{
		// Is this a new user group?
		if ( !$groupID )
		{
			// Get existing groups
			$groups = $this->getGroups();
			$group['order_id'] = count($groups) + 1;

			// Save user group
			$groupID = $this->db->insert('users_groups', $group);

			// Add table column
			if ( !$this->db->query("ALTER TABLE `:prefix:users_permissions` ADD `group_" . $groupID . "` VARCHAR(255) DEFAULT NULL") )
			{
				return false;
			}

			if ( $copyID )
			{
				$this->db->query("UPDATE `:prefix:users_permissions` SET `group_" . $groupID . "`=`group_" . $copyID . "`");
			}

			// Action hook
			hook::action('users/groups/insert', $groupID, $group);
		}
		else
		{
			// Save user group
			$this->db->update('users_groups', $group, array('group_id' => $groupID), 1);

			// Action hook
			hook::action('users/groups/update', $groupID, $group);
		}

		$this->cache->cleanup();

		return $groupID;
	}

	public function getGroup($groupID, $escape = true)
	{
		// Get user group
		$group = $this->db->query("SELECT * FROM `:prefix:users_groups` WHERE `group_id`=? LIMIT 1", array($groupID))->row();

		// Set name
		if ( $group )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$group['name_' . $language] = text_helper::entities($group['name_' . $language]);
				}
			}

			$group['name'] = $group['name_' . session::item('language')];
		}

		return $group;
	}

	public function getGroups($escape = true)
	{
		// Get user groups
		$groups = $this->db->query("SELECT * FROM `:prefix:users_groups` ORDER BY `order_id` ASC")->result();

		foreach ( $groups as $index => $group )
		{
			$groups[$index]['name'] = $escape ? text_helper::entities($group['name_' . session::item('language')]) : $group['name_' . session::item('language')];
		}

		return $groups;
	}

	public function deleteGroup($groupID, $group)
	{
		// Drop table column
		if ( !$this->db->query("ALTER TABLE `:prefix:users_permissions` DROP `group_" . $groupID . "`") )
		{
			return false;
		}

		// Delete user group
		if ( $retval = $this->db->delete('users_groups', array('group_id' => $groupID), 1) )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:users_groups` SET `order_id`=`order_id`-1 WHERE `order_id`>?", array($group['order_id']));

			// Action hook
			hook::action('users/groups/delete', $groupID, $group);
		}

		$this->cache->cleanup();

		return $retval;
	}

	public function getPermissions($groupID, $plugin = '')
	{
		$permissions = array();
		$result = $this->db->query("SELECT * FROM `:prefix:users_permissions` " . ( $plugin ? "WHERE plugin=?" : "" ) . " ORDER BY `cp` ASC, `order_id` ASC", array($plugin))->result();
		foreach ( $result as $permission )
		{
			$section = $permission['cp'] ? 'cp' : 'ca';

			if ( $permission['items'] )
			{
				$permission['items'] = @json_decode($permission['items'], true);
				if ( !is_array($permission['items']) )
				{
					$permission['items'] = array();
				}

				foreach ( $permission['items'] as $index => $key )
				{
					if ( __($key, $permission['plugin'] . '_config') !== false )
					{
						$permission['items'][$index] = __($key, $permission['plugin'] . '_config');
					}
					elseif ( __($key, $permission['plugin']) !== false )
					{
						$permission['items'][$index] = __($key, $permission['plugin']);
					}
					elseif ( __($index, $key) !== false )
					{
						$permission['items'][$index] = __($index, $key);
					}
					else
					{
						$permission['items'][$index] = $key;
					}
				}
			}

			if ( __($permission['keyword'], $permission['plugin'] . '_permissions') !== false )
			{
				$name = __($permission['keyword'], $permission['plugin'] . '_permissions', array(), array(), false);
			}
			elseif ( __($permission['keyword'], 'system_permissions') !== false )
			{
				$name = __($permission['keyword'], 'system_permissions', array(), array(), false);
			}
			else
			{
				$name = $permission['keyword'];
			}

			$permissions[$section][] = array(
				'name' => $name,
				'keyword' => $permission['keyword'],
				'type' => $permission['type'],
				'callback' => $permission['callback'],
				'items' => $permission['items'],
				'value' => $permission['group_' . $groupID],
				'guests' => $permission['guests'],
				'order_id' => $permission['order_id']
			);
		}

		return $permissions;
	}

	public function savePermissions($groupID, $plugin, $permissions, $orderID = array())
	{
		// Loop through permissions groups
		foreach ( $permissions as $keyword => $value )
		{
			$data = array('group_' . $groupID => $value);
			if ( $orderID && isset($orderID[$keyword]) )
			{
				$data['order_id'] = $orderID[$keyword];
			}

			// Update permissions
			$this->db->update('users_permissions', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);
		}

		// Action hook
		hook::action('users/permissions/update', $groupID, $plugin, $permissions);

		if ( $groupID == session::item('group_id') )
		{
			session::delete('group_id', 'permissions_system');
		}

		$this->cache->cleanup();

		return $groupID;
	}

	public function getPlugins($escape = true)
	{
		$plugins = array();

		$result = $this->db->query("SELECT `plugin` FROM `:prefix:users_permissions` GROUP BY `plugin`")->result();

		foreach ( $result as $plugin )
		{
			$plugins[$plugin['plugin']] = $escape ? text_helper::entities(config::item('plugins', 'core', $plugin['plugin'], 'name')) : config::item('plugins', 'core', $plugin['plugin'], 'name');
		}

		asort($plugins);

		return $plugins;
	}

	public function isUsers($groupID)
	{
		$user = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:users` WHERE `group_id`=? LIMIT 1", array($groupID))->row();

		return $user['totalrows'] ? true : false;
	}
}
