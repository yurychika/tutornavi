<?php

class Utilities_Counters_Model extends Model
{
	public function updateCounters($plugin)
	{
		if ( file_exists(DOCPATH . 'models/' . $plugin['keyword'] . '/' . $plugin['keyword'] . EXT) )
		{
			$model = loader::model($plugin['keyword'] . '/' . $plugin['keyword'], array(), null);
			if ( method_exists($model, 'updateDbCounters') )
			{
				$plugins[] = $plugin['name'];
			}
		}

		return $templates;
	}

	public function getPlugins($escape = true)
	{
		$plugins = array();

		foreach ( config::item('plugins', 'core') as $plugin )
		{
			if ( file_exists(DOCPATH . 'models/' . $plugin['keyword'] . '/' . $plugin['keyword'] . EXT) )
			{
				$model = loader::model($plugin['keyword'] . '/' . $plugin['keyword'], array(), null);
				if ( method_exists($model, 'updateDbCounters') )
				{
					$plugins[$plugin['keyword']] = $escape ? text_helper::entities($plugin['name']) : $plugin['name'];
				}
			}
		}

		asort($plugins);

		return $plugins;
	}
}
