<?php

class Users_Users_Model extends Users_Session_Model
{
	public function __construct()
	{
		parent::__construct();

		// Is this control panel?
		if ( strtolower(uri::segment(1)) == 'cp' && !$this->isLoggedin() && ( uri::segment(2) != 'users' || uri::segment(3) != 'login' ) )
		{
			router::redirect('cp/users/login');
		}
	}

	public function saveUser($userID, $user)
	{
		// Is this a new user?
		if ( !$userID )
		{
			// Save plain text password
			$password = $user['password'];

			// Set basic data
			$user['password'] = $this->encryptPassword($user['password']);
			$user['ip_address'] = input::ipaddress();
			$user['join_date'] = date_helper::now();
			$user['language_id'] = config::item('language_id', 'system');
			$user['template_id'] = config::item('template_id', 'system');
			$user['time_zone'] = config::item('time_zone', 'system');

			// Insert user
			$userID = $this->db->insert('users', $user);

			$profile['profile_id'] = $userID;

			// Insert profile
			$this->db->insert('users_data_' . config::item('usertypes', 'core', 'keywords', $user['type_id']), $profile);

			// Do we have default privacy set?
			if ( config::item('privacy_default', 'users') > 1 )
			{
				$config = array(
					'privacy_profile' => config::item('privacy_default', 'users'),
				);
				$this->saveConfig($userID, $config, false);
			}

			// Save timeline action
			timeline_helper::save('user_signup', $userID, $userID, ( isset($user['active'], $user['verified']) && $user['active'] && $user['verified'] ? 1 : 0 ));

			// Action hook
			hook::action('users/account/insert', $userID, array_merge($user, array('password_plain' => $password)));
		}
		// Existing user
		else
		{
			$password = '';
			$user['user_id'] = $userID;

			// Do we have a password?
			if ( isset($user['password']) )
			{
				// Is our password empty?
				if ( $user['password'] != '' )
				{
					// Save plain text password
					$password = $user['password'];

					// Set password
					$user['password'] = $this->savePassword($userID, $user['password'], false);
				}
				else
				{
					unset($user['password']);
				}
			}

			// Update user
			$this->db->update('users', $user, array('user_id' => $userID), 1);

			// Update timeline action
			timeline_helper::update(true, 'user_signup', $userID, $userID, ( isset($user['active'], $user['verified']) && $user['active'] && $user['verified'] ? 1 : 0 ));

			// Action hook
			hook::action('users/account/update', $userID, array_merge($user, array('password_plain' => $password)));
		}

		return $userID;
	}

	public function saveConfig($userID, $config, $update = true)
	{
		if ( !$config )
		{
			return true;
		}

		$current = $update ? $this->getUserConfig($userID) : array();

		foreach ( $config as $keyword => $value )
		{
			if ( isset($current[$keyword]) )
			{
				// Update user config
				$retval = $this->db->update('users_config', array('val' => $value), array('user_id' => $userID, 'keyword' => $keyword), 1);
			}
			else
			{
				// Insert user config
				$retval = $this->db->insert('users_config', array('user_id' => $userID, 'keyword' => $keyword, 'val' => $value));
			}
		}

		// Are updating existing records?
		if ( $update )
		{
			// Clean up counters
			$this->counters_model->deleteCounters('user', $userID);

			if ( session::item('user_id') == $userID )
			{
				// Clean up config section
				session::delete('', 'config');

				// Clean up profile session
				session::delete('profile_id');
			}

			// Action hook
			hook::action('users/config/update', $userID, $config);
		}

		return true;
	}

	public function deleteConfig($userID, $config)
	{
		if ( !$config )
		{
			return true;
		}

		// Delete configuration
		$retval = $this->db->delete('users_config', array('user_id' => $userID, 'keyword' => $config), count($config));

		// Clean up counters
		$this->counters_model->deleteCounters('user', $userID);

		if ( session::item('user_id') == $userID )
		{
			// Clean up config section
			session::delete('', 'config');

			// Clean up profile session
			session::delete('profile_id');
		}

		// Action hook
		hook::action('users/config/update', $userID, $config);

		return true;
	}

	public function savePrivacy($userID, $config)
	{
		// Save user privacy
		$retval = $this->saveConfig($userID, $config);

		// Clean up counters
		$this->counters_model->deleteCounters('user', $userID);

		if ( session::item('user_id') == $userID )
		{
			// Clean up config section
			session::delete('', 'config');
		}

		// Action hook
		hook::action('users/privacy/update', $userID, $config);

		return $retval;
	}

