<?php

class System_Hooks_Model extends Model
{
	public function getHooks($manual = 0, $keyword = '')
	{
		$hooks = array('action' => array(), 'filter' => array());

		//$result = $this->db->query("SELECT * FROM `:prefix:core_hooks` WHERE `manual`=? " . ( $keyword ? " AND `keyword`=?" : "" ) . " ORDER BY `keyword` ASC, `order_id` ASC", array($manual, $keyword))->result();
		$result = $this->db->query("SELECT * FROM `:prefix:core_hooks` ORDER BY `keyword` ASC, `order_id` ASC, `plugin` ASC", array($manual, $keyword))->result();

		foreach ( $result as $hook )
		{
			$hooks[$hook['type']][$hook['keyword']][] = array(
				'path' => $hook['path'],
				'object' => $hook['object'],
				'function' => $hook['function'],
			);
		}

		return $hooks;
	}
}
