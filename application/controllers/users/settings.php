<?php

class Users_Settings_Controller extends Controller
{
	public function __construct($tabs = true, $loggedin = true)
	{
		parent::__construct();

		// Is user loggedin ?
		if ( $loggedin && !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('users/settings', __('settings', 'users'));

		// Set tabs
		if ( $tabs )
		{
			view::setTab('users/settings', __('settings', 'users'), array('class' => ( uri::segment(1) == 'users' && uri::segment(2) == 'settings' && (!uri::segment(3) || in_array(uri::segment(3), array('email', 'password', 'username', 'cancel'))) || uri::segment(1) == 'billing' && uri::segment(2) != 'invoices' ? 'active' : '' ) . ' icon-users-settings'));
			if ( config::item('privacy_edit', 'users') )
			{
				view::setTab('users/settings/privacy', __('privacy', 'users'), array('class' => ( uri::segment(1) == 'users' && uri::segment(3) == 'privacy' ? 'active' : '' ) . ' icon-users-privacy'));
			}
			if ( config::item('notifications_edit', 'users') )
			{
				view::setTab('users/settings/notifications', __('notifications', 'users'), array('class' => ( uri::segment(1) == 'users' && uri::segment(3) == 'notifications' ? 'active' : '' ) . ' icon-users-notifications'));
			}
			if ( config::item('blacklist_active', 'users') )
			{
				view::setTab('users/blocked', __('blacklist', 'users'), array('class' => ( uri::segment(1) == 'users' && uri::segment(2) == 'blocked' ? 'active' : '' ) . ' icon-users-blacklist'));
			}
		}

		// Filter hook
		hook::action('users/settings/tabs');
	}

	public function index()
	{
		$this->account();
	}

	public function account()
	{
		// Create account settings
		$settings = array();
		$settings['email'] = array(
			'name' => __('email', 'users'),
			'keyword' => 'email',
			'type' => 'static',
			'value' => text_helper::entities(session::item('email')) . ' - ' .
				html_helper::anchor('users/settings/email', __('email_change', 'users')) .
				( config::item('auth_methods', 'users', 'default') ? ' - ' . html_helper::anchor('users/settings/password', __('password_change', 'users')) : '' ) .
				( session::permission('users_account_cancel', 'users') ? ' - ' . html_helper::anchor('users/settings/cancel', __('account_cancel', 'users')) : '' )
		);

		if ( config::item('user_username', 'users') )
		{
			$settings['username'] = array(
				'name' => __('username', 'users'),
				'keyword' => 'username',
				'type' => 'static',
				'value' => text_helper::entities(session::item('username')) . ( config::item('user_username_modify', 'users') ? ' - ' . html_helper::anchor('users/settings/username', __('username_change', 'users')) : '' ),
			);
		}

		if ( !config::item('time_zone_override', 'system') )
		{
			$settings['time_zone'] = array(
				'name' => __('time_zone', 'users_account'),
				'keyword' => 'time_zone',
				'type' => 'select',
				'items' => date_helper::timezones(),
				'value' => session::item('time_zone'),
				'rules' => array('callback__is_valid_time_zone'),
			);
		}

		if ( !config::item('language_override', 'system') && session::permission('change_languages', 'system') )
		{
			$settings['language_id'] = array(
				'name' => __('language', 'users_account'),
				'keyword' => 'language_id',
				'type' => 'select',
				'items' => config::item('languages', 'core', 'names'),
				'value' => session::item('language_id'),
				'rules' => array('callback__is_valid_language_id'),
			);
		}

		if ( !config::item('template_override', 'system') && session::permission('change_templates', 'system') )
		{
			$settings['template_id'] = array(
				'name' => __('template', 'users_account'),
				'keyword' => 'template_id',
				'type' => 'select',
				'items' => config::item('templates', 'core', 'names'),
				'value' => session::item('template_id'),
				'rules' => array('callback__is_valid_template_id'),
			);
		}

		// Filter hook
		$settings = hook::filter('users/settings/account/options', $settings);

		// Assign vars
		view::assign(array('settings' => $settings));

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($settings);
		}

		// Set title
		view::setTitle(__('settings', 'users'));

		// Load view
		view::load('users/settings/account');
	}