	public function saveNotifications($userID, $config)
	{
		// Save user notifications
		$retval = $this->saveConfig($userID, $config);

		if ( session::item('user_id') == $userID )
		{
			// Clean up config section
			session::delete('', 'config');
		}

		// Action hook
		hook::action('users/notifications/update', $userID, $config);

		return $retval;
	}

	public function saveProfile($userID, $typeID, $profileOld, $fields, $extra = array(), $static = false)
	{
		$type = config::item('usertypes', 'core', 'keywords', $typeID);

		// Save user profile
		$userID = $this->fields_model->saveValues('profile', $userID, $profileOld, $fields, $extra, $static, '', $type);

		// Update name1/name2
		$names = array();
		foreach ( array(1, 2) as $index )
		{
			if ( $static && isset($profileOld['data_' . config::item('usertypes', 'core', 'fields', $typeID, $index)]) && $profileOld['data_' . config::item('usertypes', 'core', 'fields', $typeID, $index)] )
			{
				$names['name' . $index] = $profileOld['data_' . config::item('usertypes', 'core', 'fields', $typeID, $index)];
			}
			elseif ( !$static && input::post('data_' . config::item('usertypes', 'core', 'fields', $typeID, $index)) )
			{
				$names['name' . $index] = $static ? $profileOld['data_' . config::item('usertypes', 'core', 'fields', $typeID, $index)] : input::post('data_' . config::item('usertypes', 'core', 'fields', $typeID, $index));
			}
		}

		if ( $names )
		{
			$this->saveUser($userID, $names);
		}

		// Clean up profile session
		session::delete('profile_id');

		return $userID;
	}

	public function cancelUser($userID)
	{
		$this->db->update('users', array('group_id' => config::item('group_cancelled_id', 'users')), array('user_id' => $userID), 1);
	}

	public function saveUsername($userID, $username)
	{
		// Save username
		$retval = $this->db->update('users', array('username' => $username), array('user_id' => $userID), 1);

		// Action hook
		hook::action('users/username/update', $userID, $username);

		return $retval;
	}

	public function savePassword($userID, $password, $save = true)
	{
		$hash = $this->encryptPassword($password);

		// Save password
		if ( $save )
		{
			$retval = $this->db->update('users', array('password' => $hash), array('user_id' => $userID), 1);
		}

		// Action hook
		hook::action('users/password/update', $userID, $password, $hash);

		return $hash;
	}

	public function saveEmail($userID, $email)
	{
		// Save email
		$retval = $this->db->update('users', array('email' => $email), array('user_id' => $userID), 1);

		// Action hook
		hook::action('users/email/update', $userID, $email);

		return $retval;
	}

	public function saveLastvisit($userID)
	{
		// Save last visit
		$retval = $this->db->update('users', array('visit_date' => date_helper::now()), array('user_id' => $userID), 1);

		return $retval;
	}

	public function savePicture($userID, $pictureID)
	{
		$data = array(
			'picture_id' => $pictureID,
			'picture_active' => session::permission('users_pictures_approve', 'users') ? 1 : 9,
			'picture_date' => date_helper::now(),
		);

		// Save picture
		$retval = $this->db->update('users', $data, array('user_id' => $userID), 1);

		// Save timeline action
		if ( session::item('timeline_user_picture', 'config') === false || session::item('timeline_user_picture', 'config') )
		{
			timeline_helper::delete('user_picture', $userID, $userID);
			timeline_helper::save('user_picture', $userID, $userID, session::permission('users_pictures_approve', 'users') ? 1 : 0);
		}

		// Action hook
		hook::action('users/picture/update', $userID, $pictureID);

		return $retval;
	}

