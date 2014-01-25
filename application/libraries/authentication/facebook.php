<?php

class Authentication_Facebook extends Library
{
	protected $config = array();
	protected $facebook = array();

	public function __construct($config = array())
	{
		parent::__construct();

		$this->config = $config;
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'Facebook',
			'description' => 'Facebook authentication library.',
			'settings' => array(
				array(
					'name' => 'App ID',
					'keyword' => 'app_id',
					'type' => 'text',
					'class' => 'input-large',
					'required' => true,
					'value' => '',
				),
				array(
					'name' => 'App secret',
					'keyword' => 'app_secret',
					'type' => 'text',
					'class' => 'input-large',
					'required' => true,
					'value' => '',
				),
			),
		);

		return $params;
	}

	public function initialize()
	{
		include_once DOCPATH . 'libraries/authentication/facebook/facebook.php';

		$this->facebook = new Facebook(array(
			'appId'  => $this->config['app_id'],
			'secret' => $this->config['app_secret'],
		));
	}

	public function saveToken($userID)
	{
		$data = array(
			'user_id' => $userID,
			'facebook_id' => session::item('connection', 'remote_connect', 'facebook_id'),
			'token' => session::item('connection', 'remote_connect', 'token'),
		);

		$this->db->insert('users_facebook', $data);

		return true;
	}

	public function getToken($userID = 0, $facebookID = 0)
	{
		if ( $userID )
		{
			$column = 'user_id';
			$value = $userID;
		}
		else
		{
			$column = 'facebook_id';
			$value = $facebookID;
		}

		$tokens = $this->db->query("SELECT `user_id`, `facebook_id`, `token`, `expire_date` FROM `:prefix:users_facebook` WHERE `$column`=? LIMIT 1", array($value))->row();

		return $tokens;
	}

	public function getUser()
	{
		$user = $this->facebook->api('/me');

		return $user;
	}

	public function authorize($action = '')
	{
		$this->initialize();

		$url = $this->facebook->getLoginUrl(array(
			'redirect_uri' => config::siteURL(config::siteURL('users/connect/confirm/facebook/' . $action)),
			'scope' => join(',', array(
				'email',
				'user_birthday',
				'user_status',
				'publish_stream',
				'offline_access',
			)),
        ));

        router::redirect($url);
	}

	public function confirm($action = '')
	{
		$this->initialize();

		// Get facebook user ID
		$facebookID = $this->facebook->getUser();

		// Do we have facebook user ID?
		if ( $facebookID )
		{
			// Get saved token
			$token = $this->getToken(0, $facebookID);

			// Do we have saved token or are we logging in?
			if ( $token || $action == 'login' && $token )
			{
				$this->users_model->login($token['user_id']);
				router::redirect(session::item('slug').'#home');
			}
			// Are we signing up?
			elseif ( !$token || $action == 'signup' )
			{
				// Get user data and token
				$user = $this->getUser();
				$token = $this->facebook->getAccessToken();

				// Do we have user data and token?
				if ( $user && $token )
				{
					$connection = array(
						'name' => 'facebook',
						'facebook_id' => $facebookID,
						'token' => $token,
					);

					session::set(array('connection' => $connection), '', 'remote_connect');

					if ( !session::item('account', 'signup') )
					{
						$account = array(
							'email' => isset($user['email']) ? $user['email'] : '',
							'username' => isset($user['username']) ? $user['username'] : '',
						);

						session::set(array('account' => $account), '', 'signup');
					}

					if ( !session::item('profile', 'signup') )
					{
						$profile = array();
						if ( isset($user['gender']) )
						{
							$profile['data_gender'] = $user['gender'] == 'male' ? 1 : 0;
						}
						if ( isset($user['birthday']) )
						{
							$profile['data_birthday'] = substr($user['birthday'], -4).substr($user['birthday'], 0, 2).substr($user['birthday'], 3, 2);
						}

						session::set(array('profile' => $profile), '', 'signup');
					}

					router::redirect('users/signup#account');
				}
			}
		}

        router::redirect('users/login');
	}

	public function isInUse()
	{
		$user = $this->db->query("SELECT `user_id` FROM `:prefix:users_facebook` LIMIT 1")->row();

		return $user ? true : false;
	}

	public function install()
	{
		$this->dbforge->dropTable(':prefix:users_facebook');
		$this->dbforge->createTable(':prefix:users_facebook',
			array(
				array('name' => 'user_id', 'type' => 'bigint', 'constraint' => 12, 'unsigned' => true, 'null' => false),
				array('name' => 'facebook_id', 'type' => 'bigint', 'constraint' => 20, 'unsigned' => true, 'null' => false),
				array('name' => 'token', 'type' => 'varchar', 'constraint' => 255, 'null' => false, 'default' => ''),
				array('name' => 'expire_date', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
			),
			array('user_id'),
			array('facebook_id'),
			array(),
			false,
			$this->dbforge->getEngine()
		);
	}

	public function uninstall()
	{
		$this->dbforge->dropTable(':prefix:users_facebook');
	}
}