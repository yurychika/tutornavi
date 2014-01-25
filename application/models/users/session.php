<?php

class Users_Session_Model extends Model
{
	public $timeout = 5;

	public function __construct()
	{
		parent::__construct();

		$authID = session::item('auth_id');
		$userID = session::item('user_id');
		$ipaddress = substr(input::ipaddress(), 0, 15);
		$useragent = substr(input::useragent(), 0, 255);

		$user = array();
		if ( $authID && ( $user = $this->getSession($authID, $userID, $ipaddress, $useragent) ) )
		{
			if ( $user['active_date'] < ( date_helper::now() - ( 60 * $this->timeout ) ) )
			{
				$this->saveSession($authID, $userID, $ipaddress, $useragent);

				if ( isset($user['user_id']) && $user['user_id'] )
				{
					$this->saveLastvisit($user['user_id']);
				}
			}
		}
		else
		{
			$cookie = cookie::item('sessdata');
			$cookie = $cookie ? @json_decode($cookie, true) : array();

			if ( $cookie && is_array($cookie) )
			{
				$userID = isset($cookie['user_id']) ? $cookie['user_id'] : '';
				$email = isset($cookie['email']) ? $cookie['email'] : '';
				$passhash = isset($cookie['passhash']) ? $cookie['passhash'] : '';

				if ( $userID && is_numeric($userID) && $userID > 0 )
				{
					if ( $user = $this->getUser($userID, false, false) )
					{
						$newPasshash = $this->generatePasshash($email, $user['password']);

						if ( $user['active'] && $user['verified'] && strcmp($email, $user['email']) == 0 && strcmp($passhash, $newPasshash) == 0 )
						{
							$authID = $this->saveSession(0, $user['user_id'], $ipaddress, $useragent);

							$this->saveLastvisit($user['user_id']);
						}
						else
						{
							$user = array();
						}
					}
				}
			}
		}

		if ( !$user || !isset($user['user_id']) || !$user['user_id'] || !$this->createUserSession($user) )
		{
			$userID = 0;

			if ( !$user )
			{
				$authID = $this->saveSession(0, $userID, $ipaddress, $useragent);
			}

			$this->createGuestSession();
		}

		session::set('auth_id', $authID);
		session::set('user_id', $userID);

		// Is the site offline?
		if ( !input::isCP() && !config::item('site_online', 'system') && !session::permission('site_access_offline', 'system') && uri::getURI() != 'site/offline' && uri::segment(1) != 'load' )
		{
			router::redirect('site/offline');
		}
		// Is this the control panel?
		elseif ( input::isCP() && !session::permission('site_access_cp', 'system') && ( uri::getURI() != 'cp' && uri::getURI() != 'cp/users/login' && uri::getURI() != 'cp/users/login/license' ) )
		{
			router::redirect('cp/users/login');
		}

		if ( !input::isCP() && $this->isLoggedin() && session::permission('site_access_cp', 'system') && uri::segment(1) != 'load' && input::demo(0, '', session::item('user_id')) )
		{
			$this->logout();
			view::setInfo('For the purposes of this demo you may not use front end of the site under the administrator account. As such we have now logged you out.<br/>Feel free ' . html_helper::anchor('users/signup', 'register on the site') . ' to test user end functionality or ' . html_helper::anchor('users/login', 'login') . ' using your existing account details if you have one already.');
			router::redirect();
		}
	}

