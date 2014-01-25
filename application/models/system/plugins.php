<?php

class System_Plugins_Model extends Model
{
	public function getPlugins($escape = true)
	{
		$plugins = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_plugins` ORDER BY `name` ASC")->result();

		foreach ( $result as $plugin )
		{
			if ( $escape )
			{
				$plugin['name'] = text_helper::entities($plugin['name']);
				$plugin['version'] = text_helper::entities($plugin['version']);
			}

			$plugins[$plugin['keyword']] = $plugin;
		}

		return $plugins;
	}

	public function getPlugin($keyword, $escape = true)
	{
		$plugin = $this->db->query("SELECT * FROM `:prefix:core_plugins` WHERE `keyword`=? LIMIT 1", array($keyword))->row();

		if ( $plugin && $escape )
		{
			$plugin['name'] = text_helper::entities($plugin['name']);
			$plugin['version'] = text_helper::entities($plugin['version']);
		}

		return $plugin;
	}

	public function scanPlugins($merge = true, $escape = true)
	{
		// Load file helper and read plugins directory
		loader::helper('file');
		$dirs = file_helper::scanDirectoryNames(DOCPATH . 'plugins');

		$plugins = array();

		// Loop through found directories
		foreach ( $dirs as $plugin )
		{
			if ( $manifest = $this->getManifest($plugin, false, $escape) )
			{
				$plugins[$plugin] = $manifest;
			}
		}

		// Do we need to merge results with installed plugins?
		if ( $merge )
		{
			// Loop through installed plugins
			foreach ( $this->getPlugins($escape) as $plugin )
			{
				if ( isset($plugins[$plugin['keyword']]) )
				{
					$plugins[$plugin['keyword']]['plugin_id'] = $plugin['plugin_id'];
					$plugins[$plugin['keyword']]['name'] = $plugin['name'];
					$plugins[$plugin['keyword']]['version_new'] = $plugins[$plugin['keyword']]['version'];
					$plugins[$plugin['keyword']]['version'] = $plugin['version'];
					$plugins[$plugin['keyword']]['system'] = $plugin['system'];
				}
			}
		}

		// Order plugins
		ksort($plugins);

		return $plugins;
	}

	public function getManifest($keyword, $existing = false, $escape = true)
	{
		$manifest = array();

		// Include manifest file
		if ( @is_file(DOCPATH . 'plugins/' . $keyword . '/manifest.php') && @include(DOCPATH . 'plugins/' . $keyword . '/manifest.php') )
		{
			// Does params variable exist?
			if ( isset($params) && isset($params['name']) )
			{
				$manifest = array(
					'keyword' => $keyword,
					'name' => $params['name'],
					'values' => array(),
					'description' => isset($params['description']) ? $params['description'] : '',
					'author' => isset($params['author']) ? $params['author'] : '',
					'website' => isset($params['website']) ? $params['website'] : '',
					'version' => isset($params['version']) ? $params['version'] : '1.0',
					'requirements' => isset($params['requirements']) && is_array($params['requirements']) ? $params['requirements'] : array(),
					'settings' => isset($params['settings']) && $params['settings'] ? 1 : 0,
				);

				if ( $escape )
				{
					foreach ( array('name', 'description', 'author', 'version') as $item )
					{
						$manifest[$item] = text_helper::entities($manifest[$item]);
					}
				}
			}
		}

		if ( $existing )
		{
			$plugin = $this->db->query("SELECT * FROM `:prefix:core_plugins` WHERE `keyword`=? LIMIT 1", array($keyword))->row();
			if ( $plugin )
			{
				$manifest['version_current'] = $escape ? text_helper::entities($plugin['version']) : $plugin['version'];
			}
		}

		return $manifest;
	}

	public function getSetupClass($keyword, $manifest)
	{
		// Load plugins library
		loader::model('system/pluginsdb', array(), false);

		// Verify manifest file
		if ( @is_file(DOCPATH . 'plugins/' . $keyword . '/install.php') )
		{
			// Include manifest file
			if ( @include(DOCPATH . 'plugins/' . $keyword . '/install.php') )
			{
				if ( class_exists('Plugins_' . $keyword . '_Install', false) )
				{
					$class = 'Plugins_' . $keyword . '_Install';
					return new $class($manifest);
				}
			}
		}

		return false;
	}

	public function install($keyword)
	{
		// Get plugin
		$manifest = $this->getManifest($keyword, false, false);

		// Create data array
		$data = array(
			'name' => $manifest['name'],
			'keyword' => $manifest['keyword'],
			'version' => $manifest['version'],
			'settings' => $manifest['settings'],
		);

		// Does installation class exist?
		if ( $class = $this->getSetupClass($keyword, $manifest) )
		{
			if ( method_exists($class, 'install') )
			{
				$this->db->startTransaction();
				if ( $class->install() )
				{
					$this->runInstallFunctions($class, 'install');
				}
				$this->db->endTransaction();
			}
			else
			{
				view::setError(__('no_class_method', 'system_plugins', array('%method' => 'Install')));
				return false;
			}
		}
		else
		{
			view::setError(__('no_class', 'system_plugins'));
			return false;
		}

		// Insert plugin
		$pluginID = $this->db->insert('core_plugins', $data);

		if ( $pluginID )
		{
			// Action hook
			hook::action('system/plugins/install', $pluginID, $data);
		}

		session::delete('', 'config');
		session::set('group_id', 0, 'permissions_system');
		$this->cache->cleanup();

		return $pluginID;
	}

	public function update($pluginID, $manifest)
	{
		// Does installation class exist?
		if ( $class = $this->getSetupClass($manifest['keyword'], $manifest) )
		{
			if ( method_exists($class, 'update') )
			{
				$this->db->startTransaction();
				if ( $class->update() )
				{
					$this->runInstallFunctions($class, 'update');
				}
				$this->db->endTransaction();
			}
			else
			{
				view::setError(__('no_class_method', 'system_plugins', array('%method' => 'Update')));
				return false;
			}
		}
		else
		{
			view::setError(__('no_class', 'system_plugins'));
			return false;
		}

		// Create data array
		$data = array(
			'name' => $manifest['name'],
			'version' => $manifest['version'],
		);

		// Update plugin
		$retval = $this->db->update('core_plugins', $data, array('plugin_id' => $pluginID));

		if ( $retval )
		{
			// Action hook
			hook::action('system/plugins/update', $pluginID, $manifest);
		}

		session::delete('', 'config');
		session::set('group_id', 0, 'permissions_system');
		$this->cache->cleanup();

		return $retval;
	}

	public function uninstall($pluginID, $plugin)
	{
		// Does installation class exist?
		if ( $class = $this->getSetupClass($plugin['keyword'], $plugin) )
		{
			if ( method_exists($class, 'uninstall') )
			{
				if ( $class->uninstall() )
				{
					$this->runInstallFunctions($class, 'uninstall');
				}
			}
			else
			{
				view::setError(__('no_class_method', 'system_plugins', array('%method' => 'Uninstall')));
				return false;
			}
		}
		else
		{
			view::setError(__('no_class', 'system_plugins'));
			return false;
		}

		// Delete plugin
		$retval = $this->db->delete('core_plugins', array('plugin_id' => $pluginID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('system/plugins/uninstall', $pluginID, $plugin);
		}

		session::delete('', 'config');
		session::set('group_id', 0, 'permissions_system');
		$this->cache->cleanup();

		return $retval;
	}

	public function checkRequirements($plugins)
	{
		foreach ( $plugins as $plugin => $version )
		{
			if ( !config::item('plugins', 'core', strtolower($plugin)) || config::item('plugins', 'core', strtolower($plugin), 'version') < $version )
			{
				if ( strtolower($plugin) == 'system' )
				{
					view::setError(__('plugin_system_required', 'system_plugins', array('%version' => $version)));
				}
				else
				{
					view::setError(__('plugin_required', 'system_plugins', array('%plugin' => $plugin . ' v' . $version)));
				}
				return false;
			}
		}

		return true;
	}

	public function runInstallFunctions($class, $action)
	{
		$class->{$action.'Resources'}();
		$class->{$action.'Hooks'}();
		$class->{$action.'Lists'}();
		$class->{$action.'Permissions'}();
		$class->{$action.'Settings'}();
		$class->{$action.'MetaTags'}();
		$class->{$action.'Fields'}();
		$class->{$action.'Timeline'}();
		$class->{$action.'EmailTemplates'}();
		$class->{$action.'LanguageData'}();
	}
}
