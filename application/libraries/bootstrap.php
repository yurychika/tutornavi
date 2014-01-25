<?php defined('SYSPATH') || die('No direct script access allowed.');

class Bootstrap extends Library
{
	protected $data = array();

	public function __construct($config = array())
	{
		$this->initialize($config);
	}

	public function initialize($config = array())
	{
		$this->data = array();

		if ( isset($config['cache']) && !$config['cache'] || !( $this->data = $this->cache->item('core_config_' . ( input::isCP() ? 'cp' : 'fe' ), true) ) || isset($this->data['settings']['system']['devmode']) && $this->data['settings']['system']['devmode'] )
		{
			if ( isset($this->data['settings']['system']['devmode']) )
			{
				$this->cache->cleanup();
			}

			$this->getSettings();
			$this->getPlugins();
			$this->getResources();
			$this->getHooks();
			$this->getLanguages();
			$this->getTemplates();
			$this->getStorages();

			$this->cache->set('core_config_' . ( input::isCP() ? 'cp' : 'fe' ), $this->data, 60*60*24*30, true);
		}

		$this->setSettings();
		$this->setPlugins();
		$this->setResources();
		$this->setHooks();
		$this->setLanguages();
		$this->setTemplates();
		$this->setStorages();

		hook::action('initialize');
	}

	public function update($config = array())
	{
		$this->data = array();

		if ( isset($config['cache']) && !$config['cache'] || !( $this->data = $this->cache->item('core_config_' . ( input::isCP() ? 'cp' : 'fe' ) . '_' . session::item('language'), true) ) )
		{
			if ( !isset($config['update']) || !$config['update'] )
			{
				$this->getLists();
				$this->getCountries();
			}
			$this->getUserTypes();
			$this->getUserGroups();

			$this->cache->set('core_config_' . ( input::isCP() ? 'cp' : 'fe' ) . '_' . session::item('language'), $this->data, 60*60*24*30, true);
		}

		if ( !isset($config['update']) || !$config['update'] )
		{
			$this->setLists();
			$this->setCountries();
		}
		$this->setUserTypes();
		$this->setUserGroups();

		$this->updateConfig();
	}

	public function getSettings()
	{
		loader::model('system/config');

		$settings = $this->config_model->getSettings('', true);

		foreach ( $settings as $plugin => $groups )
		{
			foreach ( $groups as $group => $values )
			{
				foreach ( $values as $setting )
				{
					$this->data['settings'][$plugin][$setting['keyword']] = $setting['value'];

					if ( $setting['type'] == 'dimensions' )
					{
						$dimensions = explode('x', $setting['value']);
						$this->data['settings'][$plugin][$setting['keyword'] . '_width'] = isset($dimensions[0]) ? $dimensions[0] : 0;
						$this->data['settings'][$plugin][$setting['keyword'] . '_height'] = isset($dimensions[1]) ? $dimensions[1] : 0;
					}
				}
			}
			ksort($this->data['settings'][$plugin]);
		}
		ksort($this->data['settings']);

		if ( isset($this->data['settings']['system']['devmode']) && $this->data['settings']['system']['devmode'] )
		{
			error_reporting(-1);
			ini_set('display_errors', 1);
		}
	}

	public function setSettings()
	{
		config::set('site_url', config::siteURL('/'), 'config');

		foreach ( $this->data['settings'] as $plugin => $settings )
		{
			config::set($settings, '', $plugin);
		}
	}

	public function getPlugins()
	{
		loader::model('system/plugins');

		$plugins = $this->plugins_model->getPlugins(false);

		foreach ( $plugins as $plugin )
		{
			$this->data['plugins'][$plugin['keyword']] = array(
				'plugin_id' => $plugin['plugin_id'],
				'keyword' => $plugin['keyword'],
				'name' => $plugin['name'],
				'version' => $plugin['version'],
			);
		}
	}

	public function setPlugins()
	{
		config::set('plugins', $this->data['plugins'], 'core');
	}

	public function getResources()
	{
		loader::model('system/resources');

		$this->data['resources'] = $this->resources_model->getResources();
	}

	public function setResources()
	{
		config::set('resources', $this->data['resources'], 'core');
	}

	public function getHooks()
	{
		loader::model('system/hooks');

		$this->data['hooks'] = $this->hooks_model->getHooks();

		if ( isset($this->data['hooks']['filter']['initialize/config']) )
		{
			hook::addFilters(array('initialize/config' => $this->data['hooks']['filter']['initialize/config']));
			unset($this->data['hooks']['filter']['initialize/config']);

			$this->data = hook::filter('initialize/config', $this->data);
		}
	}

