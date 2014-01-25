<?php

class CP_Billing_Gateways_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('gateways_manage', 'billing') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'billing');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'billing', 'items'));

		loader::model('billing/gateways');

		view::setTrail('cp/billing/transactions', __('billing', 'system_navigation'));
		view::setTrail('cp/billing/gateways', __('billing_gateways', 'system_navigation'));
	}

	public function index()
	{
		// Get gateways
		if ( !( $gateways = $this->gateways_model->scanGateways() ) )
		{
			view::setError(__('no_gateways', 'billing_gateways'));
			router::redirect('cp/billing/transactions');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/billing/gateways/browse',
			'keyword' => 'billing_gateways',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'status' => array(
					'html' => __('status', 'system'),
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
		foreach ( $gateways as $gateway )
		{
			if ( isset($gateway['gateway_id']) && $gateway['gateway_id'] )
			{
				if ( $gateway['settings'] )
				{
					$actions['settings'] = html_helper::anchor('cp/billing/gateways/settings/' . $gateway['keyword'], __('settings', 'system'), array('class' => 'settings'));
				}
				$actions['uninstall'] = html_helper::anchor('cp/billing/gateways/uninstall/' . $gateway['keyword'], __('uninstall', 'system'), array('data-html' => __('gateway_uninstall?', 'billing_gateways'), 'data-role' => 'confirm', 'class' => 'uninstall'));
			}
			else
			{
				$actions['install'] = html_helper::anchor('cp/billing/gateways/install/' . $gateway['keyword'], __('install', 'system'), array('class' => 'install'));
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => text_helper::entities($gateway['name']),
				),
				'status' => array(
					'html' => $gateway['active'] ? '<span class="label success small">' . __('active', 'system') . '</span>' : '<span class="label important small">' . __('inactive', 'system') . '</span>',
				),
				'actions' => array(
					'html' => $actions,
				),
			);
		}

		// Filter hooks
		hook::filter('cp/billing/gateways/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('billing_gateways_manage', 'system_navigation'));

		// Load view
		view::load('cp/billing/gateways/browse');
	}

	public function settings()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get gateway
		if ( !$keyword || !( $gateway = $this->gateways_model->getGateway($keyword) ) )
		{
			view::setError(__('no_gateway', 'billing_gateways'));
			router::redirect('cp/billing/gateways');
		}

		// Get manifest
		$manifest = $this->gateways_model->getManifest($keyword);

		$settings = array();
		// Create settings
		foreach ( $manifest['settings'] as $setting )
		{
			if ( $setting['type'] != 'system' )
			{
				$settings[] = $setting;
			}
		}

		// Do we have any settings for this gateway?
		if ( !$manifest['settings'] )
		{
			view::setError(__('no_gateway_settings', 'billing_gateways'));
			router::redirect('cp/billing/gateways');
		}

		// Assign vars
		view::assign(array('manifest' => $manifest, 'gateway' => $gateway, 'settings' => $settings));

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($keyword, $manifest, $gateway, $settings);
		}

		// Set title
		view::setTitle(__('settings', 'system'));

		// Set trail
		view::setTrail('cp/billing/gateways/settings/' . $keyword, __('settings', 'system') . ' - ' . text_helper::entities($gateway['name']));

		// Load view
		view::load('cp/billing/gateways/settings');
	}

	public function _saveSettings($keyword, $manifest, $gateway, $settings)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'name' => array(
				'label' => __('name', 'system'),
				'rules' => array('required', 'max_length' => 128),
			),
			'active' => array(
				'label' => __('active', 'system'),
				'rules' => array('intval'),
			),
		);

		// Loop through available settings
		foreach ( $settings as $setting )
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

		$rules['active'] = array(
			'label' => __('active', 'system'),
			'rules' => array('required'),
		);

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
			if ( $setting['type'] == 'system' )
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

		// Load gateway library
		$gateway = loader::library('payments/' . $keyword, array(), null);

		// Does validation method exist?
		if ( method_exists($gateway, 'validateSettings') )
		{
			// Validate settings
			if ( !$gateway->validateSettings($settings) )
			{
				return false;
			}
		}

		$this->gateways_model->saveSettings(input::post('name'), $keyword, $settings, ( input::post('active') ? 1 : 0 ));

		view::setInfo(__('settings_saved', 'system'));

		router::redirect('cp/billing/gateways/settings/' . $keyword);
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/billing/gateways') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get gateways
		if ( !$keyword || !( $gateways = $this->gateways_model->scanGateways() ) )
		{
			view::setError(__('no_gateways', 'billing_gateways'));
			router::redirect('cp/billing/settings');
		}

		// Does gateway exist and is it installed?
		if ( !isset($gateways[$keyword]) || isset($gateways[$keyword]['gateway_id']) )
		{
			router::redirect('cp/billing/gateways');
		}

		// Install gateway
		$this->gateways_model->install($keyword);

		view::setInfo(__('gateway_installed', 'billing_gateways'));
		router::redirect('cp/billing/gateways');
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/billing/gateways') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get gateway
		if ( !$keyword || !($gateway = $this->gateways_model->getGateway($keyword)) )
		{
			view::setError(__('no_gateway', 'billing_gateways'));
			router::redirect('cp/billing/gateways');
		}

		// Has this gateway been used already?
		if ( $this->gateways_model->isInUse($gateway['gateway_id']) )
		{
			view::setError(__('gateway_in_use', 'billing_gateways'));
			router::redirect('cp/billing/gateways');
		}

		// Uninstall gateway
		$this->gateways_model->uninstall($gateway);

		view::setInfo(__('gateway_uninstalled', 'billing_gateways'));
		router::redirect('cp/billing/gateways');
	}
}
