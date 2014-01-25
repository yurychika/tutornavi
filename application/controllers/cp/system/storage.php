<?php

class CP_System_Storage_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('storage_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/storage', 'items'));

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/storage', __('system_storage', 'system_navigation'));
	}

	public function index()
	{
		// Get storage services
		if ( !( $services = $this->storage_model->scanServices() ) )
		{
			view::setError(__('no_services', 'system_storage'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/storage',
			'keyword' => 'storages',
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
				$status['html'] = $service['default'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : html_helper::anchor('cp/system/storage/setdefault/' . $service['keyword'], __('no', 'system'), array('class' => 'label important small'));
				$actions['html']['settings'] = html_helper::anchor('cp/system/storage/settings/' . $service['keyword'], __('settings', 'system'), array('class' => 'settings'));
				$actions['html']['uninstall'] = html_helper::anchor('cp/system/storage/uninstall/' . $service['keyword'], __('uninstall', 'system'), array('data-html' => __('service_uninstall?', 'system_storage'), 'data-role' => 'confirm', 'class' => 'uninstall'));
			}
			else
			{
				$status['html'] = '<span class="label important small">' . __('no', 'system') . '</span>';
				$actions['html']['install'] = html_helper::anchor('cp/system/storage/install/' . $service['keyword'], __('install', 'system'), array('class' => 'install'));
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => text_helper::entities($service['name']),
				),
				'description' => array(
					'html' => text_helper::entities($service['description']),
				),
				'status' => $status,
				'actions' => $actions,
			);
		}

		// Filter hooks
		hook::filter('cp/system/storage/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('system_storages_manage', 'system_navigation'));

		// Load view
		view::load('cp/system/storage/browse');
	}

	public function settings()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get service
		if ( !$keyword || !( $service = $this->storage_model->getService($keyword) ) )
		{
			view::setError(__('no_service', 'system_storage'));
			router::redirect('cp/system/storage');
		}

		// Get manifest
		$manifest = $this->storage_model->getManifest($keyword);

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
		view::setTrail('cp/system/storage/settings/' . $keyword, __('settings', 'system') . ' - ' . text_helper::entities($service['name']));

		// Load view
		view::load('cp/system/storage/settings');
	}

	public function _saveSettings($keyword, $manifest, $service)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array();

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
			$value = input::post($setting['keyword']);

			if ( $setting['type'] == 'checkbox' )
			{
				$value = array_flip($value);
			}

			$settings[$setting['keyword']] = $value;
		}

		// Load storage service library
		$class = loader::library('storages/' . $keyword, array(), null);

		// Does custom validation method exist?
		if ( method_exists($class, 'validateSettings') )
		{
			// Run custom validation method
			if ( ( $settings = $class->validateSettings($settings) ) === false )
			{
				return false;
			}
		}

		$this->storage_model->saveSettings($service['service_id'], $settings, $service);

		view::setInfo(__('settings_saved', 'system'));

		router::redirect('cp/system/storage/settings/' . $keyword);
	}

	public function setDefault()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/storage') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get service
		if ( !$keyword || !( $service = $this->storage_model->getService($keyword) ) )
		{
			view::setError(__('no_service', 'system_storage'));
			router::redirect('cp/system/storage');
		}

		// Is this a default storage?
		if ( !$service['default'] )
		{
			// Set default storage
			$this->storage_model->setDefault($service['service_id'], $service);
		}

		router::redirect('cp/system/storage');
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/storage') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get services
		if ( !$keyword || !( $services = $this->storage_model->scanServices() ) )
		{
			view::setError(__('no_services', 'system_storage'));
			router::redirect('cp/system/config/system');
		}

		// Does service exist and is it installed?
		if ( !isset($services[$keyword]) || isset($services[$keyword]['service_id']) )
		{
			router::redirect('cp/system/storage');
		}

		// Install service
		$this->storage_model->install($keyword);

		view::setInfo(__('service_installed', 'system_storage'));
		router::redirect('cp/system/storage');
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/storage') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get service
		if ( !$keyword || !( $service = $this->storage_model->getService($keyword) ) )
		{
			view::setError(__('no_service', 'system_storage'));
			router::redirect('cp/system/storage');
		}

		// Do we have any files linked to the storage service?
		if ( $this->storage_model->isInUse($service['service_id']) )
		{
			view::setError(__('storage_in_use', 'system_storage'));
			router::redirect('cp/system/storage');
		}

		// Is this a default library?
		if ( $service['default'] )
		{
			view::setError(__('storage_default', 'system_storage'));
			router::redirect('cp/system/storage');
		}

		// Uninstall service
		$this->storage_model->uninstall($service['service_id'], $service);

		view::setInfo(__('service_uninstalled', 'system_storage'));
		router::redirect('cp/system/storage');
	}
}