	protected function _saveSettings($settings)
	{
		// Validate form fields
		foreach ( $settings as $keyword => $setting )
		{
			if ( isset($setting['rules']) )
			{
				validate::setRule($keyword, $setting['name'], $setting['rules']);
			}
		}

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get account settings
		$config = array();
		foreach ( $settings as $keyword => $setting )
		{
			if ( isset($setting['rules']) )
			{
				$config[$keyword] = input::post($keyword);
			}
		}

		$user = array();
		foreach ( array('time_zone', 'language_id', 'template_id') as $keyword )
		{
			if ( isset($config[$keyword]) )
			{
				$user[$keyword] = $config[$keyword];
				unset($config[$keyword]);
			}
		}

		// Save settings
		if ( $user && !$this->users_model->saveUser(session::item('user_id'), $user) || $config && !$this->users_model->saveConfig(session::item('user_id'), $config) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('settings_saved', 'users_account'));

		router::redirect('users/settings');
	}

	public function cancel()
	{
		if ( !session::permission('users_account_cancel', 'users') || session::permission('site_access_cp', 'system') )
		{
			router::redirect('users/settings');
		}

		// Did we confirm cancellation?
		if ( uri::segment(4) == 'confirm' )
		{
			$this->_cancelAccount();
		}

		// Set title
		view::setTitle(__('account_cancel', 'users'));

		// Load view
		view::load('users/settings/cancel');
	}

	protected function _cancelAccount()
	{
		$this->users_model->cancelUser(session::item('user_id'));
		$this->users_model->logout();

		// Success
		view::setInfo(__('account_cancelled', 'users'));

		router::redirect();
	}

	public function username()
	{
		// Can be change username?
		if ( !config::item('user_username_modify', 'users') )
		{
			router::redirect('users/settings');
		}

		// Process form values
		if ( input::post('do_save_username') )
		{
			$this->_saveUsername();
		}

		// Set title
		view::setTitle(__('username_change', 'users'));

		// Load view
		view::load('users/settings/username');
	}

