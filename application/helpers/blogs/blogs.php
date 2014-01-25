<?php defined('SYSPATH') || die('No direct script access allowed.');

class Blogs_Helper
{
	static public function getBlogs($params = array())
	{
		if ( !session::permission('users_groups_browse', 'users') && !session::permission('users_types_browse', 'users') )
		{
			return '';
		}

		loader::model('blogs/blogs');

		$template = isset($params['template']) ? $params['template'] : 'blogs/helpers/blogs';
		$user = isset($params['user']) && $params['user'] ? $params['user'] : array();
		$userID = $user ? $user['user_id'] : ( isset($params['user_id']) ? $params['user_id'] : 0 );

		if ( $userID )
		{
			$params['join_columns'][] = '`b`.`user_id`=' . $userID;
		}

		if ( !$userID || $userID != session::item('user_id') )
		{
			$params['join_columns'][] = '`b`.`active`=1';

			if ( $userID )
			{
				$params['privacy'] = $userID;
			}
			else
			{
				$params['join_columns'][] = '`b`.`public`=1';
				$params['join_columns'][] = '`u`.`verified`=1';
				$params['join_columns'][] = '`u`.`active`=1';
				$params['join_columns'][] = '`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')';
				$params['join_columns'][] = '`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')';
			}
		}

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 10;
		$params['order'] = isset($params['order']) ? $params['order'] : '';

		$blogs = codebreeder::instance()->blogs_model->getBlogs('in_list', $params['join_columns'], array(), $params['order'], $params['limit'], $params);

		view::assign(array('blogs' => $blogs, 'user' => $user, 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}
}