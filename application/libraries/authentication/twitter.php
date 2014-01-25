<?php

class Authentication_Twitter extends Library
{
	protected $config = array();
	protected $twitter = array();

	public function __construct($config = array())
	{
		parent::__construct();

		$this->config = $config;
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'Twitter',
			'description' => 'Twitter authentication library.',
			'settings' => array(
				array(
					'name' => 'Consumer key',
					'keyword' => 'consumer_key',
					'type' => 'text',
					'class' => 'input-large',
					'required' => true,
					'value' => '',
				),
				array(
					'name' => 'Consumer secret',
					'keyword' => 'consumer_secret',
					'type' => 'text',
					'class' => 'input-large',
					'required' => true,
					'value' => '',
				),
				array(
					'name' => 'Callback URL',
					'keyword' => 'callback_url',
					'type' => 'static',
					'value' => html_helper::siteURL('users/connect/confirm/twitter'),
				),
			),
		);

		return $params;
	}

	public function initialize($token = '', $secret = '')
	{
		include_once DOCPATH . 'libraries/authentication/twitter/twitter_oauth.php';

		$this->twitter = new TwitterOAuth($this->config['consumer_key'], $this->config['consumer_secret'], $token, $secret);
	}

	public function saveToken($userID)
	{
		$data = array(
			'user_id' => $userID,
			'twitter_id' => session::item('connection', 'remote_connect', 'twitter_id'),
			'token' => session::item('connection', 'remote_connect', 'token'),
			'secret' => session::item('connection', 'remote_connect', 'secret'),
		);

		$this->db->insert('users_twitter', $data);

		return true;
	}

	public function getToken($userID = 0, $twitterID = 0)
	{
		if ( $userID )
		{
			$column = 'user_id';
			$value = $userID;
		}
		else
		{
			$column = 'twitter_id';
			$value = $twitterID;
		}

		$tokens = $this->db->query("SELECT `user_id`, `twitter_id`, `token`, `secret` FROM `:prefix:users_twitter` WHERE `$column`=? LIMIT 1", array($value))->row();

		return $tokens;
	}

	public function getUser()
	{
		$user = $this->twitter->get('account/verify_credentials', array('include_entities' => 'false'));

		return $user;
	}

	public function authorize($action = '')
	{
		$this->initialize();
		$request = $this->twitter->getRequestToken(config::siteURL('users/connect/confirm/twitter/' . $action));

		if ( $request && $this->twitter->http_code == 200 )
		{
			$data = array(
				'twitter' => array(
					'token' => $request['oauth_token'],
					'secret' => $request['oauth_token_secret'],
				),
			);
			session::set($data, '', 'remote_connect');

			$url = $this->twitter->getAuthorizeURL($request['oauth_token']);
			router::redirect($url);
		}
	}

	public function confirm($action = '')
	{
		// Do we have necessary data?
		if ( input::get('oauth_token') && input::get('oauth_verifier') )
		{
			// Get temporary access token
			$this->initialize(session::item('twitter', 'remote_connect', 'token'), session::item('twitter', 'remote_connect', 'secret'));
			$access = $this->twitter->getAccessToken(input::get('oauth_verifier'));

			// Do we have temporary token?
			if ( $access )
			{
				// Get saved token
				$token = $this->getToken(0, $access['user_id']);

				// Do we have saved token or are we logging in?
				if ( $token || $action == 'login' && $token )
				{
					$this->users_model->login($token['user_id']);
					router::redirect(session::item('slug').'#home');
				}
				// Are we signing up?
				elseif ( !$token || $action == 'signup' )
				{
					// Get user data
					$this->initialize($access['oauth_token'], $access['oauth_token_secret']);
					$user = $this->getUser($access['user_id']);

					// Do we have user data?
					if ( $user && isset($user->id) )
					{
						$connection = array(
							'name' => 'twitter',
							'twitter_id' => $user->id,
							'token' => $access['oauth_token'],
							'secret' => $access['oauth_token_secret'],
						);

						session::set(array('connection' => $connection), '', 'remote_connect');

						$account = array(
							'username' => isset($user->name) ? $user->name : '',
						);

						session::set(array('account' => $account), '', 'signup');

						router::redirect('users/signup#account');
					}
				}
			}
		}

		router::redirect('users/login');
	}

	public function isInUse()
	{
		$user = $this->db->query("SELECT `user_id` FROM `:prefix:users_twitter` LIMIT 1")->row();

		return $user ? true : false;
	}

	public function install()
	{
		$this->dbforge->dropTable(':prefix:users_twitter');
		$this->dbforge->createTable(':prefix:users_twitter',
			array(
				array('name' => 'user_id', 'type' => 'bigint', 'constraint' => 12, 'unsigned' => true, 'null' => false),
				array('name' => 'twitter_id', 'type' => 'bigint', 'constraint' => 20, 'unsigned' => true, 'null' => false),
				array('name' => 'token', 'type' => 'varchar', 'constraint' => 255, 'null' => false, 'default' => ''),
				array('name' => 'secret', 'type' => 'varchar', 'constraint' => 255, 'null' => false, 'default' => ''),
			),
			array('user_id'),
			array('twitter_id'),
			array(),
			false,
			$this->dbforge->getEngine()
		);
	}

	public function uninstall()
	{
		$this->dbforge->dropTable(':prefix:users_twitter');
	}
}