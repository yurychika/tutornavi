<?php

class CP_Security_Forms_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('forms_manage', 'security') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/security', 'items'));

		loader::model('security/captchas');

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/security/forms', __('security_forms', 'system_navigation'));
	}

	public function index()
	{
		// Get captchas
		if ( !( $captchas = $this->captchas_model->scanCaptchas() ) )
		{
			view::setError(__('no_captchas', 'security_forms'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/security/forms/browse',
			'keyword' => 'captchas',
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
		foreach ( $captchas as $captcha )
		{
			$actions = $status = array();
			if ( isset($captcha['captcha_id']) && $captcha['captcha_id'] )
			{
				$status['html'] = $captcha['default'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : html_helper::anchor('cp/security/forms/setdefault/' . $captcha['keyword'], __('no', 'system'), array('class' => 'label important small'));
				$actions['settings'] = html_helper::anchor('cp/security/forms/settings/' . $captcha['keyword'], __('settings', 'system'), array('class' => 'settings'));
				$actions['uninstall'] = html_helper::anchor('cp/security/forms/uninstall/' . $captcha['keyword'], __('uninstall', 'system'), array('data-html' => __('captcha_uninstall?', 'security_forms'), 'data-role' => 'confirm', 'class' => 'uninstall'));
			}
			else
			{
				$status['html'] = '<span class="label important small">' . __('no', 'system') . '</span>';
				$actions['install'] = html_helper::anchor('cp/security/forms/install/' . $captcha['keyword'], __('install', 'system'), array('class' => 'install'));
			}

			$grid['content'][] = array(
				'name' => array(
					'html' => text_helper::entities($captcha['name']),
				),
				'description' => array(
					'html' => $captcha['description'],
				),
				'status' => $status,
				'actions' => array(
					'html' => $actions
				),
			);
		}

		// Filter hooks
		hook::filter('cp/security/forms/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('form_protection_manage', 'system_navigation'));

		// Load view
		view::load('cp/security/forms/browse');
	}

	public function settings()
	{
		// Get URI vars
		$keyword = uri::segment(5);

		// Get captcha
		if ( !$keyword || !( $captcha = $this->captchas_model->getCaptcha($keyword) ) )
		{
			view::setError(__('no_captcha', 'security_forms'));
			router::redirect('cp/security/forms');
		}

		// Get manifest
		$manifest = $this->captchas_model->getManifest($keyword);

		// Assign vars
		view::assign(array('manifest' => $manifest, 'captcha' => $captcha));

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($keyword, $manifest, $captcha);
		}

		// Set title
		view::setTitle(__('settings', 'system'));

		// Set trail
		view::setTrail('cp/security/forms/settings/' . $keyword, __('settings', 'system') . ' - ' . text_helper::entities($captcha['name']));

		// Load view
		view::load('cp/security/forms/settings');
	}

	public function _saveSettings($keyword, $manifest, $captcha)
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

		// Load captcha library
		$class = loader::library('captchas/' . $keyword, array(), null);

		// Does custom validation method exist?
		if ( method_exists($class, 'validateSettings') )
		{
			// Run custom validation method
			if ( ( $settings = $class->validateSettings($settings) ) === false )
			{
				return false;
			}
		}

		$this->captchas_model->saveSettings($captcha['captcha_id'], $settings, $captcha);

		view::setInfo(__('settings_saved', 'system'));

		router::redirect('cp/security/forms/settings/' . $keyword);
	}

	public function setDefault()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/security/forms') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get captcha
		if ( !$keyword || !( $captcha = $this->captchas_model->getCaptcha($keyword) ) )
		{
			view::setError(__('no_captcha', 'security_forms'));
			router::redirect('cp/security/forms');
		}

		// Is this a default captcha?
		if ( !$captcha['default'] )
		{
			// Set default captcha
			$this->captchas_model->setDefault($captcha['captcha_id'], $captcha);
		}

		router::redirect('cp/security/forms');
	}

	public function install()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/security/forms') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get captchas
		if ( !$keyword || !( $captchas = $this->captchas_model->scanCaptchas() ) )
		{
			view::setError(__('no_captchas', 'security_forms'));
			router::redirect('cp/system/config/system');
		}

		// Does captcha exist and is it installed?
		if ( !isset($captchas[$keyword]) || isset($captchas[$keyword]['captcha_id']) )
		{
			router::redirect('cp/security/forms');
		}

		// Install captcha
		$this->captchas_model->install($keyword);

		view::setInfo(__('captcha_installed', 'security_forms'));
		router::redirect('cp/security/forms');
	}

	public function uninstall()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/security/forms') ) return false;

		// Get URI vars
		$keyword = uri::segment(5);

		// Get captcha
		if ( !$keyword || !( $captcha = $this->captchas_model->getCaptcha($keyword) ) )
		{
			view::setError(__('no_captcha', 'security_forms'));
			router::redirect('cp/security/forms');
		}

		// Is this a default library?
		if ( $captcha['default'] )
		{
			view::setError(__('captcha_default', 'security_forms'));
			router::redirect('cp/security/forms');
		}

		// Uninstall captcha
		$this->captchas_model->uninstall($captcha['captcha_id'], $captcha);

		view::setInfo(__('captcha_uninstalled', 'security_forms'));
		router::redirect('cp/security/forms');
	}

	public function _is_valid_default($value, $default)
	{
		if ( $default && !$value )
		{
			validate::setError('_is_valid_default', __('captcha_default', 'security_forms'));
			return false;
		}

		return true;
	}
}