	public function createUserSession($user)
	{
		if ( !isset($user['group_id']) )
		{
			$user = $this->getUser($user['user_id'], false, false);
		}

		if ( config::item('devmode', 'system') || !session::permission('group_id', 'system') || session::permission('group_id', 'system') != $user['group_id'] )
		{
			$permissions = $this->getPermissions($user['group_id']);
			$permissions['system']['group_id'] = $user['group_id'];

			foreach ( $permissions as $plugin => $permission )
			{
				session::set($permission, '', 'permissions_' . $plugin);
			}
		}

		if ( !session::permission('site_login', 'system') )
		{
			return false;
		}

		session::set($user);

		if ( config::item('devmode', 'system') || !session::item('config_id', 'config') )
		{
			$config = $this->getUserConfig($user['user_id']);
			session::set($config, '', 'config');
		}

		if ( !config::item('template_override', 'system') && ( input::get('template') && ( $templateID = array_search(input::get('template'), config::item('templates', 'core', 'keywords')) ) !== false || session::item('template_custom') ) )
		{
			if ( input::get('template') )
			{
				session::set('template_custom', input::get('template'));
				$template = config::item('templates', 'core', 'keywords', $templateID);
			}
			else
			{
				$template = session::item('template_custom');
			}
		}
		else
		{
			$template = config::item('templates', 'core', 'keywords', config::item('template_override', 'system') ? config::item('template_id', 'system') : session::item('template_id'));
		}

		if ( !config::item('language_override', 'system') && ( input::get('language') && ( $languageID = array_search(input::get('language'), config::item('languages', 'core', 'keywords')) ) !== false || session::item('language_custom') ) )
		{
			if ( input::get('language') )
			{
				session::set('language_custom', input::get('language'));
				$language = config::item('languages', 'core', 'keywords', $languageID);
			}
			else
			{
				$language = session::item('language_custom');
			}
		}
		else
		{
			$language = config::item('languages', 'core', 'keywords', config::item('language_override', 'system') ? config::item('language_id', 'system') : session::item('language_id'));
		}

		session::set('language', $language);
		session::set('template', $template);

		if ( config::item('time_zone_override', 'system') )
		{
			session::set('time_zone', config::item('time_zone', 'system'));
		}

		language::setLanguage($language);

		$this->bootstrap->update();

		session::set('group_name', text_helper::entities(config::item('usergroups', 'core', $user['group_id'])));
		session::set('type_name', text_helper::entities(config::item('usertypes', 'core', 'names', $user['type_id'])));

		if ( config::item('devmode', 'system') || !session::item('profile_id') )
		{
			$profile = $this->getProfile($user['user_id'], $user['type_id'], 'all');

			if ( $user['user_id'] == session::item('user_id') )
			{
				foreach ( session::section('session') as $key => $value )
				{
					if ( strpos($key, 'data_') === 0 )
					{
						session::delete($key);
					}
				}
			}

			session::set($profile);
		}

		return true;
	}

	public function createGuestSession()
	{
		if ( config::item('devmode', 'system') || !session::permission('group_id', 'system') )
		{
			$permissions = $this->getPermissions(config::item('group_guests_id', 'users'));
			$permissions['system']['group_id'] = config::item('group_guests_id', 'users');

			foreach ( $permissions as $plugin => $permission )
			{
				session::set($permission, '', 'permissions_'.$plugin);
			}
		}

		if ( !config::item('template_override', 'system') && ( input::get('template') && ( $templateID = array_search(input::get('template'), config::item('templates', 'core', 'keywords')) ) !== false || session::item('template_custom') ) )
		{
			if ( input::get('template') )
			{
				session::set('template_custom', input::get('template'));
				$template = config::item('templates', 'core', 'keywords', $templateID);
			}
			else
			{
				$template = session::item('template_custom');
			}
		}
		else
		{
			$template = config::item('templates', 'core', 'keywords', config::item('template_id', 'system'));
		}

		if ( !config::item('language_override', 'system') && ( input::get('language') && ( $languageID = array_search(input::get('language'), config::item('languages', 'core', 'keywords')) ) !== false || session::item('language_custom') ) )
		{
			if ( input::get('language') )
			{
				session::set('language_custom', input::get('language'));
				$language = config::item('languages', 'core', 'keywords', $languageID);
			}
			else
			{
				$language = session::item('language_custom');
			}
		}
		else
		{
			$language = config::item('languages', 'core', 'keywords', config::item('language_id', 'system'));
		}

		session::set('language', $language);
		session::set('template', $template);

		language::setLanguage($language);

		session::set('time_zone', config::item('time_zone', 'system'));

		$this->bootstrap->update();

		return true;
	}

	public function saveSession($authID, $userID, $ipaddress, $useragent, $newUserID = 0)
	{
		if ( !$authID )
		{
			$authData['auth_id'] = $this->generateSesshash($ipaddress, $useragent);
			$authData['ip_address'] = $ipaddress;
			$authData['user_agent'] = $useragent;
		}

		$authData['user_id'] = $newUserID ? $newUserID : $userID;
		$authData['active_date'] = date_helper::now();

		if ( !$authID )
		{
			$this->db->insert('users_sessions', $authData);
			$authID = $authData['auth_id'];
		}
		else
		{
			$this->db->update('users_sessions', $authData, array('auth_id' => $authID, 'user_id' => $userID, 'ip_address' => $ipaddress, 'user_agent' => $useragent), 1);
		}

		return $authID;
	}

