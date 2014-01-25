<?php

class System_MetaTags_Model extends Model
{
	public function saveMetaTags($plugin, $keyword, $data)
	{
		$retval = $this->db->update('core_meta_tags', $data, array('plugin' => $plugin, 'keyword' => $keyword), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/seo/update', $plugin, $keyword, $data);
		}

		$this->cache->cleanup();

		return $retval;
	}

	public function getMetaTags($plugin)
	{
		$data = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_meta_tags` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $tags )
		{
			foreach ( config::item('languages', 'core', 'keywords') as $lang )
			{
				$data[$tags['keyword']]['meta_title_' . $lang] = $tags['meta_title_' . $lang];
				$data[$tags['keyword']]['meta_description_' . $lang] = $tags['meta_description_' . $lang];
				$data[$tags['keyword']]['meta_keywords_' . $lang] = $tags['meta_keywords_' . $lang];
			}
		}

		return $data;
	}

	public function getPlugins($escape = true)
	{
		$plugins = array();

		$result = $this->db->query("SELECT `plugin` FROM `:prefix:core_meta_tags` GROUP BY `plugin`")->result();

		foreach ( $result as $plugin )
		{
			$plugins[$plugin['plugin']] = $escape ? text_helper::entities(config::item('plugins', 'core', $plugin['plugin'], 'name')) : config::item('plugins', 'core', $plugin['plugin'], 'name');
		}

		asort($plugins);

		return $plugins;
	}

	public function set($plugin, $keyword, $replace = array(), $pageTitle = true)
	{
		if ( !( $data = $this->cache->item('core_meta_tags_' . $plugin . '_' . session::item('language')) ) )
		{
			$data = array();

			$result = $this->db->query("SELECT * FROM `:prefix:core_meta_tags` WHERE `plugin`=?", array($plugin))->result();

			foreach ( $result as $tags )
			{
				$data[$tags['keyword']]['title'] = $tags['meta_title_' . session::item('language')];
				$data[$tags['keyword']]['description'] = $tags['meta_description_' . session::item('language')];
				$data[$tags['keyword']]['keywords'] = $tags['meta_keywords_' . session::item('language')];
			}

			$this->cache->set('core_meta_tags_' . $plugin . '_' . session::item('language'), $data, 60*60*24*30);
		}

		foreach ( $replace as $section => $array )
		{
			foreach ( $array as $k => $v )
			{
				$k = '[' . $section . '.' . $k . ']';
				if ( is_array($v) )
				{
					$v = count($v) == 1 ? current($v) : implode(',', $v);
				}
				$data[$keyword]['title'] = utf8::str_replace($k, $v, $data[$keyword]['title']);
				$data[$keyword]['description'] = utf8::str_replace($k, $v, $data[$keyword]['description']);
				$data[$keyword]['keywords'] = utf8::str_replace($k, $v, $data[$keyword]['keywords']);
			}
		}

		if ( isset($data[$keyword]) )
		{
			if ( $pageTitle )
			{
				view::setTitle($data[$keyword]['title']);
			}
			else
			{
				view::setMetaTitle($data[$keyword]['title']);
			}
			view::setMetaDescription($data[$keyword]['description']);
			view::setMetaKeywords($data[$keyword]['keywords']);
		}
	}
}