	public function togglePictureStatus($userID, $user, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $user['picture_active'] == $status )
		{
			return true;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:users` SET `picture_active`=? WHERE `user_id`=? LIMIT 1", array($status, $userID));

		// Update timeline action
		timeline_helper::update(true, 'user_picture', $userID, $userID, $status);

		// Action hook
		hook::action('users/picture/' . ( $status ? 'approve' : 'decline' ), $userID);

		return true;
	}

	public function rotatePicture($userID, $pictureID, $angle)
	{
		$files = $this->storage_model->getFiles($pictureID, 5, array('', 'x', 'p', 'l', 't'));

		if ( $retval = $this->storage_model->rotate($files['x'], $angle) )
		{
			$this->storage_model->resize($files['x'], config::item('picture_dimensions', 'users'), '', 'preserve', $files['']['file_id']);
			$this->storage_model->resize($files[''], config::item('picture_dimensions_p', 'users'), 'p', 'crop', $files['p']['file_id']);
			$this->storage_model->resize($files[''], config::item('picture_dimensions_l', 'users'), 'l', 'crop', $files['l']['file_id']);
			$this->storage_model->resize($files[''], config::item('picture_dimensions_t', 'users'), 't', 'crop', $files['t']['file_id']);
		}

		// Action hook
		hook::action('users/picture/rotate', $userID, $pictureID);

		return $retval;
	}

	public function saveThumbnail($userID, $pictureID, $x, $y, $w, $h)
	{
		$files = $this->storage_model->getFiles($pictureID, 4, array('', 'p', 'l', 't'));

		foreach ( $files as $file )
		{
			if ( $file['suffix'] != '' )
			{
				$retval = $this->storage_model->thumbnail($files[''], $x, $y, $w, $h, config::item('picture_dimensions_' . $file['suffix'], 'users'), $file['suffix'], $file['file_id']);
			}
		}

		// Action hook
		hook::action('users/picture/thumbnail', $userID, $pictureID);

		return $retval;
	}

	public function deletePicture($userID, $pictureID, $update = true)
	{
		$data = array(
			'picture_id' => 0,
			'picture_active' => 0,
			'picture_date' => 0,
		);

		$retval = $this->storage_model->deleteFiles($pictureID, 5);

		if ( $update )
		{
			$this->db->update('users', $data, array('user_id' => $userID), 1);
		}

		// Delete timeline action
		timeline_helper::delete('user_picture', $userID, $userID);

		// Action hook
		hook::action('users/picture/delete', $userID, $pictureID);

		return $retval;
	}

	public function isUniqueEmail($email, $userID = 0)
	{
		$user = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:users` WHERE `email`=? AND `user_id`!=? LIMIT 1", array($email, $userID))->row();

		return $user['totalrows'] ? false : true;
	}

