<?php

class CP_Users_Controller extends Controller
{
	public $usersPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		if ( users_helper::isLoggedin() )
		{
			// Does user have permission to access this plugin?
			if ( !session::permission('users_manage', 'users') && uri::getURI() != 'cp/users/login' && uri::getURI() != 'cp/users/login/license' && uri::getURI() != 'cp/users/logout' )
			{
				view::noAccess();
			}

			view::setCustomParam('section', 'users');
			view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'users', 'items'));
		}

		view::setTrail('cp/users', __('users', 'system_navigation'));
	}

	public function login()
	{
		if ( users_helper::isLoggedin() && session::permission('site_access_cp', 'system') )
		{
			if ( uri::segment(4) == 'spy' && uri::segment(5) && is_numeric(uri::segment(5)) && uri::segment(5) > 0 )
			{
				if ( !input::demo(1, 'cp/users') && $this->users_model->login(uri::segment(5), 0, array(), true) )
				{
					router::redirect(session::item('slug'));
				}
			}

			router::redirect('cp');
		}

		if ( uri::segment(4) == 'license' && !input::demo(0, '', false) )
		{
			// Set title
			view::setTitle(__('license_change', 'system_license'));
		}
		else
		{
			// Set title
			view::setTitle(__('login', 'system_navigation'));
		}

		// Process form values
		if ( input::post('do_login') )
		{
			$this->_doLogin();
		}

		// Load view
		view::load('cp/users/login');
	}

	protected function _doLogin()
	{
		$rules = array(
			'email' => array(
				'label' => __('email', 'users'),
				'rules' => array('trim', 'required', 'max_length' => 255, 'callback__is_valid_login')
			),
			'password' => array(
				'label' => __('password', 'users'),
				'rules' => array('trim', 'required', 'min_length' => 4, 'max_length' => 128)
			),
			'remember' => array(
				'label' => __('remember_me', 'users'),
			),
		);

		if ( uri::segment(4) == 'license' && !input::demo(0, '', false) )
		{
			$rules['license'] = array(
				'label' => __('license_new', 'system_license'),
				'rules' => array('trim', 'required', 'callback__is_valid_license')
			);
		}

		validate::setRules($rules);

		if ( !validate::run() )
		{
			return false;
		}

		if ( !($user = $this->users_model->getUser(input::post('email'), false, false)) )
		{
			validate::setFieldError('email', __(( strpos(input::post('email'), '@') === false ? 'username' : 'email' ) . '_invalid', 'users_signup'));
			return false;
		}

		if ( !$this->users_model->verifyPassword(input::post('password'), $user['password'], $user['user_id']) )
		{
			validate::setFieldError('password', __('password_invalid', 'users_signup'));
			return false;
		}

		if ( uri::segment(4) == 'license' && !input::demo(0, '', false) )
		{
			$access = $this->users_model->getPermissions($user['group_id'], 'system', 'license_manage');
			if ( $access )
			{
				loader::model('system/license');
				$this->license_model->changeLicense(input::post('license'));
			}
		}

		$this->users_model->login($user['user_id'], input::post('remember'), $user);

		if ( uri::segment(4) == 'license' )
		{
			router::redirect('cp/help/license');
		}
		else
		{
			router::redirect('cp');
		}
	}

	public function logout()
	{
		if ( users_helper::isLoggedin() )
		{
			$this->users_model->logout();
		}

		router::redirect('cp/users/login');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Parameters
		$params = array(
			'join_columns' => array(),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params, 0);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Actions
		$actions = array(
			0 => __('select', 'system'),
			'approve' => __('approve', 'system'),
			'approve_email' => __('approve_email', 'system'),
			'decline' => __('decline', 'system'),
			'decline_email' => __('decline_email', 'system'),
			'verify' => __('status_verify', 'users'),
			'unverify' => __('status_unverify', 'users'),
			'delete' => __('delete', 'system'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected users
			if ( input::post('action') && isset($actions[input::post('action')]) && input::post('user_id') && is_array(input::post('user_id')) )
			{
				foreach ( input::post('user_id') as $userID )
				{
					$userID = (int)$userID;
					if ( $userID && $userID > 0 )
					{
						$this->action(input::post('action'), $userID);
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/users?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get users
		$users = array();
		if ( $params['total'] )
		{
			$users = $this->users_model->getUsers('in_list', ( isset($params['values']['type_id']) ? $params['values']['type_id'] : 0 ), $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/users',
			'keyword' => 'users',
			'header' => array(
				'check' => array(
					'html' => 'user_id',
					'class' => 'check',
				),
				'name1' => array(
					'html' => __('user', 'system'),
					'class' => 'name',
				),
				'group' => array(
					'html' => __('user_group', 'users'),
					'class' => 'group',
				),
				'type' => array(
					'html' => __('user_type', 'users'),
					'class' => 'type',
				),
				'join_date' => array(
					'html' => __('join_date', 'users'),
					'class' => 'date',
					'sortable' => true,
				),
				'active' => array(
					'html' => __('active', 'system'),
					'class' => 'status',
				),
				'verified' => array(
					'html' => __('verified', 'users'),
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
		foreach ( $users as $user )
		{
			if ( $user['active'] )
			{
				$status = html_helper::anchor('cp/users/decline/' . $user['user_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('yes', 'system'), array('class' => 'label small success'));
			}
			else
			{
				$status = html_helper::anchor('cp/users/approve/' . $user['user_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('no', 'system'), array('class' => 'label small important'));
			}

			if ( $user['verified'] )
			{
				$verified = html_helper::anchor('cp/users/unverify/' . $user['user_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('yes', 'system'), array('class' => 'label small success'));
			}
			else
			{
				$verified = html_helper::anchor('cp/users/verify/' . $user['user_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('no', 'system'), array('class' => 'label small important'));
			}

			$grid['content'][] = array(
				'check' => array(
					'html' => $user['user_id'],
				),
				'name1' => array(
					'html' => users_helper::anchor($user),
				),
				'group' => array(
					'html' => text_helper::entities(config::item('usergroups', 'core', $user['group_id'])),
				),
				'type' => array(
					'html' => text_helper::entities(config::item('usertypes', 'core', 'names', $user['type_id'])),
				),
				'join_date' => array(
					'html' => date_helper::formatDate($user['join_date']),
				),
				'status' => array(
					'html' => $status,
				),
				'verified' => array(
					'html' => $verified,
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/users/edit/' . $user['user_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/users/delete/' . $user['user_id'], __('delete', 'system'), array('data-html' => __('user_delete?', 'users'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/users?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->usersPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/users/browse/grid', $grid);
		hook::filter('cp/users/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('users_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/users?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('cp/users/edit', __('user_new', 'users'), array('class' => 'icon-text icon-users-new'));
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#users-search\').toggle();return false;'));

		// Load view
		view::load('cp/users/browse');
	}

	public function edit()
	{
		// Get URI vars
		$userID = (int)uri::segment(4);

		// Assign vars
		view::assign(array('userID' => $userID));

		// Get user
		$user = array();
		if ( $userID && !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Get user groups
		$groups = config::item('usergroups', 'core');
		unset($groups[config::item('group_guests_id', 'users')]);

		// Create privacy settings
		$settings = array();
		$settings['email'] = array(
			'name' => __('email', 'users'),
			'keyword' => 'email',
			'type' => 'text',
			'class' => 'input-xlarge',
			'value' => $user ? ( input::demo(0) ? current(explode('@', $user['email'])).'@hidden.com' : $user['email'] ) : '',
			'rules' => array('required', 'max_length' => 255, 'valid_email', 'callback__is_unique_email' => $userID)
		);

		if ( config::item('user_username', 'users') )
		{
			$settings['username'] = array(
				'name' => __('username', 'users'),
				'keyword' => 'username',
				'type' => 'text',
				'class' => 'input-xlarge',
				'value' => $user ? $user['username'] : '',
				'rules' => array('required', 'min_length' => 3, 'max_length' => 128, 'callback__is_valid_username' => $userID)
			);
		}

		$settings['password'] = array(
			'name' => __('password', 'users'),
			'keyword' => 'password',
			'type' => 'password',
			'maxlength' => 128,
			'class' => 'input-xlarge',
			'rules' => array(!$userID ? 'required' : '', 'max_length' => 128)
		);

		$settings['password2'] = array(
			'name' => __('password_confirm', 'users'),
			'keyword' => 'password2',
			'type' => 'password',
			'maxlength' => 128,
			'class' => 'input-xlarge',
			'rules' => array('max_length' => 128, 'matches' => 'password')
		);

		$settings['type_id'] = array(
			'name' => __('user_type', 'users'),
			'keyword' => 'type_id',
			'keyword' => 'type_id',
			'type' => 'select',
			'items' => $userID ? array($user['type_id'] => text_helper::entities(config::item('usertypes', 'core', 'names', $user['type_id']))) : config::item('usertypes', 'core', 'names'),
			'value' => $user ? $user['type_id'] : config::item('type_default_id', 'users'),
			'rules' => array('intval')
		);

		$settings['group_id'] = array(
			'name' => __('user_group', 'users'),
			'keyword' => 'group_id',
			'type' => 'select',
			'items' => $userID && $userID == session::item('user_id') ? array($user['group_id'] => config::item('usergroups', 'core', $user['group_id'])) : $groups,
			'value' => $user ? $user['group_id'] : config::item('group_default_id', 'users'),
			'rules' => array('intval')
		);

		if ( $userID != session::item('user_id') )
		{
			$settings['verified'] = array(
				'name' => __('verified', 'users'),
				'keyword' => 'verified',
				'type' => 'boolean',
				'value' => $user ? $user['verified'] : '1',
				'rules' => array('intval')
			);

			$settings['active'] = array(
				'name' => __('active', 'system'),
				'keyword' => 'active',
				'type' => 'boolean',
				'value' => $user ? $user['active'] : '1',
				'rules' => array('intval')
			);
		}

		if ( $userID )
		{
			$settings['join_date'] = array(
				'name' => __('join_date', 'users'),
				'keyword' => 'join_date',
				'type' => 'static',
				'value' => date_helper::formatDate($user['join_date'], 'date-time'),
			);

			$settings['visit_date'] = array(
				'name' => __('visit_date', 'users'),
				'keyword' => 'visit_date',
				'type' => 'static',
				'value' => $user['visit_date'] ? date_helper::formatDate($user['visit_date'], 'date-time') : __('never', 'system'),
			);

			$settings['ip_address'] = array(
				'name' => __('ip_address', 'users'),
				'keyword' => 'ip_address',
				'type' => 'static',
				'value' => input::demo(0) ? 'Hidden in this demo' : $user['ip_address'],
			);
		}

		// Filter hook
		$settings = hook::filter('users/settings/account/options', $settings, $user);

		// Assign vars
		view::assign(array('user' => $user, 'settings' => $settings));

		// Process form values
		if ( input::post('do_save_user') )
		{
			$this->_saveUser($userID, $settings, $user);
		}

		// Set title
		view::setTitle($userID ? __('user_edit', 'users') : __('user_new', 'users'));

		// Assign tabs
		if ( $userID )
		{
			$this->_userActions($userID, 'account');
		}

		// Set trail
		view::setTrail('cp/users/edit/' . ( $userID ? $userID : '' ), ( $userID ? __('user_edit', 'users') . ' - ' . ( $user['name1'] ? $user['name'] : $user['email'] ) : __('user_new', 'users') ));

		// Assign actions
		if ( $userID && $user['user_id'] != session::item('user_id') && $user['group_id'] != config::item('group_cancelled_id', 'users') && $user['active'] && $user['verified'] )
		{
			view::setAction('cp/users/login/spy/' . $user['user_id'], __('login', 'system_navigation'), array('class' => 'icon-text icon-users-login'));
		}

		// Load view
		view::load('cp/users/edit');
	}

	protected function _saveUser($userID, $settings, $userOld)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

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
		$data = array();
		foreach ( $settings as $keyword => $setting )
		{
			if ( isset($setting['rules']) && $keyword != 'password2' )
			{
				$data[$keyword] = input::post($keyword);
			}
		}

		if ( $userID )
		{
			$data['picture_active'] = is_array(input::post('picture_options')) && in_array('picture_active', input::post('picture_options')) ? 1 : 0;

			// Delete picture
			if ( is_array(input::post('picture_options')) && in_array('picture_delete', input::post('picture_options')) )
			{
				$this->users_model->deletePicture($userID, $userOld['picture_id']);
			}
		}

		// Save user
		$userID = $this->users_model->saveUser($userID, $data);

		// Success
		view::setInfo(__('user_saved', 'users'));

		router::redirect('cp/users/edit/' . $userID);
	}

	public function profile()
	{
		// Get URI vars
		$userID = (int)uri::segment(4);

		// Get user
		if ( !$userID || !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Get fields
		$fields = $this->fields_model->getFields('users', $user['type_id'], 'edit');

		// Get profile
		if ( !( $profile = $this->users_model->getProfile($userID, $user['type_id'], $fields, array('escape' => false, 'parse' => false)) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Assign vars
		view::assign(array('user' => $user, 'profile' => $profile, 'fields' => $fields));

		// Process form values
		if ( input::post('do_save_profile') )
		{
			$this->_saveProfile($userID, $user['type_id'], $profile, $fields);
		}

		// Set title
		view::setTitle(__('profile_edit', 'users_profile'));

		// Set trail
		view::setTrail('cp/users/edit/' . $userID, __('user_edit', 'users') . ' - ' . ( $user['name1'] ? $user['name'] : $user['email'] ));
		view::setTrail('cp/users/profile/' . $userID, __('profile', 'users'));

		// Assign tabs
		$this->_userActions($userID, 'profile');

		// Load view
		view::load('cp/users/profile');
	}

	protected function _saveProfile($userID, $typeID, $profileOld, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Validate form fields
		if ( !$this->fields_model->validateValues($fields) )
		{
			return false;
		}

		// Save profile
		if ( !$this->users_model->saveProfile($userID, $typeID, $profileOld, $fields) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('profile_saved', 'users_profile'));

		router::redirect('cp/users/profile/' . $userID);
	}

	public function settings()
	{
		// Get URI vars
		$userID = (int)uri::segment(4);

		// Get user
		if ( !$userID || !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Create privacy settings
		$settings = array();

		if ( !config::item('time_zone_override', 'system') )
		{
			$settings['time_zone'] = array(
				'name' => __('time_zone', 'users_account'),
				'keyword' => 'time_zone',
				'type' => 'select',
				'items' => date_helper::timezones(),
				'value' => array('time_zone' => $user['time_zone']),
				'rules' => array('callback__is_valid_time_zone'),
			);
		}

		if ( !config::item('language_override', 'system') )
		{
			$settings['language_id'] = array(
				'name' => __('language', 'users_account'),
				'keyword' => 'language_id',
				'type' => 'select',
				'items' => config::item('languages', 'core', 'names'),
				'value' => array('language_id' => $user['language_id']),
				'rules' => array('callback__is_valid_language_id'),
			);
		}

		if ( !config::item('template_override', 'system') )
		{
			$settings['template_id'] = array(
				'name' => __('template', 'users_account'),
				'keyword' => 'template_id',
				'type' => 'select',
				'items' => config::item('templates', 'core', 'names'),
				'value' => array('template_id' => $user['template_id']),
				'rules' => array('callback__is_valid_template_id'),
			);
		}

		// Filter hook
		$settings = hook::filter('users/settings/account/options', $settings, $user);

		// Assign vars
		view::assign(array('user' => $user, 'settings' => $settings));

		// Process form values
		if ( input::post('do_save_settings') )
		{
			$this->_saveSettings($userID, $settings);
		}

		// Set title
		view::setTitle(__('settings', 'users'));

		// Set trail
		view::setTrail('cp/users/edit/' . $userID, __('user_edit', 'users') . ' - ' . ($user['name1'] ? $user['name'] : $user['email']));
		view::setTrail('cp/users/settings/' . $userID, __('settings', 'users'));

		// Assign tabs
		$this->_userActions($userID, 'settings');

		// Load view
		view::load('cp/users/settings');
	}

	protected function _saveSettings($userID, $settings)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

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
		if ( $user && !$this->users_model->saveUser($userID, $user) || $config && !$this->users_model->saveConfig($userID, $config) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('settings_saved', 'users_account'));

		router::redirect('cp/users/settings/' . $userID);
	}

	public function privacy()
	{
		// Get URI vars
		$userID = (int)uri::segment(4);

		// Get user
		if ( !$userID || !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Create privacy settings
		$settings = array();

		// Filter hook
		$settings = hook::filter('users/settings/privacy/options', $settings, $user);

		// Assign vars
		view::assign(array('user' => $user, 'settings' => $settings));

		// Process form values
		if ( input::post('do_save_privacy') )
		{
			$this->_savePrivacy($userID, $settings);
		}

		// Set title
		view::setTitle(__('privacy', 'users'));

		// Set trail
		view::setTrail('cp/users/edit/' . $userID, __('user_edit', 'users') . ' - ' . ($user['name1'] ? $user['name'] : $user['email']));
		view::setTrail('cp/users/privacy/' . $userID, __('privacy', 'users'));

		// Assign tabs
		$this->_userActions($userID, 'privacy');

		// Load view
		view::load('cp/users/privacy');
	}

	protected function _savePrivacy($userID, $settings)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

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
		if ( $insert && !$this->users_model->saveConfig($userID, $insert) || $delete && !$this->users_model->deleteConfig($userID, $delete) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('privacy_saved', 'users_privacy'));

		router::redirect('cp/users/privacy/' . $userID);
	}

	public function notifications()
	{
		// Get URI vars
		$userID = (int)uri::segment(4);

		// Get user
		if ( !$userID || !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Create notifications settings
		$settings = array(
			'general' => array(
				'name' => __('options_general', 'users_privacy'),
				'keyword' => 'general',
				'type' => 'checkbox',
				'items' => array(),
				'value' => array(),
			)
		);

		// Filter hook
		$settings = hook::filter('users/settings/notifications/options', $settings, $user);

		// Assign vars
		view::assign(array('user' => $user, 'settings' => $settings));

		// Process form values
		if ( input::post('do_save_notifications') )
		{
			$this->_saveNotifications($userID, $settings);
		}

		// Set title
		view::setTitle(__('notifications', 'users'));

		// Set trail
		view::setTrail('cp/users/edit/' . $userID, __('user_edit', 'users') . ' - ' . ($user['name1'] ? $user['name'] : $user['email']));
		view::setTrail('cp/users/notifications/' . $userID, __('notifications', 'users'));

		// Assign tabs
		$this->_userActions($userID, 'notifications');

		// Load view
		view::load('cp/users/notifications');
	}

	protected function _saveNotifications($userID, $settings)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

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
		if ( $insert && !$this->users_model->saveConfig($userID, $insert) || $delete && !$this->users_model->deleteConfig($userID, $delete) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('notifications_saved', 'users_notifications'));

		router::redirect('cp/users/notifications/' . $userID);
	}

	public function approve()
	{
		$this->action('approve');
	}

	public function decline()
	{
		$this->action('decline');
	}

	public function verify()
	{
		$this->action('verify');
	}

	public function unverify()
	{
		$this->action('unverify');
	}

	public function delete()
	{
		$this->action('delete');
	}

	public function action($action, $actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/users') ) return false;

		// Get URI vars
		$userID = $actionID ? $actionID : (int)uri::segment(4);

		// Get user
		if ( !$userID || !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/users');
		}

		// Make sure we're not trying to decline ourselves
		if ( $userID != session::item('user_id') )
		{
			switch ( $action )
			{
				case 'approve':
				case 'approve_email':

					if ( $this->users_model->toggleUserStatus($userID, $user, 1) && $action == 'approve_email' )
					{
						loader::library('email');
						$this->email->sendTemplate('users_account_welcome', $user['email'], $user, $user['language_id']);
					}
					$str = __('user_approved', 'users');

					break;

				case 'decline':
				case 'decline_email':

					if ( $this->users_model->toggleUserStatus($userID, $user, 0) && $action == 'decline_email' )
					{
						loader::library('email');
						$this->email->sendTemplate('users_account_declined', $user['email'], $user, $user['language_id']);
					}
					$str = __('user_declined', 'users');

					break;

				case 'verify':

					$this->users_model->toggleVerifiedStatus($userID, $user, 1);
					$str = __('user_verified', 'users');

					break;

				case 'unverify':

					$this->users_model->toggleVerifiedStatus($userID, $user, 0);
					$str = __('user_unverified', 'users');

					break;

				case 'delete':

					$this->users_model->deleteUser($userID, $user);
					$str = __('user_deleted', 'users');

					break;
			}
		}
		else
		{
			$str = '';
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo($str);
		router::redirect('cp/users?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function _userActions($userID, $active = 'account')
	{
		// Set tabs
		view::setTab('cp/users/edit/' . $userID, __('user_edit', 'users'), array('class' => ( $active == 'account' ? 'active' : '' )));
		view::setTab('cp/users/profile/' . $userID, __('profile', 'users'), array('class' => ( $active == 'profile' ? 'active' : '' )));
		view::setTab('cp/users/privacy/' . $userID, __('privacy', 'users'), array('class' => ( $active == 'privacy' ? 'active' : '' )));
		view::setTab('cp/users/notifications/' . $userID, __('notifications', 'users'), array('class' => ( $active == 'notifications' ? 'active' : '' )));
		view::setTab('cp/users/settings/' . $userID, __('settings', 'users'), array('class' => ( $active == 'settings' ? 'active' : '' )));
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

	public function _is_valid_login($user)
	{
		if ( ( $return = $this->users_model->isValidLogin($user) ) !== true )
		{
			validate::setError('_is_valid_login', $return);

			return false;
		}

		return true;
	}

	public function _is_unique_email($email, $userID)
	{
		$user = $this->users_model->getUser($email);

		if ( $userID && $user && $userID != $user['user_id'] || !$userID && $user )
		{
			validate::setError('_is_unique_email', __('email_duplicate', 'users_signup'));
			return false;
		}

		return true;
	}

	public function _is_valid_username($username, $userID)
	{
		if ( ( $return = $this->users_model->isValidUsername($username, $userID, true) ) !== true )
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

	public function _is_valid_license($license)
	{
		if ( strlen($license) != 19 || !preg_match('/[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{4}\-[a-z0-9]{4}/i', $license) )
		{
			validate::setError('_is_valid_license', __('license_invalid', 'system_license'));

			return false;
		}

		return true;
	}

	protected function parseCounters($params, $typeID)
	{
		// Set filters
		$filters = array(
			array(
				'name' => __('user', 'system'),
				'type' => 'text',
				'keyword' => 'user',
			),
			array(
				'name' => __('user_group', 'users'),
				'type' => 'select',
				'keyword' => 'group',
				'items' => config::item('usergroups', 'core'),
			),
			array(
				'name' => __('user_type', 'users'),
				'type' => 'select',
				'keyword' => 'type_id',
				'items' => config::item('usertypes', 'core', 'names'),
			),
		);
		foreach ( config::item('usertypes', 'core', 'keywords') as $id => $type )
		{
			$filters['types'][$id] = $this->fields_model->getFields('users', $id, 'edit');
		}
		$filters[] = array(
			'name' => __('verified', 'users'),
			'type' => 'boolean',
			'keyword' => 'verified',
		);
		$filters[] = array(
			'name' => __('active', 'system'),
			'type' => 'boolean',
			'keyword' => 'active',
		);

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Did user submit the filter form?
		if ( input::post_get('do_search') )
		{
			$values = array();

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Check extra verified field
			$verified = input::post_get('verified');
			if ( $verified != '' )
			{
				$params['join_columns'][] = '`u`.`verified`=' . (int)$verified;
				$values['verified'] = $verified;
			}

			// Check extra status field
			$status = input::post_get('active');
			if ( $status != '' )
			{
				$params['join_columns'][] = '`u`.`active`=' . (int)$status;
				$values['active'] = $status;
			}

			// Check extra group field
			$group = input::post_get('group');
			if ( $group != '' && config::item('usergroups', 'core', $group))
			{
				$params['join_columns'][] = '`u`.`group_id`=' . $group;
				$values['group'] = $group;
			}

			// Check extra type field
			$typeID = input::post_get('type_id');
			if ( $typeID != '' && config::item('usertypes', 'core', 'keywords', $typeID) )
			{
				$params['join_columns'][] = '`u`.`type_id`=' . $typeID;
				$values['type_id'] = $typeID;
			}

			// Search users
			$searchID = $this->search_model->searchData('profile', $filters, $params['join_columns'], $values, array('type_id' => $typeID));

			// Do we have any search terms?
			if ( $searchID == 'no_terms' )
			{
				view::setError(__('search_no_terms', 'system'));
			}
			// Do we have any results?
			elseif ( $searchID == 'no_results' )
			{
				view::setError(__('search_no_results', 'system'));
				$params['total'] = 0;
				return $params;
			}
			// Redirect to search results
			else
			{
				router::redirect('cp/users?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/users');
			}

			// Combine results
			$params['join_columns'] = $search['conditions']['columns'];
			$params['join_items'] = $search['conditions']['items'];
			$params['values'] = $search['values'];
			$params['total'] = $search['results'];

			// Assign vars
			view::assign(array('values' => $search['values']));
		}
		else
		{
			// Count users
			if ( !( $params['total'] = $this->counters_model->countData('user', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_users', 'users'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->usersPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('name1', 'join_date', 'total_views', 'total_rating', 'total_likes', 'total_comments')) ? input::post_get('o') : 'join_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->usersPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->usersPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
