<?php

class System_Lists_Model extends Model
{
	public function addItem($plugin, $type, $keyword, $data = array())
	{
		$data['plugin'] = $plugin;
		$data['type'] = $type;
		$data['keyword'] = $keyword;
		$data['parent'] = isset($data['parent']) ? $data['parent'] : '';

		$row = $this->db->query("SELECT * FROM `:prefix:core_lists` WHERE `plugin`=? AND `type`=? AND `keyword`=? LIMIT 1", array($plugin, $type, $keyword))->row();

		if ( $row )
		{
			$retval = $this->db->update('core_lists', $data, array('item_id' => $row['item_id']), 1);
		}
		else
		{
			$retval = $this->db->insert('core_lists', $data);
		}

		$this->cache->cleanup();

		return $retval;
	}

	public function updateItem($itemID, $item)
	{
		// Update field
		$retval = $this->db->update('core_lists', $item, array('item_id' => $itemID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/templates/navigation/update', $itemID, $item);
		}

		return $retval;
	}

	public function deleteItem($plugin, $type, $keyword)
	{
		$this->db->delete('core_lists', array('plugin' => $plugin, 'type' => $type, 'keyword' => $keyword), 1);

		$this->cache->cleanup();
	}

	public function getItems($listID)
	{
		$items = $this->db->query("SELECT * FROM `:prefix:core_lists` WHERE `type`=? ORDER BY `order_id` ASC, `keyword` ASC", array($listID))->result();

		foreach ( $items as $index => $item )
		{
			$name = explode('|', $item['name']);
			$name = __(current($name), end($name));

			$items[$index]['name'] = $name;
		}

		return $items;
	}

	public function getItem($itemID)
	{
		$item = $this->db->query("SELECT * FROM `:prefix:core_lists` WHERE `item_id`=? LIMIT 1", array($itemID))->row();

		return $item;
	}

	public function toggleItemStatus($plugin, $type, $keyword, $status)
	{
		$retval = $this->db->update('core_lists', array('active' => $status), array('plugin' => $plugin, 'type' => $type, 'keyword' => $keyword), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/templates/navigation/status', $plugin, $type, $keyword, $status);
		}

		$this->cache->cleanup();

		return $retval;
	}

	public function getLists()
	{
		$lists = array();

		$rows = $this->db->query("SELECT `type` FROM `:prefix:core_lists` WHERE `type`!='cp_top_nav' GROUP BY `type`")->result();

		foreach ( $rows as $list )
		{
			$lists[$list['type']] = __('list_' . $list['type'], 'system_config') !== false ? __('list_' . $list['type'], 'system_config') : $list['type'];
		}

		return $lists;
	}

	public function getList($listID)
	{
		$row = $this->db->query("SELECT `type` FROM `:prefix:core_lists` WHERE `type`=? LIMIT 1", array($listID))->row();

		$row['name'] = __('list_' . $row['type'], 'system_config') !== false ? __('list_' . $row['type'], 'system_config') : $row['type'];

		return $row;
	}

	public function getSystemList($type, $parent = '', $level = 1, $active = true)
	{
		if ( $level > 4 )
		{
			return array();
		}

		$lists = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_lists` WHERE `type`=? AND `parent`=? " . ( $active ? "AND `active`=1" : "" ) . " ORDER BY `order_id` ASC, `name` ASC", array($type, $parent))->result();
		foreach ( $result as $list )
		{
			$keyword = $list['keyword'] ? $list['keyword'] : $list['item_id'];

			$name = explode('|', $list['name']);
			$name = __(current($name), end($name));

			if ( $parent == '' )
			{
				$lists[$keyword] = array(
					'name' => $list['name'],
					'uri' => $list['uri'],
					'attr' => @json_decode($list['attr'], true),
				);
				$lists[$keyword]['name'] = $name ? $name : $list['name'];
				if ( $list['keyword'] )
				{
					$lists[$keyword]['keyword'] = $list['keyword'];
					$lists[$keyword]['items'] = $this->getSystemList($type, $list['keyword'], ( $level+1 ), $active);
				}
			}
			else
			{
				$lists[$keyword] = array(
					'name' => $list['name'],
					'uri' => $list['uri'],
					'attr' => @json_decode($list['attr'], true),
				);
				$lists[$keyword]['name'] = $name ? $name : $list['name'];
				if ( $list['keyword'] )
				{
					$lists[$keyword]['keyword'] = $list['keyword'];
					$lists[$keyword]['items'] = $this->getSystemList($type, $list['keyword'], ( $level + 1 ), $active);
				}
			}
		}

		return $lists;
	}
}