	public function getSession($authID, $userID, $ipaddress, $useragent)
	{
		//$session = $this->db->query("SELECT `s`.`auth_id`, `s`.`user_id`, `s`.`active_date`, `u`.`verified`, `u`.`active`
		//	FROM `:prefix:users_sessions` AS `s` LEFT JOIN `:prefix:users` AS `u` ON `s`.`user_id`=`u`.`user_id` AND `u`.`verified`=1 AND `u`.`active`=1
		//	WHERE `s`.`auth_id`=? AND `s`.`user_id`=? AND `s`.`ip_address`=? AND `s`.`user_agent`=?
		//	LIMIT 1", array($authID, $userID, $ipaddress, $useragent))->row();
		$session = $this->db->query("SELECT `s`.`auth_id`, `s`.`user_id`, `s`.`active_date`, `u`.`verified`, `u`.`active`
			FROM `:prefix:users_sessions` AS `s` LEFT JOIN `:prefix:users` AS `u` ON `s`.`user_id`=`u`.`user_id` AND `u`.`verified`=1 AND `u`.`active`=1
			WHERE `s`.`auth_id`=? AND `s`.`user_id`=? AND `s`.`ip_address`=?
			LIMIT 1", array($authID, $userID, $ipaddress))->row();

		if ( $session && $session['user_id'] && ( !$session['verified'] || !$session['active'] ) )
		{
			$session = array();
		}

		return $session;
	}

	public function deleteSession($authID, $userID, $ipaddress, $useragent)
	{
		$retval = $this->db->delete('users_sessions', array('auth_id' => $authID, 'user_id' => $userID, 'ip_address' => $ipaddress, 'user_agent' => $useragent), 1);

		return $retval;
	}

	public function generateSesshash($ipaddress, $useragent)
	{
		$salt = text_helper::random(8);

		$sesshash = sha1($ipaddress.$useragent.$salt);

		return $sesshash;
	}

	public function generatePasshash($email, $passhash)
	{
		return sha1($email.$passhash);
	}

	public function isLoggedin($cp = false)
	{
		if ( $cp )
		{
			return session::item('user_id') && session::permission('site_access_cp', 'system') ? true : false;
		}
		else
		{
			return session::item('user_id') ? true : false;
		}
	}

	public function login($userID, $remember = 0, $user = array(), $spy = 0)
	{
		// Are we logging into someone else's account?
		if ( $spy && session::item('user_id') )
		{
			$spy = session::item('user_id');

			session::delete('', 'config');
			session::delete('profile_id');
		}

		if ( !$user && !( $user = $this->getUser($userID, false, false) ) )
		{
			return false;
		}

		session::set($user);

		if ( $remember )
		{
			$passhash = $this->generatePasshash($user['email'], $user['password']);

			$cookie = json_encode(array('user_id' => $user['user_id'], 'email' => $user['email'], 'passhash' => $passhash));

			cookie::set('sessdata', $cookie, 60*60*24*365);
		}

		$authID = session::item('auth_id');
		$ipaddress = substr(input::ipaddress(), 0, 15);
		$useragent = substr(input::useragent(), 0, 255);

		$this->saveSession($authID, $spy, $ipaddress, $useragent, $user['user_id']);

		$this->saveLastvisit($userID);

		if ( $spy )
		{
			session::set('spy_id', $spy);
		}

		// Action hook
		hook::action('users/account/login', $userID, $user);

		return true;
	}

	public function logout()
	{
		if ( session::item('spy_id') )
		{
			$oldID = session::item('user_id');
			$this->login(session::item('spy_id'), 0, array());
			session::delete('spy_id');
			router::redirect('cp/users/edit/' . $oldID);
		}

		$authID = session::item('auth_id');
		$userID = session::item('user_id');
		$ipaddress = substr(input::ipaddress(), 0, 15);
		$useragent = substr(input::useragent(), 0, 255);

		$this->deleteSession($authID, $userID, $ipaddress, $useragent);

		// Action hook
		hook::action('users/account/logout', $userID);

		cookie::delete('sessdata');

		session::set('auth_id', 0);
		session::set('user_id', 0);

		session::delete(array('auth_id', 'user_id'));
		session::destroy();

		session::initialize();

		return true;
	}
}