	public function isUniqueUsername($username, $userID = 0)
	{
		$user = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:users` WHERE `username`=? AND `user_id`!=? LIMIT 1", array($username, $userID))->row();

		return $user['totalrows'] ? false : true;
	}

	public function isValidUsername($username, $userID = 0, $unique = true, $admin = false, $error = true)
	{
		if ( is_numeric($username) )
		{
			return $error ? __('username_numeric', 'users_signup') : 'numeric';
		}
		elseif ( preg_match('/[^0-9\p{L}\-\.\_]+/u', $username) )
		{
			return $error ? __('username_alpha_numeric', 'users_signup') : 'invalid';
		}
		elseif ( config::item('plugins', 'system', $username) )
		{
			return $error ? __('username_reserved', 'users_signup') : 'reserved';
		}
		elseif ( !$admin && array_search($username, array_map(array('utf8', 'strtolower'), array_map('trim', explode("\n", config::item('usernames_banned', 'users'))))) !== false )
		{
			return $error ? __('username_reserved', 'users_signup') : 'reserved';
		}
		elseif ( $unique && !$this->isUniqueUsername($username, $userID) )
		{
			return $error ? __('username_duplicate', 'users_signup') : 'duplicate';
		}

		return true;
	}

	public function isValidLogin($user, $error = true)
	{
		if ( config::item('user_username', 'users') && strpos($user, '@') === false )
		{
			if ( is_numeric($user) )
			{
				return $error ? __('username_invalid', 'users_signup') : 'numeric';
			}
			elseif ( preg_match('/[^0-9\p{L}\-\.\_]+/u', $user) )
			{
				return $error ? __('username_invalid', 'users_signup') : 'invalid';
			}
		}
		else
		{
			if ( !validate::valid_email($user) )
			{
				return $error ? __('email_invalid', 'users_signup') : 'email';
			}
		}

		return true;
	}

	public function getPrivacyAccess($userID, $level, $error = true, $friends = -1)
	{
		if ( $level == 9 && $userID != session::item('user_id') || // Only author can access this item
			$level == 2 && !$this->isLoggedin() || // Only registered users can access this item
			$level == 3 && $userID != session::item('user_id') && ( !$friends || $friends == -1 && !$this->users_friends_model->getFriend($userID) ) ) // Only friends can access this item
		{
			if ( $error )
			{
				view::setError(__('privacy_error', 'users'));
			}
			return false;
		}

		return true;
	}

	public function isRecentSignup()
	{
		$user = $this->db->query("SELECT COUNT(*) AS `totalrows` FROM `:prefix:users` WHERE `ip_address`=? AND `join_date`>=? LIMIT 1", array(input::ipaddress(), (date_helper::now()-config::item('signup_delay', 'users')*60)))->row();

		return $user['totalrows'] ? true : false;
	}

	public function getUserConfig($userID, $keyword = '')
	{
		if ( $keyword )
		{
			$config = $this->db->query("SELECT `val` FROM `:prefix:users_config` WHERE `user_id`=? AND `keyword`=? LIMIT 1", array($userID, $keyword))->row();
		}
		else
		{
			$config = array('config_id' => $userID);
			$result = $this->db->query("SELECT `keyword`, `val` FROM `:prefix:users_config` WHERE `user_id`=?", array($userID))->result();
			foreach ( $result as $row )
			{
				$config[$row['keyword']] = $row['val'];
			}
		}

		return $config;
	}

	public function getUsersConfig($userIDs, $keyword = '')
	{
		$users = array();

		$result = $this->db->query("SELECT `user_id`, `keyword`, `val` FROM `:prefix:users_config` WHERE `user_id` IN (" . implode(',', $userIDs) . ") " . ( $keyword ? "AND `keyword`=?" : "" ), array($keyword))->result();

		foreach ( $result as $row )
		{
			$users[$row['user_id']][$row['keyword']] = $row['val'];
		}

		return $users;
	}

	public function getUser($userID, $profile = 'in_view', $config = true, $params = array())
	{
		if ( is_numeric($userID) )
		{
			$column = 'user_id';
		}
		elseif ( strpos($userID, '@') !== false )
		{
			$column = 'email';
		}
		else
		{
			$column = 'username';
		}

		$params['condition_column'] = $column;
		$params['select_columns'] = "`f`.`service_id` AS `picture_file_service_id`, `f`.`path` AS `picture_file_path`, `f`.`name` AS `picture_file_name`, `f`.`extension` AS `picture_file_ext`, `f`.`width` AS `picture_file_width`, `f`.`height` AS `picture_file_height`,
			`f`.`size` AS `picture_file_size`, `f`.`post_date` AS `picture_file_post_date`, `f`.`modify_date` AS `picture_file_modify_date`";
		$params['join_tables'] = " LEFT JOIN `:prefix:storage_files` AS `f` ON `u`.`picture_id`=`f`.`file_id`";

		$user = $this->fields_model->getRow('user', $userID, false, $params);

		if ( $user )
		{
			$userID = $user['user_id'];

			// Do we need to get configuration data?
			if ( $config )
			{
				$user['config'] = $this->getUserConfig($userID);
			}

			// Do we have any field names set?
			if ( !config::item('usertypes', 'core', 'fields', $user['type_id'], 1) )
			{
				$user['name1'] = $user['username'];
				$user['name2'] = '';
			}

			$user['name'] = users_helper::name($user['name1'], $user['name2'], ( !isset($params['escape']) || $params['escape'] ? true : false ));

			$user['group_name'] = config::item('usergroups', 'core', $user['group_id']);
			$user['type_name'] = config::item('usertypes', 'core', 'names', $user['type_id']);

			if ( !isset($params['escape']) || $params['escape'] )
			{
				$user['name1'] = text_helper::entities($user['name1']);
				$user['name2'] = text_helper::entities($user['name2']);

				$user['group_name'] = text_helper::entities($user['group_name']);
				$user['type_name'] = text_helper::entities($user['type_name']);
			}

			$user['slug'] = users_helper::slug($user['user_id'], $user['username']);
			$user['slug_id'] = users_helper::id($user['user_id'], $user['username']);

			$user['online'] = $user['visit_date'] >= date_helper::now() - 60*5 ? 1 : 0;

			if ( $profile )
			{
				unset($params['condition_column'], $params['select_columns'], $params['join_tables']);

				$profile = $this->getProfile($user['user_id'], $user['type_id'], $profile, $params);
				$user = array_merge($user, $profile);
			}
		}

		return $user;
	}

	public function updateViews($userID)
	{
		$retval = $this->db->query("UPDATE `:prefix:users` SET `total_views`=`total_views`+1 WHERE `user_id`=? LIMIT 1", array($userID));

		return $retval;
	}

	public function getProfile($userID, $typeID, $fields = array(), $params = array())
	{
		$params['type_id'] = $typeID;
		$params['table_type'] = config::item('usertypes', 'core', 'keywords', $typeID);

		$profile = $this->fields_model->getRow('profile', $userID, $fields, $params);

		return $profile;
	}

	public function getProfiles($userIDs, $typeID, $fields = array(), $params = array())
	{
		$params['type_id'] = $typeID;
		$params['table_type'] = config::item('usertypes', 'core', 'keywords', $typeID);

		// Get profile
		$profiles = $this->fields_model->getRows('profile', false, $fields, array("`p`.`profile_id` IN (" . implode(',', $userIDs) . ")"), array(), false, count($userIDs), $params);

		return $profiles;
	}

	public function getPrivacyOptions($level = 0, $guests = true)
	{
		$privacy = array();

		if ( $level <= 1 && $guests && config::item('privacy_options', 'users', 1) !== false )
		{
			$privacy[1] = __('privacy_item_everyone', 'users');
		}

		if ( $level <= 2 && config::item('privacy_options', 'users', 2) !== false )
		{
			$privacy[2] = $guests ? __('privacy_item_registered', 'users') : __('privacy_item_everyone', 'users');
		}

		if ( $level <= 3 && config::item('privacy_options', 'users', 3) !== false )
		if ( config::item('friends_active', 'users') )
		{
			$privacy[3] = __('privacy_item_friends', 'users');
		}

		if ( config::item('privacy_options', 'users', 9) !== false )
		{
			$privacy[9] = __('privacy_item_myself', 'users');
		}

		return $privacy;
	}

	public function countUsers($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = 1;

		$total = $this->getUsers(false, ( isset($params['type_id']) ? $params['type_id'] : 0 ), $columns, $items, false, 0, $params);

		return $total;
	}

	public function getUsers($fields = false, $typeID = 0, $columns = array(), $items = array(), $order = false, $limit = 15, $params = array())
	{
		// Do we have only one user type?
		if ( !$typeID && count(config::item('usertypes', 'core', 'keywords')) == 1 )
		{
			$typeID = key(config::item('usertypes', 'core', 'keywords'));
		}

		// Do we need to count users?
		if ( isset($params['count']) && $params['count'] )
		{
			if ( $typeID )
			{
				$total = $this->fields_model->countRows('profile', true, $columns, $items, array('table_type' => config::item('usertypes', 'core', 'keywords', $typeID)));
			}
			else
			{
				$total = $this->fields_model->countRows('user', false, $columns);
			}

			return $total;
		}

		$params['select_columns'] = "`f`.`service_id` AS `picture_file_service_id`, `f`.`path` AS `picture_file_path`, `f`.`name` AS `picture_file_name`, `f`.`extension` AS `picture_file_ext`, `f`.`width` AS `picture_file_width`, `f`.`height` AS `picture_file_height`,
			`f`.`size` AS `picture_file_size`, `f`.`post_date` AS `picture_file_post_date`, `f`.`modify_date` AS `picture_file_modify_date`";
		$params['join_tables'] = "LEFT JOIN `:prefix:storage_files` AS `f` ON `u`.`picture_id`=`f`.`file_id`";

		if ( $typeID )
		{
			$params['select_columns'] .= ', `u`.*';
			$params['type_id'] = $typeID;
			$params['table_type'] = config::item('usertypes', 'core', 'keywords', $typeID);
			$params['prefix_order'] = 'u';
		}

		// Get users
		$users = $this->fields_model->getRows(( $typeID ? 'profile' : 'user' ), ( $typeID ? true : false ), ( $typeID ? $fields : false ), $columns, $items, $order, $limit, $params);

		$types = array();

		// Loop through users
		foreach ( $users as $userID => $user )
		{
			// Do we need to fetch profiles?
			if ( $fields && !$typeID )
			{
				// Does user type ID exist already?
				if ( !isset($types[$user['type_id']]) )
				{
					$types[$user['type_id']] = array();
				}

				// Assign user type ID and user ID
				$types[$user['type_id']][$user['user_id']] = true;
			}

			// Do we have any field names set?
			if ( !config::item('usertypes', 'core', 'fields', $user['type_id'], 1) )
			{
				$user['name1'] = $user['username'];
				$user['name2'] = '';
			}

			$users[$userID]['name'] = users_helper::name($user['name1'], $user['name2'], ( isset($params['escape']) ? $params['escape'] : true ));
			$users[$userID]['group_name'] = config::item('usergroups', 'core', $user['group_id']);
			$users[$userID]['type_name'] = config::item('usertypes', 'core', 'names', $user['type_id']);

			if ( !isset($params['escape']) || $params['escape'] )
			{
				$users[$userID]['name1'] = text_helper::entities($user['name1']);
				$users[$userID]['name2'] = text_helper::entities($user['name2']);

				$users[$userID]['group_name'] = text_helper::entities($users[$userID]['group_name']);
				$users[$userID]['type_name'] = text_helper::entities($users[$userID]['type_name']);
			}

			$users[$userID]['slug'] = users_helper::slug($user['user_id'], $user['username']);
			$users[$userID]['slug_id'] = users_helper::id($user['user_id'], $user['username']);

			$users[$userID]['online'] = $user['visit_date'] >= date_helper::now() - 60*5 ? 1 : 0;
		}

		// Do we need to fetch profiles?
		if ( $fields && !$typeID )
		{
			$profiles = array();

			unset($params['select_columns'], $params['join_tables']);

			// Loop through user types
			foreach ( $types as $typeID => $userIDs )
			{
				// Get user profiles
				$profiles = $this->getProfiles(array_keys($userIDs), $typeID, $fields, $params);

				// Do we have any profiles?
				if ( $profiles )
				{
					// Loop through users
					foreach ( $users as $userID => $user )
					{
						// Does user ID exist?
						if ( isset($profiles[$user['user_id']]) )
						{
							// Merge user and profile fields
							$users[$userID] = array_merge($user, $profiles[$user['user_id']]);
						}
					}
				}
			}
		}

		// Do we need to fetch configuration?
		if ( isset($params['config']) && $params['config'] )
		{
			// Get settings
			$settings = $this->getUsersConfig(array_keys($users));

			foreach ( $settings as $userID => $setting )
			{
				$users[$userID]['config'] = $setting;
			}
		}

		return $users;
	}

	public function toggleUserStatus($userID, $user, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $user['active'] == $status )
		{
			return false;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:users` SET `active`=? WHERE `user_id`=? LIMIT 1", array($status, $userID));

		// Update timeline action
		timeline_helper::update(true, 'user_signup', $userID, $userID, isset($user['verified']) && $user['verified'] && $status ? 1 : 0);

		// Action hook
		hook::action('users/status/' . ( $status ? 'approve' : 'decline' ), $userID);

		return true;
	}