	protected function _saveUsername()
	{
		// Is username enabled?
		if ( !config::item('user_username', 'users') )
		{
			return;
		}

		// Creat rules
		$rules = array(
			'username' => array(
				'label' => __('username', 'users'),
				'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128, 'callback__is_valid_username')
			),
			'password' => array(
				'label' => __('password_current', 'users'),
				'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128, 'callback__is_valid_password')
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Save user
		if ( !$this->users_model->saveUsername(session::item('user_id'), input::post('username')) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('username_saved', 'users'));

		router::redirect('users/settings');
	}

	public function password()
	{
		// Process form values
		if ( input::post('do_save_password') )
		{
			$this->_savePassword();
		}

		// Set title
		view::setTitle(__('password_change', 'users'));

		// Load view
		view::load('users/settings/password');
	}

	protected function _savePassword()
	{
		// Create rules
		$rules = array();
		if ( session::item('password') )
		{
			$rules['old_password'] = array(
				'label' => __('password_current', 'users'),
				'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128, 'callback__is_valid_password')
			);
		}
		$rules['password'] = array(
			'label' => __('password_new', 'users'),
			'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128)
		);
		$rules['password2'] = array(
			'label' => __('password_confirm_new', 'users'),
			'rules' => array('trim', 'required', 'min_length' => 4, 'matches' => 'password')
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Save user
		if ( !$this->users_model->savePassword(session::item('user_id'), input::post('password')) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('password_saved', 'users'));

		router::redirect('users/settings');
	}

	public function email()
	{
		// Process form values
		if ( input::post('do_save_email') )
		{
			$this->_saveEmail();
		}

		// Set title
		view::setTitle(__('email_change', 'users'));

		// Load view
		view::load('users/settings/email');
	}

	protected function _saveEmail()
	{
		// Creat rules
		$rules = array(
			'email' => array(
				'label' => __('email_new', 'users'),
				'rules' => array('trim', 'required', 'max_length' => 255, 'valid_email', 'callback__is_unique_email')
			),
			'password' => array(
				'label' => __('password_current', 'users'),
				'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128, 'callback__is_valid_password')
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Do we need to verify email address?
		if ( config::item('signup_email_verify', 'users') )
		{
			// Load requests model
			loader::model('system/requests');

			// Is this a recent request?
			if ( config::item('signup_delay', 'users') != -1 && $this->requests_model->isRecentRequest('newemail', session::item('user_id'), 0, config::item('signup_delay', 'users')) )
			{
				// Success
				view::setError(__('email_change_recent', 'users'));
				return false;
			}

			// Load email library
			loader::library('email');

			// Save signup request
			$hash = $this->requests_model->saveRequest('newemail', session::item('user_id'), 0, input::post('email'));

			$tags = session::section('session');
			$tags['security_hash'] = $hash;
			$tags['activation_link'] = config::siteURL('users/settings/newemail/' . $hash);

			// Send activation email
			$this->email->sendTemplate('users_account_confirm', input::post('email'), $tags, session::item('language_id'));

			// Success
			view::setInfo(__('email_confirm', 'users'));
		}
		else
		{
			// Save user
			if ( !$this->users_model->saveEmail(session::item('user_id'), input::post('email')) )
			{
				view::setError(__('save_error', 'system'));
				return false;
			}

			// Success
			view::setInfo(__('email_saved', 'users'));
		}

		router::redirect('users/settings');
	}

	public function newemail()
	{
		// Get URI vars
		$hash = uri::segment(4);

		// Load requests model
		loader::model('system/requests');

		// Validate hash
		if ( !$hash || !$this->requests_model->validateRequest($hash) )
		{
			view::setError(__('request_hash_invalid', 'system'));
			router::redirect('users/settings');
		}

		// Get request
		if ( !( $request = $this->requests_model->getRequest('newemail', $hash, session::item('user_id')) ) )
		{
			view::setError(__('request_hash_expired', 'system'));
			router::redirect('users/settings');
		}

		// Update user's email address
		if ( !$this->users_model->saveEmail(session::item('user_id'), $request['val']) )
		{
			view::setError(__('save_error', 'system'));
			router::redirect('users/settings');
		}

		// Remove verification request
		$this->requests_model->deleteRequest('newemail', $hash, session::item('user_id'));

		// Success
		view::setInfo(__('email_saved', 'users'));

		router::redirect('users/settings');
	}

	public function privacy()
	{
		if ( !config::item('privacy_edit', 'users') )
		{
			error::show404();
		}

		// Create privacy settings
		$settings = array();

		// Filter hook
		$settings = hook::filter('users/settings/privacy/options', $settings, session::section() + array('config' => session::section('config')));

		// Assign vars
		view::assign(array('settings' => $settings));

		// Process form values
		if ( input::post('do_save_privacy') )
		{
			$this->_savePrivacy($settings);
		}

		// Set title
		view::setTitle(__('privacy', 'users'));

		// Load view
		view::load('users/settings/privacy');
	}

	protected function _savePrivacy($settings)
	{
		// Validate form fields
		foreach ( $settings as $keyword => $setting )
		{
			if ( isset($setting['rules']) )
			{
				validate::setRule($keyword, $setting['name'], $setting['rules']);
			}
		}

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Set privacy settings
		$insert = $delete = array();
		foreach ( $settings as $keyword => $setting )
		{
			$data = input::post($keyword);
			if ( isset($data['insert']) && isset($data['delete']) )
			{
				$insert = array_merge($insert, $data['insert']);
				$delete = array_merge($delete, $data['delete']);
			}
		}

		// Save privacy
		if ( $insert && !$this->users_model->saveConfig(session::item('user_id'), $insert) || $delete && !$this->users_model->deleteConfig(session::item('user_id'), $delete) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('privacy_saved', 'users_privacy'));

		router::redirect('users/settings/privacy');
	}

	public function notifications()
	{
		if ( !config::item('notifications_edit', 'users') )
		{
			error::show404();
		}

		// Create notifications settings
		$settings = array(
			'general' => array(
				'name' => __('options_general', 'users_notifications'),
				'keyword' => 'general',
				'type' => 'checkbox',
				'items' => array(),
				'value' => array(),
			)
		);

		// Filter hook
		$settings = hook::filter('users/settings/notifications/options', $settings);

		// Assign vars
		view::assign(array('settings' => $settings));

		// Process form values
		if ( input::post('do_save_notifications') )
		{
			$this->_saveNotifications($settings);
		}

		// Set title
		view::setTitle(__('notifications', 'users'));

		// Load view
		view::load('users/settings/notifications');
	}

	protected function _saveNotifications($settings)
	{
		// Validate form fields
		foreach ( $settings as $keyword => $setting )
		{
			if ( isset($setting['rules']) )
			{
				validate::setRule($keyword, $setting['name'], $setting['rules']);
			}
		}

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Set notifications settings
		$insert = $delete = array();
		foreach ( $settings as $keyword => $setting )
		{
			$data = input::post($keyword);
			if ( isset($data['insert']) && isset($data['delete']) )
			{
				$insert = array_merge($insert, $data['insert']);
				$delete = array_merge($delete, $data['delete']);
			}
		}

		// Save notifications
		if ( $insert && !$this->users_model->saveConfig(session::item('user_id'), $insert) || $delete && !$this->users_model->deleteConfig(session::item('user_id'), $delete) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('notifications_saved', 'users_notifications'));

		router::redirect('users/settings/notifications');
	}

	public function _parse_config_item($value, $keyword)
	{
		$insert = $delete = array();

		if ( $value == 1 )
		{
			$delete[] = $keyword;
		}
		else
		{
			$insert[$keyword] = $value;
		}

		return array('insert' => $insert, 'delete' => $delete);
	}

	public function _parse_config_array($values, $items)
	{
		$insert = $delete = array();

		foreach ( $items as $item )
		{
			if ( is_array($values) && in_array($item, $values) )
			{
				$delete[] = $item;
			}
			else
			{
				$insert[$item] = 0;
			}
		}

		return array('insert' => $insert, 'delete' => $delete);
	}

	public function _is_valid_password($password)
	{
		if ( !$this->users_model->verifyPassword($password, session::item('password')) )
		{
			validate::setError('_is_valid_password', __('password_invalid', 'users_signup'));
			return false;
		}

		return true;
	}

	public function _is_unique_email($email)
	{
		if ( !$this->users_model->isUniqueEmail($email, session::item('user_id')) )
		{
			validate::setError('_is_unique_email', __('email_duplicate', 'users_signup'));
			return false;
		}

		return true;
	}

	public function _is_valid_username($username)
	{
		if ( ( $return = $this->users_model->isValidUsername($username, session::item('user_id')) ) !== true )
		{
			validate::setError('_is_valid_username', $return);

			return false;
		}

		return true;
	}

	public function _is_valid_time_zone($timezone)
	{
		$timezones = date_helper::timezones();

		if ( !isset($timezones[$timezone]) )
		{
			return config::item('time_zone', 'system');
		}

		return $timezone;
	}

	public function _is_valid_language_id($languageID)
	{
		$languages = config::item('languages', 'core', 'names');

		if ( !isset($languages[$languageID]) )
		{
			return config::item('language_id', 'system');
		}

		return $languageID;
	}

	public function _is_valid_template_id($templateID)
	{
		$templates = config::item('templates', 'core', 'names');

		if ( !isset($templates[$templateID]) )
		{
			return config::item('template_id', 'system');
		}

		return $templateID;
	}
}
