<?php

class CP_Users_Authentication_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('users_authentication', 'users') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'users');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'users', 'items'));

		view::setTrail('cp/users', __('users', 'system_navigation'));
		view::setTrail('cp/users/authentication', __('users_authentication', 'system_navigation'));

		loader::model('users/authentication', array(), 'users_authentication_model');
	}

	public function index()
	{
		// Get services
		if ( !( $services = $this->users_authentication_model->scanServices() ) )
		{
			view::setError(__('no_services', 'users_authentication'));
			router::redirect('cp/users/authentication');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/users/authentication/browse',
			'keyword' => 'users_authentication',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'description' => array(
					'html' => __('description', 'system'),
					'class' => 'description',
				),
				'status' => array(
					'html' => __('default', 'system'),
					'class' => 'status',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $services as $service )
		{
			$actions = $status = array();
			if ( isset($service['service_id']) && $service['service_id'] )
			{
				$actions['settings'] = html_helper::anchor('cp/users/authentication/settings/' . $service['keyword'], __('settings', 'system'), array('class' => 'settings'));
				if ( $service['keyword'] != 'default' )
				{
					$actions['uninstall'] = html_helper::anchor('cp/users/authentication/uninstall/' . $service['keyword'], __('uninstall', 'system'), array('data-html' => __('service_uninstall?', 'users_authentication'), 'data-role' => 'confirm', 'class' => 'uninstall'));
				}
			}
			else
			{
				$actions['install'] = html_helper::anchor('cp/users/authentication/install/' . $service['keyword'], __('install', 'system'), array('class' => 'install'));
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => text_helper::entities($service['name']),
				),
				'description' => array(
					'html' => text_helper::entities($service['description']),
				),
				'status' => array(
					'html' => $service['active'] ? '<span class="label success small">' . __('active', 'system') . '</span>' : '<span class="label important small">' . __('inactive', 'system') . '</span>',
				),
				'actions' => array(
					'html' => $actions
				),
			);
		}

		// Filter hooks
		hook::filter('cp/users/authentication/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('users_authentication_manage', 'system_navigation'));

		// Load view
		view::load('cp/users/authentication/browse');
	}

	public function settings()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get service
		if ( !$keyword || !( $service = $this->users_authentication_model->getService($keyword) ) )
		{
			view::setError(__('no_service', 'users_authentication'));
			router::redirect('cp/users/authentication');
		}

		// Get manifest
		$manifest = $this->users_authentication_model->getManifest($keyword);

		// Is demo mode enabled?
		if ( input::demo(0) )
		{
			foreach ( $service['settings'] as $k => $v )
			{
				if ( $v )
				{
					$service['settings'][$k] = 'hidden in this demo';
				}
			}
		}

		// Assign vars
		view::assign(array('manifest' => $manifest, 'service' => $service));

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($keyword, $manifest, $service);
		}

		// Set title
		view::setTitle(__('settings', 'system'));

		// Set trail
		view::setTrail('cp/users/authentication/settings/' . $keyword, __('settings', 'system') . ' - ' . text_helper::entities($service['name']));

		// Load view
		view::load('cp/users/authentication/settings');
	}

	public function _saveSettings($keyword, $manifest, $service)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'active' => array(
				'label' => __('active', 'system'),
				'rules' => array('intval'),
			),
		);

		// Loop through available settings
		foreach ( $manifest['settings'] as $setting )
		{
			// Rule options
			$options = array();

			if ( isset($setting['required']) && $setting['required'] )
			{
				$options[] = 'required';
			}

			$rules[$setting['keyword']] = array(
				'label' => $setting['name'],
				'rules' => $options,
			);
		}

		// Assign rules
		validate::setRules($rules);

		// Run rules
		if ( !validate::run() )
		{
			return false;
		}

		$settings = array();
		foreach ( $manifest['settings'] as $setting )
		{
			if ( $setting['type'] == 'static' || $setting['type'] == 'system' )
			{
				$value = $setting['value'];
			}
			else
			{
				$value = input::post($setting['keyword']);

				if ( $setting['type'] == 'checkbox' )
				{
					$value = array_flip($value);
				}
			}

			$settings[$setting['keyword']] = $value;
		}

		// Load service library
		$class = loader::library('authentication/' . $keyword, array(), null);

		// Does custom validation method exist?
		if ( method_exists($class, 'validateSettings') )
		{
			// Run custom validation method
			if ( ( $settings = $class->validateSettings($settings) ) === false )
			{
				return false;
			}
		}

		$this->users_authentication_model->saveSettings($service['service_id'], $settings, ( input::post('active') ? 1 : 0 ), $service);

		view::setInfo(__('settings_saved', 'system'));

		router::redirect('cp/users/authentication/settings/' . $keyword);
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/users/authentication') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get services
		if ( !$keyword || !( $services = $this->users_authentication_model->scanServices() ) )
		{
			view::setError(__('no_services', 'users_authentication'));
			router::redirect('cp/users/authentication/settings');
		}

		// Does service exist and is it installed?
		if ( !isset($services[$keyword]) || isset($services[$keyword]['service_id']) )
		{
			view::setError(__('no_service', 'users_authentication'));
			router::redirect('cp/users/authentication');
		}

		// Install service
		$this->users_authentication_model->install($keyword, $services[$keyword]);

		view::setInfo(__('service_installed', 'users_authentication'));
		router::redirect('cp/users/authentication');
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/users/authentication') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get service
		if ( !$keyword || $keyword == 'default' || !( $service = $this->users_authentication_model->getService($keyword) ) )
		{
			view::setError(__('no_service', 'users_authentication'));
			router::redirect('cp/users/authentication');
		}

		// Has this service been used already?
		if ( $this->users_authentication_model->isInUse($keyword) )
		{
			view::setError(__('service_in_use', 'users_authentication'));
			router::redirect('cp/users/authentication');
		}

		// Uninstall service
		$this->users_authentication_model->uninstall($service['service_id'], $service);

		view::setInfo(__('service_uninstalled', 'users_authentication'));
		router::redirect('cp/users/authentication');
	}
}