	public function toggleVerifiedStatus($userID, $user, $status = 1)
	{
		// Are you trying to assign the same status?
		if ( $user['verified'] == $status )
		{
			return false;
		}

		// Update status
		$this->db->query("UPDATE `:prefix:users` SET `verified`=? WHERE `user_id`=? LIMIT 1", array($status, $userID));

		// Update timeline action
		timeline_helper::update(true, 'user_signup', $userID, $userID, isset($user['active']) && $user['active'] && $status ? 1 : 0);

		// Action hook
		hook::action('users/verified/' . ( $status ? 'approve' : 'decline' ), $userID);

		return true;
	}

	public function deleteUser($userID, $user)
	{
		// Delete comments
		loader::model('comments/comments');
		$this->comments_model->deleteUser($userID, $user);

		// Delete likes
		loader::model('comments/likes');
		$this->likes_model->deleteUser($userID, $user);

		// Delete votes
		loader::model('comments/votes');
		$this->votes_model->deleteUser($userID, $user);

		// Delete reports
		loader::model('reports/reports');
		$this->reports_model->deleteUser($userID, $user);

		// Delete visitors
		loader::model('users/visitors', array(), 'users_visitors_model');
		$this->users_visitors_model->deleteUser($userID, $user);

		// Delete timeline actions
		loader::model('timeline/timeline');
		$this->timeline_model->deleteUser($userID, $user);

		// Delete friends
		$this->users_friends_model->deleteUser($userID, $user);

		// Delete picture
		if ( $user['picture_id'] )
		{
			$this->deletePicture($userID, $user['picture_id'], false);
		}

		// Delete user
		$this->db->delete('users_data_' . config::item('usertypes', 'core', 'keywords', $user['type_id']), array('profile_id' => $userID), 1);
		$this->db->delete('users_data_items', array('data_id' => $userID));
		$this->db->delete('users_config', array('user_id' => $userID));

		$retval = $this->db->delete('users', array('user_id' => $userID), 1);

		// Clean up counters
		$this->counters_model->deleteCounters('user', $userID);

		// Delete timeline action
		timeline_helper::delete('user_signup', $userID, $userID);

		// Action hook
		hook::action('users/account/delete', $userID, $user);

		return $retval;
	}