	public function setHooks()
	{
		hook::addActions($this->data['hooks']['action']);
		hook::addFilters($this->data['hooks']['filter']);
	}

	public function getStorages()
	{
		loader::model('system/storage');

		$services = $this->storage_model->getServices();

		foreach ( $services as $service )
		{
			$this->data['storages'][$service['keyword']] = $service['service_id'];
			$this->data['storages'][$service['service_id']] = $service['keyword'];

			$settings = @json_decode($service['settings'], true);

			$this->data['storages']['settings'][$service['service_id']] = $settings ? $settings : array();
		}
	}

	public function setStorages()
	{
		config::set('storages', $this->data['storages'], 'core');
	}

	public function getLanguages()
	{
		loader::model('system/languages');

		$languages = $this->languages_model->getLanguages();

		foreach ( $languages as $language )
		{
			$this->data['languages']['keywords'][$language['language_id']] = $language['keyword'];
			$this->data['languages']['names'][$language['language_id']] = $language['name'];
		}
	}

	public function setLanguages()
	{
		config::set('languages', $this->data['languages'], 'core');

		if ( config::item('devmode', 'system') )
		{
			$this->languages_model->compile(config::item('languages', 'core', 'keywords', config::item('language_id', 'system')));
		}
	}

	public function getTemplates()
	{
		loader::model('system/templates');

		$templates = $this->templates_model->getTemplates();

		foreach ( $templates as $template )
		{
			$this->data['templates']['keywords'][$template['template_id']] = $template['keyword'];
			$this->data['templates']['names'][$template['template_id']] = $template['name'];
		}
	}

	public function setTemplates()
	{
		config::set('templates', $this->data['templates'], 'core');
	}

	public function getLists()
	{
		loader::model('system/lists');

		if ( input::isCP() )
		{
			$this->data['lists']['cp_top_nav'] = $this->lists_model->getSystemList('cp_top_nav');
		}
		else
		{
			$this->data['lists']['site_top_nav'] = $this->lists_model->getSystemList('site_top_nav');
			$this->data['lists']['site_bottom_nav'] = $this->lists_model->getSystemList('site_bottom_nav');
			$this->data['lists']['site_user_nav'] = $this->lists_model->getSystemList('site_user_nav');
		}
	}

	public function setLists()
	{
		foreach ( $this->data['lists'] as $keyword => $list )
		{
			config::set($keyword, $list, 'lists');
		}
	}

	public function getUserTypes()
	{
		loader::model('users/types', array(), 'users_types_model');

		$types = $this->users_types_model->getTypes(false);

		foreach ( $types as $type )
		{
			$this->data['usertypes']['keywords'][$type['type_id']] = $type['keyword'];
			$this->data['usertypes']['names'][$type['type_id']] = $type['name'];
			$this->data['usertypes']['fields'][$type['type_id']][1] = $type['field_name_1'];
			$this->data['usertypes']['fields'][$type['type_id']][2] = $type['field_name_2'];
		}
	}

	public function setUserTypes()
	{
		config::set('usertypes', $this->data['usertypes'], 'core');
	}

	public function getUserGroups()
	{
		loader::model('users/groups', array(), 'users_groups_model');

		$groups = $this->users_groups_model->getGroups(false);

		foreach ( $groups as $group )
		{
			$this->data['usergroups'][$group['group_id']] = $group['name'];
		}
	}

	public function setUserGroups()
	{
		config::set('usergroups', $this->data['usergroups'], 'core');
	}

	public function getCountries()
	{
		loader::model('system/geo');

		$this->data['countries'] = $this->geo_model->getCountries(true);
	}

	public function setCountries()
	{
		config::set('countries', $this->data['countries'], 'core');
	}

	public function updateConfig()
	{
		if ( !input::isCP() )
		{
			if ( !( $settings = $this->cache->item('core_template_config_' . session::item('template'), true) ) )
			{
				loader::model('system/templates');

				$template = $this->templates_model->getTemplate(session::item('template'));

				$settings = isset($template['settings']) ? $template['settings'] : array();

				$this->cache->set('core_template_config_' . session::item('template'), $settings, 60*60*24*30, true);
			}

			config::set($settings, '', 'template');
		}
	}
}
