<?php

class Users_Login_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Is user logged in?
		if ( users_helper::isLoggedin() && strtolower(uri::segment(3)) != 'out' )
		{
			router::redirect(session::item('slug'));
		}
	}

	public function index()
	{
		// Process form values
		if ( input::post('do_login') )
		{
			$this->_authUser();
		}

		// Set title
		view::setTitle(__('login', 'system_navigation'));

		// Load view
		view::load('users/login');
	}

	protected function _authUser()
	{
		// Create rules
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
				'rules' => 'intval',
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get user
		if ( !( $user = $this->users_model->getUser(input::post('email'), false, false) ) )
		{
			validate::setFieldError('email', __(( strpos(input::post('email'), '@') === false ? 'username' : 'email' ) . '_invalid', 'users_signup'));
			return false;
		}

		// Verify password
		if ( !$this->users_model->verifyPassword(input::post('password'), $user['password'], $user['user_id']) )
		{
			validate::setFieldError('password', __('password_invalid', 'users_signup'));
			return false;
		}

		// Is email verified?
		if ( !$user['verified'] )
		{
			view::setError(__('user_not_verified', 'users_signup'));
			return false;
		}

		// Is account active?
		if ( !$user['active'] || $user['group_id'] == config::item('group_cancelled_id', 'users') )
		{
			view::setError(__('user_not_active', 'users_signup'));
			return false;
		}

		// Log the user in
		$this->users_model->login($user['user_id'], input::post('remember'), $user);

		router::redirect(config::item('login_redirect', 'users') == 'profile' ? session::item('slug') : config::item('login_redirect', 'users'));
	}

	public function lostpass()
	{
		// Process form values
		if ( input::post('do_lost_pass') )
		{
			$this->_resetPassword();
		}

		// Set title
		view::setTitle(__('lost_password', 'system_navigation'));

		// Load view
		view::load('users/lostpass');
	}

	protected function _resetPassword()
	{
		// Create rules
		$rules = array(
			'email' => array(
				'label' => 'email',
				'rules' => array('trim', 'required', 'max_length' => 255, 'valid_email')
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get user
		if ( !( $user = $this->users_model->getUser(input::post('email')) ) )
		{
			validate::setFieldError('email', __('email_invalid', 'users_signup'));
			return false;
		}

		// Is email verifed?
		if ( !$user['verified'] )
		{
			view::setError(__('user_not_verified', 'users_signup'));
			return false;
		}

		// Loader
		loader::library('email');
		loader::model('system/requests');

		// Is this a recent request?
		if ( $this->requests_model->isRecentRequest('lostpass', $user['user_id'], 0, 5) )
		{
			// Success
			view::setError(__('request_recent_lostpass', 'users_signup'));
			return false;
		}

		// Save resend hash request
		$hash = $this->requests_model->saveRequest('lostpass', $user['user_id']);

		$user['security_hash'] = $hash;
		$user['reset_link'] = config::siteURL('users/login/newpass/' . $user['user_id'] . '/' . $hash);

		// Send activation email
		$this->email->sendTemplate('users_password_lost', $user['email'], $user, $user['language_id']);

		// Success
		view::setInfo(__('request_lostpass_sent', 'users_signup'));

		router::redirect('users/login/index/lostpass');
	}

	public function newpass()
	{
		// Get vars
		$userID = (int)uri::segment(4);
		$hash = uri::segment(5);

		// Validate user ID
		if ( !$userID )
		{
			view::setError(__('user_id_invalid', 'users_signup'));
			router::redirect('users/login');
		}

		// Loader
		loader::library('email');
		loader::model('system/requests');

		// Validate hash
		if ( !$hash || !$this->requests_model->validateRequest($hash) )
		{
			view::setError(__('request_hash_invalid', 'system'));
			router::redirect('users/login');
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($userID) ) )
		{
			view::setError(__('request_hash_invalid', 'system'));
			router::redirect('users/login');
		}

		// Get request
		if ( !( $request = $this->requests_model->getRequest('lostpass', $hash, $userID) ) )
		{
			view::setError(__('request_hash_expired', 'system'));
			router::redirect('users/login');
		}

		// Is user already active?
		if ( !$user['verified'] )
		{
			view::setError(__('user_not_verified', 'users_signup'));
			router::redirect('users/login');
		}

		// Generate new password
		$password = text_helper::random(10);

		// Update user's verification status
		$this->users_model->savePassword($userID, $password);

		// Replace tags
		$user['password'] = $password;

		// Send activation email
		$this->email->sendTemplate('users_password_new', $user['email'], $user, $user['language_id']);

		// Remove verification request
		$this->requests_model->deleteRequest('lostpass', $hash, $userID);

		// Success
		view::setInfo(__('request_newpass_sent', 'users_signup'));

		router::redirect('users/login/index/lostpass');
	}

	public function resend()
	{
		if ( !config::item('signup_enable', 'users') )
		{
			// Success
			view::setError(__('signup_disabled', 'users_signup'));
			router::redirect('users/login');
		}

		// Process form values
		if ( input::post('do_send_hash') )
		{
			$this->_resendHash();
		}

		// Set title
		view::setTitle(__('resend_activation', 'system_navigation'));

		// Load view
		view::load('users/sendhash');
	}

	protected function _resendHash()
	{
		// Create rules
		$rules = array(
			'email' => array(
				'label' => 'email',
				'rules' => array('trim', 'required', 'max_length' => 255, 'valid_email')
			),
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Does user exist?
		if ( !( $user = $this->users_model->getUser(input::post('email')) ) )
		{
			validate::setFieldError('email', __('email_invalid', 'users_signup'));
			return false;
		}

		// Is user's email already verified?
		if ( $user['verified'] )
		{
			view::setError(__('user_already_verified', 'users_signup'));
			return false;
		}

		// Loader
		loader::library('email');
		loader::model('system/requests');

		// Is this a recent request?
		if ( $this->requests_model->isRecentRequest('signup', $user['user_id'], 0, 5) )
		{
			// Success
			view::setError(__('request_recent_sendhash', 'users_signup'));
			return false;
		}

		// Save resend hash request
		$hash = $this->requests_model->saveRequest('signup', $user['user_id']);

		$user['security_hash'] = $hash;
		$user['activation_link'] = config::siteURL('users/signup/confirm/' . $user['user_id'] . '/' . $hash);

		// Send activation email
		$this->email->sendTemplate('users_account_confirm', $user['email'], $user, $user['language_id']);

		// Success
		view::setInfo(__('confirm_email', 'users_signup'));

		router::redirect('users/login/index/verify');
	}

	public function out()
	{
		$this->users_model->logout();

		router::redirect();
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
}