	public function encryptPassword($password, $salt = '')
	{
		if ( $password == '' )
		{
			return '';
		}

		// Do we have salt?
		if ( $salt == '' )
		{
			$salt = text_helper::random(8);
		}

		// Encrypt password
		$password = sha1($password.$salt);
		$password .= $salt;

		return $password;
	}

	public function verifyPassword($password, $passhash, $userID = 0)
	{
		// Generate password hash
		$newhash = $this->encryptPassword($password, substr($passhash, -8));

		// Compare password hash
		if ( strcmp($newhash, $passhash) == 0 )
		{
			return true;
		}

		// Is password using md5?
		if ( strlen($passhash) == 32 && strcmp(md5($password), $passhash) == 0 )
		{
			if ( $userID )
			{
				// Encrypt password using own encryption
				$this->savePassword($userID, $password);
			}

			return true;
		}

		return false;
	}

	public function getFriend($userID, $active = 1)
	{
		if ( !$this->isLoggedin() )
		{
			return false;
		}
		elseif ( $userID == session::item('user_id' ) )
		{
			return true;
		}

		$friend = $this->db->query("SELECT `user_id`, `friend_id`, `active`
			FROM `:prefix:users_friends`
			WHERE (`user_id`=? AND `friend_id`=? OR `user_id`=? AND `friend_id`=?) AND `active`=? LIMIT 1",
			array( session::item('user_id'), $userID, $userID, session::item('user_id'), $active ))->row();

		return $friend;
	}

