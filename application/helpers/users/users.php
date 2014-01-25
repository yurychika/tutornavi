<?php defined('SYSPATH') || die('No direct script access allowed.');

class Users_Helper
{
	static public function slug($userID = 0, $username = '', $escape = false)
	{
		$slug = users_helper::id($userID, $username, $escape);

		$slug = $slug ? 'profile/' . $slug : '';

		return $slug;
	}

	static public function id($userID = 0, $username = '', $escape = false)
	{
		if ( !$userID )
		{
			$userID = session::item('user_id');
		}

		if ( $username == '' )
		{
			$username = session::item('username');
		}

		if ( config::item('user_username', 'users') && $username != '' )
		{
			$id = $escape ? text_helper::entities($username) : $username;
		}
		else
		{
			$id = $userID;
		}

		return $id;
	}

	static public function name($name1, $name2, $escape = false)
	{
		if ( $name1 == '' )
		{
			$name1 = __('user', 'users');
			$name2 = '';
		}
		elseif ( $escape )
		{
			$name1 = text_helper::entities($name1);
			$name2 = text_helper::entities($name2);
		}

		$name = $name2 != '' ? $name1 . ' ' . $name2 : $name1;

		return $name;
	}

	static public function anchor($user, $attr = array())
	{
		if ( input::isCP() )
		{
			$user['slug'] = 'cp/users/edit/' . $user['user_id'];
		}

		if ( !input::isCP() && ( $user['group_id'] == config::item('group_cancelled_id', 'users') || !$user['verified'] || !$user['active'] ) )
		{
			$anchor = $user['name'] . ' (' . __('account_inactive', 'users') . ')';
		}
		else
		{
			$attr = $attr ? array_merge($attr, array('class' => 'username')) : array('class' => 'username');
			$anchor = html_helper::anchor($user['slug'], $user['name'], $attr);
		}

		return $anchor;
	}

	static public function isLoggedin($cp = false)
	{
		return codebreeder::instance()->users_model->isLoggedin($cp);
	}

	static public function getFriend($userID, $active = 1)
	{
		if ( !users_helper::isLoggedin() )
		{
			return false;
		}
		elseif ( $userID == session::item('user_id' ) )
		{
			return true;
		}

		return codebreeder::instance()->users_friends_model->getFriend($userID, $active);
	}

	static public function getBlockedUser($userID, $self = false)
	{
		if ( !users_helper::isLoggedin() )
		{
			return false;
		}
		elseif ( $userID == session::item('user_id' ) )
		{
			return false;
		}

		return codebreeder::instance()->users_blocked_model->getUser($userID, $self);
	}

	static public function getFriends($params = array())
	{
		$template = isset($params['template']) ? $params['template'] : 'users/helpers/users';
		$user = isset($params['user']) && $params['user'] ? $params['user'] : array();
		$userID = $user ? $user['user_id'] : ( isset($params['user_id']) ? $params['user_id'] : 0 );

		if ( !codebreeder::instance()->users_model->getPrivacyAccess($userID, ( isset($user['config']['privacy_friends']) ? $user['config']['privacy_friends'] : 1 ), false) )
		{
			return false;
		}

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 6;
		$params['profiles'] = true;

		$users = codebreeder::instance()->users_friends_model->getFriends($userID, 1, '', $params['limit'], $params);

		view::assign(array('users' => $users, 'user' => $user), '', $template);

		return view::load($template, array(), 1);
	}

	static public function getUsers($params = array())
	{
		if ( !session::permission('users_groups_browse', 'users') && !session::permission('users_types_browse', 'users') )
		{
			return '';
		}

		$template = isset($params['template']) ? $params['template'] : 'users/helpers/users';

		$params['join_columns'][] = '`u`.`verified`=1';
		$params['join_columns'][] = '`u`.`active`=1';
		$params['join_columns'][] = '`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')';
		$params['join_columns'][] = '`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')';

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 10;
		$params['order'] = isset($params['order']) ? $params['order'] : '`u`.`join_date` DESC';

		$users = codebreeder::instance()->users_model->getUsers('in_list', 0, $params['join_columns'], array(), $params['order'], $params['limit'], $params);

		view::assign(array('users' => $users, 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}

	static public function authButtons($action = 'signup', $button = '')
	{
		$buttons = array();

		foreach ( config::item('auth_methods', 'users') as $service )
		{
			if ( $service != 'default' )
			{
				$buttons[] = html_helper::anchor('users/connect/authorize/' . $service . '/' . $action, html_helper::image('assets/images/users/authentication/' . $service . '/' . ( $button ? $button : $action ) . '.png', array('border' => 0)));
			}
		}

		return $buttons;
	}
}