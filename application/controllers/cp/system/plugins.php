<?php

class CP_System_Plugins_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('plugins_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/plugins', 'items'));

		loader::model('system/plugins');

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
	}

	public function index()
	{
		// Get plugins
		if ( !( $plugins = $this->plugins_model->scanPlugins() ) )
		{
			view::setError(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/plugins',
			'keyword' => 'plugins',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'description' => array(
					'html' => __('description', 'system'),
					'class' => 'text',
				),
				'version' => array(
					'html' => __('plugin_version', 'system_plugins'),
					'class' => 'version',
				),
				'author' => array(
					'html' => __('plugin_author', 'system_plugins'),
					'class' => 'author',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $plugins as $plugin )
		{
			$version = $plugin['version'] != '' ? $plugin['version'] : '1.0.0';

			if ( isset($plugin['plugin_id']) && $plugin['plugin_id'] && version_compare($plugin['version_new'], $plugin['version']) == 1 )
			{
				$version .= ' '. html_helper::anchor('cp/system/plugins/view/' . $plugin['keyword'], '+', array('class' => 'label success small', 'title' => __('plugin_new_version', 'system_plugins', array('%version' => text_helper::entities($plugin['version_new'])))));
			}

			$author = '';
			if ( $plugin['author'] || $plugin['website'] )
			{
				if ( $plugin['author'] && $plugin['website'] )
				{
					$author = html_helper::anchor($plugin['website'], $plugin['author'], array('target' => '_blank'));
				}
				elseif ( $plugin['website'] )
				{
					$author = html_helper::anchor($plugin['website'], text_helper::entities(str_ireplace(array('http://www.', 'http://'), '', $plugin['website'])), array('target' => '_blank'));
				}
				elseif ( $plugin['author'] )
				{
					$author = $plugin['author'];
				}
			}

			$actions = array();
			if ( isset($plugin['plugin_id']) && $plugin['plugin_id'] )
			{
				if ( $plugin['settings'] )
				{
					$actions['html']['settings'] = html_helper::anchor('cp/system/plugins/settings/' . $plugin['keyword'], __('settings', 'system'), array('class' => 'settings'));
				}
				else
				{
					$actions['html']['view'] = html_helper::anchor('cp/system/plugins/view/' . $plugin['keyword'], __('details', 'system'), array('class' => 'details'));
				}

				if ( !isset($plugin['system']) || !$plugin['system'] )
				{
					$actions['html']['uninstall'] = html_helper::anchor('cp/system/plugins/uninstall/' . $plugin['keyword'], __('uninstall', 'system'), array('data-html' => __('plugin_uninstall?', 'system_plugins'), 'data-role' => 'confirm', 'class' => 'uninstall'));
				}
			}
			else
			{
				$actions['html']['install'] = html_helper::anchor('cp/system/plugins/install/' . $plugin['keyword'], __('install', 'system'), array('class' => 'install'));
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/plugins/view/' . $plugin['keyword'], $plugin['name']),
				),
				'description' => array(
					'html' => text_helper::truncate($plugin['description'], 64),
				),
				'version' => array(
					'html' => $version,
				),
				'author' => array(
					'html' => $author,
				),
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/system/plugins/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('system_plugins_manage', 'system_navigation'));

		// Load view
		view::load('cp/system/plugins/browse');
	}

	public function view()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get plugin
		if ( !$keyword || !( $plugin = $this->plugins_model->getPlugin($keyword) ) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Get manifest
		$manifest = $this->plugins_model->getManifest($keyword);

		// Assign vars
		view::assign(array('manifest' => $manifest, 'plugin' => $plugin));

		// Set title
		view::setTitle(__('plugin_view', 'system_plugins'));

		// Set trail
		view::setTrail('cp/system/plugins/view/' . $keyword, __('plugin_view', 'system_plugins') . ' - ' . $plugin['name']);

		// Load view
		view::load('cp/system/plugins/view');
	}

	public function settings()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get plugin
		if ( !$keyword || !( $plugin = $this->plugins_model->getPlugin($keyword) ) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Do we have settings enabled?
		if ( !$plugin['settings'] )
		{
			view::setError(__('no_plugin_settings', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Assign vars
		view::assign(array('plugin' => $plugin));

		// Set title
		view::setTitle(__('plugin_settings', 'system_plugins'));

		// Set trail
		view::setTrail('cp/system/plugins/view/' . $keyword, __('plugin_view', 'system_plugins') . ' - ' . $plugin['name']);

		// Load settings controller
		loader::controller('cp/system/config/system', array(), 'system_settings');
		codebreeder::instance()->controller->system_settings->browse($keyword);
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/plugins') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get plugins
		if ( !( $plugins = $this->plugins_model->scanPlugins(false, false) ) )
		{
			view::setError(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Does plugin exist and is it installed?
		if ( !isset($plugins[$keyword]) || isset($plugins[$keyword]['plugin_id']) )
		{
			router::redirect('cp/system/plugins');
		}

		// Do we have requirements?
		if ( $plugins[$keyword]['requirements'] && !$this->plugins_model->checkRequirements($plugins[$keyword]['requirements']))
		{
			router::redirect('cp/system/plugins');
		}

		// Install plugin
		if ( !$this->plugins_model->install($keyword) )
		{
			router::redirect('cp/system/plugins');
		}

		view::setInfo(__('plugin_installed', 'system_plugins'));
		router::redirect('cp/system/plugins');
	}

	public function update()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/plugins') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get plugin
		if ( !( $plugin = $this->plugins_model->getPlugin($keyword) ) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Get manifest
		if ( !( $manifest = $this->plugins_model->getManifest($keyword, true, false) ) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Is this a new version?
		if ( $plugin['version'] >= $manifest['version'] )
		{
			view::setError(__('plugin_update_latest', 'system_plugins'));
			router::redirect('cp/system/plugins/view/' . $keyword);
		}

		// Do we have requirements?
		if ( $manifest['requirements'] && !$this->plugins_model->checkRequirements($manifest['requirements']))
		{
			router::redirect('cp/system/plugins/view/' . $keyword);
		}

		// Update plugin
		if ( !$this->plugins_model->update($plugin['plugin_id'], $manifest) )
		{
			router::redirect('cp/system/plugins/view/' . $keyword);
		}

		view::setInfo(__('plugin_updated', 'system_plugins'));
		router::redirect('cp/system/plugins/view/' . $keyword);
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/plugins') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get plugin
		if ( !( $plugin = $this->plugins_model->getPlugin($keyword) ) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Is this a system plugin?
		if ( $plugin['system'] )
		{
			view::setError(__('plugin_system', 'system_plugins'));
			router::redirect('cp/system/plugins');
		}

		// Uninstall plugin
		if ( !$this->plugins_model->uninstall($plugin['plugin_id'], $plugin) )
		{
			router::redirect('cp/system/plugins');
		}

		view::setInfo(__('plugin_uninstalled', 'system_plugins'));
		router::redirect('cp/system/plugins');
	}
}