	public function getFriends()
	{
		if ( !$this->isLoggedin() )
		{
			return array();
		}

		$result = $this->db->query("SELECT `user_id`, `friend_id`, `active`
			FROM `:prefix:users_friends`
			WHERE `user_id`=? OR `friend_id`=? LIMIT ?",
			array(session::item('user_id'), session::item('user_id'), (session::item('total_friends')+session::item('total_friends_i'))))->result();

		$friends = array();
		foreach ( $result as $friend )
		{
			$friends[$friend['user_id'] == session::item('user_id') ? $friend['friend_id'] : $friend['user_id']] = $friend['active'] ? 1 : 0;
		}

		return $friends;
	}

	public function getPermissions($groupID, $plugin = '', $keyword = '')
	{
		if ( !( $permissions = $this->cache->item('users_permissions_' . $groupID . '_fe') ) )
		{
			$permissions = array();
			foreach ( $this->db->query("SELECT `plugin`, `keyword`, `type`, `group_" . $groupID . "` as `val` FROM `:prefix:users_permissions` WHERE cp=0")->result() as $permission )
			{
				if ( $permission['type'] == 'checkbox' )
				{
					$permission['val'] = explode(',', $permission['val']);
				}
				$permissions[$permission['plugin']][$permission['keyword']] = $permission['val'];
			}

			$this->cache->set('users_permissions_' . $groupID . '_fe', $permissions, 60*60*24*30);
		}

		if ( $permissions['system']['site_access_cp'] )
		{
			if ( !( $permissionscp = $this->cache->item('users_permissions_' . $groupID . '_cp') ) )
			{
				$permissionscp = array();
				foreach ( $this->db->query("SELECT `plugin`, `keyword`, `type`, `group_" . $groupID . "` as `val` FROM `:prefix:users_permissions` WHERE cp=1")->result() as $permission )
				{
					if ( $permission['type'] == 'checkbox' )
					{
						$permission['val'] = explode(',', $permission['val']);
					}
					$permissionscp[$permission['plugin']][$permission['keyword']] = $permission['val'];
				}

				$this->cache->set('users_permissions_' . $groupID . '_cp', $permissionscp, 60*60*24*30);
			}

			foreach ( $permissionscp as $index => $permissioncp )
			{
				if ( isset($permissions[$index]) )
				{
					$permissions[$index] = array_merge($permissions[$index], $permissioncp);
				}
				else
				{
					$permissions[$index] = $permissioncp;
				}
			}
		}

		if ( $plugin && $keyword )
		{
			return isset($permissions[$plugin][$keyword]) ? $permissions[$plugin][$keyword] : false;
		}
		elseif ( $plugin )
		{
			$permissions = $permissions[$plugin];
		}

		return $permissions;
	}

	public function cleanup()
	{
		// Remove old sessions
		$this->db->query("DELETE FROM `:prefix:users_sessions` WHERE `active_date`<?", array(date_helper::now()-60*60*24));
		$this->cron_model->addLog('[Users] Deleted old user sessions.');

		// Remove profile pictures uploaded by unregistered users older than 24 hours
		$resourceID = config::item('resources', 'core', 'user', 'resource_id');

		$files = $this->db->query("SELECT `file_id` FROM `:prefix:storage_files` WHERE `resource_id`=? AND `user_id`=0 AND `parent_id`=0 AND `post_date`<?", array($resourceID, ( date_helper::now()-60*60*24 )))->result();
		foreach ( $files as $file )
		{
			$this->storage_model->deleteFiles($file['file_id'], 5);
		}

		$this->cron_model->addLog('[Users] Deleted ' . count($files) . ' incomplete profile pictures.');
	}

	public function getReportedActions()
	{
		$actions = array(
			'deactivate' => __('report_item_deactivate', 'reports'),
			'delete' => __('report_item_delete', 'reports'),
		);

		return $actions;
	}

	public function runReportedAction($userID, $action)
	{
		$user = $this->getUser($userID, false, false);

		if ( $user )
		{
			if ( $action == 'deactivate' )
			{
				$this->toggleUserStatus($userID, $user, 0);
			}
			elseif ( $action == 'delete' )
			{
				$this->deleteUser($userID, $user);
			}
		}

		return true;
	}

	public function getReportedURL($userID)
	{
		$url = 'cp/users/edit/' . $userID;

		return $url;
	}

	public function updateDbCounters()
	{
		$offset = uri::segment(6, 0);
		$step = 50;
		$next = $offset + $step;

		// Count users
		$total = $this->db->query("SELECT COUNT(*) as `total_rows` FROM `:prefix:users`")->row();
		$total = $total['total_rows'];

		// Get users
		$users = $this->db->query("SELECT `user_id` FROM `:prefix:users` ORDER BY `user_id` LIMIT ?, ?", array($offset, $step))->result();

		foreach ( $users as $user )
		{
			// Visitors
			$visitors = array(
				'total_visitors' => 0,
				'total_visitors_new' => 0,
			);

			$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `new` FROM `:prefix:users_visitors` WHERE `user_id`=? GROUP BY `new`", array($user['user_id']))->result();
			foreach ( $items as $item )
			{
				$visitors['total_visitors'] = $item['total_rows'];
				if ( $item['new'] )
				{
					$visitors['total_visitors_new'] = $item['total_rows'];
				}
			}
			$this->db->update('users', $visitors, array('user_id' => $user['user_id']), 1);

			// Friends
			$friends = array(
				'total_friends' => 0,
				'total_friends_i' => 0,
			);

			$items = $this->db->query("SELECT COUNT(*) as `total_rows`, `active` FROM `:prefix:users_friends` WHERE ((`user_id`=? OR `friend_id`=?) AND `active`=1) OR (`friend_id`=? AND `active`=0) GROUP BY `active`", array($user['user_id'], $user['user_id'], $user['user_id']))->result();
			foreach ( $items as $item )
			{
				$friends['total_friends' . ( $item['active'] ? '' : '_i' )] = $item['total_rows'];
			}

			$this->db->update('users', $friends, array('user_id' => $user['user_id']), 1);
		}

		$result = array(
			'output' => __('progress_status', 'utilities_counters', array('%1' => ( $offset + $step < $total ? ( $offset + $step ) : $total ), '%2' => $total)),
			'redirect' => $next < $total ? $next : '',
		);

		return $result;
	}
}
